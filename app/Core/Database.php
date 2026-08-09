<?php

/**
 * Database - PDO Database Connection Singleton
 * 
 * Provides a single point of access to the database connection.
 * Wraps PDO with additional convenience methods for common operations.
 * 
 * @package App\Core
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use App\Helpers\Logger;
use App\Exceptions\AppException;

class Database
{
    /**
     * Singleton instance
     * @var Database|null
     */
    private static ?Database $instance = null;

    /**
     * PDO connection instance
     * @var PDO|null
     */
    private ?PDO $connection = null;

    /**
     * Transaction depth for nested transactions
     * @var int
     */
    private int $transactionDepth = 0;

    /**
     * Query log for debugging
     * @var array
     */
    private array $queryLog = [];

    /**
     * Whether to log queries
     * @var bool
     */
    private bool $logQueries = false;

    /**
     * Private constructor for singleton pattern
     */
    private function __construct()
    {
        $this->connect();
    }

    /**
     * Get singleton instance
     * 
     * @return Database
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the raw PDO connection
     * 
     * @return PDO
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }

    /**
     * Connect to the database
     * 
     * @return void
     * @throws AppException If connection fails
     */
    private function connect(): void
    {
        // Get configuration from environment
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '3306';
        $dbname = getenv('DB_NAME') ?: 'soft_sam';
        $username = getenv('DB_USER') ?: 'root';
        $password = getenv('DB_PASS') ?: '';
        $charset = getenv('DB_CHARSET') ?: 'utf8mb4';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

        try {
            $this->connection = new PDO(
                $dsn,
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}",
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                ]
            );

            // Enable query logging in debug mode
            $this->logQueries = getenv('APP_DEBUG') === 'true';

            Logger::debug('Database connection established', [
                'host' => $host,
                'database' => $dbname
            ]);
        } catch (PDOException $e) {
            Logger::error('Database connection failed', [
                'error' => $e->getMessage(),
                'host' => $host,
                'database' => $dbname
            ]);

            throw new AppException(
                'Database connection failed',
                500,
                ['error' => getenv('APP_DEBUG') === 'true' ? $e->getMessage() : 'Connection error']
            );
        }
    }

    /**
     * Prepare and execute a query
     * 
     * @param string $sql SQL query
     * @param array $params Parameters to bind
     * @return PDOStatement
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $startTime = microtime(true);

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        if ($this->logQueries) {
            $this->queryLog[] = [
                'sql' => $sql,
                'params' => $params,
                'duration_ms' => $duration
            ];

            Logger::debug('Query executed', [
                'sql' => $sql,
                'params' => $params,
                'duration_ms' => $duration
            ]);
        }

        return $stmt;
    }

    /**
     * Execute a query and return affected rows count
     * 
     * @param string $sql SQL query
     * @param array $params Parameters to bind
     * @return int Number of affected rows
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Execute a query and fetch all results
     * 
     * @param string $sql SQL query
     * @param array $params Parameters to bind
     * @return array
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Execute a query and fetch single row
     * 
     * @param string $sql SQL query
     * @param array $params Parameters to bind
     * @return array|false
     */
    public function fetch(string $sql, array $params = []): array|false
    {
        return $this->query($sql, $params)->fetch();
    }

    /**
     * Execute a query and fetch single column
     * 
     * @param string $sql SQL query
     * @param array $params Parameters to bind
     * @return mixed
     */
    public function fetchColumn(string $sql, array $params = []): mixed
    {
        return $this->query($sql, $params)->fetchColumn();
    }

    /**
     * Insert a row into a table
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return int|string Last insert ID
     */
    public function insert(string $table, array $data): int|string
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            "INSERT INTO `%s` (`%s`) VALUES (%s)",
            $table,
            implode('`, `', $columns),
            implode(', ', $placeholders)
        );

        $this->query($sql, array_values($data));

        return $this->connection->lastInsertId();
    }

    /**
     * Update rows in a table
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @param string $where WHERE clause (without "WHERE")
     * @param array $whereParams Parameters for WHERE clause
     * @return int Number of affected rows
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $setClauses = [];
        foreach (array_keys($data) as $column) {
            $setClauses[] = "`{$column}` = ?";
        }

        $sql = sprintf(
            "UPDATE `%s` SET %s WHERE %s",
            $table,
            implode(', ', $setClauses),
            $where
        );

        $params = array_merge(array_values($data), $whereParams);

        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Delete rows from a table
     * 
     * @param string $table Table name
     * @param string $where WHERE clause (without "WHERE")
     * @param array $params Parameters for WHERE clause
     * @return int Number of affected rows
     */
    public function delete(string $table, string $where, array $params = []): int
    {
        $sql = sprintf("DELETE FROM `%s` WHERE %s", $table, $where);
        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Begin a transaction
     * 
     * @return bool
     */
    public function beginTransaction(): bool
    {
        if ($this->transactionDepth === 0) {
            $result = $this->connection->beginTransaction();
        } else {
            $result = $this->connection->exec("SAVEPOINT LEVEL{$this->transactionDepth}") !== false;
        }

        $this->transactionDepth++;
        return $result;
    }

    /**
     * Commit a transaction
     * 
     * @return bool
     */
    public function commit(): bool
    {
        $this->transactionDepth--;

        if ($this->transactionDepth === 0) {
            return $this->connection->commit();
        } else {
            return $this->connection->exec("RELEASE SAVEPOINT LEVEL{$this->transactionDepth}") !== false;
        }
    }

    /**
     * Rollback a transaction
     * 
     * @return bool
     */
    public function rollBack(): bool
    {
        $this->transactionDepth--;

        if ($this->transactionDepth === 0) {
            return $this->connection->rollBack();
        } else {
            return $this->connection->exec("ROLLBACK TO SAVEPOINT LEVEL{$this->transactionDepth}") !== false;
        }
    }

    /**
     * Execute a callback within a transaction
     * 
     * @param callable $callback The callback to execute
     * @return mixed The callback's return value
     * @throws \Exception If callback throws, transaction is rolled back
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * Get the last inserted ID
     * 
     * @return string
     */
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Quote a string for use in a query
     * 
     * @param string $value The value to quote
     * @return string
     */
    public function quote(string $value): string
    {
        return $this->connection->quote($value);
    }

    /**
     * Execute a raw SQL statement
     * 
     * @param string $sql SQL statement
     * @return int|false Number of affected rows or false on failure
     */
    public function exec(string $sql): int|false
    {
        $startTime = microtime(true);

        $result = $this->connection->exec($sql);

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        if ($this->logQueries) {
            $this->queryLog[] = [
                'sql' => $sql,
                'params' => [],
                'duration_ms' => $duration
            ];

            Logger::debug('Exec executed', [
                'sql' => $sql,
                'duration_ms' => $duration
            ]);
        }

        return $result;
    }

    /**
     * Get query log
     * 
     * @return array
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    /**
     * Clear query log
     * 
     * @return void
     */
    public function clearQueryLog(): void
    {
        $this->queryLog = [];
    }

    /**
     * Check if connected to database
     * 
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->connection !== null;
    }

    /**
     * Close the connection
     * 
     * @return void
     */
    public function disconnect(): void
    {
        $this->connection = null;
        self::$instance = null;
    }

    /**
     * Prevent cloning of singleton
     */
    private function __clone() {}

    /**
     * Prevent unserialization of singleton
     * 
     * @throws \Exception
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}
