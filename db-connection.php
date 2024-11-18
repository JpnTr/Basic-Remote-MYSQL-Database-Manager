<?php
declare(strict_types=1);

class DatabaseConnection {
    private $conn;
    private $config;

    public function __construct() {
        $this->config = [
            'host' => "",  // Change this to your remote database host
            'username' => "remote",     // Change this to your database username
            'password' => "",           // Change this to your database password
            'database' => "test",       // Change this to your database name
            'charset' => "utf8mb4"
        ];
        $this->connect();
    }

    private function connect() {
        try {
            $this->conn = new mysqli(
                $this->config['host'], 
                $this->config['username'], 
                $this->config['password'], 
                $this->config['database']
            );
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            $this->conn->set_charset($this->config['charset']);
        } catch (Exception $e) {
            throw new Exception("Connection error: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    public function getConfig() {
        return $this->config;
    }

    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
