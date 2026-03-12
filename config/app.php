<?php
/**
 * Application Configuration - RBI Engineering Suite
 */

// ── Application Identity ────────────────────────────────────────────
define('APP_NAME',    'RBI Engineering Suite');
define('APP_VERSION', '1.0.0');
define('APP_ENV',     'development');          // development | staging | production

// ── Base URL & Paths ────────────────────────────────────────────────
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('BASE_URL',    $protocol . '://' . $host . '/rbi');
define('BASE_PATH',   dirname(__DIR__));       // C:\xampp\htdocs\rbi

define('CONFIG_PATH',  BASE_PATH . '/config');
define('CLASSES_PATH', BASE_PATH . '/classes');
define('INCLUDES_PATH',BASE_PATH . '/includes');
define('ASSETS_PATH',  BASE_PATH . '/assets');
define('UPLOADS_PATH', BASE_PATH . '/uploads');
define('LOGS_PATH',    BASE_PATH . '/logs');

// ── Timezone ────────────────────────────────────────────────────────
define('APP_TIMEZONE', 'UTC');
date_default_timezone_set(APP_TIMEZONE);

// ── Session Configuration ───────────────────────────────────────────
define('SESSION_NAME',     'RBI_SESSION');
define('SESSION_LIFETIME', 3600);              // 1 hour
define('SESSION_PATH',     '/');
define('SESSION_SECURE',   false);             // set true in production with HTTPS
define('SESSION_HTTPONLY',  true);

// ── Security ────────────────────────────────────────────────────────
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_MIN_LEN', 8);

// ── Pagination ──────────────────────────────────────────────────────
define('DEFAULT_PAGE_SIZE', 25);
define('MAX_PAGE_SIZE',     100);

// ── File Uploads ────────────────────────────────────────────────────
define('MAX_UPLOAD_SIZE',      10 * 1024 * 1024); // 10 MB
define('ALLOWED_UPLOAD_TYPES', ['pdf','csv','xlsx','xls','jpg','jpeg','png','doc','docx']);

// ── RBI-Specific Defaults ───────────────────────────────────────────
define('DEFAULT_CONFIDENCE_LEVEL', 0.90);
define('DEFAULT_PLAN_HORIZON_YEARS', 5);
define('RISK_MATRIX_ROWS', 5);                 // 5 x 5 matrix
define('RISK_MATRIX_COLS', 5);

// ── Logging ─────────────────────────────────────────────────────────
define('LOG_LEVEL', 'DEBUG');                  // DEBUG | INFO | WARNING | ERROR

// ── Start Session ───────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.name',            SESSION_NAME);
    ini_set('session.gc_maxlifetime',  SESSION_LIFETIME);
    ini_set('session.cookie_lifetime', SESSION_LIFETIME);
    ini_set('session.cookie_path',     SESSION_PATH);
    ini_set('session.cookie_secure',   SESSION_SECURE ? '1' : '0');
    ini_set('session.cookie_httponly',  SESSION_HTTPONLY ? '1' : '0');
    ini_set('session.use_strict_mode', '1');
    session_start();
}

// ── Error Reporting ─────────────────────────────────────────────────
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
ini_set('log_errors', '1');
ini_set('error_log', LOGS_PATH . '/php_errors.log');

// ── Autoload Classes ────────────────────────────────────────────────
spl_autoload_register(function (string $class): void {
    $file = CLASSES_PATH . '/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// ── Global Exception Handler (DB connection errors) ────────────────
set_exception_handler(function (Throwable $e): void {
    $isDbError = (
        stripos($e->getMessage(), 'Database connection failed') !== false ||
        stripos($e->getMessage(), 'SQLSTATE') !== false ||
        stripos($e->getMessage(), 'No connection could be made') !== false ||
        ($e->getPrevious() && stripos($e->getPrevious()->getMessage(), 'SQLSTATE') !== false)
    );
    if ($isDbError) {
        http_response_code(503);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Database Unavailable - RBI Engineering Suite</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
            <style>
                body { min-height: 100vh; background: linear-gradient(135deg, #0f2440 0%, #1a3a5c 50%, #2c5f8a 100%); display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', system-ui, sans-serif; }
                .error-card { background: #fff; border-radius: 16px; padding: 40px; max-width: 560px; box-shadow: 0 20px 60px rgba(0,0,0,.3); }
                .brand-icon { width: 64px; height: 64px; background: linear-gradient(135deg, #e74c3c, #c0392b); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; color: #fff; margin: 0 auto 16px; }
            </style>
        </head>
        <body>
            <div class="error-card">
                <div class="text-center mb-3">
                    <div class="brand-icon"><i class="bi bi-database-x"></i></div>
                    <h4 class="fw-bold" style="color:#c0392b">Database Connection Failed</h4>
                    <p class="text-muted">MySQL is not running or the database has not been created yet.</p>
                </div>
                <div class="alert alert-warning small mb-3">
                    <strong>Setup Instructions:</strong>
                    <ol class="mt-2 mb-0">
                        <li>Open <strong>XAMPP Control Panel</strong> (<code>C:\xampp\xampp-control.exe</code>)</li>
                        <li>Click <strong>Start</strong> next to <strong>MySQL</strong></li>
                        <li>Click <strong>Start</strong> next to <strong>Apache</strong></li>
                        <li>Open <a href="http://localhost/phpmyadmin" target="_blank"><strong>phpMyAdmin</strong></a></li>
                        <li>Click <strong>Import</strong> tab → upload these files in order:
                            <ul>
                                <li><code>database/schema.sql</code></li>
                                <li><code>database/ml_tables.sql</code></li>
                                <li><code>database/integration_tables.sql</code></li>
                            </ul>
                        </li>
                        <li>Refresh this page</li>
                    </ol>
                </div>
                <div class="text-center">
                    <a href="" class="btn btn-primary"><i class="bi bi-arrow-clockwise me-2"></i>Retry Connection</a>
                </div>
                <div class="text-center mt-3">
                    <small class="text-muted">RBI Engineering Suite v<?= defined('APP_VERSION') ? APP_VERSION : '1.0.0' ?></small>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
    // For non-DB errors, show generic error
    http_response_code(500);
    if (defined('APP_ENV') && APP_ENV === 'development') {
        echo '<h1>Error</h1><pre>' . htmlspecialchars($e->getMessage()) . "\n" . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        echo '<h1>Internal Server Error</h1><p>An unexpected error occurred.</p>';
    }
    exit;
});

// ── Bootstrap Includes ──────────────────────────────────────────────
require_once CONFIG_PATH  . '/database.php';
require_once INCLUDES_PATH . '/functions.php';
