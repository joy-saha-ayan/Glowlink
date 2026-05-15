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

// Mark Commission as Paid
if (isset($_GET['mark_paid']) && isset($_GET['id'])) {
    $order_id = (int)$_GET['id'];
    $stmt = $db->prepare("UPDATE orders SET commission_status = 'Paid' WHERE id = ?");
    $stmt->execute([$order_id]);
    
    header("Location: commission.php?success=Commission marked as Paid successfully");
    exit;
}

// Statistics
$stats = $db->query("
    SELECT 
        COALESCE(SUM(admin_commission), 0) as total_commission,
        COALESCE(SUM(CASE WHEN commission_status = 'Paid' THEN admin_commission ELSE 0 END), 0) as paid_commission,
        COALESCE(SUM(CASE WHEN commission_status = 'Unpaid' OR commission_status IS NULL THEN admin_commission ELSE 0 END), 0) as pending_commission
    FROM orders
")->fetch(PDO::FETCH_ASSOC);

// Fetch Orders
$stmt = $db->query("
    SELECT 
        o.id,
        o.order_number,
        o.total_amount,
        o.admin_commission,
        o.commission_status,
        o.payment_status,
        o.created_at,
        u.name as customer_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commission Management | GlowLink</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 8px 25px rgba(232,51,109,0.08);
        }
        .stat-card h3 { color: #666; font-size: 14px; }
        .stat-card h1 { font-size: 28px; color: #1e0d17; margin-top: 8px; }

        table { width:100%; background:white; border-radius:20px; overflow:hidden; box-shadow:0 8px 25px rgba(0,0,0,0.06); }
        th { background:#fce4ec; padding:18px 15px; text-align:left; color:var(--primary); }
        td { padding:18px 15px; border-bottom:1px solid #f0e0e6; }
        tr:hover { background:#fff5f9; }

        .badge {
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
        }
        .paid { background: #d1fae5; color: #059669; }
        .unpaid { background: #fee2e2; color: #dc2626; }

        .btn-paid {
            background: #22c55e;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="logo">Glow<span>Link</span></div>
    <div class="nav">
        <a href="admin_dashboard.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>
        <a href="user.php"><i class="fa-solid fa-users"></i> Users</a>
        <a href="pr.php"><i class="fa-solid fa-box"></i> Products</a>
        <a href="commission.php" class="active"><i class="fa-solid fa-wallet"></i> Commission</a>
        <a href="analytics.php"><i class="fa-solid fa-chart-line"></i> Analytics</a>
    </div>
    <a href="logout.php" style="margin-top:auto;color:#f87171;text-align:center;padding:14px;background:rgba(239,68,68,0.1);border-radius:12px;text-decoration:none;">
        <i class="fa-solid fa-right-from-bracket"></i> Logout
    </a>
</div>

<!-- Main Content -->
<div class="main">
    <div class="topbar">
        <h2 style="color:#E8336D;">Commission Management</h2>
        <div style="background:#fce4ec;padding:8px 18px;border-radius:50px;display:flex;align-items:center;gap:12px;">
            <div style="width:45px;height:45px;background:#E8336D;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:bold;">JS</div>
            <div><strong>Id</strong><br><small>System Administrator</small></div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Commission</h3>
            <h1>৳<?= number_format($stats['total_commission'], 2) ?></h1>
        </div>
        <div class="stat-card">
            <h3>Pending Commission</h3>
            <h1>৳<?= number_format($stats['pending_commission'], 2) ?></h1>
        </div>
        <div class="stat-card">
            <h3>Paid Commission</h3>
            <h1>৳<?= number_format($stats['paid_commission'], 2) ?></h1>
        </div>
    </div>

    <!-- Commission Table -->
    <table>
        <thead>
            <tr>
                <th>Order Number</th>
                <th>Customer</th>
                <th>Total Amount</th>
                <th>Commission Amount</th>
                <th>Status</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
                <tr><td colspan="7" style="text-align:center; padding:50px; color:#888;">No orders found</td></tr>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($order['order_number']) ?></strong></td>
                    <td><?= htmlspecialchars($order['customer_name'] ?? 'Guest') ?></td>
                    <td>৳<?= number_format($order['total_amount'], 2) ?></td>
                    <td><strong>৳<?= number_format($order['admin_commission'], 2) ?></strong></td>
                    <td>
                        <span class="badge <?= ($order['commission_status'] == 'Paid') ? 'paid' : 'unpaid' ?>">
                            <?= $order['commission_status'] ?: 'Unpaid' ?>
                        </span>
                    </td>
                    <td><?= date('d M, Y', strtotime($order['created_at'])) ?></td>
                    <td>
                        <?php if ($order['commission_status'] !== 'Paid'): ?>
                            <a href="?mark_paid=1&id=<?= $order['id'] ?>" 
                               onclick="return confirm('Mark this commission as Paid?')" 
                               class="btn-paid">
                                <i class="fa-solid fa-check"></i> Mark Paid
                            </a>
                        <?php else: ?>
                            <span style="color:#059669; font-weight:600;">✓ Paid</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>