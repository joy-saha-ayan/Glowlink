<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

try {
    $dsn = "mysql:host={$db_server};port=3308;dbname={$db_name};charset=utf8mb4";
    $db = new PDO($dsn, $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

$user_name = $_SESSION['user_name'] ?? 'Joy Saha';

// === Stats ===
try {
    $stats = $db->query("
        SELECT 
            COALESCE(SUM(total_amount), 0) as total_revenue,
            COUNT(id) as total_orders,
            COALESCE(SUM(admin_commission), 0) as pending_commission,
            (SELECT COUNT(id) FROM users WHERE role='retailer' AND status='active') as active_sellers
        FROM orders
    ")->fetch(PDO::FETCH_ASSOC);

    // Revenue Last 6 Months for Chart
    $chartData = $db->query("
        SELECT DATE_FORMAT(created_at, '%b') as month, 
               COALESCE(SUM(total_amount), 0) as revenue
        FROM orders 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY created_at ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $months = [];
    $revenues = [];
    foreach ($chartData as $row) {
        $months[] = $row['month'];
        $revenues[] = (float)$row['revenue'];
    }

    // Commission Status
    $commStatus = $db->query("
        SELECT 
            COALESCE(SUM(CASE WHEN commission_status = 'Unpaid' OR commission_status IS NULL THEN admin_commission ELSE 0 END), 0) as unpaid,
            COALESCE(SUM(CASE WHEN commission_status = 'Paid' THEN admin_commission ELSE 0 END), 0) as paid
        FROM orders
    ")->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $stats = ['total_revenue'=>0, 'total_orders'=>0, 'pending_commission'=>0, 'active_sellers'=>0];
    $months = ['Jan','Feb','Mar','Apr','May','Jun'];
    $revenues = [0,0,0,0,0,0];
    $commStatus = ['unpaid'=>0, 'paid'=>0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | GlowLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #E8336D;
            --light-pink: #fce4ec;
            --sidebar-bg: #1a0a12;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: #f7f0f3;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 25px rgba(232,51,109,0.08);
        }
        .stat-info h3 { font-size: 14px; color: #888; margin-bottom: 6px; }
        .stat-info h1 { font-size: 28px; color: #1e0d17; }
        .stat-icon {
            width: 62px; height: 62px; background: linear-gradient(135deg, #E8336D, #ff8fab);
            border-radius: 16px; display: flex; align-items: center; justify-content: center;
            color: white; font-size: 26px;
        }

        .charts {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }
        .chart-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="logo">Glow<span>Link</span></div>
    <div class="nav">
        <a href="admin_dashboard.php" class="active"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>
        <a href="user.php"><i class="fa-solid fa-users"></i> Users</a>
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
        <input type="text" class="search-bar" placeholder="Search products, sellers, users...">
        <div style="display:flex; align-items:center; gap:20px;">
            <i class="fa-solid fa-bell" style="font-size:24px; color:var(--primary); cursor:pointer;"></i>
            <div style="background:#fce4ec; padding:8px 18px; border-radius:50px; display:flex; align-items:center; gap:12px;">
                <div style="width:45px;height:45px;background:#E8336D;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:bold;">JS</div>
                <div>
                    <strong><?= htmlspecialchars($user_name) ?></strong><br>
                    <small style="color:#E8336D;">System Administrator</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-info">
                <h3>Total Revenue</h3>
                <h1>৳<?= number_format($stats['total_revenue'], 2) ?></h1>
            </div>
            <div class="stat-icon"><i class="fa-solid fa-wallet"></i></div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h3>Total Orders</h3>
                <h1><?= number_format($stats['total_orders']) ?></h1>
            </div>
            <div class="stat-icon"><i class="fa-solid fa-cart-shopping"></i></div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h3>Pending Commission</h3>
                <h1>৳<?= number_format($stats['pending_commission'], 2) ?></h1>
            </div>
            <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h3>Active Sellers</h3>
                <h1><?= $stats['active_sellers'] ?></h1>
            </div>
            <div class="stat-icon"><i class="fa-solid fa-store"></i></div>
        </div>
    </div>

    <!-- Charts -->
    <div class="charts">
        <div class="chart-card">
            <h2>Revenue Analytics (Last 6 Months)</h2>
            <canvas id="revenueChart" height="110"></canvas>
        </div>
        <div class="chart-card">
            <h2>Commission Status</h2>
            <canvas id="commissionChart" height="110"></canvas>
        </div>
    </div>
</div>

<script>
// Revenue Chart - Real Data
new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [{
            label: 'Revenue',
            data: <?= json_encode($revenues) ?>,
            borderColor: '#E8336D',
            backgroundColor: 'rgba(232, 51, 109, 0.12)',
            tension: 0.4,
            borderWidth: 4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});

// Commission Doughnut Chart
new Chart(document.getElementById('commissionChart'), {
    type: 'doughnut',
    data: {
        labels: ['Unpaid', 'Paid'],
        datasets: [{
            data: [<?= $commStatus['unpaid'] ?>, <?= $commStatus['paid'] ?>],
            backgroundColor: ['#E8336D', '#22c55e']
        }]
    },
    options: { responsive: true }
});
</script>
</body>
</html>