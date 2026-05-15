<?php
session_start();
include 'connection.php';


$id = $_GET['id'] ?? 0;

if ($id) {
    try {
        $dsn = "mysql:host={$db_server};port=3308;dbname={$db_name};charset=utf8mb4";
        $db = new PDO($dsn, $db_user, $db_pass);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);

        header("Location: users.php?deleted=1");
    } catch (PDOException $e) {
        die("Error deleting user: " . $e->getMessage());
    }
} else {
    header("Location: user.php");
}
?>