<?php
/**
 * OSIsoftPIIntegration - OSIsoft PI (AVEVA PI) Web API Integration
 *
 * Connects to PI Web API for process data retrieval, asset framework
 * navigation, real-time monitoring, and operating excursion detection.
 *
 * @package RBI Engineering Suite
 */
class OSIsoftPIIntegration
{
    private Database $db;
    private array $config;
    private ?string $authToken = null;
    private string $logPrefix = '[PI Integration]';

    private const MAX_RETRIES     = 3;
    private const RETRY_DELAY     = 1;
    private const CONNECT_TIMEOUT = 10;
    private const RESPONSE_TIMEOUT = 30;

    /**
     * Initialize PI Web API connection.
     *
     * @param array $config Keys: base_url, username, password, auth_type (basic|kerberos|bearer),
     *                      bearer_token, verify_ssl, default_server
     */
    public function __construct(array $config)
    {
        $this->db = new Database();
        $this->config = array_merge([
            'base_url'       => '',
            'username'       => '',
            'password'       => '',
            'auth_type'      => 'basic',  // basic, kerberos, bearer
            'bearer_token'   => '',
            'verify_ssl'     => true,
            'default_server' => '',
        ], $config);
    }

    // =========================================================================
    // Authentication
    // =========================================================================

    /**
     * Authenticate to PI Web API.
     *
     * @return bool True on success
     * @throws RuntimeException On failure
     */
    public function authenticate(): bool
    {
        return match ($this->config['auth_type']) {
            'basic'    => $this->authenticateBasic(),
            'kerberos' => $this->authenticateKerberos(),
            'bearer'   => $this->authenticateBearer(),
            default    => throw new RuntimeException("{$this->logPrefix} Unsupported auth type: {$this->config['auth_type']}"),
        };
    }

    private function authenticateBasic(): bool
    {
        // Validate by calling the system endpoint
        $url = $this->buildUrl('/piwebapi/system');
        $response = $this->curlRequest($url, 'GET');
        $data = json_decode($response, true);

        if (empty($data['ProductTitle'])) {
            throw new RuntimeException("{$this->logPrefix} Basic auth validation failed - unexpected response");
        }

        $this->log('INFO', "Authenticated to PI Web API: {$data['ProductTitle']} v{$data['ProductVersion']}");
        return true;
    }

    private function authenticateKerberos(): bool
    {
        // Kerberos uses negotiate authentication via cURL
        $url = $this->buildUrl('/piwebapi/system');
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT        => self::RESPONSE_TIMEOUT,
            CURLOPT_HTTPAUTH       => CURLAUTH_NEGOTIATE,
            CURLOPT_USERPWD        => ':',
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_SSL_VERIFYPEER => (bool)$this->config['verify_ssl'],
            CURLOPT_SSL_VERIFYHOST => $this->config['verify_ssl'] ? 2 : 0,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode >= 400) {
            throw new RuntimeException("{$this->logPrefix} Kerberos auth failed: " . ($error ?: "HTTP {$httpCode}"));
        }

        $data = json_decode($response, true);
        $this->log('INFO', "Kerberos auth to PI Web API: {$data['ProductTitle']}");
        return true;
    }

    private function authenticateBearer(): bool
    {
        if (empty($this->config['bearer_token'])) {
            throw new RuntimeException("{$this->logPrefix} Bearer token not configured");
        }
        $this->authToken = $this->config['bearer_token'];

        $url = $this->buildUrl('/piwebapi/system');
        $response = $this->curlRequest($url, 'GET');
        $data = json_decode($response, true);

        if (empty($data['ProductTitle'])) {
            throw new RuntimeException("{$this->logPrefix} Bearer token validation failed");
        }

        $this->log('INFO', "Bearer auth to PI Web API: {$data['ProductTitle']}");
        return true;
    }

    // =========================================================================
    // Data Servers & Asset Framework
    // =========================================================================

    /**
     * List available PI Data Archive servers.
     *
     * @return array Data servers with WebId, Name, ServerVersion
     */
    public function getDataServers(): array
    {
        $url = $this->buildUrl('/piwebapi/dataservers');
        $response = $this->curlRequest($url, 'GET');
        $data = json_decode($response, true);
        return $data['Items'] ?? [];
    }

    /**
     * List AF (Asset Framework) servers.
     *
     * @return array Asset servers
     */
    public function getAssetServers(): array
    {
        $url = $this->buildUrl('/piwebapi/assetservers');
        $response = $this->curlRequest($url, 'GET');
        $data = json_decode($response, true);
        return $data['Items'] ?? [];
    }

    /**
     * Get AF databases for a given asset server.
     *
     * @param string $serverWebId Asset server WebId
     * @return array AF databases
     */
    public function getAFDatabases(string $serverWebId): array
    {
        $url = $this->buildUrl("/piwebapi/assetservers/{$serverWebId}/assetdatabases");
        $response = $this->curlRequest($url, 'GET');
        $data = json_decode($response, true);
        return $data['Items'] ?? [];
    }

    /**
     * Get AF elements (equipment hierarchy) within a database or parent element.
     *
     * @param string $databaseWebId Database or parent element WebId
     * @param string $path          Optional path filter (e.g., "Plant1\\Unit2")
     * @return array AF elements
     */
    public function getAFElements(string $databaseWebId, string $path = ''): array
    {
        if ($path) {
            $url = $this->buildUrl("/piwebapi/assetdatabases/{$databaseWebId}/elements", [
                'searchFullHierarchy' => 'true',
                'nameFilter'          => '*',
                'templateName'        => '',
            ]);
        } else {
            $url = $this->buildUrl("/piwebapi/assetdatabases/{$databaseWebId}/elements");
        }

        $response = $this->curlRequest($url, 'GET');
        $data = json_decode($response, true);
        return $data['Items'] ?? [];
    }

    /**
     * Get attributes of an AF element (data references to PI points).
     *
     * @param string $elementWebId AF element WebId
     * @return array Element attributes
     */
    public function getAFAttributes(string $elementWebId): array
    {
        $url = $this->buildUrl("/piwebapi/elements/{$elementWebId}/attributes");
        $response = $this->curlRequest($url, 'GET');
        $data = json_decode($response, true);
        return $data['Items'] ?? [];
    }

    /**
     * Search PI points (tags) on a data server.
     *
     * @param string $serverWebId Data server WebId
     * @param string $nameFilter  Tag name filter (supports wildcards: *, ?)
     * @return array Matching PI points
     */
    public function getPIPoints(string $serverWebId, string $nameFilter = '*'): array
    {
        $url = $this->buildUrl("/piwebapi/dataservers/{$serverWebId}/points", [
            'nameFilter' => $nameFilter,
            'maxCount'   => 1000,
        ]);

        $response = $this->curlRequest($url, 'GET');
        $data = json_decode($response, true);
        return $data['Items'] ?? [];
    }

    // =========================================================================
    // Stream Data Retrieval
    // =========================================================================

    /**
     * Get recorded (raw) values for a PI point.
     *
     * @param string $webId     PI point or attribute WebId
     * @param string $startTime Start time (ISO 8601 or PI time: *-7d, t, y)
     * @param string $endTime   End time
     * @param int    $maxCount  Maximum number of values
     * @return array Recorded values with timestamps
     */
    public function getRecordedValues(string $webId, string $startTime, string $endTime, int $maxCount = 10000): array
    {
        $url = $this->buildUrl("/piwebapi/streams/{$webId}/recorded", [
            'startTime' => $startTime,
            'endTime'   => $endTime,
            'maxCount'  => $maxCount,
        ]);

        $response = $this->curlRequest($url, 'GET');
        $data = json_decode($response, true);
        return $data['Items'] ?? [];
    }

    /**
     * Get interpolated values at regular intervals.
     *
     * @param string $webId     WebId
     * @param string $startTime Start time
     * @param string $endTime   End time
     * @param string $interval  Interval (e.g., '1h', '15m', '1d')
     * @return array Interpolated values
     */
    public function getInterpolatedValues(string $webId, string $startTime, string $endTime, string $interval): array
    {
        $url = $this->buildUrl("/piwebapi/streams/{$webId}/interpolated", [
            'startTime' => $startTime,
            'endTime'   => $endTime,
            'interval'  => $interval,
        ]);

        $response = $this->curlRequest($url, 'GET');
        $data = json_decode($response, true);
        return $data['Items'] ?? [];
    }

    /**
     * Get summary statistics over a time period.
     *
     * @param string $webId       WebId
     * @param string $startTime   Start time
     * @param string $endTime     End time
     * @param string $summaryType Summary type: Total, Average, Minimum, Maximum, Range, StdDev, Count, All
     * @return array Summary values
     */
    public function getSummaryValues(string $webId, string $startTime, string $endTime, string $summaryType = 'All'): array
    {
        $url = $this->buildUrl("/piwebapi/streams/{$webId}/summary", [
            'startTime'   => $startTime,
            'endTime'     => $endTime,
            'summaryType' => $summaryType,
        ]);

        $response = $this->curlRequest($url, 'GET');
        $data = json_decode($response, true);
        return $data['Items'] ?? [];
    }

    /**
     * Get current snapshot value for a PI point.
     *
     * @param string $webId PI point WebId
     * @return array Current value with timestamp and quality
     */
    public function getCurrentValue(string $webId): array
    {
        $url = $this->buildUrl("/piwebapi/streams/{$webId}/value");
        $response = $this->curlRequest($url, 'GET');
        return json_decode($response, true) ?: [];
    }

    /**
     * Batch retrieval of multiple stream values.
     *
     * @param array  $webIds    Array of PI point WebIds
     * @param string $startTime Start time
     * @param string $endTime   End time
     * @return array Keyed by WebId
     */
    public function getMultipleStreamValues(array $webIds, string $startTime, string $endTime): array
    {
        // Use PI Web API batch endpoint
        $batchRequests = [];
        foreach ($webIds as $i => $webId) {
            $batchRequests["request_{$i}"] = [
                'Method'   => 'GET',
                'Resource' => $this->buildUrl("/piwebapi/streams/{$webId}/recorded", [
                    'startTime' => $startTime,
                    'endTime'   => $endTime,
                    'maxCount'  => 1000,
                ]),
            ];
        }

        $url = $this->buildUrl('/piwebapi/batch');
        $response = $this->curlRequest($url, 'POST', json_encode($batchRequests), [
            'Content-Type: application/json',
        ]);

        $data = json_decode($response, true) ?: [];
        $results = [];

        foreach ($webIds as $i => $webId) {
            $key = "request_{$i}";
            $results[$webId] = $data[$key]['Content']['Items'] ?? [];
        }

        return $results;
    }

    /**
     * Write a value to a PI point.
     *
     * @param string $webId     PI point WebId
     * @param mixed  $value     Value to write
     * @param string $timestamp Timestamp (ISO 8601 or PI time)
     * @return bool Success
     */
    public function writeValue(string $webId, $value, string $timestamp = '*'): bool
    {
        $payload = [
            'Timestamp' => $timestamp,
            'Value'     => $value,
            'Good'      => true,
        ];

        $url = $this->buildUrl("/piwebapi/streams/{$webId}/value");
        $this->curlRequest($url, 'POST', json_encode($payload), [
            'Content-Type: application/json',
        ]);

        $this->log('INFO', "Wrote value {$value} to PI point {$webId}");
        return true;
    }

    /**
     * Set up WebSocket channel subscription for real-time data.
     * Returns channel configuration for client-side WebSocket connection.
     *
     * @param array    $webIds   PI point WebIds to subscribe to
     * @param callable $callback Not used server-side; returns config for client
     * @return array WebSocket channel configuration
     */
    public function subscribeToUpdates(array $webIds, callable $callback = null): array
    {
        // PI Web API Channels for real-time data
        $channelUrl = str_replace(['http://', 'https://'], ['ws://', 'wss://'],
            rtrim($this->config['base_url'], '/')) . '/piwebapi/streams/channel';

        $streams = [];
        foreach ($webIds as $webId) {
            $streams[] = $this->buildUrl("/piwebapi/streams/{$webId}/channel");
        }

        return [
            'channel_url' => $channelUrl,
            'streams'     => $streams,
            'web_ids'     => $webIds,
            'auth_type'   => $this->config['auth_type'],
            'instructions'=> 'Use client-side WebSocket to connect. Auth via query param or headers.',
        ];
    }

    // =========================================================================
    // RBI-Specific Functions
    // =========================================================================

    /**
     * Configure mapping between PI tags and RBI asset parameters.
     *
     * @param array $mappingConfig Array of [asset_id, pi_tag_name, pi_web_id, parameter_type, unit, thresholds]
     * @return int Number of mappings created/updated
     */
    public function mapPITagsToAssets(array $mappingConfig): int
    {
        $count = 0;

        foreach ($mappingConfig as $mapping) {
            $existing = $this->db->query(
                "SELECT id FROM pi_tag_mappings WHERE asset_id = ? AND pi_tag_name = ? LIMIT 1",
                [$mapping['asset_id'], $mapping['pi_tag_name']]
            )->fetch();

            $data = [
                'asset_id'       => $mapping['asset_id'],
                'pi_tag_name'    => $mapping['pi_tag_name'],
                'pi_web_id'      => $mapping['pi_web_id'] ?? '',
                'parameter_type' => $mapping['parameter_type'] ?? 'temperature',
                'unit'           => $mapping['unit'] ?? '',
                'scaling_factor' => $mapping['scaling_factor'] ?? 1.0,
                'offset'         => $mapping['offset'] ?? 0.0,
                'min_threshold'  => $mapping['min_threshold'] ?? null,
                'max_threshold'  => $mapping['max_threshold'] ?? null,
                'is_active'      => $mapping['is_active'] ?? 1,
            ];

            if ($existing) {
                $this->db->update('pi_tag_mappings', $data, 'id = ?', [$existing['id']]);
            } else {
                $this->db->insert('pi_tag_mappings', $data);
            }
            $count++;
        }

        $this->log('INFO', "Configured {$count} PI tag mappings");
        return $count;
    }

    /**
     * Pull process data for a specific asset and time range, storing results in DB.
     *
     * @param int   $assetId   RBI asset ID
     * @param array $timeRange Keys: start, end
     * @return array Sync results
     */
    public function syncProcessData(int $assetId, array $timeRange): array
    {
        $results = ['tags_synced' => 0, 'values_stored' => 0, 'errors' => []];

        // Get tag mappings for this asset
        $mappings = $this->db->query(
            "SELECT * FROM pi_tag_mappings WHERE asset_id = ? AND is_active = 1",
            [$assetId]
        )->fetchAll();

        if (empty($mappings)) {
            return $results;
        }

        $startTime = $timeRange['start'] ?? '*-24h';
        $endTime = $timeRange['end'] ?? '*';

        foreach ($mappings as $mapping) {
            try {
                $webId = $mapping['pi_web_id'];
                if (!$webId) continue;

                // Get recorded values
                $values = $this->getRecordedValues($webId, $startTime, $endTime, 5000);

                foreach ($values as $val) {
                    if (!isset($val['Value']) || !is_numeric($val['Value'])) continue;

                    $scaledValue = ($val['Value'] * $mapping['scaling_factor']) + $mapping['offset'];
                    $timestamp = $this->parsePITimestamp($val['Timestamp'] ?? '');

                    if (!$timestamp) continue;

                    $this->db->insert('iot_sensor_readings', [
                        'asset_id'       => $assetId,
                        'sensor_type'    => $mapping['parameter_type'],
                        'tag_name'       => $mapping['pi_tag_name'],
                        'value'          => $scaledValue,
                        'unit'           => $mapping['unit'],
                        'quality'        => ($val['Good'] ?? true) ? 'good' : 'questionable',
                        'reading_time'   => $timestamp,
                        'created_at'     => date('Y-m-d H:i:s'),
                    ]);

                    $results['values_stored']++;
                }

                // Update last value cache
                if (!empty($values)) {
                    $lastVal = end($values);
                    $this->db->update('pi_tag_mappings', [
                        'last_value'   => ($lastVal['Value'] * $mapping['scaling_factor']) + $mapping['offset'],
                        'last_updated' => $this->parsePITimestamp($lastVal['Timestamp'] ?? ''),
                    ], 'id = ?', [$mapping['id']]);
                }

                $results['tags_synced']++;
            } catch (\Throwable $e) {
                $results['errors'][] = "Tag {$mapping['pi_tag_name']}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Analyze PI data for operating excursions.
     *
     * @param int   $assetId    RBI asset ID
     * @param array $thresholds Override thresholds [parameter_type => [min, max]]
     * @return array Detected excursions
     */
    public function getExcursions(int $assetId, array $thresholds = []): array
    {
        $mappings = $this->db->query(
            "SELECT * FROM pi_tag_mappings WHERE asset_id = ? AND is_active = 1",
            [$assetId]
        )->fetchAll();

        $excursions = [];
        $lookback = '*-30d';

        foreach ($mappings as $mapping) {
            $webId = $mapping['pi_web_id'];
            if (!$webId) continue;

            $minThreshold = $thresholds[$mapping['parameter_type']]['min'] ?? $mapping['min_threshold'];
            $maxThreshold = $thresholds[$mapping['parameter_type']]['max'] ?? $mapping['max_threshold'];

            if ($minThreshold === null && $maxThreshold === null) continue;

            try {
                $values = $this->getRecordedValues($webId, $lookback, '*', 50000);
                $inExcursion = false;
                $excursionStart = null;
                $peakValue = null;

                foreach ($values as $val) {
                    if (!isset($val['Value']) || !is_numeric($val['Value'])) continue;

                    $scaledValue = ($val['Value'] * $mapping['scaling_factor']) + $mapping['offset'];
                    $timestamp = $val['Timestamp'] ?? '';

                    $isExcursion = false;
                    $excursionType = '';

                    if ($maxThreshold !== null && $scaledValue > $maxThreshold) {
                        $isExcursion = true;
                        $excursionType = $this->getExcursionType($mapping['parameter_type'], 'high');
                    } elseif ($minThreshold !== null && $scaledValue < $minThreshold) {
                        $isExcursion = true;
                        $excursionType = $this->getExcursionType($mapping['parameter_type'], 'low');
                    }

                    if ($isExcursion && !$inExcursion) {
                        $inExcursion = true;
                        $excursionStart = $timestamp;
                        $peakValue = $scaledValue;
                    } elseif ($isExcursion && $inExcursion) {
                        if (abs($scaledValue) > abs($peakValue)) {
                            $peakValue = $scaledValue;
                        }
                    } elseif (!$isExcursion && $inExcursion) {
                        // Excursion ended
                        $startTs = $this->parsePITimestamp($excursionStart);
                        $endTs = $this->parsePITimestamp($timestamp);
                        $duration = $startTs && $endTs ? (int)((strtotime($endTs) - strtotime($startTs)) / 60) : 0;
                        $thresholdVal = ($maxThreshold !== null && $peakValue > $maxThreshold) ? $maxThreshold : $minThreshold;

                        $severity = $this->calculateExcursionSeverity($peakValue, $thresholdVal, $duration);

                        $excursion = [
                            'asset_id'        => $assetId,
                            'excursion_type'  => $excursionType,
                            'start_time'      => $startTs,
                            'end_time'        => $endTs,
                            'duration_minutes'=> $duration,
                            'peak_value'      => round($peakValue, 4),
                            'threshold_value' => round($thresholdVal, 4),
                            'severity'        => $severity,
                            'pi_tag'          => $mapping['pi_tag_name'],
                        ];

                        $excursions[] = $excursion;

                        // Store in database
                        $excursion['created_at'] = date('Y-m-d H:i:s');
                        $this->db->insert('operating_excursions', $excursion);

                        $inExcursion = false;
                    }
                }
            } catch (\Throwable $e) {
                $this->log('WARNING', "Excursion analysis failed for tag {$mapping['pi_tag_name']}: " . $e->getMessage());
            }
        }

        return $excursions;
    }

    /**
     * Calculate operating severity index based on actual vs design conditions.
     *
     * @param int    $assetId RBI asset ID
     * @param string $period  Analysis period (e.g., '*-365d' or '*-1y')
     * @return array Severity index and contributing factors
     */
    public function calculateOperatingSeverity(int $assetId, string $period = '*-365d'): array
    {
        $result = [
            'asset_id'         => $assetId,
            'severity_index'   => 0.0,
            'factors'          => [],
            'excursion_count'  => 0,
            'total_excursion_hours' => 0,
        ];

        // Get design conditions
        $design = $this->db->query(
            "SELECT dd.design_pressure_mpa, dd.design_temperature_c
             FROM asset_registry ar
             LEFT JOIN design_data dd ON ar.id = dd.asset_id
             WHERE ar.id = ?",
            [$assetId]
        )->fetch();

        if (!$design) return $result;

        // Get tag mappings
        $mappings = $this->db->query(
            "SELECT * FROM pi_tag_mappings WHERE asset_id = ? AND is_active = 1",
            [$assetId]
        )->fetchAll();

        foreach ($mappings as $mapping) {
            $webId = $mapping['pi_web_id'];
            if (!$webId) continue;

            try {
                $summary = $this->getSummaryValues($webId, $period, '*', 'All');

                $avg = null;
                $max = null;
                foreach ($summary as $s) {
                    $type = $s['Type'] ?? '';
                    if ($type === 'Average') $avg = $s['Value']['Value'] ?? null;
                    if ($type === 'Maximum') $max = $s['Value']['Value'] ?? null;
                }

                if ($avg === null) continue;

                $scaledAvg = ($avg * $mapping['scaling_factor']) + $mapping['offset'];
                $scaledMax = $max !== null ? ($max * $mapping['scaling_factor']) + $mapping['offset'] : $scaledAvg;

                // Calculate severity factor based on parameter type
                $designValue = match ($mapping['parameter_type']) {
                    'temperature' => $design['design_temperature_c'],
                    'pressure'    => $design['design_pressure_mpa'],
                    default       => null,
                };

                if ($designValue && $designValue > 0) {
                    $ratio = $scaledMax / $designValue;
                    $factor = max(0, ($ratio - 0.8) / 0.2); // 0 at 80% design, 1 at 100%
                    $result['factors'][] = [
                        'parameter'    => $mapping['parameter_type'],
                        'tag'          => $mapping['pi_tag_name'],
                        'average'      => round($scaledAvg, 2),
                        'maximum'      => round($scaledMax, 2),
                        'design_value' => $designValue,
                        'ratio'        => round($ratio, 3),
                        'factor'       => round($factor, 3),
                    ];
                    $result['severity_index'] = max($result['severity_index'], $factor);
                }
            } catch (\Throwable $e) {
                $this->log('WARNING', "Severity calc failed for {$mapping['pi_tag_name']}: " . $e->getMessage());
            }
        }

        // Count historical excursions
        $excursionStats = $this->db->query(
            "SELECT COUNT(*) as cnt, COALESCE(SUM(duration_minutes), 0) as total_min
             FROM operating_excursions
             WHERE asset_id = ? AND start_time >= DATE_SUB(NOW(), INTERVAL 365 DAY)",
            [$assetId]
        )->fetch();

        $result['excursion_count'] = (int)($excursionStats['cnt'] ?? 0);
        $result['total_excursion_hours'] = round(($excursionStats['total_min'] ?? 0) / 60, 1);

        // Adjust severity by excursion frequency
        if ($result['excursion_count'] > 10) {
            $result['severity_index'] = min(2.0, $result['severity_index'] * 1.5);
        }

        $result['severity_index'] = round($result['severity_index'], 3);

        return $result;
    }

    /**
     * Get data from online corrosion monitoring probes (ER probes, LPR probes).
     *
     * @param string $probeTagPrefix PI tag prefix for corrosion probes (e.g., "PLANT1.CORR.*")
     * @return array Probe readings with corrosion rate calculations
     */
    public function getCorrosionProbeData(string $probeTagPrefix): array
    {
        $results = [];

        // Get data servers to search tags
        $servers = $this->getDataServers();
        if (empty($servers)) return $results;

        $serverWebId = $servers[0]['WebId'] ?? '';
        $points = $this->getPIPoints($serverWebId, $probeTagPrefix);

        foreach ($points as $point) {
            $webId = $point['WebId'] ?? '';
            $tagName = $point['Name'] ?? '';

            if (!$webId) continue;

            try {
                // Get last 30 days of data
                $values = $this->getRecordedValues($webId, '*-30d', '*', 10000);
                $current = $this->getCurrentValue($webId);
                $summary = $this->getSummaryValues($webId, '*-30d', '*', 'All');

                $avgRate = null;
                $maxRate = null;
                $minRate = null;

                foreach ($summary as $s) {
                    $type = $s['Type'] ?? '';
                    $val = $s['Value']['Value'] ?? null;
                    if ($type === 'Average') $avgRate = $val;
                    if ($type === 'Maximum') $maxRate = $val;
                    if ($type === 'Minimum') $minRate = $val;
                }

                $results[] = [
                    'tag_name'       => $tagName,
                    'web_id'         => $webId,
                    'current_value'  => $current['Value'] ?? null,
                    'current_time'   => $current['Timestamp'] ?? null,
                    'avg_30d'        => $avgRate,
                    'max_30d'        => $maxRate,
                    'min_30d'        => $minRate,
                    'unit'           => $point['EngineeringUnits'] ?? 'mpy',
                    'data_points'    => count($values),
                    'point_type'     => $point['PointType'] ?? '',
                ];
            } catch (\Throwable $e) {
                $this->log('WARNING', "Corrosion probe data failed for {$tagName}: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Build AF element search query.
     *
     * @param array $criteria Keys: name, template, category, attributeFilter
     * @return string AF search query string
     */
    public function buildAFSearchQuery(array $criteria): string
    {
        $parts = [];

        if (!empty($criteria['name'])) {
            $parts[] = "name:{$criteria['name']}";
        }
        if (!empty($criteria['template'])) {
            $parts[] = "template:{$criteria['template']}";
        }
        if (!empty($criteria['category'])) {
            $parts[] = "category:{$criteria['category']}";
        }
        if (!empty($criteria['attributeFilter'])) {
            foreach ($criteria['attributeFilter'] as $attr => $value) {
                $parts[] = "|{$attr}:{$value}";
            }
        }

        return implode(' ', $parts);
    }

    /**
     * Parse PI Web API error response.
     *
     * @param array $response Decoded response
     * @return string Error message
     */
    public function handlePIError(array $response): string
    {
        if (isset($response['Errors'])) {
            $errors = is_array($response['Errors']) ? implode('; ', $response['Errors']) : $response['Errors'];
            $this->log('ERROR', "PI Error: {$errors}");
            return "PI Web API Error: {$errors}";
        }

        if (isset($response['Message'])) {
            $this->log('ERROR', "PI Error: {$response['Message']}");
            return "PI Web API Error: {$response['Message']}";
        }

        return 'Unknown PI Web API error: ' . json_encode($response);
    }

    // =========================================================================
    // Internal Helpers
    // =========================================================================

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

            $opts = [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
                CURLOPT_TIMEOUT        => self::RESPONSE_TIMEOUT,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_SSL_VERIFYPEER => (bool)$this->config['verify_ssl'],
                CURLOPT_SSL_VERIFYHOST => $this->config['verify_ssl'] ? 2 : 0,
            ];

            // Authentication
            if ($this->config['auth_type'] === 'basic') {
                $opts[CURLOPT_USERPWD] = $this->config['username'] . ':' . $this->config['password'];
            } elseif ($this->config['auth_type'] === 'kerberos') {
                $opts[CURLOPT_HTTPAUTH] = CURLAUTH_NEGOTIATE;
                $opts[CURLOPT_USERPWD] = ':';
            } elseif ($this->authToken) {
                $headers[] = 'Authorization: Bearer ' . $this->authToken;
                $opts[CURLOPT_HTTPHEADER] = $headers;
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

    private function parsePITimestamp(?string $timestamp): ?string
    {
        if (!$timestamp) return null;
        $ts = strtotime($timestamp);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }

    private function getExcursionType(string $paramType, string $direction): string
    {
        return match (true) {
            $paramType === 'pressure' && $direction === 'high'     => 'over_pressure',
            $paramType === 'temperature' && $direction === 'high'  => 'over_temperature',
            $paramType === 'temperature' && $direction === 'low'   => 'under_temperature',
            $paramType === 'flow_rate' && $direction === 'high'    => 'high_flow',
            $paramType === 'flow_rate' && $direction === 'low'     => 'low_flow',
            $paramType === 'vibration' && $direction === 'high'    => 'high_vibration',
            default => 'corrosive_conditions',
        };
    }

    private function calculateExcursionSeverity(float $peakValue, float $thresholdValue, int $durationMinutes): string
    {
        if ($thresholdValue == 0) return 'minor';

        $exceedance = abs($peakValue - $thresholdValue) / abs($thresholdValue);

        if ($exceedance > 0.2 || $durationMinutes > 480) return 'critical';
        if ($exceedance > 0.1 || $durationMinutes > 120) return 'severe';
        if ($exceedance > 0.05 || $durationMinutes > 30) return 'moderate';
        return 'minor';
    }

    private function log(string $level, string $message): void
    {
        error_log("{$this->logPrefix} [{$level}] {$message}");
    }
}
