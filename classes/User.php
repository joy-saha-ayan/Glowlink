<?php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $name;
    public $email;
    public $password;
    public $role;
    private $password_hash;

    public function __construct($db) {
        $this->conn = $db;
    }

    // ইমেইল চেক করার ফাংশন
    public function emailExists() {
        $query = "SELECT id, name, password_hash, role FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $this->email = htmlspecialchars(strip_tags($this->email));
        $stmt->bindParam(1, $this->email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->role = $row['role'];
            $this->password_hash = $row['password_hash'];
            return true;
        }
        return false;
    }

    // নতুন ইউজার রেজিস্ট্রেশন
    public function register() {
        $query = "INSERT INTO " . $this->table_name . " SET name=:name, email=:email, password_hash=:password_hash, role=:role";
        $stmt = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->role = htmlspecialchars(strip_tags($this->role));
        
        // পাসওয়ার্ড সিকিউর করা হচ্ছে
        $secure_password = password_hash($this->password, PASSWORD_BCRYPT);

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password_hash", $secure_password);
        $stmt->bindParam(":role", $this->role);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // ইউজার লগিন
    public function login() {
        if($this->emailExists()) {
            if(password_verify($this->password, $this->password_hash)) {
                return true;
            }
        }
        return false;
    }
}
?>