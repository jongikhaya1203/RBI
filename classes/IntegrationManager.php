<?php
/**
 * IntegrationManager - Unified integration orchestration layer
 *
 * Manages all external system integrations: configuration, sync scheduling,
 * health monitoring, conflict resolution, and field mapping.
 *
 * @package RBI Engineering Suite
 */
class IntegrationManager
{
    private Database $db;
    private array $integrations = [];
    private string $logPrefix = '[Integration Manager]';

    public function __construct()
    {
        $this->db = new Database();
        $this->loadIntegrations();
    }

    /**
     * Load all configured integrations from the database.
     */
    private function loadIntegrations(): void
    {
        try {
            $this->integrations = $this->db->query(
                "SELECT * FROM integration_configs WHERE 1=1 ORDER BY integration_name"
            )->fetchAll();
        } catch (\Throwable $e) {
            $this->integrations = [];
            error_log("{$this->logPrefix} Failed to load integrations: " . $e->getMessage());
        }
    }

    // =========================================================================
    // Integration Status & Discovery
    // =========================================================================

    /**
     * List all configured integrations with their current status.
     *
     * @return array Integrations with status metadata
     */
    public function getActiveIntegrations(): array
    {
        $result = [];

        foreach ($this->integrations as $config) {
            $lastSync = $this->db->query(
                "SELECT * FROM integration_sync_log WHERE integration_id = ? ORDER BY started_at DESC LIMIT 1",
                [$config['id']]
            )->fetch();

            $errorCount = $this->db->query(
                "SELECT COUNT(*) as cnt FROM integration_sync_log
                 WHERE integration_id = ? AND status = 'failed'
                 AND started_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
                [$config['id']]
            )->fetchColumn();

            $conflictCount = $this->db->query(
                "SELECT COUNT(*) as cnt FROM integration_conflict_log
                 WHERE integration_id = ? AND resolution = 'pending'",
                [$config['id']]
            )->fetchColumn();

            $result[] = [
                'id'                => $config['id'],
                'name'              => $config['integration_name'],
                'type'              => $config['system_type'],
                'vendor'            => $config['vendor'],
                'is_active'         => (bool)$config['is_active'],
                'auth_type'         => $config['auth_type'],
                'sync_direction'    => $config['sync_direction'],
                'sync_frequency'    => $config['sync_frequency_minutes'],
                'last_sync_at'      => $config['last_sync_at'],
                'last_sync_status'  => $config['last_sync_status'],
                'last_sync_message' => $config['last_sync_message'],
                'errors_24h'        => (int)$errorCount,
                'pending_conflicts' => (int)$conflictCount,
                'health'            => $this->calculateHealth($config, $lastSync, (int)$errorCount),
                'last_sync_detail'  => $lastSync,
            ];
        }

        return $result;
    }

    /**
     * Test connectivity to an external system.
     *
     * @param int $integrationId Integration config ID
     * @return array Test results with latency and status
     */
    public function testConnection(int $integrationId): array
    {
        $config = $this->getConfigById($integrationId);
        $result = [
            'integration_id' => $integrationId,
            'name'           => $config['integration_name'],
            'vendor'         => $config['vendor'],
            'status'         => 'unknown',
            'latency_ms'     => 0,
            'message'        => '',
            'tested_at'      => date('Y-m-d H:i:s'),
            'details'        => [],
        ];

        $startTime = microtime(true);

        try {
            $credentials = $this->decryptCredentials($config);
            $vendor = strtolower($config['vendor'] ?? '');

            if (str_contains($vendor, 'sap')) {
                $sap = new SAPIntegration(array_merge($credentials, [
                    'host'           => $config['api_base_url'],
                    'integration_id' => $integrationId,
                    'verify_ssl'     => APP_ENV !== 'development',
                ]));
                $sap->authenticate();
                $result['details']['system'] = 'SAP Plant Maintenance';

            } elseif (str_contains($vendor, 'maximo')) {
                $maximo = new MaximoIntegration(array_merge($credentials, [
                    'base_url'  => $config['api_base_url'],
                    'auth_type' => $config['auth_type'],
                    'verify_ssl'=> APP_ENV !== 'development',
                ]));
                $maximo->authenticate();
                $result['details']['system'] = 'IBM Maximo';

            } elseif (str_contains($vendor, 'osisoft') || str_contains($vendor, 'pi') || str_contains($vendor, 'aveva')) {
                $pi = new OSIsoftPIIntegration(array_merge($credentials, [
                    'base_url'  => $config['api_base_url'],
                    'auth_type' => $config['auth_type'],
                    'verify_ssl'=> APP_ENV !== 'development',
                ]));
                $pi->authenticate();
                $servers = $pi->getDataServers();
                $result['details']['system'] = 'OSIsoft PI';
                $result['details']['data_servers'] = count($servers);

            } else {
                // Generic HTTP connectivity test
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL            => $config['api_base_url'],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_TIMEOUT        => 15,
                    CURLOPT_NOBODY         => true,
                    CURLOPT_SSL_VERIFYPEER => APP_ENV !== 'development',
                ]);
                curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);

                if ($error) throw new RuntimeException($error);
                if ($httpCode >= 500) throw new RuntimeException("Server returned HTTP {$httpCode}");

                $result['details']['http_code'] = $httpCode;
            }

            $result['status'] = 'connected';
            $result['message'] = 'Connection successful';

        } catch (\Throwable $e) {
            $result['status'] = 'failed';
            $result['message'] = $e->getMessage();
        }

        $result['latency_ms'] = round((microtime(true) - $startTime) * 1000);

        // Update config with test result
        $this->db->update('integration_configs', [
            'last_sync_status'  => $result['status'] === 'connected' ? 'success' : 'failed',
            'last_sync_message' => $result['message'],
            'updated_at'        => date('Y-m-d H:i:s'),
        ], 'id = ?', [$integrationId]);

        return $result;
    }

    // =========================================================================
    // Synchronization
    // =========================================================================

    /**
     * Run full sync for all active integrations with progress tracking.
     *
     * @return array Results for each integration
     */
    public function syncAll(): array
    {
        $results = [];

        foreach ($this->integrations as $config) {
            if (!$config['is_active']) continue;

            $results[] = $this->runSync($config['id'], 'full');
        }

        return $results;
    }

    /**
     * Run sync for a specific integration.
     *
     * @param int    $integrationId Integration config ID
     * @param string $syncType      full|incremental|manual
     * @return array Sync results
     */
    public function runSync(int $integrationId, string $syncType = 'manual'): array
    {
        $config = $this->getConfigById($integrationId);

        // Create sync log entry
        $logId = $this->db->insert('integration_sync_log', [
            'integration_id'    => $integrationId,
            'sync_type'         => $syncType,
            'direction'         => $config['sync_direction'],
            'started_at'        => date('Y-m-d H:i:s'),
            'status'            => 'running',
            'created_by'        => $_SESSION['user_id'] ?? null,
        ]);

        $result = [
            'sync_log_id'       => $logId,
            'integration_id'    => $integrationId,
            'name'              => $config['integration_name'],
            'records_processed' => 0,
            'records_created'   => 0,
            'records_updated'   => 0,
            'records_failed'    => 0,
            'errors'            => [],
        ];

        try {
            $credentials = $this->decryptCredentials($config);
            $vendor = strtolower($config['vendor'] ?? '');

            if (str_contains($vendor, 'sap')) {
                $sap = new SAPIntegration(array_merge($credentials, [
                    'host'           => $config['api_base_url'],
                    'integration_id' => $integrationId,
                    'verify_ssl'     => APP_ENV !== 'development',
                ]));
                $syncResult = $sap->syncEquipmentToAssets();
                $result = array_merge($result, $syncResult);

            } elseif (str_contains($vendor, 'maximo')) {
                $maximo = new MaximoIntegration(array_merge($credentials, [
                    'base_url'  => $config['api_base_url'],
                    'auth_type' => $config['auth_type'],
                    'verify_ssl'=> APP_ENV !== 'development',
                ]));
                $syncResult = $maximo->syncAssetsToRBI();
                $result = array_merge($result, $syncResult);

            } elseif (str_contains($vendor, 'osisoft') || str_contains($vendor, 'pi') || str_contains($vendor, 'aveva')) {
                $pi = new OSIsoftPIIntegration(array_merge($credentials, [
                    'base_url'  => $config['api_base_url'],
                    'auth_type' => $config['auth_type'],
                    'verify_ssl'=> APP_ENV !== 'development',
                ]));
                $pi->authenticate();

                // Sync process data for all mapped assets
                $mappedAssets = $this->db->query(
                    "SELECT DISTINCT asset_id FROM pi_tag_mappings WHERE is_active = 1"
                )->fetchAll();

                foreach ($mappedAssets as $ma) {
                    $piResult = $pi->syncProcessData($ma['asset_id'], [
                        'start' => $config['last_sync_at'] ?? '*-24h',
                        'end'   => '*',
                    ]);
                    $result['records_processed'] += $piResult['tags_synced'];
                    $result['records_created'] += $piResult['values_stored'];
                    $result['errors'] = array_merge($result['errors'], $piResult['errors']);
                }
            }

            $status = empty($result['errors']) ? 'completed' : 'partial';

        } catch (\Throwable $e) {
            $status = 'failed';
            $result['errors'][] = $e->getMessage();
        }

        // Update sync log
        $this->db->update('integration_sync_log', [
            'completed_at'      => date('Y-m-d H:i:s'),
            'records_processed' => $result['records_processed'],
            'records_created'   => $result['records_created'],
            'records_updated'   => $result['records_updated'],
            'records_failed'    => $result['records_failed'],
            'status'            => $status,
            'error_log'         => !empty($result['errors']) ? json_encode($result['errors']) : null,
        ], 'id = ?', [$logId]);

        // Update integration config
        $this->db->update('integration_configs', [
            'last_sync_at'      => date('Y-m-d H:i:s'),
            'last_sync_status'  => $status === 'completed' ? 'success' : $status,
            'last_sync_message' => $status === 'completed' ? 'Sync completed successfully' : implode('; ', array_slice($result['errors'], 0, 3)),
            'updated_at'        => date('Y-m-d H:i:s'),
        ], 'id = ?', [$integrationId]);

        $result['status'] = $status;
        return $result;
    }

    /**
     * Configure sync schedule for an integration.
     *
     * @param int    $integrationId Integration ID
     * @param string $schedule      Cron-like: hourly, daily, weekly, or minutes value
     * @return bool Success
     */
    public function scheduleSync(int $integrationId, string $schedule): bool
    {
        $minutes = match ($schedule) {
            'hourly'  => 60,
            'daily'   => 1440,
            'weekly'  => 10080,
            'realtime'=> 5,
            default   => is_numeric($schedule) ? (int)$schedule : 60,
        };

        $this->db->update('integration_configs', [
            'sync_frequency_minutes' => $minutes,
            'updated_at'             => date('Y-m-d H:i:s'),
        ], 'id = ?', [$integrationId]);

        return true;
    }

    /**
     * Get sync execution history for an integration.
     *
     * @param int $integrationId Integration ID
     * @param int $limit         Max records
     * @return array Sync history records
     */
    public function getSyncHistory(int $integrationId, int $limit = 50): array
    {
        return $this->db->query(
            "SELECT isl.*, u.first_name, u.last_name
             FROM integration_sync_log isl
             LEFT JOIN users u ON isl.created_by = u.id
             WHERE isl.integration_id = ?
             ORDER BY isl.started_at DESC
             LIMIT ?",
            [$integrationId, $limit]
        )->fetchAll();
    }

    // =========================================================================
    // Field Mapping
    // =========================================================================

    /**
     * Get field mappings for an integration.
     *
     * @param int    $integrationId Integration ID
     * @param string $entityType    Optional filter by entity type
     * @return array Field mappings
     */
    public function getFieldMapping(int $integrationId, string $entityType = ''): array
    {
        $sql = "SELECT * FROM integration_field_mappings WHERE integration_id = ?";
        $params = [$integrationId];

        if ($entityType) {
            $sql .= " AND entity_type = ?";
            $params[] = $entityType;
        }

        $sql .= " ORDER BY entity_type, is_key DESC, external_field";

        return $this->db->query($sql, $params)->fetchAll();
    }

    /**
     * Save field mapping configuration.
     *
     * @param int   $integrationId Integration ID
     * @param array $mappings      Array of mapping records
     * @return int Number of mappings saved
     */
    public function saveFieldMapping(int $integrationId, array $mappings): int
    {
        $count = 0;

        foreach ($mappings as $mapping) {
            $data = [
                'integration_id'     => $integrationId,
                'entity_type'        => $mapping['entity_type'] ?? 'equipment',
                'external_field'     => $mapping['external_field'] ?? '',
                'internal_field'     => $mapping['internal_field'] ?? '',
                'transform_function' => $mapping['transform_function'] ?? null,
                'default_value'      => $mapping['default_value'] ?? null,
                'is_key'             => $mapping['is_key'] ?? 0,
                'is_required'        => $mapping['is_required'] ?? 1,
                'direction'          => $mapping['direction'] ?? 'both',
            ];

            if (empty($data['external_field']) || empty($data['internal_field'])) continue;

            // Upsert
            $existing = $this->db->query(
                "SELECT id FROM integration_field_mappings
                 WHERE integration_id = ? AND entity_type = ? AND external_field = ?",
                [$integrationId, $data['entity_type'], $data['external_field']]
            )->fetch();

            if ($existing) {
                $data['updated_at'] = date('Y-m-d H:i:s');
                $this->db->update('integration_field_mappings', $data, 'id = ?', [$existing['id']]);
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                $this->db->insert('integration_field_mappings', $data);
            }
            $count++;
        }

        return $count;
    }

    // =========================================================================
    // Conflict Resolution
    // =========================================================================

    /**
     * Get unresolved data conflicts.
     *
     * @param int|null $integrationId Optional filter
     * @return array Pending conflicts
     */
    public function getConflicts(?int $integrationId = null): array
    {
        $sql = "SELECT icl.*, ic.integration_name, ic.vendor
                FROM integration_conflict_log icl
                JOIN integration_configs ic ON icl.integration_id = ic.id
                WHERE icl.resolution = 'pending'";
        $params = [];

        if ($integrationId) {
            $sql .= " AND icl.integration_id = ?";
            $params[] = $integrationId;
        }

        $sql .= " ORDER BY icl.created_at DESC";

        return $this->db->query($sql, $params)->fetchAll();
    }

    /**
     * Resolve a data conflict.
     *
     * @param int    $conflictId Conflict log ID
     * @param string $resolution internal_wins|external_wins|manual|merged
     * @param mixed  $mergedValue Optional merged value for 'manual' resolution
     * @return bool Success
     */
    public function resolveConflict(int $conflictId, string $resolution, $mergedValue = null): bool
    {
        $conflict = $this->db->find('integration_conflict_log', $conflictId);
        if (!$conflict) {
            throw new InvalidArgumentException("Conflict {$conflictId} not found");
        }

        // Apply the resolution
        $applyValue = match ($resolution) {
            'internal_wins' => $conflict['internal_value'],
            'external_wins' => $conflict['external_value'],
            'manual', 'merged' => $mergedValue ?? $conflict['external_value'],
            default => throw new InvalidArgumentException("Invalid resolution: {$resolution}"),
        };

        // Update the RBI record if needed
        if ($resolution !== 'internal_wins' && $conflict['internal_id']) {
            try {
                $this->db->update(
                    $this->resolveTableName($conflict['entity_type']),
                    [$conflict['field_name'] => $applyValue, 'updated_at' => date('Y-m-d H:i:s')],
                    'id = ?',
                    [$conflict['internal_id']]
                );
            } catch (\Throwable $e) {
                error_log("{$this->logPrefix} Failed to apply conflict resolution: " . $e->getMessage());
            }
        }

        // Mark conflict as resolved
        $this->db->update('integration_conflict_log', [
            'resolution'  => $resolution === 'manual' ? 'manual' : $resolution,
            'resolved_by' => $_SESSION['user_id'] ?? null,
            'resolved_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$conflictId]);

        return true;
    }

    // =========================================================================
    // Health Monitoring
    // =========================================================================

    /**
     * Get overall integration health dashboard data.
     *
     * @return array Health metrics for all integrations
     */
    public function getIntegrationHealth(): array
    {
        $health = [
            'overall_status' => 'healthy',
            'integrations'   => [],
            'summary'        => [
                'total_active'       => 0,
                'total_connected'    => 0,
                'total_errors_24h'   => 0,
                'total_records_24h'  => 0,
                'pending_conflicts'  => 0,
            ],
        ];

        $integrations = $this->getActiveIntegrations();

        foreach ($integrations as $int) {
            if ($int['is_active']) $health['summary']['total_active']++;
            if ($int['health'] === 'healthy') $health['summary']['total_connected']++;
            $health['summary']['total_errors_24h'] += $int['errors_24h'];
            $health['summary']['pending_conflicts'] += $int['pending_conflicts'];

            $health['integrations'][] = $int;
        }

        // Get total records synced in last 24h
        $syncStats = $this->db->query(
            "SELECT COALESCE(SUM(records_processed), 0) as total
             FROM integration_sync_log
             WHERE started_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        )->fetchColumn();

        $health['summary']['total_records_24h'] = (int)$syncStats;

        // Determine overall status
        if ($health['summary']['total_errors_24h'] > 10) {
            $health['overall_status'] = 'critical';
        } elseif ($health['summary']['total_errors_24h'] > 0 || $health['summary']['pending_conflicts'] > 0) {
            $health['overall_status'] = 'warning';
        }

        return $health;
    }

    /**
     * Get data flow configuration for an integration.
     *
     * @param int $integrationId Integration ID
     * @return array Data flow description
     */
    public function getDataFlowDiagram(int $integrationId): array
    {
        $config = $this->getConfigById($integrationId);
        $mappings = $this->getFieldMapping($integrationId);

        $entityTypes = array_unique(array_column($mappings, 'entity_type'));

        $flows = [];
        foreach ($entityTypes as $entity) {
            $entityMappings = array_filter($mappings, fn($m) => $m['entity_type'] === $entity);
            $inbound = array_filter($entityMappings, fn($m) => $m['direction'] !== 'outbound');
            $outbound = array_filter($entityMappings, fn($m) => $m['direction'] !== 'inbound');

            $flows[] = [
                'entity_type'    => $entity,
                'inbound_fields' => count($inbound),
                'outbound_fields'=> count($outbound),
                'direction'      => $config['sync_direction'],
            ];
        }

        return [
            'integration'  => $config['integration_name'],
            'vendor'       => $config['vendor'],
            'source'       => $config['api_base_url'],
            'destination'  => 'RBI Engineering Suite',
            'direction'    => $config['sync_direction'],
            'data_flows'   => $flows,
        ];
    }

    // =========================================================================
    // Sync Logging
    // =========================================================================

    /**
     * Log a sync operation.
     *
     * @return int Log entry ID
     */
    public function logSync(int $integrationId, string $direction, int $records, string $status, array $errors = []): int
    {
        return $this->db->insert('integration_sync_log', [
            'integration_id'    => $integrationId,
            'sync_type'         => 'manual',
            'direction'         => $direction,
            'started_at'        => date('Y-m-d H:i:s'),
            'completed_at'      => date('Y-m-d H:i:s'),
            'records_processed' => $records,
            'status'            => $status,
            'error_log'         => !empty($errors) ? json_encode($errors) : null,
            'created_by'        => $_SESSION['user_id'] ?? null,
        ]);
    }

    // =========================================================================
    // Import/Export Mapping Templates
    // =========================================================================

    /**
     * Export CSV template for field mapping configuration.
     *
     * @param string $integrationType sap|maximo|pi
     * @return string CSV content
     */
    public function exportMappingTemplate(string $integrationType): string
    {
        $templates = [
            'sap' => [
                ['equipment', 'Equipment', 'asset_tag', '', '', '1', '1', 'both'],
                ['equipment', 'EquipmentName', 'equipment_name', '', '', '0', '1', 'inbound'],
                ['equipment', 'FunctionalLocation', 'sap_floc', '', '', '0', '0', 'inbound'],
                ['equipment', 'EquipmentCategory', 'asset_type', 'mapAssetType', '', '0', '0', 'inbound'],
                ['equipment', 'ConstructionYear', 'construction_year', '', '', '0', '0', 'inbound'],
                ['equipment', 'ManufacturerPartNbr', 'manufacturer', '', '', '0', '0', 'inbound'],
                ['equipment', 'ManufacturerSerialNumber', 'serial_number', '', '', '0', '0', 'inbound'],
                ['work_order', 'MaintenanceOrder', 'external_reference', '', '', '1', '1', 'both'],
                ['work_order', 'MaintenanceOrderDesc', 'plan_name', '', '', '0', '1', 'both'],
                ['work_order', 'MaintOrdBasicStartDate', 'plan_start_date', 'parseSAPDate', '', '0', '0', 'both'],
            ],
            'maximo' => [
                ['asset', 'assetnum', 'asset_tag', '', '', '1', '1', 'both'],
                ['asset', 'description', 'equipment_name', '', '', '0', '1', 'inbound'],
                ['asset', 'status', 'status', 'mapMaximoStatus', 'in_service', '0', '0', 'inbound'],
                ['asset', 'location', 'location_description', '', '', '0', '0', 'inbound'],
                ['asset', 'assettype', 'asset_type', 'mapMaximoAssetType', 'other', '0', '0', 'inbound'],
                ['asset', 'manufacturer', 'manufacturer', '', '', '0', '0', 'inbound'],
                ['asset', 'serialnum', 'serial_number', '', '', '0', '0', 'inbound'],
                ['asset', 'installdate', 'installation_date', 'parseDate', '', '0', '0', 'inbound'],
                ['workorder', 'wonum', 'external_reference', '', '', '1', '1', 'both'],
                ['workorder', 'description', 'plan_name', '', '', '0', '1', 'both'],
            ],
            'pi' => [
                ['tag', 'pi_tag_name', 'tag_name', '', '', '1', '1', 'inbound'],
                ['tag', 'parameter_type', 'sensor_type', '', 'temperature', '0', '1', 'inbound'],
                ['tag', 'unit', 'unit_of_measure', '', '', '0', '0', 'inbound'],
                ['tag', 'scaling_factor', 'scaling_factor', '', '1.0', '0', '0', 'inbound'],
                ['tag', 'min_threshold', 'alarm_low', '', '', '0', '0', 'inbound'],
                ['tag', 'max_threshold', 'alarm_high', '', '', '0', '0', 'inbound'],
            ],
        ];

        $rows = $templates[$integrationType] ?? $templates['sap'];

        $csv = "entity_type,external_field,internal_field,transform_function,default_value,is_key,is_required,direction\n";
        foreach ($rows as $row) {
            $csv .= implode(',', $row) . "\n";
        }

        return $csv;
    }

    /**
     * Import field mapping configuration from CSV data.
     *
     * @param int    $integrationId Integration ID
     * @param string $csvData       CSV content
     * @return array Import results
     */
    public function importMappingConfig(int $integrationId, string $csvData): array
    {
        $results = ['imported' => 0, 'skipped' => 0, 'errors' => []];

        $lines = explode("\n", trim($csvData));
        if (count($lines) < 2) {
            $results['errors'][] = 'CSV must have header row and at least one data row';
            return $results;
        }

        $header = str_getcsv(array_shift($lines));
        $headerMap = array_flip($header);

        foreach ($lines as $i => $line) {
            if (trim($line) === '') continue;

            $fields = str_getcsv($line);
            $rowNum = $i + 2;

            try {
                $mapping = [
                    'entity_type'        => $fields[$headerMap['entity_type'] ?? 0] ?? '',
                    'external_field'     => $fields[$headerMap['external_field'] ?? 1] ?? '',
                    'internal_field'     => $fields[$headerMap['internal_field'] ?? 2] ?? '',
                    'transform_function' => $fields[$headerMap['transform_function'] ?? 3] ?? null,
                    'default_value'      => $fields[$headerMap['default_value'] ?? 4] ?? null,
                    'is_key'             => (int)($fields[$headerMap['is_key'] ?? 5] ?? 0),
                    'is_required'        => (int)($fields[$headerMap['is_required'] ?? 6] ?? 1),
                    'direction'          => $fields[$headerMap['direction'] ?? 7] ?? 'both',
                ];

                if (empty($mapping['external_field']) || empty($mapping['internal_field'])) {
                    $results['skipped']++;
                    continue;
                }

                $this->saveFieldMapping($integrationId, [$mapping]);
                $results['imported']++;

            } catch (\Throwable $e) {
                $results['errors'][] = "Row {$rowNum}: " . $e->getMessage();
            }
        }

        return $results;
    }

    // =========================================================================
    // Internal Helpers
    // =========================================================================

    private function getConfigById(int $id): array
    {
        foreach ($this->integrations as $config) {
            if ((int)$config['id'] === $id) return $config;
        }

        // Try loading from DB
        $config = $this->db->find('integration_configs', $id);
        if (!$config) {
            throw new InvalidArgumentException("Integration config {$id} not found");
        }
        return $config;
    }

    private function decryptCredentials(array $config): array
    {
        $creds = [];

        if (!empty($config['credentials_encrypted'])) {
            // In production, use proper AES-256 decryption
            $decrypted = $config['credentials_encrypted'];
            if (is_string($decrypted)) {
                $decoded = json_decode($decrypted, true);
                if (is_array($decoded)) {
                    $creds = $decoded;
                } else {
                    // Try base64 decode fallback
                    $b64 = base64_decode($decrypted, true);
                    if ($b64) {
                        $decoded = json_decode($b64, true);
                        if (is_array($decoded)) $creds = $decoded;
                    }
                }
            }
        }

        return $creds;
    }

    private function calculateHealth(array $config, ?array $lastSync, int $errorCount): string
    {
        if (!$config['is_active']) return 'inactive';
        if ($errorCount > 5) return 'critical';
        if ($errorCount > 0) return 'warning';

        if ($config['last_sync_status'] === 'failed') return 'critical';
        if ($config['last_sync_status'] === 'partial') return 'warning';

        // Check data freshness
        if ($config['last_sync_at']) {
            $syncAge = time() - strtotime($config['last_sync_at']);
            $expectedInterval = ($config['sync_frequency_minutes'] ?? 60) * 60;
            if ($syncAge > $expectedInterval * 3) return 'warning';
        } elseif ($config['last_sync_status'] === 'never') {
            return 'unknown';
        }

        return 'healthy';
    }

    private function resolveTableName(string $entityType): string
    {
        return match ($entityType) {
            'equipment', 'asset' => 'asset_registry',
            'work_order'         => 'inspection_plans',
            'inspection'         => 'inspection_tasks',
            'measurement'        => 'corrosion_rate_tracking',
            default              => 'asset_registry',
        };
    }
}
