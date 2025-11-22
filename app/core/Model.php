<?php
// FILE: /app/core/Model.php

/**
 * Model - Base model class for database operations
 *
 * Provides common CRUD operations and query building methods.
 * All models should extend this class.
 */
class Model
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';

    /**
     * Constructor - initializes database connection
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find all records with optional conditions
     *
     * @param array $conditions WHERE conditions
     * @param string $orderBy ORDER BY clause
     * @param int $limit LIMIT value
     * @param int $offset OFFSET value
     * @return array Records
     */
    public function findAll($conditions = [], $orderBy = null, $limit = null, $offset = null)
    {
        $sql = "SELECT * FROM `{$this->table}`";
        $params = [];

        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "`$key` = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }

        if ($limit) {
            $sql .= " LIMIT $limit";
        }

        if ($offset) {
            $sql .= " OFFSET $offset";
        }

        return $this->db->query($sql, $params);
    }

    /**
     * Find a single record by ID
     *
     * @param int $id Primary key value
     * @return array|null Record or null
     */
    public function findById($id)
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ? LIMIT 1";
        return $this->db->queryOne($sql, [$id]);
    }

    /**
     * Find a single record by conditions
     *
     * @param array $conditions WHERE conditions
     * @return array|null Record or null
     */
    public function findOne($conditions = [])
    {
        $sql = "SELECT * FROM `{$this->table}`";
        $params = [];

        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "`$key` = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " LIMIT 1";

        return $this->db->queryOne($sql, $params);
    }

    /**
     * Insert a new record
     *
     * @param array $data Associative array of column => value
     * @return int Last inserted ID
     */
    public function insert($data)
    {
        $columns = array_keys($data);
        $values = array_values($data);

        $columnList = '`' . implode('`, `', $columns) . '`';
        $placeholders = implode(', ', array_fill(0, count($values), '?'));

        $sql = "INSERT INTO `{$this->table}` ($columnList) VALUES ($placeholders)";

        $this->db->execute($sql, $values);
        return $this->db->lastInsertId();
    }

    /**
     * Update a record by ID
     *
     * @param int $id Primary key value
     * @param array $data Associative array of column => value
     * @return int Number of affected rows
     */
    public function update($id, $data)
    {
        $set = [];
        $params = [];

        foreach ($data as $key => $value) {
            $set[] = "`$key` = ?";
            $params[] = $value;
        }

        $params[] = $id;

        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $set) .
               " WHERE `{$this->primaryKey}` = ?";

        return $this->db->execute($sql, $params);
    }

    /**
     * Update records by conditions
     *
     * @param array $data Data to update
     * @param array $conditions WHERE conditions
     * @return int Number of affected rows
     */
    public function updateWhere($data, $conditions)
    {
        $set = [];
        $params = [];

        foreach ($data as $key => $value) {
            $set[] = "`$key` = ?";
            $params[] = $value;
        }

        $where = [];
        foreach ($conditions as $key => $value) {
            $where[] = "`$key` = ?";
            $params[] = $value;
        }

        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $set) .
               " WHERE " . implode(' AND ', $where);

        return $this->db->execute($sql, $params);
    }

    /**
     * Delete a record by ID
     *
     * @param int $id Primary key value
     * @return int Number of affected rows
     */
    public function delete($id)
    {
        $sql = "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?";
        return $this->db->execute($sql, [$id]);
    }

    /**
     * Delete records by conditions
     *
     * @param array $conditions WHERE conditions
     * @return int Number of affected rows
     */
    public function deleteWhere($conditions)
    {
        $where = [];
        $params = [];

        foreach ($conditions as $key => $value) {
            $where[] = "`$key` = ?";
            $params[] = $value;
        }

        $sql = "DELETE FROM `{$this->table}` WHERE " . implode(' AND ', $where);
        return $this->db->execute($sql, $params);
    }

    /**
     * Count records with optional conditions
     *
     * @param array $conditions WHERE conditions
     * @return int Count
     */
    public function count($conditions = [])
    {
        $sql = "SELECT COUNT(*) as count FROM `{$this->table}`";
        $params = [];

        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "`$key` = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $result = $this->db->queryOne($sql, $params);
        return (int) $result['count'];
    }

    /**
     * Execute a raw query
     *
     * @param string $sql SQL query
     * @param array $params Parameters
     * @return array Results
     */
    public function query($sql, $params = [])
    {
        return $this->db->query($sql, $params);
    }

    /**
     * Execute a raw query and return one result
     *
     * @param string $sql SQL query
     * @param array $params Parameters
     * @return array|null Result
     */
    public function queryOne($sql, $params = [])
    {
        return $this->db->queryOne($sql, $params);
    }
}
