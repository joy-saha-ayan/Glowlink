<?php
session_start();
include 'connection.php';



try {
    $dsn = "mysql:host={$db_server};port=3308;dbname={$db_name};charset=utf8mb4";
    $db = new PDO($dsn, $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? 'all';

$query = "
    SELECT id, name, email, role, status, created_at, profile_pic 
    FROM users 
    WHERE 1=1
";
$params = [];

if ($search) {
    $query .= " AND (name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role_filter !== 'all') {
    $query .= " AND role = ?";
    $params[] = $role_filter;
}

$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users | GlowLink Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #E8336D;
            --sidebar-bg: #1a0a12;
            --bg: #f7f0f3;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            color: white;
            position: fixed;
            height: 100vh;
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
        }
        .logo { font-size: 32px; font-weight: 700; margin-bottom: 50px; text-align:center; }
        .logo span { color: #ff8fab; }
        .nav a {
            display: flex; align-items: center; gap: 12px;
            padding: 14px 20px; color: #e0b4c9; text-decoration: none;
            border-radius: 12px; margin-bottom: 6px;
        }
        .nav a:hover, .nav a.active { background: rgba(232,51,109,0.25); color: white; }

        .main { margin-left: 260px; flex: 1; padding: 25px; }
        .topbar {
            background: white; border-radius: 20px; padding: 15px 25px;
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        .search-bar {
            background: #f8f0f4; border: none; outline: none;
            padding: 12px 20px; border-radius: 50px; width: 380px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .page-header h1 { font-size: 28px; color: #1e0d17; }

        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        .filters select, .filters input {
            padding: 10px 16px;
            border: 1px solid #ddd;
            border-radius: 12px;
            background: white;
        }

        table {
            width: 100%;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
        }
        th {
            background: #fce4ec;
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            color: var(--primary);
        }
        td {
            padding: 18px 15px;
            border-bottom: 1px solid #f0e0e6;
        }
        tr:hover { background: #fff5f9; }
        .badge {
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
        }
        .active { background: #d1fae5; color: #059669; }
        .inactive { background: #fee2e2; color: #dc2626; }
        .role-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
        }
        .btn-edit { background: #3b82f6; color: white; }
        .btn-delete { background: #ef4444; color: white; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="logo">Glow<span>Link</span></div>
    <div class="nav">
        <a href="admin_dashboard.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>
        <a href="user.php" class="active"><i class="fa-solid fa-users"></i> Users</a>
        <a href="pr.php"><i class="fa-solid fa-box"></i> Products</a>
        <a href="commission.php"><i class="fa-solid fa-wallet"></i> Commission</a>
        <a href="analytics.php"><i class="fa-solid fa-chart-line"></i> Analytics</a>
    </div>
    <a href="logout.php" style="margin-top:auto; color:#f87171; text-align:center; padding:14px; background:rgba(239,68,68,0.1); border-radius:12px; text-decoration:none;">
        <i class="fa-solid fa-right-from-bracket"></i> Logout
    </a>
</div>

<!-- Main Content -->
<div class="main">
    <div class="topbar">
        <input type="text" class="search-bar" placeholder="Search products, sellers, users..." value="<?= htmlspecialchars($search) ?>">
        <div style="display:flex; align-items:center; gap:20px;">
            <i class="fa-solid fa-bell" style="font-size:24px; color:#E8336D;"></i>
            <div style="background:#fce4ec; padding:8px 18px; border-radius:50px; display:flex; align-items:center; gap:12px;">
                <div style="width:45px;height:45px;background:#E8336D;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:bold;">JS</div>
                <div>
                    <strong>Id</strong><br>
                    <small style="color:#E8336D;">System Administrator</small>
                </div>
            </div>
        </div>
    </div>

    <div class="page-header">
        <h1>All Users</h1>
        <button onclick="location.href='add_user.php'" class="btn" style="background:var(--primary); color:white;">
            <i class="fa-solid fa-plus"></i> Add New User
        </button>
    </div>

    <!-- Filters -->
    <div class="filters">
        <form method="GET" style="display:flex; gap:15px;">
            <select name="role" onchange="this.form.submit()">
                <option value="all" <?= $role_filter=='all'?'selected':'' ?>>All Roles</option>
                <option value="customer" <?= $role_filter=='customer'?'selected':'' ?>>Customer</option>
                <option value="retailer" <?= $role_filter=='retailer'?'selected':'' ?>>Retailer</option>
                <option value="driver" <?= $role_filter=='driver'?'selected':'' ?>>Driver</option>
                <option value="admin" <?= $role_filter=='admin'?'selected':'' ?>>Admin</option>
            </select>
            <button type="submit" class="btn" style="background:#E8336D; color:white;">Filter</button>
        </form>
    </div>

    <!-- Users Table -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="7" style="text-align:center; padding:40px; color:#888;">No users found.</td></tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <span class="role-badge" style="background:#fce4ec; color:var(--primary);">
                            <?= ucfirst($user['role']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= $user['status']=='active' ? 'active' : 'inactive' ?>">
                            <?= ucfirst($user['status']) ?>
                        </span>
                    </td>
                    <td><?= date('d M, Y', strtotime($user['created_at'])) ?></td>
                    <td>
                        <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-edit"><i class="fa-solid fa-edit"></i></a>
                        <a href="delete_user.php?id=<?= $user['id'] ?>" onclick="return confirm('Delete this user?')" class="btn btn-delete"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>