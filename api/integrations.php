<?php
/**
 * Integration API Endpoints - RBI Engineering Suite
 *
 * POST  ?action=test_connection   - Test connection to external system
 * POST  ?action=sync              - Trigger sync (params: integration_id, sync_type)
 * POST  ?action=sync_all          - Sync all active integrations
 * POST  ?action=save_config       - Save integration configuration
 * POST  ?action=field_mapping     - Save field mapping
 * POST  ?action=resolve_conflict  - Resolve a data conflict
 * POST  ?action=schedule_sync     - Set sync schedule
 * POST  ?action=save_pi_tag       - Save PI tag mapping
 * GET   ?action=sync_status       - Get sync progress
 * GET   ?action=sync_history      - Get sync history
 * GET   ?action=conflicts         - Get unresolved conflicts
 * GET   ?action=health            - Get integration health status
 * GET   ?action=pi_current_values - Get current PI tag values
 * GET   ?action=pi_historical     - Get historical PI data
 * GET   ?action=mapping_template  - Export mapping template CSV
 */
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = new Database();

    if ($method === 'GET') {
        $action = $_GET['action'] ?? '';
        handleGet($action, $db);
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $action = $input['action'] ?? $_GET['action'] ?? '';
        handlePost($action, $input, $db);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
} catch (\Throwable $e) {
    error_log('[Integration API] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// =============================================================================
// GET Handlers
// =============================================================================
function handleGet(string $action, Database $db): void
{
    switch ($action) {
        case 'health':
            $manager = new IntegrationManager();
            jsonResponse($manager->getIntegrationHealth());
            break;

        case 'sync_history':
            $integrationId = (int)($_GET['integration_id'] ?? 0);
            $limit = (int)($_GET['limit'] ?? 50);

            if ($integrationId > 0) {
                $manager = new IntegrationManager();
                jsonResponse($manager->getSyncHistory($integrationId, $limit));
            } else {
                $history = $db->query(
                    "SELECT isl.*, ic.integration_name
                     FROM integration_sync_log isl
                     JOIN integration_configs ic ON isl.integration_id = ic.id
                     ORDER BY isl.started_at DESC LIMIT ?",
                    [$limit]
                )->fetchAll();
                jsonResponse($history);
            }
            break;

        case 'sync_status':
            $jobId = (int)($_GET['job_id'] ?? 0);
            if ($jobId <= 0) {
                jsonError('job_id required', 400);
                return;
            }
            $log = $db->find('integration_sync_log', $jobId);
            if (!$log) {
                jsonError('Sync job not found', 404);
                return;
            }
            jsonResponse($log);
            break;

        case 'conflicts':
            $integrationId = (int)($_GET['integration_id'] ?? 0) ?: null;
            $manager = new IntegrationManager();
            jsonResponse($manager->getConflicts($integrationId));
            break;

        case 'pi_current_values':
            $tagMappings = $db->query(
                "SELECT ptm.*, ar.asset_tag
                 FROM pi_tag_mappings ptm
                 JOIN asset_registry ar ON ptm.asset_id = ar.id
                 WHERE ptm.is_active = 1 AND ptm.pi_web_id IS NOT NULL AND ptm.pi_web_id != ''
                 ORDER BY ar.asset_tag"
            )->fetchAll();

            $piConfig = $db->query(
                "SELECT * FROM integration_configs WHERE vendor LIKE '%PI%' OR vendor LIKE '%OSIsoft%' OR vendor LIKE '%AVEVA%' LIMIT 1"
            )->fetch();

            if (!$piConfig || empty($tagMappings)) {
                jsonResponse(['tags' => [], 'message' => 'No PI configuration or tag mappings found']);
                return;
            }

            try {
                $creds = json_decode($piConfig['credentials_encrypted'] ?? '{}', true) ?: [];
                $pi = new OSIsoftPIIntegration(array_merge($creds, [
                    'base_url'  => $piConfig['api_base_url'],
                    'auth_type' => $piConfig['auth_type'] ?? 'basic',
                    'verify_ssl'=> APP_ENV !== 'development',
                ]));
                $pi->authenticate();

                $values = [];
                foreach ($tagMappings as $tm) {
                    try {
                        $current = $pi->getCurrentValue($tm['pi_web_id']);
                        $values[] = [
                            'tag_name'   => $tm['pi_tag_name'],
                            'asset_tag'  => $tm['asset_tag'],
                            'parameter'  => $tm['parameter_type'],
                            'value'      => $current['Value'] ?? null,
                            'timestamp'  => $current['Timestamp'] ?? null,
                            'good'       => $current['Good'] ?? true,
                            'unit'       => $tm['unit'],
                        ];

                        // Update cache
                        if (isset($current['Value']) && is_numeric($current['Value'])) {
                            $db->update('pi_tag_mappings', [
                                'last_value'   => ($current['Value'] * $tm['scaling_factor']) + $tm['offset'],
                                'last_updated' => date('Y-m-d H:i:s'),
                            ], 'id = ?', [$tm['id']]);
                        }
                    } catch (\Throwable $e) {
                        $values[] = [
                            'tag_name' => $tm['pi_tag_name'],
                            'error'    => $e->getMessage(),
                        ];
                    }
                }

                jsonResponse(['tags' => $values]);
            } catch (\Throwable $e) {
                jsonError('PI connection failed: ' . $e->getMessage());
            }
            break;

        case 'pi_historical':
            $webId = $_GET['web_id'] ?? '';
            $start = $_GET['start'] ?? '*-7d';
            $end   = $_GET['end'] ?? '*';

            if (!$webId) {
                jsonError('web_id required', 400);
                return;
            }

            $piConfig = $db->query(
                "SELECT * FROM integration_configs WHERE vendor LIKE '%PI%' OR vendor LIKE '%OSIsoft%' OR vendor LIKE '%AVEVA%' LIMIT 1"
            )->fetch();

            if (!$piConfig) {
                jsonError('PI not configured');
                return;
            }

            try {
                $creds = json_decode($piConfig['credentials_encrypted'] ?? '{}', true) ?: [];
                $pi = new OSIsoftPIIntegration(array_merge($creds, [
                    'base_url'  => $piConfig['api_base_url'],
                    'auth_type' => $piConfig['auth_type'] ?? 'basic',
                    'verify_ssl'=> APP_ENV !== 'development',
                ]));
                $pi->authenticate();

                $values = $pi->getInterpolatedValues($webId, $start, $end, '1h');
                $formatted = array_map(function ($v) {
                    return [
                        'timestamp' => isset($v['Timestamp']) ? date('M j H:i', strtotime($v['Timestamp'])) : '',
                        'value'     => $v['Value'] ?? null,
                        'good'      => $v['Good'] ?? true,
                    ];
                }, $values);

                jsonResponse($formatted);
            } catch (\Throwable $e) {
                jsonError('Failed to fetch historical data: ' . $e->getMessage());
            }
            break;

        case 'mapping_template':
            $type = $_GET['type'] ?? 'sap';
            $manager = new IntegrationManager();
            $csv = $manager->exportMappingTemplate($type);

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="mapping_template_' . $type . '.csv"');
            echo $csv;
            exit;

        default:
            jsonError('Unknown action: ' . $action, 400);
    }
}

// =============================================================================
// POST Handlers
// =============================================================================
function handlePost(string $action, array $input, Database $db): void
{
    switch ($action) {
        case 'test_connection':
            $integrationId = (int)($input['integration_id'] ?? 0);

            if ($integrationId > 0) {
                $manager = new IntegrationManager();
                $result = $manager->testConnection($integrationId);
                jsonResponse($result);
            } else {
                // Test with provided config (not yet saved)
                $result = testConnectionDirect($input, $db);
                jsonResponse($result);
            }
            break;

        case 'sync':
            $integrationId = (int)($input['integration_id'] ?? 0);
            $syncType = $input['sync_type'] ?? 'manual';

            if ($integrationId <= 0) {
                jsonError('integration_id required', 400);
                return;
            }

            $manager = new IntegrationManager();
            $result = $manager->runSync($integrationId, $syncType);
            jsonResponse($result);
            break;

        case 'sync_all':
            $manager = new IntegrationManager();
            $results = $manager->syncAll();
            jsonResponse($results);
            break;

        case 'save_config':
            $result = saveIntegrationConfig($input, $db);
            jsonResponse($result);
            break;

        case 'field_mapping':
            $integrationId = (int)($input['integration_id'] ?? 0);
            $mappings = $input['mappings'] ?? [];

            if ($integrationId <= 0 || empty($mappings)) {
                jsonError('integration_id and mappings required', 400);
                return;
            }

            $manager = new IntegrationManager();
            $count = $manager->saveFieldMapping($integrationId, $mappings);
            jsonResponse(['saved' => $count]);
            break;

        case 'resolve_conflict':
            $conflictId = (int)($input['conflict_id'] ?? 0);
            $resolution = $input['resolution'] ?? '';
            $mergedValue = $input['merged_value'] ?? null;

            if ($conflictId <= 0 || !$resolution) {
                jsonError('conflict_id and resolution required', 400);
                return;
            }

            $manager = new IntegrationManager();
            $manager->resolveConflict($conflictId, $resolution, $mergedValue);
            jsonResponse(['resolved' => true]);
            break;

        case 'schedule_sync':
            $integrationId = (int)($input['integration_id'] ?? 0);
            $schedule = $input['schedule'] ?? '60';

            if ($integrationId <= 0) {
                jsonError('integration_id required', 400);
                return;
            }

            $manager = new IntegrationManager();
            $manager->scheduleSync($integrationId, $schedule);
            jsonResponse(['scheduled' => true]);
            break;

        case 'save_pi_tag':
            $data = [
                'asset_id'       => (int)($input['asset_id'] ?? 0),
                'pi_tag_name'    => $input['pi_tag_name'] ?? '',
                'pi_web_id'      => $input['pi_web_id'] ?? '',
                'parameter_type' => $input['parameter_type'] ?? 'temperature',
                'unit'           => $input['unit'] ?? '',
                'scaling_factor' => (float)($input['scaling_factor'] ?? 1.0),
                'offset'         => (float)($input['offset'] ?? 0.0),
                'min_threshold'  => isset($input['min_threshold']) && $input['min_threshold'] !== '' ? (float)$input['min_threshold'] : null,
                'max_threshold'  => isset($input['max_threshold']) && $input['max_threshold'] !== '' ? (float)$input['max_threshold'] : null,
                'is_active'      => 1,
            ];

            if ($data['asset_id'] <= 0 || empty($data['pi_tag_name'])) {
                jsonError('asset_id and pi_tag_name required', 400);
                return;
            }

            // Check if mapping exists
            $existing = $db->query(
                "SELECT id FROM pi_tag_mappings WHERE asset_id = ? AND pi_tag_name = ? LIMIT 1",
                [$data['asset_id'], $data['pi_tag_name']]
            )->fetch();

            if ($existing) {
                $db->update('pi_tag_mappings', $data, 'id = ?', [$existing['id']]);
                jsonResponse(['id' => $existing['id'], 'action' => 'updated']);
            } else {
                $id = $db->insert('pi_tag_mappings', $data);
                jsonResponse(['id' => $id, 'action' => 'created']);
            }
            break;

        case 'import_mapping':
            $integrationId = (int)($input['integration_id'] ?? 0);
            $csvData = $input['csv_data'] ?? '';

            if ($integrationId <= 0 || empty($csvData)) {
                jsonError('integration_id and csv_data required', 400);
                return;
            }

            $manager = new IntegrationManager();
            $result = $manager->importMappingConfig($integrationId, $csvData);
            jsonResponse($result);
            break;

        default:
            jsonError('Unknown action: ' . $action, 400);
    }
}

// =============================================================================
// Helper Functions
// =============================================================================

function testConnectionDirect(array $input, Database $db): array
{
    $result = [
        'status'     => 'unknown',
        'latency_ms' => 0,
        'message'    => '',
        'tested_at'  => date('Y-m-d H:i:s'),
        'details'    => [],
    ];

    $startTime = microtime(true);
    $host = $input['host'] ?? $input['base_url'] ?? '';

    if (empty($host)) {
        $result['status'] = 'failed';
        $result['message'] = 'Host URL is required';
        return $result;
    }

    try {
        $vendor = strtolower($input['vendor'] ?? '');

        if (str_contains($vendor, 'sap')) {
            $sap = new SAPIntegration([
                'host'                => $host,
                'client'              => $input['client'] ?? '100',
                'user'                => $input['username'] ?? '',
                'password'            => $input['password'] ?? '',
                'system_id'           => $input['system_id'] ?? '',
                'auth_type'           => $input['auth_type'] ?? 'basic',
                'oauth_token_url'     => $input['oauth_token_url'] ?? '',
                'oauth_client_id'     => $input['oauth_client_id'] ?? '',
                'oauth_client_secret' => $input['oauth_client_secret'] ?? '',
                'verify_ssl'          => !empty($input['verify_ssl']),
            ]);
            $sap->authenticate();
            $result['details']['system'] = 'SAP Plant Maintenance';

        } elseif (str_contains($vendor, 'maximo') || str_contains($vendor, 'ibm')) {
            $maximo = new MaximoIntegration([
                'base_url'            => $host,
                'api_key'             => $input['api_key'] ?? '',
                'username'            => $input['username'] ?? '',
                'password'            => $input['password'] ?? '',
                'auth_type'           => $input['auth_type'] ?? 'apikey',
                'site_id'             => $input['site_id'] ?? '',
                'org_id'              => $input['org_id'] ?? '',
                'verify_ssl'          => !empty($input['verify_ssl']),
            ]);
            $maximo->authenticate();
            $result['details']['system'] = 'IBM Maximo';

        } elseif (str_contains($vendor, 'pi') || str_contains($vendor, 'osisoft') || str_contains($vendor, 'aveva')) {
            $pi = new OSIsoftPIIntegration([
                'base_url'       => $host,
                'username'       => $input['username'] ?? '',
                'password'       => $input['password'] ?? '',
                'auth_type'      => $input['auth_type'] ?? 'basic',
                'bearer_token'   => $input['bearer_token'] ?? '',
                'verify_ssl'     => !empty($input['verify_ssl']),
            ]);
            $pi->authenticate();
            $servers = $pi->getDataServers();
            $result['details']['system'] = 'OSIsoft PI Web API';
            $result['details']['data_servers'] = count($servers);

        } else {
            // Generic test
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $host,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_NOBODY         => true,
                CURLOPT_SSL_VERIFYPEER => !empty($input['verify_ssl']),
            ]);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) throw new RuntimeException($error);
            $result['details']['http_code'] = $httpCode;
        }

        $result['status'] = 'connected';
        $result['message'] = 'Connection successful';

    } catch (\Throwable $e) {
        $result['status'] = 'failed';
        $result['message'] = $e->getMessage();
    }

    $result['latency_ms'] = round((microtime(true) - $startTime) * 1000);
    return $result;
}

function saveIntegrationConfig(array $input, Database $db): array
{
    $integrationId = (int)($input['integration_id'] ?? 0);

    $data = [
        'integration_name'    => $input['vendor'] ?? $input['integration_name'] ?? 'Integration',
        'system_type'         => $input['system_type'] ?? 'cmms',
        'vendor'              => $input['vendor'] ?? '',
        'api_base_url'        => $input['host'] ?? $input['base_url'] ?? '',
        'auth_type'           => mapAuthType($input['auth_type'] ?? 'basic'),
        'sync_direction'      => $input['sync_direction'] ?? 'bidirectional',
        'sync_frequency_minutes' => (int)($input['sync_frequency'] ?? 60),
        'is_active'           => 1,
        'updated_at'          => date('Y-m-d H:i:s'),
    ];

    // Store credentials (in production, encrypt these)
    $credentials = array_filter([
        'username'            => $input['username'] ?? '',
        'password'            => $input['password'] ?? '',
        'api_key'             => $input['api_key'] ?? '',
        'client'              => $input['client'] ?? '',
        'system_id'           => $input['system_id'] ?? '',
        'oauth_token_url'     => $input['oauth_token_url'] ?? '',
        'oauth_client_id'     => $input['oauth_client_id'] ?? '',
        'oauth_client_secret' => $input['oauth_client_secret'] ?? '',
        'bearer_token'        => $input['bearer_token'] ?? '',
        'site_id'             => $input['site_id'] ?? '',
        'org_id'              => $input['org_id'] ?? '',
    ]);

    $data['credentials_encrypted'] = json_encode($credentials);

    // Store extra config
    $filterConfig = array_filter([
        'client'    => $input['client'] ?? '',
        'system_id' => $input['system_id'] ?? '',
        'language'  => $input['language'] ?? 'EN',
        'site_id'   => $input['site_id'] ?? '',
        'org_id'    => $input['org_id'] ?? '',
    ]);
    $data['filter_config'] = json_encode($filterConfig);

    if ($integrationId > 0) {
        $db->update('integration_configs', $data, 'id = ?', [$integrationId]);
        return ['id' => $integrationId, 'action' => 'updated'];
    } else {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['created_by'] = $_SESSION['user_id'] ?? null;
        $id = $db->insert('integration_configs', $data);
        return ['id' => $id, 'action' => 'created'];
    }
}

function mapAuthType(string $type): string
{
    return match ($type) {
        'apikey', 'api_key' => 'api_key',
        'oauth2'            => 'oauth2',
        'basic', 'ldap'     => 'basic',
        'bearer', 'kerberos'=> 'certificate',
        default             => 'api_key',
    };
}

function jsonResponse($data): void
{
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

function jsonError(string $message, int $httpCode = 500): void
{
    http_response_code($httpCode);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}
