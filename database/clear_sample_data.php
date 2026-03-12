<?php
/**
 * RBI Engineering Suite — Clear Sample Data
 *
 * Removes ALL sample/transactional data while preserving system defaults:
 *   - roles, permissions, role_permissions (system config)
 *   - damage_mechanisms (API 571 library)
 *   - risk_matrices, risk_matrix_cells (5x5 matrix)
 *   - inspection_strategies (default strategies)
 *   - dashboards, dashboard_widgets (default dashboard)
 *   - report_templates (system templates)
 *   - default admin user (id=1)
 *   - default site hierarchy (id=1)
 *
 * Usage:
 *   CLI:     php clear_sample_data.php
 *   Browser: http://localhost/rbi/database/clear_sample_data.php
 */

$isCli = (php_sapi_name() === 'cli');
$br    = $isCli ? "\n" : "<br>\n";
$bold  = function ($t) use ($isCli) { return $isCli ? "\033[1m{$t}\033[0m" : "<strong>{$t}</strong>"; };

if (!$isCli) {
    echo "<!DOCTYPE html><html><head><title>RBI Clear Sample Data</title>"
       . "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}"
       . ".ok{color:#4ec9b0;}.err{color:#f44747;}.warn{color:#dcdcaa;}.info{color:#569cd6;}"
       . "h2{color:#ce9178;}</style></head><body>";
    echo "<h2>RBI Engineering Suite &mdash; Clear Sample Data</h2>";
}

function status($type, $msg) {
    global $isCli;
    if ($isCli) {
        $colors = ['ok' => "\033[32m", 'err' => "\033[31m", 'warn' => "\033[33m", 'info' => "\033[34m"];
        $icons  = ['ok' => '[OK]', 'err' => '[ERR]', 'warn' => '[WARN]', 'info' => '[INFO]'];
        return ($colors[$type] ?? '') . ($icons[$type] ?? '') . " {$msg}\033[0m";
    }
    return "<span class='{$type}'>[" . strtoupper($type) . "] {$msg}</span>";
}

// ─── Database connection ─────────────────────────────────────────────────────

$host = '127.0.0.1';
$db   = 'rbi_engineering';
$user = 'root';
$pass = '';

echo "{$bold('Connecting to database...')}{$br}";

try {
    $pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    echo status('err', "Connection failed: " . $e->getMessage()) . $br;
    exit(1);
}

echo status('ok', "Connected to {$db}@{$host}") . $br;

// ─── Disable FK checks ──────────────────────────────────────────────────────

$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
echo $br . status('warn', "Foreign key checks DISABLED") . $br . $br;

// ─── Define clear operations ─────────────────────────────────────────────────
// Order: most-dependent tables first (reverse dependency order).
// Each entry: [table_name, condition | null (= TRUNCATE)]
//   null  -> TRUNCATE (clear all rows)
//   string -> DELETE WHERE condition (preserve system data)

$clearOps = [
    // ML / Analytics tables (no system defaults)
    ['integration_conflict_log',    null],
    ['integration_data_cache',      null],
    ['integration_field_mappings',  null],
    ['integration_sync_log',        null],
    ['operating_excursions',        null],
    ['pi_tag_mappings',             null],
    ['monte_carlo_results',         null],
    ['asset_clusters',              null],
    ['ml_predictions',              null],
    ['ml_models',                   null],
    ['risk_alerts',                 null],
    ['risk_scores',                 null],

    // IoT
    ['iot_sensor_readings',         null],
    ['iot_sensors',                  null],

    // Digital twin
    ['digital_twin_models',         null],

    // External inspection
    ['external_inspection_databases', null],

    // SCADA
    ['scada_data_feeds',            null],

    // Reports (preserve system templates)
    ['saved_reports',               null],

    // Audit / Activity (transactional)
    ['user_activity_log',           null],
    ['audit_trail',                 null],

    // Financial & Sensitivity
    ['financial_risk_models',       null],
    ['sensitivity_analyses',        null],

    // Risk Rankings
    ['risk_rankings',               null],

    // Inspection chain (reverse: findings -> tasks -> intervals -> plans)
    ['inspection_findings',         null],
    ['inspection_history',          null],
    ['work_priorities',             null],
    ['inspection_tasks',            null],
    ['inspection_intervals',        null],
    ['inspection_plans',            null],

    // Risk assessment chain
    ['consequence_of_failure',      null],
    ['probability_of_failure',      null],
    ['risk_assessments',            null],

    // Integrity analytics
    ['remaining_life_estimates',    null],
    ['corrosion_rate_history',      null],
    ['corrosion_rate_tracking',     null],

    // Damage mechanism assignments (keep the library)
    ['susceptibility_inputs',       null],
    ['damage_mechanism_assignments', null],

    // Corrosion circuits
    ['corrosion_circuit_assets',    null],
    ['corrosion_circuits',          null],

    // Operational & design data
    ['operational_data',            null],
    ['design_data',                 null],

    // Asset registry
    ['asset_registry',              null],

    // Equipment hierarchy: keep default site (id=1)
    ['equipment_hierarchy',         'id > 1'],

    // Users: keep default admin (id=1)
    ['users',                       'id > 1'],
];

// ─── Execute clears ──────────────────────────────────────────────────────────

echo "{$bold('Clearing sample data...')}{$br}{$br}";
$totalCleared = 0;

foreach ($clearOps as [$table, $condition]) {
    try {
        // Count before
        $before = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();

        if ($condition === null) {
            $pdo->exec("TRUNCATE TABLE `{$table}`");
            $removed = $before;
        } else {
            $removed = $pdo->exec("DELETE FROM `{$table}` WHERE {$condition}");
        }

        $totalCleared += $removed;

        if ($removed > 0) {
            echo status('ok', str_pad($table, 38) . ": {$removed} rows removed") . $br;
        } else {
            echo status('info', str_pad($table, 38) . ": already empty") . $br;
        }
    } catch (PDOException $e) {
        // Table may not exist
        $msg = $e->getMessage();
        if (strpos($msg, "doesn't exist") !== false) {
            echo status('warn', str_pad($table, 38) . ": table not found (skipped)") . $br;
        } else {
            echo status('err', str_pad($table, 38) . ": {$msg}") . $br;
        }
    }
}

// ─── Re-enable FK checks ────────────────────────────────────────────────────

$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
echo $br . status('ok', "Foreign key checks RE-ENABLED") . $br;

// ─── Verify preserved data ──────────────────────────────────────────────────

echo $br . "{$bold('=== Preserved System Data ===')}{$br}";

$preserved = [
    'roles'                => null,
    'permissions'          => null,
    'role_permissions'     => null,
    'damage_mechanisms'    => null,
    'risk_matrices'        => null,
    'risk_matrix_cells'    => null,
    'inspection_strategies' => null,
    'dashboards'           => null,
    'dashboard_widgets'    => null,
    'report_templates'     => null,
    'users'                => 'Default admin',
    'equipment_hierarchy'  => 'Default site',
];

foreach ($preserved as $table => $note) {
    try {
        $count = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
        $extra = $note ? " ({$note})" : '';
        echo status('info', str_pad($table, 35) . ": {$count} records retained{$extra}") . $br;
    } catch (PDOException $e) {
        echo status('warn', str_pad($table, 35) . ": table not found") . $br;
    }
}

// ─── Summary ─────────────────────────────────────────────────────────────────

echo $br . "{$bold('=== Summary ===')}{$br}";
echo status('ok', "Total rows removed: {$totalCleared}") . $br;
echo status('ok', "Sample data cleared successfully. System defaults preserved.") . $br;

if (!$isCli) {
    echo "</body></html>";
}
