<?php
// glowlinkp/config/Database.php

class Database {
    // Database credentials
    private $host = 'localhost';
    private $db_name = 'glowlinkp_db';
    private $username = 'root';
    private $password = '';
    public $conn;

    // Method to get the database connection
    public function getConnection() {
        $this->conn = null;

        try {
            // Using PDO for high security and prepared statements
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                $this->username, 
                $this->password
            );
            
            // Set PDO error mode to exception for easier debugging
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Ensure data is sent with correct encoding
            $this->conn->exec("set names utf8");
            
        } catch(PDOException $exception) {
            echo "Database Connection Error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>