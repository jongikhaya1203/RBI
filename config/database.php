<?php
/**
 * Database Configuration - RBI Engineering Suite
 * Singleton PDO connection manager for MySQL/MariaDB
 */

defined('DB_HOST')     || define('DB_HOST', '127.0.0.1');
defined('DB_PORT')     || define('DB_PORT', '3306');
defined('DB_NAME')     || define('DB_NAME', 'rbi_engineering');
defined('DB_USER')     || define('DB_USER', 'root');
defined('DB_PASS')     || define('DB_PASS', '');
defined('DB_CHARSET')  || define('DB_CHARSET', 'utf8mb4');

/**
 * DatabaseConnection - Singleton wrapper around PDO
 */
class DatabaseConnection
{
    private static ?DatabaseConnection $instance = null;
    private ?PDO $pdo = null;

    /** @var array<string, mixed> Default PDO options */
    private array $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        PDO::ATTR_PERSISTENT         => false,
    ];

    private function __construct()
    {
        $this->connect();
    }

    // Prevent cloning and unserialization
    private function __clone() {}
    public function __wakeup()
    {
        throw new \RuntimeException('Cannot unserialize singleton');
    }

    /**
     * Get the singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Establish the PDO connection
     */
    private function connect(): void
    {
        if ($this->pdo !== null) {
            return;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $this->options);
        } catch (PDOException $e) {
            error_log('[RBI DB] Connection failed: ' . $e->getMessage());
            throw new RuntimeException(
                'Database connection failed. Please check your configuration.',
                (int) $e->getCode(),
                $e
            );
        }
    }

    /**
     * Return the raw PDO handle
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * Close the connection explicitly
     */
    public function close(): void
    {
        $this->pdo = null;
        self::$instance = null;
    }
}
