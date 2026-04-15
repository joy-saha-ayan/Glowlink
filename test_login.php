<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'c:/xampp/htdocs/glowlinkp/config/Database.php';
require_once 'c:/xampp/htdocs/glowlinkp/classes/User.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $u = new User($conn);
    // Find the latest user to test login
    $stmt = $conn->query("SELECT email, password_hash FROM users ORDER BY id DESC LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $u->email = $row['email'];
        $u->password = 'password'; // we used 'password' in the last file
        $res = $u->login();
        echo "Login Result: "; var_dump($res);
        if ($res) {
            echo " Role: " . $u->role;
        }
    } else {
        echo "No users found.";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
