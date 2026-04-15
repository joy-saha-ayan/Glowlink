<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'c:/xampp/htdocs/glowlinkp/config/Database.php';
require_once 'c:/xampp/htdocs/glowlinkp/classes/User.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    if (!$conn) {
        echo "No connection!\n";
        exit;
    }
    
    $u = new User($conn);
    $u->name = 'test';
    $u->email = 'test' . rand(1, 1000) . '@test.com';
    $u->password = 'password';
    $u->role = 'customer';
    
    $res = $u->register();
    echo "Register Result: "; var_dump($res);
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
