<?php
// FILE: /app/core/Database.php

/**
 * Database - PDO wrapper for secure database connections
 *
 * Provides a singleton PDO connection with prepared statement support
 * for secure database operations.
 */
class Database
{
    private static $instance = null;
    private $connection;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
        $config = require __DIR__ . '/../../config/database.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['database']
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $this->connection = new PDO($dsn, $config['username'], $config['password'], $options);
        } catch (PDOException $e) {
            // Log error in production, show error in development
            if (getenv('APP_ENV') === 'development') {
                die('Database connection failed: ' . $e->getMessage());
            } else {
                error_log('Database connection failed: ' . $e->getMessage());
                die('Database connection failed. Please contact support.');
            }
        }
    }

    /**
     * Get the singleton Database instance
     *
     * @return Database
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the PDO connection
     *
     * @return PDO
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Execute a query with parameters (SELECT queries)
     *
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters to bind
     * @return array Result set
     */
    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Query failed: ' . $e->getMessage() . ' | SQL: ' . $sql);
            throw $e;
        }
    }

    /**
     * Execute a query and return a single row
     *
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters to bind
     * @return array|null Single row or null
     */
    public function queryOne($sql, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            error_log('Query failed: ' . $e->getMessage() . ' | SQL: ' . $sql);
            throw $e;
        }
    }

    /**
     * Execute a non-SELECT query (INSERT, UPDATE, DELETE)
     *
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters to bind
     * @return int Number of affected rows
     */
    public function execute($sql, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log('Execute failed: ' . $e->getMessage() . ' | SQL: ' . $sql);
            throw $e;
        }
    }

    /**
     * Get the last inserted ID
     *
     * @return int Last insert ID
     */
    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Start a database transaction
     */
    public function beginTransaction()
    {
        $this->connection->beginTransaction();
    }

    /**
     * Commit the current transaction
     */
    public function commit()
    {
        $this->connection->commit();
    }

    /**
     * Roll back the current transaction
     */
    public function rollBack()
    {
        $this->connection->rollBack();
    }

    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}

    /**
     * Prevent unserializing of the instance
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
