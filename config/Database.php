<?php
// glowlinkp/config/Database.php

class Database {
    // Database credentials
    private $host = '127.0.0.1'; // 127.0.0.1 use korbo localhost er jaygay
    private $port = '3308';      // Amader notun kaj kora port
    private $db_name = 'glowlinkp_db';
    private $username = 'root';
    private $password = '';
    public $conn;

    // Method to get the database connection
    public function getConnection() {
        $this->conn = null;

        try {
            // PDO connection string with port
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name, 
                $this->username, 
                $this->password
            );
            
            // Set PDO error mode to exception
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
            
        } catch(PDOException $exception) {
            echo "Database Connection Error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>