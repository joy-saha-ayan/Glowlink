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

// Date Filter
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date'] ?? date('Y-m-d');

// Main Statistics
$stats = $db->prepare("
    SELECT 
        COALESCE(SUM(total_amount), 0) as total_revenue,
        COUNT(id) as total_orders,
        COUNT(DISTINCT user_id) as total_customers,
        (SELECT COUNT(id) FROM users WHERE role='retailer') as total_sellers,
        (SELECT COUNT(id) FROM products) as total_products
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$stats->execute([$start_date, $end_date]);
$stat = $stats->fetch(PDO::FETCH_ASSOC);

// Revenue Trend (Last 6 Months)
$revenue_trend = $db->prepare("
    SELECT DATE_FORMAT(created_at, '%b %Y') as month, 
           COALESCE(SUM(total_amount), 0) as revenue
    FROM orders 
    WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY created_at
");
$revenue_trend->execute();
$months = [];
$revenues = [];
while ($row = $revenue_trend->fetch()) {
    $months[] = $row['month'];
    $revenues[] = (float)$row['revenue'];
}

// Top Products (Safe Query - Using only orders table)
$top_products = $db->query("
    SELECT 
        SUBSTRING_INDEX(SUBSTRING_INDEX(cart_items, 'name\":\"', -1), '\"', 1) as product_name,
        COUNT(id) as order_count,
        COALESCE(SUM(total_amount), 0) as revenue
    FROM orders 
    WHERE cart_items IS NOT NULL AND cart_items != ''
    GROUP BY product_name
    ORDER BY revenue DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics | GlowLink Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .topbar { background:white; border-radius:20px; padding:15px 25px; display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; }

        .stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px,1fr)); gap:20px; margin-bottom:30px; }
        .stat-card { background:white; padding:24px; border-radius:20px; box-shadow:0 8px 25px rgba(0,0,0,0.06); }
        .stat-card h3 { color:#666; font-size:14px; }
        .stat-card h1 { font-size:28px; margin:10px 0; color:#1e0d17; }

        .charts-grid { display:grid; grid-template-columns:2fr 1fr; gap:25px; margin-bottom:30px; }
        .chart-card { background:white; padding:25px; border-radius:20px; box-shadow:0 8px 25px rgba(0,0,0,0.06); }

        table { width:100%; background:white; border-radius:20px; overflow:hidden; }
        th { background:#fce4ec; padding:16px; text-align:left; color:var(--primary); }
        td { padding:16px; border-bottom:1px solid #f0e0e6; }
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
        <a href="commission.php"><i class="fa-solid fa-wallet"></i> Commission</a>
        <a href="analytics.php" class="active"><i class="fa-solid fa-chart-line"></i> Analytics</a>
    </div>
    <a href="logout.php" style="margin-top:auto;color:#f87171;text-align:center;padding:14px;background:rgba(239,68,68,0.1);border-radius:12px;text-decoration:none;">
        <i class="fa-solid fa-right-from-bracket"></i> Logout
    </a>
</div>

<div class="main">
    <div class="topbar">
        <h2 style="color:#E8336D;">Business Analytics</h2>
        <form method="GET" style="display:flex; gap:10px; align-items:center;">
            <input type="date" name="start_date" value="<?= $start_date ?>">
            <input type="date" name="end_date" value="<?= $end_date ?>">
            <button type="submit" style="background:#E8336D;color:white;padding:10px 20px;border:none;border-radius:10px;">Apply Filter</button>
        </form>
    </div>

    <!-- Key Metrics -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Revenue</h3>
            <h1>৳<?= number_format($stat['total_revenue'], 2) ?></h1>
        </div>
        <div class="stat-card">
            <h3>Total Orders</h3>
            <h1><?= number_format($stat['total_orders']) ?></h1>
        </div>
        <div class="stat-card">
            <h3>Active Customers</h3>
            <h1><?= number_format($stat['total_customers']) ?></h1>
        </div>
        <div class="stat-card">
            <h3>Total Products</h3>
            <h1><?= number_format($stat['total_products']) ?></h1>
        </div>
    </div>

    <!-- Charts -->
    <div class="charts-grid">
        <div class="chart-card">
            <h3>Revenue Trend (Last 6 Months)</h3>
            <canvas id="revenueChart" height="130"></canvas>
        </div>
        <div class="chart-card">
            <h3>Top Products</h3>
            <canvas id="topProductsChart" height="130"></canvas>
        </div>
    </div>

    <!-- Top Products Table -->
    <div class="chart-card">
        <h3>Top Performing Products</h3>
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Orders</th>
                    <th>Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($top_products)): ?>
                    <tr><td colspan="3" style="text-align:center;padding:30px;">No data available</td></tr>
                <?php else: ?>
                    <?php foreach ($top_products as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['product_name']) ?></td>
                        <td><?= $p['order_count'] ?></td>
                        <td>৳<?= number_format($p['revenue'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Revenue Chart
new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [{
            label: 'Revenue',
            data: <?= json_encode($revenues) ?>,
            borderColor: '#E8336D',
            backgroundColor: 'rgba(232, 51, 109, 0.1)',
            tension: 0.4,
            borderWidth: 4,
            fill: true
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }}
});

// Top Products Doughnut
new Chart(document.getElementById('topProductsChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($top_products, 'product_name')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($top_products, 'revenue')) ?>,
            backgroundColor: ['#E8336D', '#ff8fab', '#3b82f6', '#22c55e', '#eab308']
        }]
    },
    options: { responsive: true }
});
</script>
</body>
</html>