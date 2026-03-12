<?php
/**
 * Database - PDO Wrapper with prepared statement support
 *
 * Provides a fluent API for common CRUD operations while
 * enforcing prepared statements for all user-supplied data.
 */
class Database
{
    private PDO $pdo;
    private ?PDOStatement $stmt = null;

    public function __construct()
    {
        $this->pdo = DatabaseConnection::getInstance()->getConnection();
    }

    // ── Raw / Advanced ──────────────────────────────────────────────

    /**
     * Execute an arbitrary prepared statement
     *
     * @param  string $sql    SQL with named or positional placeholders
     * @param  array  $params Bind values
     * @return self
     */
    public function query(string $sql, array $params = []): self
    {
        try {
            $this->stmt = $this->pdo->prepare($sql);
            $this->stmt->execute($params);
        } catch (PDOException $e) {
            error_log('[RBI DB] Query error: ' . $e->getMessage() . ' | SQL: ' . $sql);
            throw new RuntimeException('Database query failed.', 0, $e);
        }
        return $this;
    }

    /**
     * Fetch a single row
     */
    public function fetch(): ?array
    {
        $row = $this->stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Fetch all rows
     */
    public function fetchAll(): array
    {
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch a single scalar value (first column of first row)
     */
    public function fetchColumn(): mixed
    {
        return $this->stmt->fetchColumn();
    }

    /**
     * Return the number of affected rows from the last statement
     */
    public function rowCount(): int
    {
        return $this->stmt->rowCount();
    }

    // ── CRUD Helpers ────────────────────────────────────────────────

    /**
     * INSERT a row and return the new auto-increment ID
     *
     * @param  string $table
     * @param  array  $data  Associative column => value
     * @return int           Last insert ID
     */
    public function insert(string $table, array $data): int
    {
        $columns      = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO `{$table}` ({$columns}) VALUES ({$placeholders})";

        try {
            $this->stmt = $this->pdo->prepare($sql);
            $this->stmt->execute(array_values($data));
            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log('[RBI DB] Insert error: ' . $e->getMessage());
            throw new RuntimeException('Database insert failed.', 0, $e);
        }
    }

    /**
     * UPDATE rows matching a WHERE clause
     *
     * @param  string $table
     * @param  array  $data        Columns to update
     * @param  string $where       WHERE clause with placeholders
     * @param  array  $whereParams Bind values for the WHERE clause
     * @return int                 Number of affected rows
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $setClauses = [];
        foreach (array_keys($data) as $col) {
            $setClauses[] = "{$col} = ?";
        }
        $sql = "UPDATE `{$table}` SET " . implode(', ', $setClauses) . " WHERE {$where}";

        try {
            $this->stmt = $this->pdo->prepare($sql);
            $this->stmt->execute(array_merge(array_values($data), $whereParams));
            return $this->stmt->rowCount();
        } catch (PDOException $e) {
            error_log('[RBI DB] Update error: ' . $e->getMessage());
            throw new RuntimeException('Database update failed.', 0, $e);
        }
    }

    /**
     * DELETE rows matching a WHERE clause
     *
     * @param  string $table
     * @param  string $where
     * @param  array  $params
     * @return int              Number of deleted rows
     */
    public function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM `{$table}` WHERE {$where}";

        try {
            $this->stmt = $this->pdo->prepare($sql);
            $this->stmt->execute($params);
            return $this->stmt->rowCount();
        } catch (PDOException $e) {
            error_log('[RBI DB] Delete error: ' . $e->getMessage());
            throw new RuntimeException('Database delete failed.', 0, $e);
        }
    }

    /**
     * Find a single row by primary key
     */
    public function find(string $table, int $id, string $primaryKey = 'id'): ?array
    {
        return $this->query(
            "SELECT * FROM `{$table}` WHERE `{$primaryKey}` = ? LIMIT 1",
            [$id]
        )->fetch();
    }

    /**
     * Find all rows with optional ordering and limit
     */
    public function findAll(string $table, string $orderBy = 'id ASC', int $limit = 0, int $offset = 0): array
    {
        $sql = "SELECT * FROM `{$table}` ORDER BY {$orderBy}";
        if ($limit > 0) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }
        return $this->query($sql)->fetchAll();
    }

    // ── Transactions ────────────────────────────────────────────────

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }

    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Run a callable inside a transaction; auto-commit or rollback.
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    // ── Utility ─────────────────────────────────────────────────────

    /**
     * Return the underlying PDO handle for edge cases
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
