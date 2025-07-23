<?php
require_once 'config.php';

class Database {
    private $conn;

    public function __construct() {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($this->conn->connect_error) {
            throw new Exception("Connection failed: " . $this->conn->connect_error);
        }

        $this->conn->set_charset("utf8mb4");
    }

    public function getConnection() {
        return $this->conn;
    }

    public function query($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("SQL error: " . $this->conn->error . " in query: " . $sql);
        }

        if (!empty($params)) {
            $types = '';
            $values = [];

            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
                $values[] = $param;
            }

            $stmt->bind_param($types, ...$values);
        }

        $stmt->execute();
        return $stmt;
    }

    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close(); // 🔴 important to avoid "commands out of sync"
        return $data;
    }

    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close(); // 🔴 important
        return $data;
    }

    public function insert($table, $data) {
        $columns = [];
        $placeholders = [];
        $values = [];

        foreach ($data as $column => $value) {
            $columns[] = "`$column`";
            $placeholders[] = '?';
            $values[] = $value;
        }

        $sql = "INSERT INTO `$table` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->query($sql, $values);
        $stmt->close(); // 🔴 important
        return $this->conn->insert_id;
    }

    public function update($table, $data, $conditions) {
        $setParts = [];
        $whereParts = [];
        $values = [];

        foreach ($data as $column => $value) {
            $setParts[] = "`$column` = ?";
            $values[] = $value;
        }

        foreach ($conditions as $column => $value) {
            $whereParts[] = "`$column` = ?";
            $values[] = $value;
        }

        $sql = "UPDATE `$table` SET " . implode(', ', $setParts) . " WHERE " . implode(' AND ', $whereParts);
        $stmt = $this->query($sql, $values);
        $affected = $stmt->affected_rows;
        $stmt->close(); // 🔴 important
        return $affected;
    }

    public function delete($table, $conditions) {
        $whereParts = [];
        $values = [];

        foreach ($conditions as $column => $value) {
            $whereParts[] = "`$column` = ?";
            $values[] = $value;
        }

        $sql = "DELETE FROM `$table` WHERE " . implode(' AND ', $whereParts);
        $stmt = $this->query($sql, $values);
        $affected = $stmt->affected_rows;
        $stmt->close(); // 🔴 important
        return $affected;
    }

    public function lastInsertId() {
        return $this->conn->insert_id;
    }
}

$db = new Database();
