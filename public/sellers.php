<?php
session_start();
include 'connection.php';



$search = trim($_GET['search'] ?? '');

$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, 3308);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT u.*, 
        (SELECT COUNT(id) FROM products WHERE retailer_id = u.id) as total_products,
        COALESCE((SELECT SUM(total_amount) FROM orders WHERE retailer_id = u.id), 0) as total_sales
        FROM users u 
        WHERE u.role = 'retailer'";

if ($search !== '') {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ?)";
    $stmt = $conn->prepare($sql);
    $like = "%$search%";
    $stmt->bind_param("ss", $like, $like);
} else {
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();
$sellers = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sellers | GlowLink Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root { --primary: #E8336D; --sidebar-bg: #1a0a12; }
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
        body { background:#f7f0f3; display:flex; min-height:100vh; }
        .sidebar { width:260px; background:var(--sidebar-bg); color:white; position:fixed; height:100vh; padding:30px 20px; display:flex; flex-direction:column; }
        .logo { font-size:32px; font-weight:700; margin-bottom:50px; text-align:center; }
        .logo span { color:#ff8fab; }
        .nav a { display:flex; align-items:center; gap:12px; padding:14px 20px; color:#e0b4c9; text-decoration:none; border-radius:12px; margin-bottom:6px; }
        .nav a:hover, .nav a.active { background:rgba(232,51,109,0.25); color:white; }

        .main { margin-left:260px; flex:1; padding:25px; }
        .topbar { background:white; border-radius:20px; padding:15px 25px; display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; box-shadow:0 5px 20px rgba(0,0,0,0.05); }
        .search-input { padding:12px 20px; width:380px; border:1.5px solid #eddde5; border-radius:999px; outline:none; }

        table { width:100%; background:white; border-radius:20px; overflow:hidden; box-shadow:0 8px 25px rgba(0,0,0,0.06); }
        th { background:#fce4ec; padding:18px 15px; text-align:left; color:var(--primary); }
        td { padding:18px 15px; border-bottom:1px solid #f0e0e6; }
        tr:hover { background:#fff5f9; }

        .badge {
            padding:6px 14px;
            border-radius:50px;
            font-size:13px;
            font-weight:600;
        }
        .active { background:#d1fae5; color:#059669; }
        .inactive { background:#fee2e2; color:#dc2626; }

        .btn {
            padding:8px 14px;
            border:none;
            border-radius:8px;
            cursor:pointer;
            margin:0 4px;
            text-decoration:none;
            display:inline-flex;
            align-items:center;
            gap:6px;
            font-size:14px;
        }
        .btn-edit { background:#3b82f6; color:white; }
        .btn-delete { background:#ef4444; color:white; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="logo">Glow<span>Link</span></div>
    <div class="nav">
        <a href="admin_dashboard.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>
        <a href="users.php"><i class="fa-solid fa-users"></i> Users</a>
        <a href="products.php"><i class="fa-solid fa-box"></i> Products</a>
        <a href="sellers.php" class="active"><i class="fa-solid fa-store"></i> Sellers</a>
        <a href="commission.php"><i class="fa-solid fa-wallet"></i> Commission</a>
        <a href="analytics.php"><i class="fa-solid fa-chart-line"></i> Analytics</a>
    </div>
    <a href="logout.php" style="margin-top:auto;color:#f87171;text-align:center;padding:14px;background:rgba(239,68,68,0.1);border-radius:12px;text-decoration:none;">
        <i class="fa-solid fa-right-from-bracket"></i> Logout
    </a>
</div>

<!-- Main Content -->
<div class="main">
    <div class="topbar">
        <h2 style="color:#E8336D;">Sellers Management</h2>
        <form method="GET">
            <input type="text" name="search" class="search-input" 
                   value="<?= htmlspecialchars($search) ?>" 
                   placeholder="Search sellers by name or email...">
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Seller Name</th>
                <th>Email</th>
                <th>Total Products</th>
                <th>Total Sales</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($sellers)): ?>
                <tr><td colspan="8" style="text-align:center; padding:50px;">No sellers found.</td></tr>
            <?php else: ?>
                <?php foreach ($sellers as $seller): ?>
                <tr>
                    <td><?= $seller['id'] ?></td>
                    <td><strong><?= htmlspecialchars($seller['name']) ?></strong></td>
                    <td><?= htmlspecialchars($seller['email']) ?></td>
                    <td><strong><?= $seller['total_products'] ?></strong></td>
                    <td>৳<?= number_format($seller['total_sales'], 2) ?></td>
                    <td>
                        <span class="badge <?= $seller['status']=='active' ? 'active' : 'inactive' ?>">
                            <?= ucfirst($seller['status']) ?>
                        </span>
                    </td>
                    <td><?= date('d M, Y', strtotime($seller['created_at'])) ?></td>
                    <td>
                        <a href="edit_seller.php?id=<?= $seller['id'] ?>" class="btn btn-edit">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                        <a href="delete_seller.php?id=<?= $seller['id'] ?>" 
                           onclick="return confirm('Delete this seller?')" 
                           class="btn btn-delete">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>