<?php
/**
 * Database Connection Class
 * PDO singleton with helper methods for MySQL operations
 */

class Database {
    private static ?PDO $instance = null;
    private static array $config = [];

    /**
     * Get database configuration from .env file
     */
    private static function loadConfig(): array {
        if (!empty(self::$config)) {
            return self::$config;
        }

        $envFile = __DIR__ . '/../.env';
        if (!file_exists($envFile)) {
            throw new Exception('Database configuration file (.env) not found');
        }

        $env = [];
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }
            $eq = strpos($line, '=');
            if ($eq !== false) {
                $key = trim(substr($line, 0, $eq));
                $val = trim(substr($line, $eq + 1));
                $env[$key] = $val;
            }
        }

        self::$config = [
            'host' => $env['DB_HOST'] ?? 'localhost',
            'port' => $env['DB_PORT'] ?? '3306',
            'database' => $env['DB_DATABASE'] ?? 'ampmkp_db',
            'username' => $env['DB_USERNAME'] ?? 'root',
            'password' => $env['DB_PASSWORD'] ?? '',
            'charset' => $env['DB_CHARSET'] ?? 'utf8mb4',
        ];

        return self::$config;
    }

    /**
     * Get PDO instance (singleton pattern)
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $config = self::loadConfig();

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                self::$instance = new PDO($dsn, $config['username'], $config['password'], $options);
            } catch (PDOException $e) {
                throw new Exception('Database connection failed: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }

    /**
     * Execute a query with optional parameters
     */
    public static function query(string $sql, array $params = []): PDOStatement {
        $pdo = self::getInstance();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch all rows from a query
     */
    public static function fetchAll(string $sql, array $params = []): array {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * Fetch a single row from a query
     */
    public static function fetchOne(string $sql, array $params = []): ?array {
        $result = self::query($sql, $params)->fetch();
        return $result ?: null;
    }

    /**
     * Fetch a single column value from a query
     */
    public static function fetchColumn(string $sql, array $params = [], int $column = 0) {
        return self::query($sql, $params)->fetchColumn($column);
    }

    /**
     * Insert a record and return the last insert ID
     */
    public static function insert(string $table, array $data): int|string {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $table,
            implode('`, `', $columns),
            implode(', ', $placeholders)
        );

        self::query($sql, array_values($data));
        return self::getInstance()->lastInsertId();
    }

    /**
     * Insert a record with a custom ID (for orders with string IDs)
     */
    public static function insertWithId(string $table, array $data): bool {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $table,
            implode('`, `', $columns),
            implode(', ', $placeholders)
        );

        self::query($sql, array_values($data));
        return true;
    }

    /**
     * Update records in a table
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int {
        $setClauses = [];
        $values = [];

        foreach ($data as $column => $value) {
            $setClauses[] = "`$column` = ?";
            $values[] = $value;
        }

        $sql = sprintf(
            'UPDATE `%s` SET %s WHERE %s',
            $table,
            implode(', ', $setClauses),
            $where
        );

        $stmt = self::query($sql, array_merge($values, $whereParams));
        return $stmt->rowCount();
    }

    /**
     * Delete records from a table
     */
    public static function delete(string $table, string $where, array $params = []): int {
        $sql = sprintf('DELETE FROM `%s` WHERE %s', $table, $where);
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Check if a record exists
     */
    public static function exists(string $table, string $where, array $params = []): bool {
        $sql = sprintf('SELECT 1 FROM `%s` WHERE %s LIMIT 1', $table, $where);
        return self::fetchOne($sql, $params) !== null;
    }

    /**
     * Count records in a table
     */
    public static function count(string $table, string $where = '1=1', array $params = []): int {
        $sql = sprintf('SELECT COUNT(*) FROM `%s` WHERE %s', $table, $where);
        return (int) self::fetchColumn($sql, $params);
    }

    /**
     * Begin a transaction
     */
    public static function beginTransaction(): bool {
        return self::getInstance()->beginTransaction();
    }

    /**
     * Commit a transaction
     */
    public static function commit(): bool {
        return self::getInstance()->commit();
    }

    /**
     * Rollback a transaction
     */
    public static function rollback(): bool {
        return self::getInstance()->rollBack();
    }

    /**
     * Check if a transaction is active
     */
    public static function inTransaction(): bool {
        return self::getInstance()->inTransaction();
    }

    /**
     * Execute a callback within a transaction
     */
    public static function transaction(callable $callback) {
        self::beginTransaction();
        try {
            $result = $callback();
            self::commit();
            return $result;
        } catch (Exception $e) {
            self::rollback();
            throw $e;
        }
    }

    /**
     * Escape a value for use in LIKE queries
     */
    public static function escapeLike(string $value): string {
        return str_replace(['%', '_'], ['\\%', '\\_'], $value);
    }

    /**
     * Build a WHERE IN clause
     */
    public static function buildInClause(array $values): array {
        $placeholders = array_fill(0, count($values), '?');
        return [
            'clause' => '(' . implode(', ', $placeholders) . ')',
            'params' => array_values($values)
        ];
    }

    /**
     * Get table column information
     */
    public static function getTableColumns(string $table): array {
        $sql = "SHOW COLUMNS FROM `$table`";
        return self::fetchAll($sql);
    }

    /**
     * Reset the connection (useful for long-running scripts)
     */
    public static function reset(): void {
        self::$instance = null;
    }

    /**
     * Test database connection
     */
    public static function testConnection(): bool {
        try {
            self::getInstance();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get the storage backend setting
     */
    public static function getStorageBackend(): string {
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, 'STORAGE_BACKEND=') === 0) {
                    return trim(substr($line, strlen('STORAGE_BACKEND=')));
                }
            }
        }
        return 'json'; // Default to JSON for backward compatibility
    }

    /**
     * Check if MySQL backend is enabled
     */
    public static function isMySQLEnabled(): bool {
        return self::getStorageBackend() === 'mysql';
    }
}
