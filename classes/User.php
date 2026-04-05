<?php
// glowlinkp/classes/User.php

class User {
    private $conn;
    private $table_name = "users";

    // User properties
    public $id;
    public $name;
    public $email;
    public $password;
    public $role;

    // Constructor requires a database connection to be passed in
    public function __construct($db) {
        $this->conn = $db;
    }

    // Check if an email already exists
    public function emailExists() {
        $query = "SELECT id, password_hash, role FROM " . $this->table_name . " WHERE email = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $this->email = htmlspecialchars(strip_tags($this->email));
        $stmt->bindParam(1, $this->email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->password = $row['password_hash'];
            $this->role = $row['role'];
            return true;
        }
        return false;
    }

    // Register a new user
    public function register() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET name = :name, email = :email, password_hash = :password, role = :role";

        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->role = htmlspecialchars(strip_tags($this->role));
        
        // Hash the password securely
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);

        // Bind data
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':role', $this->role);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Login user
    public function login() {
        $query = "SELECT id, name, password_hash, role FROM " . $this->table_name . " WHERE email = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $this->email = htmlspecialchars(strip_tags($this->email));
        $stmt->bindParam(1, $this->email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify password
            if(password_verify($this->password, $row['password_hash'])) {
                // Set properties for session
                $this->id = $row['id'];
                $this->name = $row['name'];
                $this->role = $row['role'];
                return true;
            }
        }
        return false;
    }
}
?>