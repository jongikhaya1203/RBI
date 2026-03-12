<?php
/**
 * MaximoIntegration - IBM Maximo Asset Management Integration
 *
 * Connects to Maximo via OSLC/REST APIs for asset data, work orders,
 * service requests, meter readings, and failure analysis data.
 *
 * @package RBI Engineering Suite
 */
class MaximoIntegration
{
    private Database $db;
    private array $config;
    private ?string $apiKey = null;
    private ?string $sessionCookie = null;
    private ?string $authToken = null;
    private string $logPrefix = '[Maximo Integration]';

    private const MAX_RETRIES     = 3;
    private const RETRY_DELAY     = 1;
    private const CONNECT_TIMEOUT = 10;
    private const RESPONSE_TIMEOUT = 30;
    private const DEFAULT_PAGE_SIZE = 100;

    /**
     * Initialize Maximo integration.
     *
     * @param array $config Keys: base_url, api_key, username, password, auth_type (apikey|basic|ldap|oauth2),
     *                      oauth_token_url, oauth_client_id, oauth_client_secret, verify_ssl, site_id, org_id
     */
    public function __construct(array $config)
    {
        $this->db = new Database();
        $this->config = array_merge([
            'base_url'            => '',
            'api_key'             => '',
            'username'            => '',
            'password'            => '',
            'auth_type'           => 'apikey',  // apikey, basic, ldap, oauth2
            'oauth_token_url'     => '',
            'oauth_client_id'     => '',
            'oauth_client_secret' => '',
            'verify_ssl'          => true,
            'site_id'             => '',
            'org_id'              => '',
            'lean'                => 1,       // Use lean responses (no namespace prefixes)
        ], $config);

        if (!empty($config['api_key'])) {
            $this->apiKey = $config['api_key'];
        }
    }

    // =========================================================================
    // Authentication
    // =========================================================================

    /**
     * Authenticate to Maximo. Supports API key, Basic/LDAP, and OAuth2.
     *
     * @return bool True on success
     * @throws RuntimeException On failure
     */
    public function authenticate(): bool
    {
        return match ($this->config['auth_type']) {
            'apikey'        => $this->authenticateApiKey(),
            'basic', 'ldap' => $this->authenticateBasic(),
            'oauth2'        => $this->authenticateOAuth2(),
            default         => throw new RuntimeException("{$this->logPrefix} Unsupported auth type: {$this->config['auth_type']}"),
        };
    }

    private function authenticateApiKey(): bool
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException("{$this->logPrefix} API key not configured");
        }

        // Validate API key by making a test request
        $url = $this->buildUrl('/maximo/oslc/os/mxasset', ['oslc.pageSize' => 1, 'oslc.select' => 'assetnum']);
        $response = $this->curlRequest($url, 'GET');
        $data = json_decode($response, true);

        if (isset($data['Error'])) {
            throw new RuntimeException("{$this->logPrefix} API key validation failed: " . ($data['Error']['message'] ?? 'Unknown error'));
        }

        $this->log('INFO', 'API key authentication validated');
        return true;
    }

    private function authenticateBasic(): bool
    {
        $url = rtrim($this->config['base_url'], '/') . '/maximo/oslc/login';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT        => self::RESPONSE_TIMEOUT,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'j_username' => $this->config['username'],
                'j_password' => $this->config['password'],
            ]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_HEADER         => true,
            CURLOPT_SSL_VERIFYPEER => (bool)$this->config['verify_ssl'],
            CURLOPT_SSL_VERIFYHOST => $this->config['verify_ssl'] ? 2 : 0,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new RuntimeException("{$this->logPrefix} Connection failed: {$error}");
        }

        if ($httpCode === 401) {
            throw new RuntimeException("{$this->logPrefix} Authentication failed: invalid credentials");
        }

        // Extract JSESSIONID cookie
        $headers = substr($response, 0, $headerSize);
        foreach (explode("\r\n", $headers) as $line) {
            if (stripos($line, 'set-cookie:') === 0 && stripos($line, 'JSESSIONID') !== false) {
                preg_match('/JSESSIONID=([^;]+)/', $line, $m);
                if (!empty($m[1])) {
                    $this->sessionCookie = $m[1];
                }
            }
        }

        if (!$this->sessionCookie) {
            throw new RuntimeException("{$this->logPrefix} Authentication succeeded but no session cookie received");
        }

        $this->log('INFO', 'Basic/LDAP authentication successful');
        return true;
    }

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
            CURLOPT_SSL_VERIFYPEER => (bool)$this->config['verify_ssl'],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode >= 400) {
            throw new RuntimeException("{$this->logPrefix} OAuth2 authentication failed: " . ($error ?: "HTTP {$httpCode}"));
        }

        $tokenData = json_decode($response, true);
        if (empty($tokenData['access_token'])) {
            throw new RuntimeException("{$this->logPrefix} OAuth2 response missing access_token");
        }

        $this->authToken = $tokenData['access_token'];
        $this->log('INFO', 'OAuth2 authentication successful');
        return true;
    }

    // =========================================================================
    // Asset Management
    // =========================================================================

    /**
     * Get assets from Maximo with optional filters.
     *
     * @param array $filters Keys: siteid, status, assettype, location, parent, search, pageSize, pageNum
     * @return array List of assets mapped to RBI fields
     */
    public function getAssets(array $filters = []): array
    {
        $params = [
            'oslc.select' => 'assetnum,description,status,status_description,location,'
                           . 'parent,assettype,installdate,manufacturer,serialnum,'
                           . 'priority,siteid,orgid,changedate,totdowntime,'
                           . 'purchaseprice,replacecost,budgetcost,ytdcost',
            'oslc.pageSize' => $filters['pageSize'] ?? self::DEFAULT_PAGE_SIZE,
            'lean'          => $this->config['lean'],
        ];

        $where = $this->buildOSLCQuery($filters);
        if ($where) {
            $params['oslc.where'] = $where;
        }

        $url = $this->buildUrl('/maximo/oslc/os/mxasset', $params);
        $response = $this->curlRequest($url, 'GET');
        $data = json_decode($response, true);

        $this->handleMaximoError($data);

        $members = $data['member'] ?? $data['rdfs:member'] ?? [];
        return array_map(function ($item) {
            return $this->mapMaximoToRBI($item, 'asset');
        }, $members);
    }

    /**
     * Get single asset with all attributes, meters, and specifications.
     *
     * @param string $assetNum Maximo asset number
     * @return array|null Asset detail
     */
    public function getAssetDetail(string $assetNum): ?array
    {
        $params = [
            'oslc.select' => '*',
            'oslc.where'  => "assetnum=\"{$assetNum}\"",
            'lean'        => $this->config['lean'],
        ];

        if (!empty($this->config['site_id'])) {
            $params['oslc.where'] .= " and siteid=\"{$this->config['site_id']}\"";
        }

        $url = $this->buildUrl('/maximo/oslc/os/mxasset', $params);
        $response = $this->curlRequest($url, 'GET');
        $data = json_decode($response, true);

        $this->handleMaximoError($data);
        $members = $data['member'] ?? $data['rdfs:member'] ?? [];

        return !empty($members) ? $this->mapMaximoToRBI($members[0], 'asset_detail') : null;
    }

    /**
     * Get operating locations hierarchy.
     *
     * @param string $site Maximo site ID
     * @return array Location hierarchy
     */
    public function getLocations(string $site = ''): array
    {
        $site = $site ?: ($this->config['site_id'] ?? '');

        $params = [
            'oslc.select'   => 'location,description,type,parent,siteid,orgid,status,glaccount',
            'oslc.pageSize' => 9999,
            'lean'          => $this->config['lean'],
        ];

        if ($site) {
            $params['oslc.where'] = "siteid=\"{$site}\" and type=\"OPERATING\"";
        }

        $url = $this->buildUrl('/maximo/oslc/os/mxoperloc', $params);
        $response = $this->curlRequest($url, 'GET');
        $data = json_decode($response, true);

        $this->handleMaximoError($data);

        $locations = $data['member'] ?? $data['rdfs:member'] ?? [];

        // Build hierarchy
        $byId = [];
        foreach ($locations as $loc) {
            $key = $loc['location'] ?? '';
            $byId[$key] = [
                'location'    => $key,
                'description' => $loc['description'] ?? '',
                'type'        => $loc['type'] ?? '',
                'parent'      => $loc['parent'] ?? '',
                'site'        => $loc['siteid'] ?? '',
                'children'    => [],
            ];
        }

        $tree = [];
        foreach ($byId as &$node) {
            if (!empty($node['parent']) && isset($byId[$node['parent']])) {
                $byId[$node['parent']]['children'][] = &$node;
            } else {
                $tree[] = &$node;
            }
        }

        return $tree;
    }

    // =========================================================================
    // Work Orders
    // =========================================================================

    /**
     * Get work orders with filters.
     *
     * @param array $filters Keys: worktype, status, assetnum, location, date_from, date_to, search
     * @return array Work orders
     */
    public function getWorkOrders(array $filters = []): array
    {
        $params = [
            'oslc.select'   => 'wonum,description,status,status_description,worktype,'
                             . 'schedstart,schedfinish,actstart,actfinish,assetnum,'
                             . 'location,jpnum,wopriority,siteid,reportdate,'
                             . 'targstartdate,targcompdate,estdur,actlabhrs,actmatcost',
            'oslc.pageSize'  => $filters['pageSize'] ?? self::DEFAULT_PAGE_SIZE,
            'oslc.orderBy'   => '-schedstart',
            'lean'           => $this->config['lean'],
        ];

        $where = $this->buildOSLCQuery($filters, 'workorder');
        if ($where) {
            $params['oslc.where'] = $where;
        }

        $url = $this->buildUrl('/maximo/oslc/os/mxwo', $params);
        $response = $this->curlRequest($url, 'GET');
        $data = json_decode($response, true);

        $this->handleMaximoError($data);
        return $data['member'] ?? $data['rdfs:member'] ?? [];
    }

    /**
     * Get service requests related to equipment issues.
     *
     * @param array $filters Keys: assetnum, status, date_from, date_to
     * @return array Service requests
     */
    public function getServiceRequests(array $filters = []): array
    {
        $params = [
            'oslc.select'   => 'ticketid,description,status,status_description,assetnum,'
                             . 'location,reportdate,reportedby,internalpriority,siteid',
            'oslc.pageSize'  => $filters['pageSize'] ?? self::DEFAULT_PAGE_SIZE,
            'oslc.orderBy'   => '-reportdate',
            'lean'           => $this->config['lean'],
        ];

        $whereParts = [];
        if (!empty($filters['assetnum'])) {
            $whereParts[] = "assetnum=\"{$filters['assetnum']}\"";
        }
        if (!empty($filters['status'])) {
            $whereParts[] = "status=\"{$filters['status']}\"";
        }
        if (!empty($this->config['site_id'])) {
            $whereParts[] = "siteid=\"{$this->config['site_id']}\"";
        }
        if ($whereParts) {
            $params['oslc.where'] = implode(' and ', $whereParts);
        }

        $url = $this->buildUrl('/maximo/oslc/os/mxsr', $params);
        $response = $this->curlRequest($url, 'GET');
        $data = json_decode($response, true);

        $this->handleMaximoError($data);
        return $data['member'] ?? $data['rdfs:member'] ?? [];
    }

    /**
     * Get meter readings for an asset (thickness, condition measurements).
     *
     * @param string $assetNum Maximo asset number
     * @return array Meter readings
     */
    public function getMeterReadings(string $assetNum): array
    {
        $params = [
            'oslc.select'   => 'assetmeterid,assetnum,metername,metertype,lastreading,'
                             . 'lastreadingdate,average,measureunitid,remarks',
            'oslc.where'    => "assetnum=\"{$assetNum}\"",
            'lean'          => $this->config['lean'],
        ];

        if (!empty($this->config['site_id'])) {
            $params['oslc.where'] .= " and siteid=\"{$this->config['site_id']}\"";
        }

        $url = $this->buildUrl('/maximo/oslc/os/mxassetmeter', $params);
        $response = $this->curlRequest($url, 'GET');
        $data = json_decode($response, true);

        $this->handleMaximoError($data);
        return $data['member'] ?? $data['rdfs:member'] ?? [];
    }

    /**
     * Get failure reports with failure class/problem/cause/remedy hierarchy.
     *
     * @param string $assetNum Maximo asset number
     * @return array Failure analysis data
     */
    public function getFailureReports(string $assetNum): array
    {
        // Get work orders with failure information
        $params = [
            'oslc.select'   => 'wonum,description,failurecode,problemcode,causecode,'
                             . 'remarkdesc,reportdate,actstart,actfinish,assetnum',
            'oslc.where'    => "assetnum=\"{$assetNum}\" and failurecode!=\"\"",
            'oslc.orderBy'  => '-reportdate',
            'oslc.pageSize' => 200,
            'lean'          => $this->config['lean'],
        ];

        $url = $this->buildUrl('/maximo/oslc/os/mxwo', $params);
        $response = $this->curlRequest($url, 'GET');
        $data = json_decode($response, true);

        $this->handleMaximoError($data);
        return $data['member'] ?? $data['rdfs:member'] ?? [];
    }

    // =========================================================================
    // Write Operations
    // =========================================================================

    /**
     * Create work order for a scheduled inspection.
     *
     * @param array $data Keys: description, assetnum, worktype, jpnum, wopriority,
     *                     schedstart, schedfinish, location, siteid, tasks[]
     * @return array Created WO data
     */
    public function createWorkOrder(array $data): array
    {
        $payload = [
            'description' => $data['description'] ?? 'RBI Inspection',
            'assetnum'    => $data['assetnum'] ?? '',
            'worktype'    => $data['worktype'] ?? 'PM',
            'jpnum'       => $data['jpnum'] ?? '',
            'wopriority'  => $data['wopriority'] ?? 2,
            'schedstart'  => $this->formatMaximoDate($data['schedstart'] ?? date('Y-m-d')),
            'schedfinish' => $this->formatMaximoDate($data['schedfinish'] ?? date('Y-m-d', strtotime('+7 days'))),
            'location'    => $data['location'] ?? '',
            'siteid'      => $data['siteid'] ?? $this->config['site_id'],
            'orgid'       => $data['orgid'] ?? $this->config['org_id'],
        ];

        // Add safety plan if specified
        if (!empty($data['safety_plan'])) {
            $payload['hassafetplan'] = true;
            $payload['safetyplan'] = $data['safety_plan'];
        }

        $url = $this->buildUrl('/maximo/oslc/os/mxwo');
        $response = $this->curlRequest($url, 'POST', json_encode($payload), [
            'Content-Type: application/json',
        ]);

        $result = json_decode($response, true);
        $this->handleMaximoError($result);

        $this->log('INFO', "Created Maximo WO: " . ($result['wonum'] ?? 'unknown'));
        return $result;
    }

    /**
     * Update work order status or add actuals.
     *
     * @param string $woNum Work order number
     * @param array  $data Fields to update
     * @return array Updated WO data
     */
    public function updateWorkOrder(string $woNum, array $data): array
    {
        // First get the WO href
        $params = [
            'oslc.where'  => "wonum=\"{$woNum}\"",
            'oslc.select' => 'wonum,href',
            'lean'        => $this->config['lean'],
        ];
        if (!empty($this->config['site_id'])) {
            $params['oslc.where'] .= " and siteid=\"{$this->config['site_id']}\"";
        }

        $url = $this->buildUrl('/maximo/oslc/os/mxwo', $params);
        $response = $this->curlRequest($url, 'GET');
        $woData = json_decode($response, true);
        $this->handleMaximoError($woData);

        $members = $woData['member'] ?? $woData['rdfs:member'] ?? [];
        if (empty($members)) {
            throw new RuntimeException("{$this->logPrefix} Work order {$woNum} not found");
        }

        $woHref = $members[0]['href'] ?? $members[0]['rdf:about'] ?? '';
        if (!$woHref) {
            throw new RuntimeException("{$this->logPrefix} Could not determine WO resource URL");
        }

        // PATCH the work order
        $response = $this->curlRequest($woHref, 'PATCH', json_encode($data), [
            'Content-Type: application/json',
            'x-method-override: PATCH',
            'patchtype: MERGE',
        ]);

        $result = json_decode($response, true) ?: [];
        $this->log('INFO', "Updated Maximo WO {$woNum}");
        return $result;
    }

    /**
     * Create a service request for inspection findings.
     */
    public function createServiceRequest(array $data): array
    {
        $payload = [
            'description'      => $data['description'] ?? 'RBI Inspection Finding',
            'assetnum'         => $data['assetnum'] ?? '',
            'location'         => $data['location'] ?? '',
            'reportedby'       => $data['reportedby'] ?? $this->config['username'],
            'internalpriority' => $data['priority'] ?? 3,
            'siteid'           => $data['siteid'] ?? $this->config['site_id'],
        ];

        $url = $this->buildUrl('/maximo/oslc/os/mxsr');
        $response = $this->curlRequest($url, 'POST', json_encode($payload), [
            'Content-Type: application/json',
        ]);

        $result = json_decode($response, true);
        $this->handleMaximoError($result);

        $this->log('INFO', "Created Maximo SR: " . ($result['ticketid'] ?? 'unknown'));
        return $result;
    }

    /**
     * Add a meter reading (e.g., thickness measurement).
     */
    public function addMeterReading(string $assetNum, array $data): array
    {
        $payload = [
            'assetnum'   => $assetNum,
            'metername'  => $data['metername'] ?? 'THICKNESS',
            'newreading' => $data['value'] ?? 0,
            'newreadingdate' => $this->formatMaximoDate($data['date'] ?? date('Y-m-d')),
            'siteid'     => $data['siteid'] ?? $this->config['site_id'],
        ];

        if (!empty($data['remarks'])) {
            $payload['remarks'] = $data['remarks'];
        }

        $url = $this->buildUrl('/maximo/oslc/os/mxassetmeter');
        $response = $this->curlRequest($url, 'POST', json_encode($payload), [
            'Content-Type: application/json',
        ]);

        $result = json_decode($response, true) ?: [];
        $this->handleMaximoError($result);

        $this->log('INFO', "Added meter reading for asset {$assetNum}");
        return $result;
    }

    // =========================================================================
    // Synchronization
    // =========================================================================

    /**
     * Full bidirectional sync between Maximo and RBI.
     *
     * @return array Sync results
     */
    public function syncAssetsToRBI(): array
    {
        $syncLog = [
            'started_at'       => date('Y-m-d H:i:s'),
            'records_processed'=> 0,
            'records_created'  => 0,
            'records_updated'  => 0,
            'records_failed'   => 0,
            'conflicts'        => 0,
            'errors'           => [],
        ];

        try {
            $this->authenticate();

            // Paginate through all assets
            $pageNum = 1;
            $pageSize = self::DEFAULT_PAGE_SIZE;

            do {
                $assets = $this->getAssets(['pageSize' => $pageSize, 'pageNum' => $pageNum]);
                if (empty($assets)) break;

                foreach ($assets as $asset) {
                    $syncLog['records_processed']++;
                    try {
                        $result = $this->upsertAssetFromMaximo($asset);
                        if ($result['action'] === 'created') {
                            $syncLog['records_created']++;
                        } elseif ($result['action'] === 'updated') {
                            $syncLog['records_updated']++;
                        }
                        if (!empty($result['conflicts'])) {
                            $syncLog['conflicts'] += count($result['conflicts']);
                        }
                    } catch (\Throwable $e) {
                        $syncLog['records_failed']++;
                        $syncLog['errors'][] = "Asset {$asset['asset_tag']}: " . $e->getMessage();
                    }
                }

                $pageNum++;
            } while (count($assets) === $pageSize);

        } catch (\Throwable $e) {
            $syncLog['errors'][] = 'Sync aborted: ' . $e->getMessage();
            $this->log('ERROR', 'Asset sync failed: ' . $e->getMessage());
        }

        $syncLog['completed_at'] = date('Y-m-d H:i:s');
        return $syncLog;
    }

    /**
     * Map Maximo PM work orders to RBI inspection plans/tasks.
     *
     * @return array Sync results
     */
    public function syncWorkOrdersToInspections(): array
    {
        $results = [
            'wo_processed'     => 0,
            'plans_created'    => 0,
            'plans_updated'    => 0,
            'errors'           => [],
        ];

        try {
            $this->authenticate();

            // Get PM/inspection type work orders
            $workOrders = $this->getWorkOrders([
                'worktype' => 'PM',
                'status'   => 'APPR',
            ]);

            foreach ($workOrders as $wo) {
                $results['wo_processed']++;
                try {
                    $assetNum = $wo['assetnum'] ?? '';
                    if (!$assetNum) continue;

                    // Find matching RBI asset
                    $asset = $this->db->query(
                        "SELECT id FROM asset_registry WHERE asset_tag = ? OR serial_number = ? LIMIT 1",
                        [$assetNum, $assetNum]
                    )->fetch();

                    if (!$asset) continue;

                    // Check if plan already exists for this WO
                    $existing = $this->db->query(
                        "SELECT id FROM inspection_plans WHERE external_reference = ? LIMIT 1",
                        [$wo['wonum'] ?? '']
                    )->fetch();

                    if ($existing) {
                        $this->db->update('inspection_plans', [
                            'status'     => $this->mapMaximoStatus($wo['status'] ?? ''),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ], 'id = ?', [$existing['id']]);
                        $results['plans_updated']++;
                    } else {
                        $this->db->insert('inspection_plans', [
                            'asset_id'           => $asset['id'],
                            'plan_name'          => $wo['description'] ?? 'Maximo WO ' . ($wo['wonum'] ?? ''),
                            'plan_start_date'    => $this->parseMaximoDate($wo['schedstart'] ?? null),
                            'plan_end_date'      => $this->parseMaximoDate($wo['schedfinish'] ?? null),
                            'priority'           => $this->mapMaximoPriority($wo['wopriority'] ?? 2),
                            'status'             => 'planned',
                            'external_reference' => $wo['wonum'] ?? '',
                            'created_at'         => date('Y-m-d H:i:s'),
                            'updated_at'         => date('Y-m-d H:i:s'),
                        ]);
                        $results['plans_created']++;
                    }
                } catch (\Throwable $e) {
                    $results['errors'][] = "WO {$wo['wonum']}: " . $e->getMessage();
                }
            }
        } catch (\Throwable $e) {
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Get all condition monitoring data for an asset.
     *
     * @param string $assetNum Maximo asset number
     * @return array Condition data grouped by type
     */
    public function getConditionMonitoringData(string $assetNum): array
    {
        $meters = $this->getMeterReadings($assetNum);

        $grouped = [
            'thickness'   => [],
            'vibration'   => [],
            'temperature' => [],
            'pressure'    => [],
            'other'       => [],
        ];

        foreach ($meters as $meter) {
            $name = strtolower($meter['metername'] ?? '');
            if (str_contains($name, 'thick')) {
                $grouped['thickness'][] = $meter;
            } elseif (str_contains($name, 'vibr')) {
                $grouped['vibration'][] = $meter;
            } elseif (str_contains($name, 'temp')) {
                $grouped['temperature'][] = $meter;
            } elseif (str_contains($name, 'press')) {
                $grouped['pressure'][] = $meter;
            } else {
                $grouped['other'][] = $meter;
            }
        }

        return $grouped;
    }

    // =========================================================================
    // OSLC Query Building
    // =========================================================================

    /**
     * Build OSLC query string from filters.
     *
     * @param array  $filters   Filter parameters
     * @param string $context   Entity context (asset|workorder)
     * @return string OSLC where clause
     */
    public function buildOSLCQuery(array $filters, string $context = 'asset'): string
    {
        $parts = [];

        if (!empty($this->config['site_id'])) {
            $parts[] = "siteid=\"{$this->config['site_id']}\"";
        }

        foreach ($filters as $key => $value) {
            if ($value === '' || $value === null) continue;

            switch ($key) {
                case 'assetnum':
                    $parts[] = "assetnum=\"{$value}\"";
                    break;
                case 'status':
                    $parts[] = "status=\"{$value}\"";
                    break;
                case 'assettype':
                    $parts[] = "assettype=\"{$value}\"";
                    break;
                case 'location':
                    $parts[] = "location=\"{$value}\"";
                    break;
                case 'parent':
                    $parts[] = "parent=\"{$value}\"";
                    break;
                case 'worktype':
                    $parts[] = "worktype=\"{$value}\"";
                    break;
                case 'date_from':
                    $field = $context === 'workorder' ? 'schedstart' : 'changedate';
                    $parts[] = "{$field}>=\"{$value}T00:00:00\"";
                    break;
                case 'date_to':
                    $field = $context === 'workorder' ? 'schedfinish' : 'changedate';
                    $parts[] = "{$field}<=\"{$value}T23:59:59\"";
                    break;
                case 'search':
                    $parts[] = "description=\"%{$value}%\"";
                    break;
            }
        }

        return implode(' and ', $parts);
    }

    /**
     * Handle Maximo pagination - retrieve all pages.
     *
     * @param string $url       Initial URL
     * @param int    $pageSize  Records per page
     * @return array All results combined
     */
    public function paginateResults(string $url, int $pageSize = 100): array
    {
        $allResults = [];
        $currentUrl = $url;

        do {
            $response = $this->curlRequest($currentUrl, 'GET');
            $data = json_decode($response, true);
            $this->handleMaximoError($data);

            $members = $data['member'] ?? $data['rdfs:member'] ?? [];
            $allResults = array_merge($allResults, $members);

            // Check for next page link
            $nextPage = null;
            if (isset($data['responseInfo']['nextPage']['href'])) {
                $nextPage = $data['responseInfo']['nextPage']['href'];
            } elseif (isset($data['oslc:responseInfo']['oslc:nextPage']['rdf:resource'])) {
                $nextPage = $data['oslc:responseInfo']['oslc:nextPage']['rdf:resource'];
            }

            $currentUrl = $nextPage;
        } while ($currentUrl && count($members) === $pageSize);

        return $allResults;
    }

    // =========================================================================
    // Error Handling
    // =========================================================================

    /**
     * Parse Maximo OSLC error format and throw if error detected.
     *
     * @param array|null $response Decoded response
     * @throws RuntimeException On error
     */
    public function handleMaximoError(?array $response): void
    {
        if ($response === null) return;

        // Maximo error format
        if (isset($response['Error'])) {
            $error = $response['Error'];
            $msg = $error['message'] ?? 'Unknown Maximo error';
            $code = $error['reasonCode'] ?? '';
            $statusCode = $error['statusCode'] ?? '';

            $this->log('ERROR', "Maximo Error [{$code}] HTTP {$statusCode}: {$msg}");
            throw new RuntimeException("{$this->logPrefix} [{$code}]: {$msg}");
        }

        // OSLC error format
        if (isset($response['oslc:Error'])) {
            $msg = $response['oslc:Error']['oslc:message'] ?? 'Unknown OSLC error';
            $this->log('ERROR', "OSLC Error: {$msg}");
            throw new RuntimeException("{$this->logPrefix} OSLC: {$msg}");
        }
    }

    // =========================================================================
    // Internal Helpers
    // =========================================================================

    /**
     * Map Maximo entity fields to RBI data model.
     */
    private function mapMaximoToRBI(array $data, string $entityType): array
    {
        $mappings = [
            'asset' => [
                'assetnum'     => 'asset_tag',
                'description'  => 'equipment_name',
                'status'       => 'status',
                'location'     => 'location',
                'parent'       => 'parent_asset',
                'assettype'    => 'asset_type',
                'installdate'  => 'installation_date',
                'manufacturer' => 'manufacturer',
                'serialnum'    => 'serial_number',
                'priority'     => 'priority',
                'siteid'       => 'site_id',
            ],
            'asset_detail' => [
                'assetnum'      => 'asset_tag',
                'description'   => 'equipment_name',
                'status'        => 'status',
                'location'      => 'location',
                'parent'        => 'parent_asset',
                'assettype'     => 'asset_type',
                'installdate'   => 'installation_date',
                'manufacturer'  => 'manufacturer',
                'serialnum'     => 'serial_number',
                'priority'      => 'priority',
                'purchaseprice' => 'purchase_price',
                'replacecost'   => 'replacement_cost',
                'totdowntime'   => 'total_downtime',
                'ytdcost'       => 'ytd_cost',
            ],
        ];

        $map = $mappings[$entityType] ?? $mappings['asset'];
        $result = [];

        foreach ($map as $maxField => $rbiField) {
            $value = $data[$maxField] ?? null;
            if ($value !== null && (str_contains($rbiField, 'date') || $rbiField === 'installation_date')) {
                $value = $this->parseMaximoDate($value);
            }
            $result[$rbiField] = $value;
        }

        $result['_source'] = 'maximo';
        $result['_synced_at'] = date('Y-m-d H:i:s');

        return $result;
    }

    private function upsertAssetFromMaximo(array $mapped): array
    {
        $assetTag = $mapped['asset_tag'] ?? null;
        if (!$assetTag) {
            throw new RuntimeException('Missing asset_tag');
        }

        unset($mapped['_source'], $mapped['_synced_at']);

        $existing = $this->db->query(
            "SELECT id, updated_at FROM asset_registry WHERE asset_tag = ? LIMIT 1",
            [$assetTag]
        )->fetch();

        $dbData = array_filter([
            'equipment_name'    => $mapped['equipment_name'] ?? null,
            'asset_type'        => $this->mapMaximoAssetType($mapped['asset_type'] ?? ''),
            'manufacturer'      => $mapped['manufacturer'] ?? null,
            'serial_number'     => $mapped['serial_number'] ?? null,
            'installation_date' => $mapped['installation_date'] ?? null,
            'status'            => $this->mapMaximoStatusToRBI($mapped['status'] ?? ''),
        ], fn($v) => $v !== null);

        $dbData['updated_at'] = date('Y-m-d H:i:s');
        $result = ['action' => '', 'conflicts' => []];

        if ($existing) {
            $this->db->update('asset_registry', $dbData, 'id = ?', [$existing['id']]);
            $result['action'] = 'updated';
        } else {
            $dbData['asset_tag'] = $assetTag;
            $dbData['status'] = $dbData['status'] ?? 'in_service';
            $dbData['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('asset_registry', $dbData);
            $result['action'] = 'created';
        }

        return $result;
    }

    private function buildUrl(string $path, array $params = []): string
    {
        $url = rtrim($this->config['base_url'], '/') . $path;
        if ($params) {
            $url .= '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        }
        return $url;
    }

    private function curlRequest(string $url, string $method, ?string $body = null, array $extraHeaders = []): string
    {
        $attempt = 0;
        $lastError = '';

        while ($attempt < self::MAX_RETRIES) {
            $attempt++;

            $ch = curl_init();
            $headers = array_merge(['Accept: application/json'], $extraHeaders);

            // Authentication headers
            if ($this->apiKey) {
                $headers[] = 'apikey: ' . $this->apiKey;
                $headers[] = 'MAXAUTH: ' . base64_encode($this->config['username'] . ':' . $this->config['password']);
            } elseif ($this->authToken) {
                $headers[] = 'Authorization: Bearer ' . $this->authToken;
            }

            $opts = [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
                CURLOPT_TIMEOUT        => self::RESPONSE_TIMEOUT,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_SSL_VERIFYPEER => (bool)$this->config['verify_ssl'],
                CURLOPT_SSL_VERIFYHOST => $this->config['verify_ssl'] ? 2 : 0,
            ];

            // Basic auth fallback
            if ($this->config['auth_type'] === 'basic' || $this->config['auth_type'] === 'ldap') {
                if (!$this->apiKey && !$this->authToken) {
                    $opts[CURLOPT_USERPWD] = $this->config['username'] . ':' . $this->config['password'];
                }
            }

            // Session cookie
            if ($this->sessionCookie) {
                $opts[CURLOPT_COOKIE] = 'JSESSIONID=' . $this->sessionCookie;
            }

            switch ($method) {
                case 'POST':
                    $opts[CURLOPT_POST] = true;
                    if ($body !== null) $opts[CURLOPT_POSTFIELDS] = $body;
                    break;
                case 'PUT':
                case 'PATCH':
                    $opts[CURLOPT_CUSTOMREQUEST] = $method;
                    if ($body !== null) $opts[CURLOPT_POSTFIELDS] = $body;
                    break;
                case 'DELETE':
                    $opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                    break;
            }

            curl_setopt_array($ch, $opts);

            $startTime = microtime(true);
            $response = curl_exec($ch);
            $elapsed = round((microtime(true) - $startTime) * 1000);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            $this->log('DEBUG', "{$method} {$url} -> HTTP {$httpCode} ({$elapsed}ms)");

            if (!$curlError && $httpCode >= 200 && $httpCode < 300) {
                return $response;
            }

            if ($httpCode === 401 || $httpCode === 403 || $httpCode === 404) {
                $lastError = "HTTP {$httpCode}: " . substr($response, 0, 500);
                break;
            }

            $lastError = $curlError ?: "HTTP {$httpCode}: " . substr($response, 0, 500);
            $this->log('WARNING', "Attempt {$attempt}/{self::MAX_RETRIES} failed: {$lastError}");

            if ($attempt < self::MAX_RETRIES) {
                sleep(self::RETRY_DELAY * pow(2, $attempt - 1));
            }
        }

        throw new RuntimeException("{$this->logPrefix} Request failed after {$attempt} attempts: {$lastError}");
    }

    private function formatMaximoDate(string $date): string
    {
        $ts = strtotime($date);
        return $ts ? date('Y-m-d\TH:i:s', $ts) : $date;
    }

    private function parseMaximoDate(?string $date): ?string
    {
        if (!$date) return null;
        $ts = strtotime($date);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    private function mapMaximoAssetType(string $type): string
    {
        $map = [
            'PRESSURE VESSEL' => 'vessel',
            'VESSEL'          => 'vessel',
            'PIPE'            => 'piping',
            'PIPING'          => 'piping',
            'EXCHANGER'       => 'heat_exchanger',
            'TANK'            => 'tank',
            'VALVE'           => 'valve',
            'PUMP'            => 'rotating_equipment',
            'COMPRESSOR'      => 'rotating_equipment',
        ];
        return $map[strtoupper($type)] ?? 'other';
    }

    private function mapMaximoStatusToRBI(string $status): string
    {
        $map = [
            'OPERATING'     => 'in_service',
            'ACTIVE'        => 'in_service',
            'NOT READY'     => 'out_of_service',
            'DECOMMISSIONED'=> 'decommissioned',
            'BROKEN'        => 'out_of_service',
        ];
        return $map[strtoupper($status)] ?? 'in_service';
    }

    private function mapMaximoStatus(string $status): string
    {
        $map = [
            'WAPPR' => 'planned',
            'APPR'  => 'approved',
            'INPRG' => 'in_progress',
            'COMP'  => 'completed',
            'CLOSE' => 'closed',
            'CAN'   => 'cancelled',
        ];
        return $map[strtoupper($status)] ?? 'planned';
    }

    private function mapMaximoPriority($priority): string
    {
        return match ((int)$priority) {
            1       => 'critical',
            2       => 'high',
            3       => 'medium',
            default => 'low',
        };
    }

    private function log(string $level, string $message): void
    {
        error_log("{$this->logPrefix} [{$level}] {$message}");
    }
}
