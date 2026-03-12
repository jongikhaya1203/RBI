<?php
/**
 * Integration - External system connectors
 *
 * CMMS synchronization, SCADA/IoT data ingestion, digital twin sync,
 * and inspection data import/export.
 */
class Integration
{
    private Database $db;

    /** Supported integration types */
    private const TYPES = [
        'cmms'          => 'Computerized Maintenance Management System',
        'scada'         => 'SCADA / DCS',
        'iot'           => 'IoT Sensor Platform',
        'digital_twin'  => 'Digital Twin',
        'erp'           => 'ERP System',
        'inspection'    => 'Inspection Data Import',
    ];

    public function __construct()
    {
        $this->db = new Database();
    }

    // ── CMMS Integration ────────────────────────────────────────────

    /**
     * Configure a CMMS connection
     *
     * @return int  Integration config ID
     */
    public function configureCMMS(array $config): int
    {
        $this->validateConfig($config, ['system_name', 'api_url']);

        return $this->db->insert('integration_configs', [
            'integration_type'  => 'cmms',
            'system_name'       => $config['system_name'],        // e.g., 'SAP PM', 'Maximo', 'Meridium'
            'api_url'           => $config['api_url'],
            'api_key'           => $config['api_key'] ?? null,
            'auth_type'         => $config['auth_type'] ?? 'api_key',  // api_key, oauth2, basic
            'auth_credentials'  => !empty($config['auth_credentials'])
                                    ? json_encode($config['auth_credentials'])
                                    : null,
            'sync_interval'     => $config['sync_interval'] ?? 60,     // minutes
            'field_mapping'     => json_encode($config['field_mapping'] ?? $this->defaultCMMSMapping()),
            'is_active'         => 1,
            'last_sync_at'      => null,
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Sync work orders and equipment data from CMMS
     *
     * @return array  Sync results summary
     */
    public function syncCMMS(int $configId): array
    {
        $config = $this->getConfig($configId, 'cmms');
        $fieldMapping = json_decode($config['field_mapping'], true) ?: [];

        $results = [
            'config_id'         => $configId,
            'sync_started_at'   => date('Y-m-d H:i:s'),
            'records_fetched'   => 0,
            'records_created'   => 0,
            'records_updated'   => 0,
            'errors'            => [],
        ];

        try {
            // Fetch data from CMMS API
            $cmmsData = $this->callExternalAPI($config['api_url'] . '/equipment', [
                'modified_since' => $config['last_sync_at'] ?? '2000-01-01',
            ], $config);

            $results['records_fetched'] = count($cmmsData);

            foreach ($cmmsData as $record) {
                try {
                    $mapped = $this->mapFields($record, $fieldMapping);
                    $this->upsertAssetFromCMMS($mapped);
                    $results['records_updated']++;
                } catch (\Throwable $e) {
                    $results['errors'][] = "Record {$record['id']}: " . $e->getMessage();
                }
            }

            // Update last sync timestamp
            $this->db->update('integration_configs', [
                'last_sync_at' => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ], 'id = ?', [$configId]);

        } catch (\Throwable $e) {
            $results['errors'][] = 'Sync failed: ' . $e->getMessage();
            error_log('[RBI Integration] CMMS sync failed: ' . $e->getMessage());
        }

        $results['sync_completed_at'] = date('Y-m-d H:i:s');
        $this->logSync($results);

        return $results;
    }

    // ── SCADA Integration ───────────────────────────────────────────

    /**
     * Sync process data from SCADA/DCS system
     *
     * @return array  Sync results
     */
    public function syncSCADA(int $configId): array
    {
        $config = $this->getConfig($configId, 'scada');

        $results = [
            'config_id'       => $configId,
            'sync_started_at' => date('Y-m-d H:i:s'),
            'tags_processed'  => 0,
            'alerts_generated'=> 0,
            'errors'          => [],
        ];

        try {
            // Fetch tag values from SCADA
            $tagData = $this->callExternalAPI($config['api_url'] . '/tags/current', [], $config);

            foreach ($tagData as $tag) {
                try {
                    $this->processScadaTag($tag);
                    $results['tags_processed']++;

                    // Check for exceedance of operating limits
                    $alert = $this->checkOperatingLimits($tag);
                    if ($alert) {
                        $this->createAlert($alert);
                        $results['alerts_generated']++;
                    }
                } catch (\Throwable $e) {
                    $results['errors'][] = "Tag {$tag['tag_id']}: " . $e->getMessage();
                }
            }

            $this->db->update('integration_configs', [
                'last_sync_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$configId]);

        } catch (\Throwable $e) {
            $results['errors'][] = 'SCADA sync failed: ' . $e->getMessage();
        }

        $results['sync_completed_at'] = date('Y-m-d H:i:s');
        $this->logSync($results);

        return $results;
    }

    // ── IoT Data Processing ─────────────────────────────────────────

    /**
     * Process incoming IoT sensor data (corrosion probes, thickness sensors, vibration)
     *
     * @param  array $sensorData  Array of sensor readings
     * @return array  Processing results
     */
    public function processIoTData(array $sensorData): array
    {
        $results = [
            'received'   => count($sensorData),
            'processed'  => 0,
            'alerts'     => 0,
            'errors'     => [],
        ];

        foreach ($sensorData as $reading) {
            try {
                $this->validateSensorReading($reading);

                // Store raw reading
                $this->db->insert('iot_readings', [
                    'sensor_id'    => $reading['sensor_id'],
                    'asset_id'     => $reading['asset_id'] ?? null,
                    'sensor_type'  => $reading['sensor_type'],   // corrosion_probe, thickness, temperature, vibration
                    'value'        => $reading['value'],
                    'unit'         => $reading['unit'] ?? null,
                    'quality'      => $reading['quality'] ?? 'good',
                    'timestamp'    => $reading['timestamp'] ?? date('Y-m-d H:i:s'),
                    'created_at'   => date('Y-m-d H:i:s'),
                ]);

                // Process based on sensor type
                if ($reading['sensor_type'] === 'corrosion_probe' && !empty($reading['asset_id'])) {
                    $this->updateCorrosionFromIoT($reading);
                }

                // Alert on threshold exceedance
                if ($this->exceedsThreshold($reading)) {
                    $this->createAlert([
                        'asset_id'   => $reading['asset_id'] ?? null,
                        'alert_type' => 'iot_threshold',
                        'severity'   => 'warning',
                        'message'    => "Sensor {$reading['sensor_id']} value {$reading['value']} exceeds threshold.",
                    ]);
                    $results['alerts']++;
                }

                $results['processed']++;
            } catch (\Throwable $e) {
                $results['errors'][] = $e->getMessage();
            }
        }

        return $results;
    }

    // ── Digital Twin Sync ───────────────────────────────────────────

    /**
     * Sync asset data with a digital twin platform
     *
     * @return array  Sync results
     */
    public function syncDigitalTwin(int $configId, array $assetIds = []): array
    {
        $config = $this->getConfig($configId, 'digital_twin');

        $results = [
            'config_id'       => $configId,
            'assets_synced'   => 0,
            'errors'          => [],
        ];

        // If no specific assets, get all active
        if (empty($assetIds)) {
            $assets = $this->db->query(
                "SELECT id FROM assets WHERE status = 'active'"
            )->fetchAll();
            $assetIds = array_column($assets, 'id');
        }

        foreach ($assetIds as $assetId) {
            try {
                $asset = (new Asset())->getById($assetId);
                $riskData = $this->db->query(
                    "SELECT * FROM risk_assessments WHERE asset_id = ? ORDER BY calculated_at DESC LIMIT 1",
                    [$assetId]
                )->fetch();

                $payload = [
                    'asset_tag'      => $asset['asset_tag'],
                    'condition'      => [
                        'thickness'      => $asset['measured_thickness'] ?? $asset['nominal_thickness'],
                        'condition_grade'=> $asset['condition_grade'] ?? 'B',
                        'corrosion_rate' => $riskData['damage_factor'] ?? null,
                    ],
                    'risk'           => [
                        'pof'         => $riskData['pof_value'] ?? null,
                        'cof'         => $riskData['cof_value'] ?? null,
                        'risk_level'  => $riskData['risk_level'] ?? null,
                    ],
                    'operational'    => [
                        'pressure'    => $asset['operating_pressure'],
                        'temperature' => $asset['operating_temperature'],
                    ],
                    'synced_at'      => date('Y-m-d\TH:i:s\Z'),
                ];

                $this->callExternalAPI(
                    $config['api_url'] . '/assets/' . $asset['asset_tag'],
                    $payload,
                    $config,
                    'PUT'
                );

                $results['assets_synced']++;
            } catch (\Throwable $e) {
                $results['errors'][] = "Asset {$assetId}: " . $e->getMessage();
            }
        }

        $this->db->update('integration_configs', [
            'last_sync_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$configId]);

        return $results;
    }

    // ── Inspection Data Import ──────────────────────────────────────

    /**
     * Import inspection data from CSV/external sources
     *
     * @param  string $filePath  Path to the CSV file
     * @return array  Import results
     */
    public function importInspectionData(string $filePath, array $columnMap = []): array
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException('File not found: ' . $filePath);
        }

        $results = [
            'file'             => basename($filePath),
            'rows_read'        => 0,
            'rows_imported'    => 0,
            'rows_skipped'     => 0,
            'errors'           => [],
        ];

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new RuntimeException('Cannot open file: ' . $filePath);
        }

        // Read header row
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            throw new RuntimeException('Empty CSV file.');
        }

        // Default column mapping
        if (empty($columnMap)) {
            $columnMap = [
                'asset_tag'          => 'Asset Tag',
                'inspection_date'    => 'Inspection Date',
                'measured_thickness' => 'Measured Thickness (mm)',
                'method'             => 'Inspection Method',
                'reading_location'   => 'Location',
                'inspector'          => 'Inspector',
                'notes'              => 'Notes',
            ];
        }

        // Resolve column indices
        $colIndices = [];
        foreach ($columnMap as $field => $headerName) {
            $idx = array_search($headerName, $headers);
            if ($idx !== false) {
                $colIndices[$field] = $idx;
            }
        }

        $this->db->beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $results['rows_read']++;

                try {
                    $assetTag = $row[$colIndices['asset_tag'] ?? 0] ?? null;
                    if (!$assetTag) {
                        $results['rows_skipped']++;
                        continue;
                    }

                    // Find asset by tag
                    $asset = $this->db->query(
                        "SELECT id FROM assets WHERE asset_tag = ? LIMIT 1",
                        [$assetTag]
                    )->fetch();

                    if (!$asset) {
                        $results['errors'][] = "Row {$results['rows_read']}: Asset '{$assetTag}' not found.";
                        $results['rows_skipped']++;
                        continue;
                    }

                    $this->db->insert('inspection_readings', [
                        'asset_id'           => $asset['id'],
                        'reading_date'       => $row[$colIndices['inspection_date'] ?? 1] ?? date('Y-m-d'),
                        'measured_thickness' => (float) ($row[$colIndices['measured_thickness'] ?? 2] ?? 0),
                        'method'             => $row[$colIndices['method'] ?? 3] ?? 'ut_spot',
                        'reading_location'   => $row[$colIndices['reading_location'] ?? 4] ?? null,
                        'created_at'         => date('Y-m-d H:i:s'),
                    ]);

                    $results['rows_imported']++;
                } catch (\Throwable $e) {
                    $results['errors'][] = "Row {$results['rows_read']}: " . $e->getMessage();
                    $results['rows_skipped']++;
                }
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        } finally {
            fclose($handle);
        }

        return $results;
    }

    // ── Configuration Management ────────────────────────────────────

    /**
     * Get all integration configurations
     */
    public function getAllConfigs(): array
    {
        return $this->db->query(
            "SELECT * FROM integration_configs ORDER BY integration_type, system_name"
        )->fetchAll();
    }

    /**
     * Get a specific integration config and validate its type
     */
    private function getConfig(int $configId, string $expectedType): array
    {
        $config = $this->db->find('integration_configs', $configId);
        if (!$config) {
            throw new InvalidArgumentException("Integration config not found: {$configId}");
        }
        if ($config['integration_type'] !== $expectedType) {
            throw new InvalidArgumentException("Config {$configId} is not a {$expectedType} integration.");
        }
        if (!$config['is_active']) {
            throw new RuntimeException("Integration config {$configId} is inactive.");
        }
        return $config;
    }

    // ── Internal Helpers ────────────────────────────────────────────

    private function validateConfig(array $config, array $required): void
    {
        foreach ($required as $field) {
            if (empty($config[$field])) {
                throw new InvalidArgumentException("Integration config field '{$field}' is required.");
            }
        }
    }

    private function validateSensorReading(array $reading): void
    {
        if (empty($reading['sensor_id'])) {
            throw new InvalidArgumentException('Sensor ID is required.');
        }
        if (!isset($reading['value'])) {
            throw new InvalidArgumentException('Sensor value is required.');
        }
        if (empty($reading['sensor_type'])) {
            throw new InvalidArgumentException('Sensor type is required.');
        }
    }

    /**
     * Call an external REST API
     */
    private function callExternalAPI(string $url, array $data, array $config, string $method = 'GET'): array
    {
        $ch = curl_init();

        $headers = ['Content-Type: application/json', 'Accept: application/json'];

        // Authentication
        $authType = $config['auth_type'] ?? 'api_key';
        if ($authType === 'api_key' && !empty($config['api_key'])) {
            $headers[] = 'X-API-Key: ' . $config['api_key'];
        } elseif ($authType === 'basic') {
            $creds = json_decode($config['auth_credentials'] ?? '{}', true);
            curl_setopt($ch, CURLOPT_USERPWD, ($creds['username'] ?? '') . ':' . ($creds['password'] ?? ''));
        }

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url . ($method === 'GET' && $data ? '?' . http_build_query($data) : ''),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new RuntimeException("API call failed: {$error}");
        }
        if ($httpCode >= 400) {
            throw new RuntimeException("API returned HTTP {$httpCode}: {$response}");
        }

        return json_decode($response, true) ?: [];
    }

    private function mapFields(array $record, array $mapping): array
    {
        $mapped = [];
        foreach ($mapping as $localField => $remoteField) {
            $mapped[$localField] = $record[$remoteField] ?? null;
        }
        return $mapped;
    }

    private function upsertAssetFromCMMS(array $data): void
    {
        if (empty($data['asset_tag'])) return;

        $existing = $this->db->query(
            "SELECT id FROM assets WHERE asset_tag = ? LIMIT 1",
            [$data['asset_tag']]
        )->fetch();

        $data['updated_at'] = date('Y-m-d H:i:s');

        if ($existing) {
            unset($data['asset_tag']);
            $this->db->update('assets', $data, 'id = ?', [$existing['id']]);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('assets', $data);
        }
    }

    private function processScadaTag(array $tag): void
    {
        if (empty($tag['asset_id'])) return;

        $this->db->insert('scada_readings', [
            'tag_id'     => $tag['tag_id'],
            'asset_id'   => $tag['asset_id'],
            'tag_name'   => $tag['tag_name'] ?? null,
            'value'      => $tag['value'],
            'unit'       => $tag['unit'] ?? null,
            'timestamp'  => $tag['timestamp'] ?? date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function checkOperatingLimits(array $tag): ?array
    {
        if (empty($tag['asset_id'])) return null;

        $asset = $this->db->query(
            "SELECT design_pressure, design_temperature FROM assets WHERE id = ?",
            [$tag['asset_id']]
        )->fetch();

        if (!$asset) return null;

        $tagName = strtolower($tag['tag_name'] ?? '');
        if (str_contains($tagName, 'pressure') && $asset['design_pressure'] && $tag['value'] > $asset['design_pressure'] * 0.9) {
            return [
                'asset_id'   => $tag['asset_id'],
                'alert_type' => 'operating_exceedance',
                'severity'   => 'critical',
                'message'    => "Pressure {$tag['value']} approaching design limit {$asset['design_pressure']}.",
            ];
        }

        if (str_contains($tagName, 'temperature') && $asset['design_temperature'] && $tag['value'] > $asset['design_temperature'] * 0.9) {
            return [
                'asset_id'   => $tag['asset_id'],
                'alert_type' => 'operating_exceedance',
                'severity'   => 'critical',
                'message'    => "Temperature {$tag['value']} approaching design limit {$asset['design_temperature']}.",
            ];
        }

        return null;
    }

    private function updateCorrosionFromIoT(array $reading): void
    {
        $this->db->insert('inspection_readings', [
            'asset_id'           => $reading['asset_id'],
            'reading_date'       => date('Y-m-d', strtotime($reading['timestamp'] ?? 'now')),
            'measured_thickness' => $reading['value'],
            'method'             => 'iot_sensor',
            'created_at'         => date('Y-m-d H:i:s'),
        ]);
    }

    private function exceedsThreshold(array $reading): bool
    {
        $threshold = $this->db->query(
            "SELECT threshold_min, threshold_max FROM sensor_thresholds
             WHERE sensor_id = ? AND is_active = 1 LIMIT 1",
            [$reading['sensor_id']]
        )->fetch();

        if (!$threshold) return false;

        return ($threshold['threshold_min'] !== null && $reading['value'] < $threshold['threshold_min'])
            || ($threshold['threshold_max'] !== null && $reading['value'] > $threshold['threshold_max']);
    }

    private function createAlert(array $alert): void
    {
        $this->db->insert('alerts', [
            'asset_id'    => $alert['asset_id'],
            'alert_type'  => $alert['alert_type'],
            'severity'    => $alert['severity'],
            'message'     => $alert['message'],
            'is_read'     => 0,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    private function logSync(array $results): void
    {
        try {
            $this->db->insert('integration_sync_log', [
                'config_id'     => $results['config_id'] ?? null,
                'status'        => empty($results['errors']) ? 'success' : 'partial',
                'summary'       => json_encode($results),
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            error_log('[RBI Integration] Sync log failed: ' . $e->getMessage());
        }
    }

    private function defaultCMMSMapping(): array
    {
        return [
            'asset_tag'           => 'equipment_id',
            'name'                => 'description',
            'asset_type'          => 'equipment_type',
            'material'            => 'material_spec',
            'design_pressure'     => 'design_press',
            'design_temperature'  => 'design_temp',
            'operating_pressure'  => 'operating_press',
            'operating_temperature'=> 'operating_temp',
            'install_date'        => 'install_date',
            'status'              => 'status',
        ];
    }
}
