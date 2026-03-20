<?php
/**
 * System Health Check - RBI Engineering Suite
 * Lightweight endpoint for load balancers and monitoring tools.
 * Does not load the full application stack to avoid session locks.
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$startTime = microtime(true);
$health = [
    'status' => 'pass',
    'timestamp' => gmdate('c'),
    'checks' => [],
    'errors' => []
];

// 1. Check Database Connection
try {
    require_once __DIR__ . '/config/database.php';
    
    $db = DatabaseConnection::getInstance()->getConnection();
    $stmt = $db->query("SELECT 1");
    
    if ($stmt) {
        $health['checks']['database'] = 'pass';
    } else {
        throw new Exception("Database query failed to return a result.");
    }
} catch (\Throwable $e) {
    $health['status'] = 'fail';
    $health['checks']['database'] = 'fail';
    $health['errors'][] = 'Database connection error: ' . $e->getMessage();
}

// 2. Check essential directories (Uploads & Logs)
$dirs = [];
foreach ($dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path) && is_writable($path)) {
        $health['checks']["dir_{$dir}"] = 'pass';
    } else {
        $health['status'] = 'fail';
        $health['checks']["dir_{$dir}"] = 'fail';
        $health['errors'][] = "Directory missing or not writable: {$dir}";
    }
}

$health['duration_ms'] = round((microtime(true) - $startTime) * 1000, 2);

http_response_code($health['status'] === 'pass' ? 200 : 503);

if (empty($health['errors'])) {
    unset($health['errors']);
}

echo json_encode($health, JSON_PRETTY_PRINT);