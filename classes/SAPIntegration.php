<?php
/**
 * SAPIntegration - SAP Plant Maintenance (PM) / S/4HANA Integration
 *
 * Connects to SAP via OData REST APIs for equipment master data,
 * maintenance orders, notifications, and measurement documents.
 * Supports both SAP ECC (OData v2) and S/4HANA (OData v4).
 *
 * @package RBI Engineering Suite
 */
class SAPIntegration
{
    private Database $db;
    private array $config;
    private ?string $authToken = null;
    private ?string $csrfToken = null;
    private array $cookies = [];
    private string $logPrefix = '[SAP Integration]';

    /** Maximum retry attempts for transient failures */
    private const MAX_RETRIES = 3;
    /** Base delay in seconds for exponential backoff */
    private const RETRY_DELAY = 1;
    /** Connection timeout in seconds */
    private const CONNECT_TIMEOUT = 10;
    /** Response timeout in seconds */
    private const RESPONSE_TIMEOUT = 30;

    /** SAP OData API base paths */
    private const API_EQUIPMENT          = '/sap/opu/odata/sap/API_EQUIPMENT/A_Equipment';
    private const API_FUNCTIONAL_LOC     = '/sap/opu/odata/sap/API_FUNCTIONAL_LOCATION/A_FunctionalLocation';
    private const API_MAINT_ORDER        = '/sap/opu/odata/sap/API_MAINTENANCE_ORDER/A_MaintenanceOrder';
    private const API_MAINT_NOTIFICATION = '/sap/opu/odata/sap/API_MAINTNOTIFICATION/A_MaintenanceNotification';
    private const API_MEASUREMENT_DOC    = '/sap/opu/odata/sap/API_MEASUREMENTDOCUMENT/A_MeasurementDocument';
    private const API_MEAS_POINT         = '/sap/opu/odata/sap/API_MEASURINGPOINT/A_MeasuringPoint';

    /**
     * Initialize SAP integration with connection configuration.
     *
     * @param array $config Keys: host, client, user, password, system_id, auth_type (basic|oauth2),
     *                      oauth_token_url, oauth_client_id, oauth_client_secret, verify_ssl
     */
    public function __construct(array $config)
    {
        $this->db = new Database();
        $this->config = array_merge([
            'host'                => '',
            'client'              => '100',
            'user'                => '',
            'password'            => '',
            'system_id'           => '',
            'auth_type'           => 'basic',  // basic or oauth2
            'oauth_token_url'     => '',
            'oauth_client_id'     => '',
            'oauth_client_secret' => '',
            'verify_ssl'          => true,
            'language'            => 'EN',
        ], $config);
    }

    // =========================================================================
    // Authentication
    // =========================================================================

    /**
     * Authenticate to SAP system.
     * For Basic Auth: validates credentials and fetches CSRF token.
     * For OAuth2: obtains bearer token from SAP authorization server.
     *
     * @return bool True if authentication succeeds
     * @throws RuntimeException On authentication failure
     */
    public function authenticate(): bool
    {
        if ($this->config['auth_type'] === 'oauth2') {
            return $this->authenticateOAuth2();
        }
        return $this->authenticateBasic();
    }

    /**
     * Basic authentication with CSRF token fetch.
     */
    private function authenticateBasic(): bool
    {
        $url = rtrim($this->config['host'], '/') . self::API_EQUIPMENT . '?$top=1';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT        => self::RESPONSE_TIMEOUT,
            CURLOPT_USERPWD        => $this->config['user'] . ':' . $this->config['password'],
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'x-csrf-token: Fetch',
                'sap-client: ' . $this->config['client'],
                'sap-language: ' . $this->config['language'],
            ],
            CURLOPT_SSL_VERIFYPEER => (bool)$this->config['verify_ssl'],
            CURLOPT_SSL_VERIFYHOST => $this->config['verify_ssl'] ? 2 : 0,
            CURLOPT_HEADER         => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->log('ERROR', "Basic auth failed: {$error}");
            throw new RuntimeException("{$this->logPrefix} Connection failed: {$error}");
        }

        if ($httpCode === 401) {
            throw new RuntimeException("{$this->logPrefix} Authentication failed: invalid credentials");
        }

        if ($httpCode >= 400) {
            throw new RuntimeException("{$this->logPrefix} Authentication failed with HTTP {$httpCode}");
        }

        // Extract CSRF token and cookies from response headers
        $headers = substr($response, 0, $headerSize);
        $this->csrfToken = $this->extractHeader($headers, 'x-csrf-token');
        $this->extractCookies($headers);

        $this->log('INFO', 'Basic authentication successful');
        return true;
    }

    /**
     * OAuth2 client credentials flow for SAP S/4HANA Cloud.
     */
    private function authenticateOAuth2(): bool
    {
        if (empty($this->config['oauth_token_url'])) {
            throw new RuntimeException("{$this->logPrefix} OAuth2 token URL not configured");
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->config['oauth_token_url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT        => self::RESPONSE_TIMEOUT,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type'    => 'client_credentials',
                'client_id'     => $this->config['oauth_client_id'],
                'client_secret' => $this->config['oauth_client_secret'],
            ]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_SSL_VERIFYPEER => (bool)$this->config['verify_ssl'],
            CURLOPT_SSL_VERIFYHOST => $this->config['verify_ssl'] ? 2 : 0,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode >= 400) {
            $msg = $error ?: "HTTP {$httpCode}: {$response}";
            $this->log('ERROR', "OAuth2 token request failed: {$msg}");
            throw new RuntimeException("{$this->logPrefix} OAuth2 authentication failed: {$msg}");
        }

        $tokenData = json_decode($response, true);
        if (empty($tokenData['access_token'])) {
            throw new RuntimeException("{$this->logPrefix} OAuth2 response missing access_token");
        }

        $this->authToken = $tokenData['access_token'];
        $this->log('INFO', 'OAuth2 authentication successful, token expires in ' . ($tokenData['expires_in'] ?? '?') . 's');
        return true;
    }

    // =========================================================================
    // Equipment Master Data
    // =========================================================================

    /**
     * Get equipment list from SAP with optional filters.
     *
     * @param array $filters Keys: plant, functional_location, category, search, top, skip
     * @return array List of equipment records mapped to RBI fields
     */
    public function getEquipmentList(array $filters = []): array
    {
        $params = [
            '$format' => 'json',
            '$top'    => $filters['top'] ?? 100,
            '$skip'   => $filters['skip'] ?? 0,
            '$select' => 'Equipment,EquipmentName,FunctionalLocation,EquipmentCategory,'
                       . 'ManufacturerPartNbr,ConstructionYear,CompanyCode,PlanningPlant,'
                       . 'MaintenancePlant,CostCenter,InventoryNumber,AcquisitionDate,'
                       . 'ManufacturerSerialNumber,AssetLocation',
        ];

        $odataFilter = $this->buildODataFilter($filters);
        if ($odataFilter) {
            $params['$filter'] = $odataFilter;
        }

        $response = $this->sapGet(self::API_EQUIPMENT, $params);
        $results = $response['d']['results'] ?? $response['value'] ?? [];

        return array_map(function ($item) {
            return $this->mapSAPFieldsToRBI($item, 'equipment');
        }, $results);
    }

    /**
     * Get detailed equipment data including classification.
     *
     * @param string $equipmentId SAP Equipment number
     * @return array|null Equipment detail mapped to RBI fields
     */
    public function getEquipmentDetail(string $equipmentId): ?array
    {
        $params = [
            '$format' => 'json',
            '$expand' => 'to_Classification,to_Partner',
        ];

        $response = $this->sapGet(self::API_EQUIPMENT . "('{$equipmentId}')", $params);
        $data = $response['d'] ?? $response ?? null;

        if (!$data || isset($data['error'])) {
            return null;
        }

        return $this->mapSAPFieldsToRBI($data, 'equipment_detail');
    }

    /**
     * Get functional location hierarchy for a plant.
     *
     * @param string $plant SAP plant code
     * @return array Hierarchical functional location tree
     */
    public function getFunctionalLocations(string $plant): array
    {
        $params = [
            '$format' => 'json',
            '$filter' => "MaintenancePlant eq '{$plant}'",
            '$select' => 'FunctionalLocation,FunctionalLocationName,FuncLocCategory,'
                       . 'SuperiorFunctionalLocation,MaintenancePlant,CompanyCode,'
                       . 'ConstructionYear,FuncLocIsDeleted',
            '$top'    => 9999,
        ];

        $response = $this->sapGet(self::API_FUNCTIONAL_LOC, $params);
        $locations = $response['d']['results'] ?? $response['value'] ?? [];

        return $this->buildHierarchyTree($locations);
    }

    // =========================================================================
    // Maintenance Orders & Notifications
    // =========================================================================

    /**
     * Get maintenance orders with filters.
     *
     * @param array $filters Keys: order_type, equipment, date_from, date_to, status, plant
     * @return array List of maintenance orders
     */
    public function getMaintenanceOrders(array $filters = []): array
    {
        $params = [
            '$format' => 'json',
            '$top'    => $filters['top'] ?? 100,
            '$skip'   => $filters['skip'] ?? 0,
            '$select' => 'MaintenanceOrder,MaintenanceOrderDesc,OrderType,Equipment,'
                       . 'FunctionalLocation,MaintenancePlanningPlant,MaintenanceOrderType,'
                       . 'MaintOrdBasicStartDate,MaintOrdBasicEndDate,MaintenanceActivityType,'
                       . 'MaintPriority,SystemStatusText,UserStatusText',
            '$orderby' => 'MaintOrdBasicStartDate desc',
        ];

        $filterParts = [];
        if (!empty($filters['order_type'])) {
            $filterParts[] = "OrderType eq '{$filters['order_type']}'";
        }
        if (!empty($filters['equipment'])) {
            $filterParts[] = "Equipment eq '{$filters['equipment']}'";
        }
        if (!empty($filters['date_from'])) {
            $filterParts[] = "MaintOrdBasicStartDate ge datetime'{$filters['date_from']}T00:00:00'";
        }
        if (!empty($filters['date_to'])) {
            $filterParts[] = "MaintOrdBasicEndDate le datetime'{$filters['date_to']}T23:59:59'";
        }
        if (!empty($filters['plant'])) {
            $filterParts[] = "MaintenancePlanningPlant eq '{$filters['plant']}'";
        }
        if ($filterParts) {
            $params['$filter'] = implode(' and ', $filterParts);
        }

        $response = $this->sapGet(self::API_MAINT_ORDER, $params);
        return $response['d']['results'] ?? $response['value'] ?? [];
    }

    /**
     * Get maintenance notifications (breakdowns, malfunctions) for failure analysis.
     *
     * @param array $filters Keys: notification_type, equipment, date_from, date_to
     * @return array Notifications list
     */
    public function getMaintenanceNotifications(array $filters = []): array
    {
        $params = [
            '$format' => 'json',
            '$top'    => $filters['top'] ?? 100,
            '$select' => 'MaintenanceNotification,NotificationType,NotificationText,'
                       . 'Equipment,FunctionalLocation,ReportedByUser,CreationDate,'
                       . 'MalfunctionStartDate,MalfunctionEndDate,NotifProcessingPhase,'
                       . 'MaintPriority,Breakdown',
            '$orderby' => 'CreationDate desc',
        ];

        $filterParts = [];
        if (!empty($filters['notification_type'])) {
            $filterParts[] = "NotificationType eq '{$filters['notification_type']}'";
        }
        if (!empty($filters['equipment'])) {
            $filterParts[] = "Equipment eq '{$filters['equipment']}'";
        }
        if (!empty($filters['date_from'])) {
            $filterParts[] = "CreationDate ge datetime'{$filters['date_from']}T00:00:00'";
        }
        if (!empty($filters['date_to'])) {
            $filterParts[] = "CreationDate le datetime'{$filters['date_to']}T23:59:59'";
        }
        if ($filterParts) {
            $params['$filter'] = implode(' and ', $filterParts);
        }

        $response = $this->sapGet(self::API_MAINT_NOTIFICATION, $params);
        return $response['d']['results'] ?? $response['value'] ?? [];
    }

    /**
     * Get measurement documents (thickness readings, pressure, temperature).
     *
     * @param string $equipmentId SAP equipment number
     * @return array List of measurement readings
     */
    public function getMeasurementDocuments(string $equipmentId): array
    {
        // First get measurement points for the equipment
        $pointParams = [
            '$format' => 'json',
            '$filter' => "Equipment eq '{$equipmentId}'",
            '$select' => 'MeasuringPoint,MeasuringPointDescription,Equipment,MeasuringPointCodeGroup,'
                       . 'MeasuringPointCodeGroupValue,CharacteristicName,TargetValue,'
                       . 'MeasuringPointLowerLimit,MeasuringPointUpperLimit,UnitOfMeasurement',
        ];

        $pointsResponse = $this->sapGet(self::API_MEAS_POINT, $pointParams);
        $points = $pointsResponse['d']['results'] ?? $pointsResponse['value'] ?? [];

        $measurements = [];
        foreach ($points as $point) {
            $measParams = [
                '$format'  => 'json',
                '$filter'  => "MeasuringPoint eq '{$point['MeasuringPoint']}'",
                '$select'  => 'MeasurementDocument,MeasuringPoint,MeasurementReadingDate,'
                            . 'MsmtRdngValue,MeasurementUnit,MsmtReadingDescription,'
                            . 'MsmtRdngByUser,MsmtValuationCode',
                '$orderby' => 'MeasurementReadingDate desc',
                '$top'     => 100,
            ];

            $measResponse = $this->sapGet(self::API_MEASUREMENT_DOC, $measParams);
            $docs = $measResponse['d']['results'] ?? $measResponse['value'] ?? [];

            foreach ($docs as $doc) {
                $doc['_MeasuringPointDetail'] = $point;
                $measurements[] = $doc;
            }
        }

        return $measurements;
    }

    // =========================================================================
    // Write Operations
    // =========================================================================

    /**
     * Create a new maintenance order in SAP for an inspection task.
     *
     * @param array $data Keys: equipment, description, order_type, priority, start_date, end_date,
     *                     functional_location, plant, work_center, operations[]
     * @return array Created order data including order number
     */
    public function createMaintenanceOrder(array $data): array
    {
        $this->fetchCsrfToken();

        $payload = [
            'OrderType'                => $data['order_type'] ?? 'PM01',
            'Equipment'                => $data['equipment'] ?? '',
            'MaintenanceOrderDesc'     => $data['description'] ?? 'RBI Inspection',
            'FunctionalLocation'       => $data['functional_location'] ?? '',
            'MaintenancePlanningPlant' => $data['plant'] ?? '',
            'MaintPriority'            => $data['priority'] ?? '2',
            'MaintOrdBasicStartDate'   => $this->formatSAPDate($data['start_date'] ?? date('Y-m-d')),
            'MaintOrdBasicEndDate'     => $this->formatSAPDate($data['end_date'] ?? date('Y-m-d', strtotime('+30 days'))),
            'MainWorkCenter'           => $data['work_center'] ?? '',
        ];

        $response = $this->sapPost(self::API_MAINT_ORDER, $payload);

        $this->log('INFO', "Created SAP maintenance order: " . ($response['d']['MaintenanceOrder'] ?? 'unknown'));
        return $response['d'] ?? $response;
    }

    /**
     * Create a notification for findings/defects discovered during inspection.
     *
     * @param array $data Keys: equipment, notification_type, description, priority,
     *                     malfunction_start, breakdown, functional_location
     * @return array Created notification data
     */
    public function createNotification(array $data): array
    {
        $this->fetchCsrfToken();

        $payload = [
            'NotificationType'    => $data['notification_type'] ?? 'M2',  // M2 = Malfunction report
            'NotificationText'    => $data['description'] ?? '',
            'Equipment'           => $data['equipment'] ?? '',
            'FunctionalLocation'  => $data['functional_location'] ?? '',
            'MaintPriority'       => $data['priority'] ?? '3',
            'Breakdown'           => !empty($data['breakdown']) ? 'X' : '',
            'MalfunctionStartDate'=> $this->formatSAPDate($data['malfunction_start'] ?? date('Y-m-d')),
        ];

        $response = $this->sapPost(self::API_MAINT_NOTIFICATION, $payload);

        $this->log('INFO', "Created SAP notification: " . ($response['d']['MaintenanceNotification'] ?? 'unknown'));
        return $response['d'] ?? $response;
    }

    /**
     * Post a new measurement document with thickness reading.
     *
     * @param array $data Keys: measuring_point, value, unit, date, description
     * @return array Created document data
     */
    public function updateMeasurementReading(array $data): array
    {
        $this->fetchCsrfToken();

        $payload = [
            'MeasuringPoint'         => $data['measuring_point'] ?? '',
            'MsmtRdngValue'          => (string)($data['value'] ?? 0),
            'MeasurementUnit'        => $data['unit'] ?? 'MM',
            'MeasurementReadingDate' => $this->formatSAPDate($data['date'] ?? date('Y-m-d')),
            'MsmtReadingDescription' => $data['description'] ?? 'RBI thickness reading',
        ];

        $response = $this->sapPost(self::API_MEASUREMENT_DOC, $payload);

        $this->log('INFO', "Posted measurement reading for point {$data['measuring_point']}");
        return $response['d'] ?? $response;
    }

    // =========================================================================
    // Synchronization
    // =========================================================================

    /**
     * Full sync: pull all SAP equipment and create/update RBI asset_registry records.
     * Handles field mapping, deduplication, and conflict resolution.
     *
     * @return array Sync results summary
     */
    public function syncEquipmentToAssets(): array
    {
        $syncLog = [
            'started_at'       => date('Y-m-d H:i:s'),
            'records_processed'=> 0,
            'records_created'  => 0,
            'records_updated'  => 0,
            'records_failed'   => 0,
            'errors'           => [],
            'conflicts'        => [],
        ];

        try {
            $this->authenticate();
            $skip = 0;
            $batchSize = 200;

            do {
                $equipment = $this->getEquipmentList(['top' => $batchSize, 'skip' => $skip]);
                if (empty($equipment)) break;

                foreach ($equipment as $item) {
                    $syncLog['records_processed']++;
                    try {
                        $result = $this->upsertAsset($item);
                        if ($result === 'created') {
                            $syncLog['records_created']++;
                        } elseif ($result === 'updated') {
                            $syncLog['records_updated']++;
                        }
                    } catch (\Throwable $e) {
                        $syncLog['records_failed']++;
                        $syncLog['errors'][] = "Equipment {$item['asset_tag']}: " . $e->getMessage();
                    }
                }

                $skip += $batchSize;
            } while (count($equipment) === $batchSize);

        } catch (\Throwable $e) {
            $syncLog['errors'][] = 'Sync aborted: ' . $e->getMessage();
            $this->log('ERROR', 'Equipment sync failed: ' . $e->getMessage());
        }

        $syncLog['completed_at'] = date('Y-m-d H:i:s');
        return $syncLog;
    }

    /**
     * Push RBI inspection findings back to SAP as notifications and/or orders.
     *
     * @param int $inspectionId RBI inspection task ID
     * @return array Sync results
     */
    public function syncInspectionResults(int $inspectionId): array
    {
        $results = ['notifications_created' => 0, 'orders_created' => 0, 'errors' => []];

        try {
            $this->authenticate();

            // Get inspection task and findings
            $task = $this->db->query(
                "SELECT it.*, ip.asset_id, ar.asset_tag, ar.equipment_name
                 FROM inspection_tasks it
                 JOIN inspection_plans ip ON it.plan_id = ip.id
                 JOIN asset_registry ar ON ip.asset_id = ar.id
                 WHERE it.id = ?",
                [$inspectionId]
            )->fetch();

            if (!$task) {
                throw new RuntimeException("Inspection task {$inspectionId} not found");
            }

            // Get findings for this task
            $findings = $this->db->query(
                "SELECT * FROM inspection_findings WHERE task_id = ? AND disposition != 'closed'",
                [$inspectionId]
            )->fetchAll();

            foreach ($findings as $finding) {
                try {
                    // Create notification for each finding
                    $notifData = [
                        'equipment'           => $task['asset_tag'],
                        'notification_type'    => $this->mapFindingSeverityToNotifType($finding),
                        'description'          => "RBI Finding: " . ($finding['description'] ?? 'Inspection finding'),
                        'priority'             => $this->mapFindingPriority($finding),
                        'malfunction_start'    => $finding['found_date'] ?? date('Y-m-d'),
                    ];

                    $this->createNotification($notifData);
                    $results['notifications_created']++;

                    // For critical findings, also create a maintenance order
                    if (($finding['severity'] ?? '') === 'critical') {
                        $orderData = [
                            'equipment'    => $task['asset_tag'],
                            'description'  => "URGENT: " . ($finding['description'] ?? 'Critical finding'),
                            'order_type'   => 'PM02',
                            'priority'     => '1',
                            'start_date'   => date('Y-m-d'),
                        ];
                        $this->createMaintenanceOrder($orderData);
                        $results['orders_created']++;
                    }
                } catch (\Throwable $e) {
                    $results['errors'][] = "Finding {$finding['id']}: " . $e->getMessage();
                }
            }
        } catch (\Throwable $e) {
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Get full plant structure: Plant > Functional Location > Equipment > Sub-equipment.
     *
     * @return array Nested plant hierarchy
     */
    public function getPlantStructure(): array
    {
        $this->authenticate();

        $plants = [];
        $funcLocs = $this->getFunctionalLocations($this->config['plant'] ?? '');
        $equipment = $this->getEquipmentList(['top' => 9999]);

        // Group equipment by functional location
        $equipByLoc = [];
        foreach ($equipment as $eq) {
            $loc = $eq['functional_location'] ?? 'UNASSIGNED';
            $equipByLoc[$loc][] = $eq;
        }

        // Attach equipment to functional locations
        foreach ($funcLocs as &$loc) {
            $locId = $loc['functional_location'] ?? '';
            $loc['equipment'] = $equipByLoc[$locId] ?? [];
        }

        return [
            'plant'               => $this->config['plant'] ?? '',
            'functional_locations' => $funcLocs,
            'total_equipment'      => count($equipment),
        ];
    }

    // =========================================================================
    // Field Mapping
    // =========================================================================

    /**
     * Map SAP fields to RBI data model fields.
     *
     * @param array  $sapData    Raw SAP entity data
     * @param string $entityType equipment|equipment_detail|work_order|notification
     * @return array Mapped RBI fields
     */
    public function mapSAPFieldsToRBI(array $sapData, string $entityType): array
    {
        $mappings = $this->getFieldMappings($entityType);
        $mapped = [];

        foreach ($mappings as $sapField => $rbiField) {
            $value = $sapData[$sapField] ?? null;

            // Apply type-specific transformations
            if ($value !== null) {
                if (str_contains($rbiField, 'date') && $value) {
                    $value = $this->parseSAPDate($value);
                }
                if (str_contains($rbiField, 'pressure') || str_contains($rbiField, 'temperature') || str_contains($rbiField, 'thickness')) {
                    $value = is_numeric($value) ? (float)$value : null;
                }
            }

            $mapped[$rbiField] = $value;
        }

        $mapped['_source'] = 'sap';
        $mapped['_synced_at'] = date('Y-m-d H:i:s');

        return $mapped;
    }

    /**
     * Get default field mappings for an entity type.
     */
    private function getFieldMappings(string $entityType): array
    {
        $mappings = [
            'equipment' => [
                'Equipment'              => 'asset_tag',
                'EquipmentName'          => 'equipment_name',
                'FunctionalLocation'     => 'functional_location',
                'EquipmentCategory'      => 'asset_type',
                'ManufacturerPartNbr'    => 'manufacturer_model',
                'ConstructionYear'       => 'construction_year',
                'PlanningPlant'          => 'plant_code',
                'ManufacturerSerialNumber'=> 'serial_number',
                'AcquisitionDate'        => 'installation_date',
                'AssetLocation'          => 'location_description',
                'CompanyCode'            => 'company_code',
                'CostCenter'             => 'cost_center',
            ],
            'equipment_detail' => [
                'Equipment'              => 'asset_tag',
                'EquipmentName'          => 'equipment_name',
                'FunctionalLocation'     => 'functional_location',
                'EquipmentCategory'      => 'asset_type',
                'ManufacturerPartNbr'    => 'manufacturer_model',
                'ConstructionYear'       => 'construction_year',
                'PlanningPlant'          => 'plant_code',
                'ManufacturerSerialNumber'=> 'serial_number',
                'AcquisitionDate'        => 'installation_date',
                'TechIdentNo'            => 'technical_id',
                'AssetLocation'          => 'location_description',
                'ObjectWeight'           => 'weight',
                'ObjectWeightUnit'       => 'weight_unit',
            ],
            'work_order' => [
                'MaintenanceOrder'         => 'external_wo_number',
                'MaintenanceOrderDesc'     => 'description',
                'OrderType'                => 'order_type',
                'Equipment'                => 'asset_tag',
                'MaintPriority'            => 'priority',
                'MaintOrdBasicStartDate'   => 'scheduled_start',
                'MaintOrdBasicEndDate'     => 'scheduled_end',
                'SystemStatusText'         => 'status',
            ],
            'notification' => [
                'MaintenanceNotification'  => 'external_notif_number',
                'NotificationText'         => 'description',
                'NotificationType'         => 'notification_type',
                'Equipment'                => 'asset_tag',
                'CreationDate'             => 'created_date',
                'MalfunctionStartDate'     => 'malfunction_start',
                'Breakdown'                => 'is_breakdown',
                'MaintPriority'            => 'priority',
            ],
        ];

        // Override with database-configured mappings if available
        try {
            $dbMappings = $this->db->query(
                "SELECT external_field, internal_field FROM integration_field_mappings
                 WHERE integration_id = ? AND entity_type = ?",
                [$this->config['integration_id'] ?? 0, $entityType]
            )->fetchAll();

            if (!empty($dbMappings)) {
                $custom = [];
                foreach ($dbMappings as $m) {
                    $custom[$m['external_field']] = $m['internal_field'];
                }
                return $custom;
            }
        } catch (\Throwable $e) {
            // Fall back to defaults silently
        }

        return $mappings[$entityType] ?? $mappings['equipment'];
    }

    // =========================================================================
    // OData Helpers
    // =========================================================================

    /**
     * Build OData $filter query string from PHP array.
     *
     * @param array $filters Key-value pairs. Supports operators via prefix: 'ge:', 'le:', 'ne:', 'like:'
     * @return string OData filter expression
     */
    public function buildODataFilter(array $filters): string
    {
        $parts = [];
        $odataFields = [
            'plant'               => 'PlanningPlant',
            'functional_location' => 'FunctionalLocation',
            'category'            => 'EquipmentCategory',
            'company_code'        => 'CompanyCode',
        ];

        foreach ($filters as $key => $value) {
            if (!isset($odataFields[$key]) || $value === '' || $value === null) continue;

            $field = $odataFields[$key];

            if (str_starts_with($value, 'ge:')) {
                $parts[] = "{$field} ge '" . substr($value, 3) . "'";
            } elseif (str_starts_with($value, 'le:')) {
                $parts[] = "{$field} le '" . substr($value, 3) . "'";
            } elseif (str_starts_with($value, 'ne:')) {
                $parts[] = "{$field} ne '" . substr($value, 3) . "'";
            } elseif (str_starts_with($value, 'like:')) {
                $parts[] = "substringof('" . substr($value, 5) . "',{$field})";
            } else {
                $parts[] = "{$field} eq '{$value}'";
            }
        }

        if (!empty($filters['search'])) {
            $search = addslashes($filters['search']);
            $parts[] = "(substringof('{$search}',EquipmentName) or substringof('{$search}',Equipment))";
        }

        return implode(' and ', $parts);
    }

    /**
     * Execute OData batch request for multiple operations in a single HTTP call.
     *
     * @param array $operations Array of ['method' => 'GET|POST', 'path' => '...', 'body' => [...]]
     * @return array Array of responses
     */
    public function batchRequest(array $operations): array
    {
        $this->fetchCsrfToken();

        $boundary = 'batch_' . uniqid();
        $changesetBoundary = 'changeset_' . uniqid();

        $batchBody = '';
        $hasChanges = false;

        foreach ($operations as $op) {
            $method = strtoupper($op['method'] ?? 'GET');

            if ($method === 'GET') {
                $batchBody .= "--{$boundary}\r\n";
                $batchBody .= "Content-Type: application/http\r\n";
                $batchBody .= "Content-Transfer-Encoding: binary\r\n\r\n";
                $batchBody .= "GET " . ($op['path'] ?? '') . "?$" . "format=json HTTP/1.1\r\n";
                $batchBody .= "Accept: application/json\r\n\r\n";
            } else {
                if (!$hasChanges) {
                    $batchBody .= "--{$boundary}\r\n";
                    $batchBody .= "Content-Type: multipart/mixed; boundary={$changesetBoundary}\r\n\r\n";
                    $hasChanges = true;
                }
                $batchBody .= "--{$changesetBoundary}\r\n";
                $batchBody .= "Content-Type: application/http\r\n";
                $batchBody .= "Content-Transfer-Encoding: binary\r\n\r\n";
                $batchBody .= "{$method} " . ($op['path'] ?? '') . " HTTP/1.1\r\n";
                $batchBody .= "Content-Type: application/json\r\n";
                $batchBody .= "Accept: application/json\r\n\r\n";
                $batchBody .= json_encode($op['body'] ?? []) . "\r\n";
            }
        }

        if ($hasChanges) {
            $batchBody .= "--{$changesetBoundary}--\r\n";
        }
        $batchBody .= "--{$boundary}--\r\n";

        $url = rtrim($this->config['host'], '/') . '/sap/opu/odata/sap/$batch';
        $headers = array_merge($this->buildAuthHeaders(), [
            'Content-Type: multipart/mixed; boundary=' . $boundary,
        ]);

        $response = $this->curlRequest($url, 'POST', $batchBody, $headers);
        return $this->parseBatchResponse($response);
    }

    // =========================================================================
    // Error Handling
    // =========================================================================

    /**
     * Parse SAP OData error response and return meaningful message.
     *
     * @param array $response Raw response data
     * @return string Human-readable error message
     */
    public function handleSAPError(array $response): string
    {
        // OData v2 error format
        if (isset($response['error']['message']['value'])) {
            $msg = $response['error']['message']['value'];
            $code = $response['error']['code'] ?? 'UNKNOWN';
            $this->log('ERROR', "SAP OData Error [{$code}]: {$msg}");
            return "SAP Error [{$code}]: {$msg}";
        }

        // OData v4 error format
        if (isset($response['error']['message'])) {
            $msg = is_string($response['error']['message']) ? $response['error']['message'] : json_encode($response['error']['message']);
            $code = $response['error']['code'] ?? 'UNKNOWN';
            $this->log('ERROR', "SAP OData Error [{$code}]: {$msg}");
            return "SAP Error [{$code}]: {$msg}";
        }

        // Nested inner error details
        if (isset($response['error']['innererror'])) {
            $inner = $response['error']['innererror'];
            $details = [];
            if (isset($inner['errordetails'])) {
                foreach ($inner['errordetails'] as $detail) {
                    $details[] = ($detail['message'] ?? '') . ' (' . ($detail['code'] ?? '') . ')';
                }
            }
            if ($details) {
                return "SAP Error Details: " . implode('; ', $details);
            }
        }

        return 'Unknown SAP error: ' . json_encode($response);
    }

    // =========================================================================
    // Internal HTTP Methods
    // =========================================================================

    /**
     * Execute an OData GET request with retry logic.
     */
    private function sapGet(string $path, array $params = []): array
    {
        $url = rtrim($this->config['host'], '/') . $path;
        if ($params) {
            $url .= '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        }

        $headers = array_merge($this->buildAuthHeaders(), [
            'Accept: application/json',
            'sap-client: ' . $this->config['client'],
            'sap-language: ' . $this->config['language'],
        ]);

        $response = $this->curlRequest($url, 'GET', null, $headers);
        $data = json_decode($response, true);

        if (isset($data['error'])) {
            throw new RuntimeException($this->handleSAPError($data));
        }

        return $data ?: [];
    }

    /**
     * Execute an OData POST request.
     */
    private function sapPost(string $path, array $payload): array
    {
        $url = rtrim($this->config['host'], '/') . $path;

        $headers = array_merge($this->buildAuthHeaders(), [
            'Accept: application/json',
            'Content-Type: application/json',
            'sap-client: ' . $this->config['client'],
            'sap-language: ' . $this->config['language'],
        ]);

        if ($this->csrfToken) {
            $headers[] = 'x-csrf-token: ' . $this->csrfToken;
        }

        $response = $this->curlRequest($url, 'POST', json_encode($payload), $headers);
        $data = json_decode($response, true);

        if (isset($data['error'])) {
            throw new RuntimeException($this->handleSAPError($data));
        }

        return $data ?: [];
    }

    /**
     * Fetch CSRF token needed for write operations.
     */
    private function fetchCsrfToken(): void
    {
        if ($this->csrfToken && $this->config['auth_type'] === 'oauth2') {
            return; // OAuth2 doesn't always need CSRF
        }

        $url = rtrim($this->config['host'], '/') . self::API_EQUIPMENT . '?$top=0';
        $headers = array_merge($this->buildAuthHeaders(), [
            'Accept: application/json',
            'x-csrf-token: Fetch',
            'sap-client: ' . $this->config['client'],
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT        => self::RESPONSE_TIMEOUT,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_HEADER         => true,
            CURLOPT_SSL_VERIFYPEER => (bool)$this->config['verify_ssl'],
            CURLOPT_SSL_VERIFYHOST => $this->config['verify_ssl'] ? 2 : 0,
        ]);

        if (!empty($this->cookies)) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->buildCookieString());
        }

        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $headerStr = substr($response, 0, $headerSize);
        $this->csrfToken = $this->extractHeader($headerStr, 'x-csrf-token');
        $this->extractCookies($headerStr);
    }

    /**
     * Build authentication headers based on auth type.
     */
    private function buildAuthHeaders(): array
    {
        if ($this->config['auth_type'] === 'oauth2' && $this->authToken) {
            return ['Authorization: Bearer ' . $this->authToken];
        }

        // Basic auth is set via CURLOPT_USERPWD in curlRequest
        return [];
    }

    /**
     * Execute cURL request with retry logic and exponential backoff.
     */
    private function curlRequest(string $url, string $method, ?string $body = null, array $headers = []): string
    {
        $attempt = 0;
        $lastError = '';

        while ($attempt < self::MAX_RETRIES) {
            $attempt++;

            $ch = curl_init();
            $opts = [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
                CURLOPT_TIMEOUT        => self::RESPONSE_TIMEOUT,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_SSL_VERIFYPEER => (bool)$this->config['verify_ssl'],
                CURLOPT_SSL_VERIFYHOST => $this->config['verify_ssl'] ? 2 : 0,
            ];

            // Basic auth
            if ($this->config['auth_type'] === 'basic') {
                $opts[CURLOPT_USERPWD] = $this->config['user'] . ':' . $this->config['password'];
            }

            // Cookies
            if (!empty($this->cookies)) {
                $opts[CURLOPT_COOKIE] = $this->buildCookieString();
            }

            switch ($method) {
                case 'POST':
                    $opts[CURLOPT_POST] = true;
                    if ($body !== null) $opts[CURLOPT_POSTFIELDS] = $body;
                    break;
                case 'PUT':
                case 'PATCH':
                case 'DELETE':
                    $opts[CURLOPT_CUSTOMREQUEST] = $method;
                    if ($body !== null) $opts[CURLOPT_POSTFIELDS] = $body;
                    break;
            }

            curl_setopt_array($ch, $opts);

            $startTime = microtime(true);
            $response = curl_exec($ch);
            $elapsed = round((microtime(true) - $startTime) * 1000);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            curl_close($ch);

            // Log request
            $this->log('DEBUG', "{$method} {$url} -> HTTP {$httpCode} ({$elapsed}ms)");

            // Success
            if (!$curlError && $httpCode >= 200 && $httpCode < 300) {
                return $response;
            }

            // Non-retryable errors
            if ($httpCode === 401 || $httpCode === 403 || $httpCode === 404) {
                $lastError = "HTTP {$httpCode}: " . substr($response, 0, 500);
                break;
            }

            // Retryable: network errors, 429, 500, 502, 503, 504
            $lastError = $curlError ?: "HTTP {$httpCode}: " . substr($response, 0, 500);
            $this->log('WARNING', "Request attempt {$attempt}/{self::MAX_RETRIES} failed: {$lastError}");

            if ($attempt < self::MAX_RETRIES) {
                $delay = self::RETRY_DELAY * pow(2, $attempt - 1);
                sleep($delay);
            }
        }

        throw new RuntimeException("{$this->logPrefix} Request failed after {$attempt} attempts: {$lastError}");
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Upsert an asset record from SAP data into asset_registry.
     */
    private function upsertAsset(array $mapped): string
    {
        $assetTag = $mapped['asset_tag'] ?? null;
        if (!$assetTag) {
            throw new RuntimeException('Missing asset_tag in mapped data');
        }

        // Remove internal markers
        unset($mapped['_source'], $mapped['_synced_at']);

        $existing = $this->db->query(
            "SELECT id, updated_at FROM asset_registry WHERE asset_tag = ? LIMIT 1",
            [$assetTag]
        )->fetch();

        // Build insert/update data from mapped fields to actual DB columns
        $dbData = [
            'equipment_name' => $mapped['equipment_name'] ?? null,
            'sap_floc'       => $mapped['functional_location'] ?? null,
            'asset_type'     => $this->mapAssetType($mapped['asset_type'] ?? ''),
            'manufacturer'   => $mapped['manufacturer_model'] ?? null,
            'serial_number'  => $mapped['serial_number'] ?? null,
        ];

        if (!empty($mapped['installation_date'])) {
            $dbData['installation_date'] = $mapped['installation_date'];
        }

        // Remove null values to avoid overwriting with null
        $dbData = array_filter($dbData, fn($v) => $v !== null);
        $dbData['updated_at'] = date('Y-m-d H:i:s');

        if ($existing) {
            $this->db->update('asset_registry', $dbData, 'id = ?', [$existing['id']]);
            return 'updated';
        } else {
            $dbData['asset_tag'] = $assetTag;
            $dbData['status'] = 'in_service';
            $dbData['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('asset_registry', $dbData);
            return 'created';
        }
    }

    /**
     * Build functional location hierarchy tree from flat list.
     */
    private function buildHierarchyTree(array $locations): array
    {
        $byId = [];
        foreach ($locations as $loc) {
            $key = $loc['FunctionalLocation'] ?? '';
            $byId[$key] = [
                'functional_location' => $key,
                'name'                => $loc['FunctionalLocationName'] ?? '',
                'category'            => $loc['FuncLocCategory'] ?? '',
                'superior'            => $loc['SuperiorFunctionalLocation'] ?? '',
                'plant'               => $loc['MaintenancePlant'] ?? '',
                'children'            => [],
            ];
        }

        $tree = [];
        foreach ($byId as $key => &$node) {
            if (!empty($node['superior']) && isset($byId[$node['superior']])) {
                $byId[$node['superior']]['children'][] = &$node;
            } else {
                $tree[] = &$node;
            }
        }

        return $tree;
    }

    private function formatSAPDate(string $date): string
    {
        $ts = strtotime($date);
        return $ts ? "/Date(" . ($ts * 1000) . ")/" : $date;
    }

    private function parseSAPDate(?string $sapDate): ?string
    {
        if (!$sapDate) return null;

        // Format: /Date(1234567890000)/
        if (preg_match('/\/Date\((\d+)\)\//', $sapDate, $m)) {
            return date('Y-m-d', (int)($m[1] / 1000));
        }

        // ISO format
        $ts = strtotime($sapDate);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    private function mapAssetType(string $sapCategory): string
    {
        $map = [
            'M' => 'vessel',
            'P' => 'piping',
            'H' => 'heat_exchanger',
            'T' => 'tank',
            'V' => 'valve',
            'I' => 'instrument',
            'R' => 'rotating_equipment',
        ];
        return $map[strtoupper($sapCategory)] ?? 'other';
    }

    private function mapFindingSeverityToNotifType(array $finding): string
    {
        $severity = $finding['severity'] ?? 'low';
        return match ($severity) {
            'critical' => 'M1',  // Malfunction
            'high'     => 'M2',  // Activity report
            default    => 'M3',  // Condition report
        };
    }

    private function mapFindingPriority(array $finding): string
    {
        $severity = $finding['severity'] ?? 'low';
        return match ($severity) {
            'critical' => '1',
            'high'     => '2',
            'medium'   => '3',
            default    => '4',
        };
    }

    private function extractHeader(string $headers, string $name): ?string
    {
        $name = strtolower($name);
        foreach (explode("\r\n", $headers) as $line) {
            if (str_starts_with(strtolower($line), $name . ':')) {
                return trim(substr($line, strlen($name) + 1));
            }
        }
        return null;
    }

    private function extractCookies(string $headers): void
    {
        foreach (explode("\r\n", $headers) as $line) {
            if (stripos($line, 'set-cookie:') === 0) {
                $cookie = trim(substr($line, 11));
                $parts = explode(';', $cookie);
                $nameValue = explode('=', $parts[0], 2);
                if (count($nameValue) === 2) {
                    $this->cookies[$nameValue[0]] = $nameValue[1];
                }
            }
        }
    }

    private function buildCookieString(): string
    {
        $pairs = [];
        foreach ($this->cookies as $name => $value) {
            $pairs[] = "{$name}={$value}";
        }
        return implode('; ', $pairs);
    }

    private function parseBatchResponse(string $response): array
    {
        // Simplified batch response parser
        $results = [];
        $parts = preg_split('/--batch/', $response);
        foreach ($parts as $part) {
            if (str_contains($part, 'application/json')) {
                $jsonStart = strpos($part, '{');
                if ($jsonStart !== false) {
                    $json = substr($part, $jsonStart);
                    $jsonEnd = strrpos($json, '}');
                    if ($jsonEnd !== false) {
                        $json = substr($json, 0, $jsonEnd + 1);
                        $results[] = json_decode($json, true) ?: [];
                    }
                }
            }
        }
        return $results;
    }

    private function log(string $level, string $message): void
    {
        error_log("{$this->logPrefix} [{$level}] {$message}");
    }
}
