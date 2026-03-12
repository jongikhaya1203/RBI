<?php
/**
 * RBI Engineering Suite — Sample Data Loader
 *
 * Creates demo users (with proper password hashing) then loads sample_data.sql.
 * Safe to re-run: uses INSERT IGNORE / ON DUPLICATE KEY UPDATE.
 *
 * Usage:
 *   CLI:     php load_sample_data.php
 *   Browser: http://localhost/rbi/database/load_sample_data.php
 */

// Detect environment
$isCli = (php_sapi_name() === 'cli');
$br    = $isCli ? "\n" : "<br>\n";
$bold  = function ($t) use ($isCli) { return $isCli ? "\033[1m{$t}\033[0m" : "<strong>{$t}</strong>"; };

if (!$isCli) {
    echo "<!DOCTYPE html><html><head><title>RBI Sample Data Loader</title>"
       . "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}"
       . ".ok{color:#4ec9b0;}.err{color:#f44747;}.warn{color:#dcdcaa;}.info{color:#569cd6;}"
       . "h2{color:#ce9178;}</style></head><body>";
    echo "<h2>RBI Engineering Suite &mdash; Sample Data Loader</h2>";
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
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    $msg = "Database connection failed: " . $e->getMessage();
    echo $isCli ? "\033[31m{$msg}\033[0m\n" : "<span class='err'>{$msg}</span>{$br}";
    exit(1);
}

echo status('ok', "Connected to {$db}@{$host}") . $br;

// ─── Helper functions ────────────────────────────────────────────────────────

function status($type, $msg) {
    global $isCli, $br;
    if ($isCli) {
        $colors = ['ok' => "\033[32m", 'err' => "\033[31m", 'warn' => "\033[33m", 'info' => "\033[34m"];
        $icons  = ['ok' => '[OK]', 'err' => '[ERR]', 'warn' => '[WARN]', 'info' => '[INFO]'];
        return ($colors[$type] ?? '') . ($icons[$type] ?? '') . " {$msg}\033[0m";
    }
    return "<span class='{$type}'>[" . strtoupper($type) . "] {$msg}</span>";
}

// ─── Step 1: Create demo users ───────────────────────────────────────────────

echo $br . "{$bold('Step 1: Creating demo users...')}{$br}";

$demoPassword = 'demo123';
$hash = password_hash($demoPassword, PASSWORD_DEFAULT);

$demoUsers = [
    [
        'username'   => 'john.engineer',
        'email'      => 'john.engineer@petrotech.com',
        'first_name' => 'John',
        'last_name'  => 'Martinez',
        'job_title'  => 'Senior RBI Engineer',
        'department' => 'Asset Integrity',
        'phone'      => '+1-555-0101',
        'role_key'   => 'engineer',
        'status'     => 'active',
    ],
    [
        'username'   => 'sarah.inspector',
        'email'      => 'sarah.inspector@petrotech.com',
        'first_name' => 'Sarah',
        'last_name'  => 'Chen',
        'job_title'  => 'Lead Inspector — API 510/570',
        'department' => 'Inspection Services',
        'phone'      => '+1-555-0102',
        'role_key'   => 'inspector',
        'status'     => 'active',
    ],
    [
        'username'   => 'mike.manager',
        'email'      => 'mike.manager@petrotech.com',
        'first_name' => 'Mike',
        'last_name'  => 'Thompson',
        'job_title'  => 'Integrity Manager',
        'department' => 'Asset Integrity',
        'phone'      => '+1-555-0103',
        'role_key'   => 'engineer',
        'status'     => 'active',
    ],
    [
        'username'   => 'lisa.viewer',
        'email'      => 'lisa.viewer@petrotech.com',
        'first_name' => 'Lisa',
        'last_name'  => 'Patel',
        'job_title'  => 'Operations Supervisor',
        'department' => 'Operations',
        'phone'      => '+1-555-0104',
        'role_key'   => 'viewer',
        'status'     => 'active',
    ],
];

$userStmt = $pdo->prepare("
    INSERT INTO `users` (`username`, `email`, `password_hash`, `first_name`, `last_name`,
                          `job_title`, `department`, `phone`, `role_id`, `status`)
    SELECT :username, :email, :password_hash, :first_name, :last_name,
           :job_title, :department, :phone, r.id, :status
    FROM `roles` r WHERE r.role_key = :role_key
    ON DUPLICATE KEY UPDATE
        password_hash = VALUES(password_hash),
        first_name    = VALUES(first_name),
        last_name     = VALUES(last_name),
        job_title     = VALUES(job_title),
        department    = VALUES(department),
        phone         = VALUES(phone),
        status        = VALUES(status)
");

$usersCreated = 0;
foreach ($demoUsers as $u) {
    try {
        $userStmt->execute([
            ':username'      => $u['username'],
            ':email'         => $u['email'],
            ':password_hash' => $hash,
            ':first_name'    => $u['first_name'],
            ':last_name'     => $u['last_name'],
            ':job_title'     => $u['job_title'],
            ':department'    => $u['department'],
            ':phone'         => $u['phone'],
            ':role_key'      => $u['role_key'],
            ':status'        => $u['status'],
        ]);
        $usersCreated++;
        echo status('ok', "User: {$u['username']} ({$u['email']}) — role: {$u['role_key']}") . $br;
    } catch (PDOException $e) {
        echo status('err', "User {$u['username']}: " . $e->getMessage()) . $br;
    }
}

echo status('info', "Users processed: {$usersCreated}/" . count($demoUsers) . " (password: {$demoPassword})") . $br;

// ─── Step 2: Load sample_data.sql ────────────────────────────────────────────

echo $br . "{$bold('Step 2: Loading sample_data.sql...')}{$br}";

$sqlFile = __DIR__ . '/sample_data.sql';

if (!file_exists($sqlFile)) {
    echo status('err', "File not found: {$sqlFile}") . $br;
    exit(1);
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    echo status('err', "Could not read {$sqlFile}") . $br;
    exit(1);
}

echo status('info', "Read " . number_format(strlen($sql)) . " bytes from sample_data.sql") . $br;

// Split into individual statements (semicolons not inside quotes)
// Simple approach: split on ";\n" to avoid splitting inside VALUES
$statements = preg_split('/;\s*\n/', $sql);
$totalStmts  = 0;
$successStmts = 0;
$errorStmts   = 0;
$skipStmts    = 0;
$errors       = [];

foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if (empty($stmt)) continue;

    // Skip USE and pure comments
    if (preg_match('/^\s*(--|\/\*|USE\s|SET\s+@)/i', $stmt)) {
        // Still execute SET statements
        if (preg_match('/^\s*SET\s/i', $stmt)) {
            try {
                $pdo->exec($stmt);
            } catch (PDOException $e) {
                // Silently continue for SET statements
            }
        }
        $skipStmts++;
        continue;
    }

    $totalStmts++;

    try {
        $affected = $pdo->exec($stmt);
        $successStmts++;

        // Log progress for INSERT statements
        if (preg_match('/INSERT\s+IGNORE\s+INTO\s+`?(\w+)`?/i', $stmt, $m)) {
            $rows = $affected ?: 0;
            echo status('ok', "Loaded: {$m[1]} ({$rows} rows affected)") . $br;
        } elseif (preg_match('/^\s*SET\b/i', $stmt)) {
            // silently pass SET statements
        }
    } catch (PDOException $e) {
        $errorStmts++;
        $shortMsg = substr($e->getMessage(), 0, 120);
        // Try to identify the table
        $table = '?';
        if (preg_match('/INTO\s+`?(\w+)`?/i', $stmt, $m)) {
            $table = $m[1];
        }
        echo status('err', "{$table}: {$shortMsg}") . $br;
        $errors[] = ['table' => $table, 'error' => $e->getMessage()];
    }
}

// ─── Summary ─────────────────────────────────────────────────────────────────

echo $br . "{$bold('=== Summary ===')}{$br}";
echo status('info', "SQL statements executed: {$successStmts}") . $br;
echo status($errorStmts > 0 ? 'warn' : 'ok', "Errors: {$errorStmts}") . $br;
echo status('info', "Skipped (comments/USE): {$skipStmts}") . $br;

// Quick data counts
$tables = [
    'users', 'equipment_hierarchy', 'asset_registry', 'design_data',
    'operational_data', 'corrosion_circuits', 'corrosion_circuit_assets',
    'damage_mechanism_assignments', 'risk_assessments', 'probability_of_failure',
    'consequence_of_failure', 'inspection_plans', 'inspection_tasks',
    'inspection_findings', 'corrosion_rate_tracking', 'corrosion_rate_history',
    'remaining_life_estimates', 'risk_scores', 'risk_alerts',
    'financial_risk_models', 'risk_rankings',
];

echo $br . "{$bold('=== Record Counts ===')}{$br}";
foreach ($tables as $tbl) {
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM `{$tbl}`")->fetchColumn();
        echo status('info', str_pad($tbl, 35) . ": {$count} records") . $br;
    } catch (PDOException $e) {
        echo status('warn', str_pad($tbl, 35) . ": table not found") . $br;
    }
}

if ($errorStmts > 0) {
    echo $br . "{$bold('=== Error Details ===')}{$br}";
    foreach ($errors as $err) {
        echo status('err', "[{$err['table']}] {$err['error']}") . $br;
    }
}

echo $br . status('ok', "Sample data loading complete!") . $br;
echo status('info', "Demo login credentials:") . $br;
echo "  john.engineer@petrotech.com  / demo123  (Engineer)" . $br;
echo "  sarah.inspector@petrotech.com / demo123  (Inspector)" . $br;
echo "  mike.manager@petrotech.com   / demo123  (Engineer)" . $br;
echo "  lisa.viewer@petrotech.com    / demo123  (Viewer)" . $br;

if (!$isCli) {
    echo "</body></html>";
}
