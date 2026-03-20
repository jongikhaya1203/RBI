<?php
/**
 * Simple Database Migration Script
 * Run this via CLI: php migrate.php
 * Or access via browser: http://localhost/rbi/migrate.php
 */

$isCli = php_sapi_name() === 'cli';
if (!$isCli) {
    echo "<pre>";
}

echo "Starting Database Migration...\n\n";

try {
    // Connect to MySQL server (without selecting a DB, as schema.sql creates it)
    // Adjust credentials if your XAMPP setup uses a password
    $db = new PDO("mysql:host=localhost;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $files = [
        __DIR__ . '/database/schema.sql',
        __DIR__ . '/database/ml_tables.sql',
        __DIR__ . '/database/integration_tables.sql',
        __DIR__ . '/database/seed_data.sql'
    ];

    foreach ($files as $file) {
        if (file_exists($file)) {
            echo "Executing " . basename($file) . "...\n";
            $sql = file_get_contents($file);
            $db->exec($sql);
            echo "Successfully executed " . basename($file) . "\n\n";
        } else {
            echo "Error: File not found - " . basename($file) . "\n\n";
        }
    }

    echo "Migration complete! You can now log into the application.\n";
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}