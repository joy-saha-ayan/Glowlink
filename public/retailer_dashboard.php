<?php
session_start();
include 'connection.php';

mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'retailer') {
    header("Location: login.php");
    exit;
}

try {
    $dsn = "mysql:host={$db_server};port=3308;dbname={$db_name};charset=utf8mb4";
    $db  = new PDO($dsn, $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

$retailer_id = $_SESSION['user_id'] ?? 1;
$user_name   = $_SESSION['user_name'] ?? 'Retailer Admin';

$stats = ['total_revenue' => 0, 'total_orders' => 0, 'products_sold' => 0, 'pending_orders' => 0];
$recent_orders      = [];
$low_stock_products = [];
$chart_labels       = [];
$chart_data         = [];

// ── 1. Stats Overview ────────────────────────────────────────────────────────
try {
    $stmt = $db->prepare(
        "SELECT
            COALESCE(SUM(total_amount), 0) AS total_revenue,
            COUNT(id) AS total_orders,
            SUM(CASE WHEN LOWER(status) = 'pending' THEN 1 ELSE 0 END) AS pending_orders
         FROM orders"
    );
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_revenue']  = $row['total_revenue']  ?? 0;
    $stats['total_orders']   = $row['total_orders']   ?? 0;
    $stats['pending_orders'] = $row['pending_orders'] ?? 0;
} catch (PDOException $e) { 
    error_log("Stats Error: " . $e->getMessage()); 
}

// ── 2. Products Sold ──────────────────────────────────────────────────────────
try {
    // Note: oi.quantity column thakte hobe order_items table a
    $stmt2 = $db->query(
        "SELECT COALESCE(SUM(oi.quantity), 0) AS sold
         FROM order_items oi
         JOIN orders o ON oi.order_id = o.id
         WHERE LOWER(o.status) != 'pending'"
    );
    $stats['products_sold'] = $stmt2->fetch(PDO::FETCH_ASSOC)['sold'] ?? 0;
} catch (PDOException $e) { 
    error_log("Products Sold Error: " . $e->getMessage()); 
}

// ── 3. Recent Orders ──────────────────────────────────────────────────────────
try {
    $stmt3 = $db->query(
        "SELECT o.id, o.total_amount, o.status, o.created_at,
                MAX(p.name) AS product_name,
                MAX(p.main_image_url) AS main_image_url,
                MAX(c.name) AS customer_name
         FROM orders o
         LEFT JOIN order_items oi ON o.id = oi.order_id
         LEFT JOIN products p  ON oi.product_id = p.id
         LEFT JOIN users c  ON o.user_id = c.id
         GROUP BY o.id
         ORDER BY o.created_at DESC
         LIMIT 5"
    );
    $recent_orders = $stmt3->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { 
    error_log("Recent Orders Error: " . $e->getMessage()); 
}

// ── 4. Low Stock Alerts ───────────────────────────────────────────────────────
try {
    $stmt4 = $db->query(
        "SELECT name, stock
         FROM products
         WHERE stock <= 10
         ORDER BY stock ASC
         LIMIT 5"
    );
    $low_stock_products = $stmt4->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { 
    error_log("Low Stock Error: " . $e->getMessage()); 
}

// ── 5. Revenue Chart (last 7 days) ──────────────────────────────────────────
try {
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $chart_labels[$date] = date('D', strtotime($date));
        $chart_data[$date]   = 0;
    }

    $stmt5 = $db->query(
        "SELECT DATE(created_at) AS order_day, SUM(total_amount) AS daily_revenue
         FROM orders
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
         GROUP BY DATE(created_at)"
    );

    while ($r = $stmt5->fetch(PDO::FETCH_ASSOC)) {
        $day = $r['order_day'];
        if (isset($chart_data[$day])) {
            $chart_data[$day] = (float)$r['daily_revenue'];
        }
    }

    $chart_labels = array_values($chart_labels);
    $chart_data   = array_values($chart_data);
} catch (PDOException $e) { 
    error_log("Chart Error: " . $e->getMessage()); 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | GlowLink</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary:      #E8336D;
            --primary-soft: #fce4ec;
            --primary-dark: #c0235a;
            --accent:       #FF8FAB;
            --sidebar-bg:   #1a0a12;
            --sidebar-text: #c9a0b4;
            --sidebar-hover:rgba(232,51,109,0.18);
            --bg:           #f7f0f3;
            --card:         #ffffff;
            --border:       #eddde5;
            --text:         #1e0d17;
            --muted:        #8a6070;
            --success:      #10b981;
            --warning:      #f59e0b;
            --danger:       #ef4444;
            --blue:         #3b82f6;
            --radius:       16px;
            --radius-sm:    10px;
            --shadow:       0 4px 24px rgba(232,51,109,0.08);
        }
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'DM Sans',sans-serif;
            background:var(--bg);
            color:var(--text);
            display:flex;
            height:100vh;
            overflow:hidden;
        }

        /* ── Sidebar ── */
        .sidebar {
            width:260px; background:var(--sidebar-bg);
            display:flex; flex-direction:column;
            padding:32px 16px 24px;
            position:fixed; top:0; left:0; bottom:0;
            z-index:100;
        }
        .logo { font-family:'Playfair Display',serif; font-size:26px; color:#fff; text-align:center; margin-bottom:40px; letter-spacing:.5px; }
        .logo span { color:var(--accent); }
        .nav-links { list-style:none; display:flex; flex-direction:column; gap:4px; flex:1; }
        .nav-links a {
            display:flex; align-items:center; gap:12px;
            padding:12px 16px; color:var(--sidebar-text);
            text-decoration:none; border-radius:var(--radius-sm);
            font-size:14px; font-weight:500; transition:all .2s;
        }
        .nav-links a i { width:18px; text-align:center; font-size:15px; }
        .nav-links a:hover, .nav-links a.active { background:var(--sidebar-hover); color:var(--accent); }
        .nav-links a.active { border-left:3px solid var(--primary); }
        .sidebar-logout {
            display:flex; align-items:center; gap:10px;
            padding:12px 16px; color:#f87171;
            text-decoration:none; border-radius:var(--radius-sm);
            font-size:14px; font-weight:500;
            background:rgba(239,68,68,.08); transition:all .2s;
        }
        .sidebar-logout:hover { background:rgba(239,68,68,.18); }

        /* ── Main ── */
        .main-wrapper { margin-left:260px; flex:1; display:flex; flex-direction:column; overflow:hidden; }

        .top-header {
            background:var(--card); border-bottom:1px solid var(--border);
            padding:0 40px; height:70px;
            display:flex; align-items:center; justify-content:space-between;
            box-shadow:0 2px 12px rgba(0,0,0,.04); flex-shrink:0;
        }
        .search-bar {
            display:flex; align-items:center; gap:10px;
            background:#f7f0f3; border:1.5px solid var(--border);
            border-radius:999px; padding:10px 20px; width:320px;
        }
        .search-bar input { border:none; background:transparent; outline:none; font-size:14px; color:var(--text); width:100%; }
        .header-right { display:flex; align-items:center; gap:16px; }
        .notif-btn {
            width:40px; height:40px; border-radius:50%;
            background:var(--primary-soft); border:none; cursor:pointer;
            display:flex; align-items:center; justify-content:center;
            color:var(--primary); font-size:15px;
        }
        .user-pill {
            display:flex; align-items:center; gap:10px;
            padding:6px 16px 6px 6px;
            background:var(--primary-soft); border-radius:999px;
        }
        .user-pill img { width:34px; height:34px; border-radius:50%; border:2px solid var(--primary); }
        .user-pill div p:first-child { font-size:13px; font-weight:600; color:var(--primary-dark); }
        .user-pill div p:last-child  { font-size:11px; color:var(--muted); }

        /* ── Content ── */
        .content { padding:32px 40px 40px; overflow-y:auto; flex:1; }

        /* ── Stat cards ── */
        .stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:20px; margin-bottom:28px; }
        .stat-card {
            background:var(--card); border:1px solid var(--border);
            border-radius:var(--radius); padding:22px 24px;
            display:flex; align-items:center; justify-content:space-between;
            box-shadow:var(--shadow);
        }
        .stat-card h4 { font-size:12px; font-weight:600; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:6px; }
        .stat-card h2 { font-size:26px; font-weight:700; color:var(--text); }
        .stat-icon { width:52px; height:52px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0; }
        .icon-pink   { background:linear-gradient(135deg,#E8336D,#ff75a0); color:#fff; }
        .icon-blue   { background:linear-gradient(135deg,#3b82f6,#60a5fa); color:#fff; }
        .icon-green  { background:linear-gradient(135deg,#10b981,#34d399); color:#fff; }
        .icon-orange { background:linear-gradient(135deg,#f59e0b,#fbbf24); color:#fff; }

        /* ── Dashboard grid ── */
        .dashboard-grid { display:grid; grid-template-columns:2fr 1fr; gap:24px; margin-bottom:24px; }

        .glass-card {
            background:var(--card); border:1px solid var(--border);
            border-radius:var(--radius); padding:24px;
            box-shadow:var(--shadow);
        }
        .card-heading {
            font-family:'Playfair Display',serif;
            font-size:15px; color:var(--text);
            margin-bottom:20px;
            display:flex; align-items:center; gap:8px;
        }
        .card-heading i { color:var(--primary); }

        /* ── Low stock ── */
        .stock-item {
            display:flex; justify-content:space-between; align-items:center;
            padding:11px 14px;
            border-left:3px solid var(--danger);
            background:#fff5f5; border-radius:8px; margin-bottom:10px;
            font-size:13px;
        }
        .stock-badge {
            background:rgba(239,68,68,.12); color:var(--danger);
            font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px;
        }

        /* ── Recent orders ── */
        .order-item {
            display:flex; align-items:center; justify-content:space-between;
            padding:14px; background:#fdf6f8;
            border-radius:12px; margin-bottom:10px; gap:12px;
        }
        .order-product { display:flex; align-items:center; gap:12px; flex:1; min-width:0; }
        .order-product img { width:44px; height:44px; border-radius:10px; object-fit:cover; flex-shrink:0; border:1px solid var(--border); }
        .order-product h5 { font-size:13px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .order-product p  { font-size:12px; color:var(--muted); }
        .order-amount { font-weight:700; font-size:14px; color:var(--primary); white-space:nowrap; }
        .badge {
            padding:4px 12px; border-radius:20px;
            font-size:11px; font-weight:700; text-transform:uppercase;
            white-space:nowrap;
        }
        .badge-pending   { background:rgba(245,158,11,.15); color:#d97706; }
        .badge-assigned  { background:rgba(59,130,246,.15);  color:#2563eb; }
        .badge-delivered { background:rgba(16,185,129,.15);  color:#059669; }
        .badge-cancelled { background:rgba(239,68,68,.15);   color:#dc2626; }
        .badge-default   { background:rgba(100,116,139,.12); color:#475569; }

        .empty-state { text-align:center; padding:50px 20px; color:var(--muted); }
        .empty-state i { font-size:50px; color:var(--border); margin-bottom:12px; display:block; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="logo">Glow<span>Link</span></div>
    <ul class="nav-links">
        <li><a href="retailer_dashboard.php" class="active"><i class="fa-solid fa-border-all"></i> Dashboard</a></li>
        <li><a href="products.php"><i class="fa-solid fa-box-open"></i> Products</a></li>
        <li><a href="orders.php"><i class="fa-solid fa-clipboard-list"></i> Orders</a></li>
        <li><a href="my_products.php"><i class="fa-solid fa-tags"></i> My Products</a></li>
        <li><a href="admin_analysis.php"><i class="fa-solid fa-chart-line"></i> Analytics</a></li>
        <li><a href="setting.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
    </ul>
    <a class="sidebar-logout" href="logout.php">
        <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
    </a>
</aside>

<div class="main-wrapper">
    <header class="top-header">
        <div class="search-bar">
            <i class="fa-solid fa-magnifying-glass" style="color:var(--muted)"></i>
            <input type="text" placeholder="Search orders, products...">
        </div>
        <div class="header-right">
            <button class="notif-btn"><i class="fa-regular fa-bell"></i></button>
            <div class="user-pill">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=E8336D&color=fff" alt="avatar">
                <div>
                    <p><?= htmlspecialchars($user_name) ?></p>
                    <p>Retailer Admin</p>
                </div>
            </div>
        </div>
    </header>

    <div class="content">

        <div class="stats-grid">
            <div class="stat-card">
                <div>
                    <h4>Total Revenue</h4>
                    <h2>৳<?= number_format($stats['total_revenue'], 2) ?></h2>
                </div>
                <div class="stat-icon icon-pink"><i class="fa-solid fa-wallet"></i></div>
            </div>
            <div class="stat-card">
                <div>
                    <h4>Total Orders</h4>
                    <h2><?= $stats['total_orders'] ?></h2>
                </div>
                <div class="stat-icon icon-blue"><i class="fa-solid fa-cart-shopping"></i></div>
            </div>
            <div class="stat-card">
                <div>
                    <h4>Products Sold</h4>
                    <h2><?= $stats['products_sold'] ?></h2>
                </div>
                <div class="stat-icon icon-green"><i class="fa-solid fa-box"></i></div>
            </div>
            <div class="stat-card">
                <div>
                    <h4>Pending Orders</h4>
                    <h2><?= $stats['pending_orders'] ?></h2>
                </div>
                <div class="stat-icon icon-orange"><i class="fa-solid fa-clock-rotate-left"></i></div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="glass-card">
                <div class="card-heading"><i class="fa-solid fa-chart-area"></i> Revenue Overview (Last 7 Days)</div>
                <div style="position:relative;height:280px;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
            <div class="glass-card">
                <div class="card-heading"><i class="fa-solid fa-triangle-exclamation"></i> Low Stock Alerts</div>
                <?php if (empty($low_stock_products)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-circle-check" style="color:var(--success)"></i>
                        <p>All products well stocked!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($low_stock_products as $p): ?>
                        <div class="stock-item">
                            <span style="font-weight:600;"><?= htmlspecialchars($p['name']) ?></span>
                            <span class="stock-badge"><?= $p['stock'] ?> left</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="glass-card">
            <div class="card-heading"><i class="fa-solid fa-clock-rotate-left"></i> Recent Orders</div>
            <?php if (empty($recent_orders)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-clipboard-list"></i>
                    <p>No orders yet</p>
                </div>
            <?php else: ?>
                <?php foreach ($recent_orders as $order):
                    $status_key = strtolower(trim($order['status'] ?? ''));
                    $badge_cls  = match($status_key) {
                        'pending'   => 'badge-pending',
                        'assigned'  => 'badge-assigned',
                        'delivered' => 'badge-delivered',
                        'cancelled' => 'badge-cancelled',
                        default     => 'badge-default',
                    };
                    $img = !empty($order['main_image_url'])
                        ? htmlspecialchars($order['main_image_url'])
                        : 'https://placehold.co/44x44/fce4ec/E8336D?text=GL';
                ?>
                <div class="order-item">
                    <div class="order-product">
                        <img src="<?= $img ?>" alt="" onerror="this.src='https://placehold.co/44x44/fce4ec/E8336D?text=GL'">
                        <div>
                            <h5><?= htmlspecialchars($order['product_name'] ?? '—') ?></h5>
                            <p><?= htmlspecialchars($order['customer_name'] ?? 'Guest') ?> &middot;
                               <?= date('M d, Y', strtotime($order['created_at'])) ?></p>
                        </div>
                    </div>
                    <div class="order-amount">৳<?= number_format($order['total_amount'], 2) ?></div>
                    <span class="badge <?= $badge_cls ?>"><?= htmlspecialchars($order['status']) ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div></div><script>
const ctx = document.getElementById('revenueChart').getContext('2d');
const grad = ctx.createLinearGradient(0,0,0,280);
grad.addColorStop(0,'rgba(232,51,109,0.25)');
grad.addColorStop(1,'rgba(232,51,109,0.0)');

new Chart(ctx, {
    type:'line',
    data:{
        labels: <?= json_encode($chart_labels) ?>,
        datasets:[{
            label:'Revenue (৳)',
            data: <?= json_encode($chart_data) ?>,
            borderColor:'#E8336D',
            backgroundColor: grad,
            tension:0.4,
            fill:true,
            pointBackgroundColor:'#fff',
            pointBorderColor:'#E8336D',
            pointBorderWidth:2,
            pointRadius:4,
            pointHoverRadius:6,
        }]
    },
    options:{
        responsive:true,
        maintainAspectRatio:false,
        plugins:{ legend:{display:false} },
        scales:{
            y:{ grid:{color:'#f0dde5'}, beginAtZero:true,
                ticks:{callback:v=>'৳'+v.toLocaleString()} },
            x:{ grid:{display:false} }
        }
    }
});
</script>
</body>
</html>