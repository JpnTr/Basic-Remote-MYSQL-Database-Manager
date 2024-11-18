<?php
declare(strict_types=1);

class DatabaseManager {
    private $conn;
    private $dbConnection;

    public function __construct() {
        $this->dbConnection = new DatabaseConnection();
        $this->conn = $this->dbConnection->getConnection();
    }

    public function getTables() {
        $tables = [];
        $result = $this->conn->query("SHOW TABLES");
        if ($result) {
            while ($row = $result->fetch_row()) {
                $tables[] = $row[0];
            }
        }
        return $tables;
    }

    public function executeQuery($sql) {
        try {
            $result = $this->conn->multi_query($sql);
            $output = [];
            
            do {
                if ($result = $this->conn->store_result()) {
                    $rows = [];
                    while ($row = $result->fetch_assoc()) {
                        $rows[] = $row;
                    }
                    $output[] = ['type' => 'success', 'data' => $rows];
                    $result->free();
                } else {
                    if ($this->conn->errno) {
                        $output[] = ['type' => 'error', 'message' => $this->conn->error];
                    } else {
                        $output[] = ['type' => 'success', 'message' => 'Query executed successfully'];
                    }
                }
            } while ($this->conn->more_results() && $this->conn->next_result());

            return $output;
        } catch (Exception $e) {
            return [['type' => 'error', 'message' => $e->getMessage()]];
        }
    }

    public function dropTable($tableName) {
        try {
            // Sanitize the table name to prevent SQL injection
            $tableName = $this->conn->real_escape_string($tableName);
            $sql = "DROP TABLE `$tableName`";
            if ($this->conn->query($sql)) {
                return [['type' => 'success', 'message' => "Table '$tableName' dropped successfully"]];
            }
            return [['type' => 'error', 'message' => $this->conn->error]];
        } catch (Exception $e) {
            return [['type' => 'error', 'message' => $e->getMessage()]];
        }
    }

    public function getConfig() {
        return $this->dbConnection->getConfig();
    }

    public function getTableColumns($tableName) {
        try {
            $tableName = $this->conn->real_escape_string($tableName);
            $result = $this->conn->query("SHOW COLUMNS FROM `$tableName`");
            $columns = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $columns[] = $row;
                }
            }
            return $columns;
        } catch (Exception $e) {
            return [];
        }
    }

    public function getTableRecords($tableName, $limit = 10) {
        try {
            $tableName = $this->conn->real_escape_string($tableName);
            $result = $this->conn->query("SELECT * FROM `$tableName` LIMIT $limit");
            $records = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $records[] = $row;
                }
            }
            return $records;
        } catch (Exception $e) {
            return [];
        }
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
