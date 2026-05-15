<?php
session_start();
include 'connection.php';



$id = $_GET['id'] ?? 0;

try {
    $dsn = "mysql:host={$db_server};port=3308;dbname={$db_name};charset=utf8mb4";
    $db = new PDO($dsn, $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_POST) {
        $stmt = $db->prepare("UPDATE users SET name=?, email=?, role=?, status=? WHERE id=?");
        $stmt->execute([
            $_POST['name'],
            $_POST['email'],
            $_POST['role'],
            $_POST['status'],
            $id
        ]);
        header("Location: users.php?success=1");
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background:#f7f0f3; padding:40px; }
        .form-container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        input, select {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 10px;
        }
        button {
            background: #E8336D;
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Edit User</h2>
    <form method="POST">
        <label>Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

        <label>Role</label>
        <select name="role" required>
            <option value="customer" <?= $user['role']=='customer'?'selected':'' ?>>Customer</option>
            <option value="retailer" <?= $user['role']=='retailer'?'selected':'' ?>>Retailer</option>
            <option value="driver" <?= $user['role']=='driver'?'selected':'' ?>>Driver</option>
            <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Admin</option>
        </select>

        <label>Status</label>
        <select name="status" required>
            <option value="active" <?= $user['status']=='active'?'selected':'' ?>>Active</option>
            <option value="inactive" <?= $user['status']=='inactive'?'selected':'' ?>>Inactive</option>
        </select>

        <button type="submit">Update User</button>
        <a href="user.php" style="margin-left:15px; color:#666; text-decoration:none;">Cancel</a>
    </form>
</div>

</body>
</html>