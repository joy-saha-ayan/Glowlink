<?php
session_start();
require_once '../config/Database.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'retailer') {
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();
$retailer_id = $_SESSION['user_id'] ?? 1; 
$user_name = $_SESSION['user_name'] ?? 'Retailer Admin';

$stats = ['total_revenue' => 0, 'total_orders' => 0, 'products_sold' => 0, 'pending_orders' => 0];
$recent_orders = [];
$low_stock_products = [];
$chart_labels = [];
$chart_data = [];
$retailer_logo = '';

if ($db) {
    try {
        $logo_query = "SELECT logo FROM settings WHERE retailer_id = :rid LIMIT 1";
        $stmt_logo = $db->prepare($logo_query);
        $stmt_logo->bindParam(':rid', $retailer_id);
        $stmt_logo->execute();
        $logo_data = $stmt_logo->fetch(PDO::FETCH_ASSOC);
        if ($logo_data && !empty($logo_data['logo'])) {
            $retailer_logo = $logo_data['logo'];
        }

        $stat_query = "SELECT 
            COALESCE(SUM(price), 0) as total_revenue, 
            COUNT(id) as total_orders, 
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_orders 
            FROM orders WHERE retailer_id = :rid";
        $stmt = $db->prepare($stat_query);
        $stmt->bindParam(':rid', $retailer_id);
        $stmt->execute();
        $real_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stats['total_revenue'] = $real_stats['total_revenue'];
        $stats['total_orders'] = $real_stats['total_orders'];
        $stats['pending_orders'] = $real_stats['pending_orders'];

        $sold_query = "SELECT COUNT(id) as sold FROM orders WHERE retailer_id = :rid AND status != 'Pending'";
        $stmt_sold = $db->prepare($sold_query);
        $stmt_sold->bindParam(':rid', $retailer_id);
        $stmt_sold->execute();
        $stats['products_sold'] = $stmt_sold->fetch(PDO::FETCH_ASSOC)['sold'] ?? 0;

        $stock_query = "SELECT name, stock FROM products WHERE retailer_id = :rid AND stock <= 5 ORDER BY stock ASC LIMIT 4";
        $stmt_stock = $db->prepare($stock_query);
        $stmt_stock->bindParam(':rid', $retailer_id);
        $stmt_stock->execute();
        $low_stock_products = $stmt_stock->fetchAll(PDO::FETCH_ASSOC);

        $order_query = "SELECT o.id, o.customer_name, p.name as product_name, p.image, o.price, o.status 
                        FROM orders o 
                        JOIN products p ON o.product_id = p.id 
                        WHERE o.retailer_id = :rid ORDER BY o.order_date DESC LIMIT 5";
        $stmt_order = $db->prepare($order_query);
        $stmt_order->bindParam(':rid', $retailer_id);
        $stmt_order->execute();
        $recent_orders = $stmt_order->fetchAll(PDO::FETCH_ASSOC);

        $chart_query = "SELECT DATE(order_date) as order_day, SUM(price) as daily_revenue 
                        FROM orders 
                        WHERE retailer_id = :rid AND order_date >= DATE(NOW()) - INTERVAL 7 DAY 
                        GROUP BY DATE(order_date) ORDER BY DATE(order_date) ASC";
        $stmt_chart = $db->prepare($chart_query);
        $stmt_chart->bindParam(':rid', $retailer_id);
        $stmt_chart->execute();
        
        while($row = $stmt_chart->fetch(PDO::FETCH_ASSOC)) {
            $chart_labels[] = date('D', strtotime($row['order_day']));
            $chart_data[] = $row['daily_revenue'];
        }

        if(empty($chart_labels)) {
            $chart_labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            $chart_data = [0, 0, 0, 0, 0, 0, 0];
        }

    } catch (PDOException $e) {
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GlowLink Pro Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg-color: #050b14;
            --glass-bg: rgba(30, 41, 59, 0.4);
            --glass-border: rgba(255, 255, 255, 0.08);
            --primary: #8b5cf6;
            --primary-hover: #7c3aed;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        
        body { 
            background: var(--bg-color); color: var(--text-main); 
            display: flex; min-height: 100vh; overflow-x: hidden; position: relative;
        }

        body::before { content: ''; position: fixed; top: -20%; left: -10%; width: 50%; height: 50%; background: radial-gradient(circle, rgba(139, 92, 246, 0.15) 0%, transparent 70%); z-index: -1; }
        body::after { content: ''; position: fixed; bottom: -20%; right: -10%; width: 50%; height: 50%; background: radial-gradient(circle, rgba(16, 185, 129, 0.1) 0%, transparent 70%); z-index: -1; }

        .sidebar { width: 280px; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(20px); border-right: 1px solid var(--glass-border); padding: 30px 20px; display: flex; flex-direction: column; position: fixed; height: 100vh; box-shadow: 10px 0 30px rgba(0,0,0,0.5); z-index: 100; }
        .logo { font-size: 26px; font-weight: 700; color: #fff; text-transform: uppercase; letter-spacing: 2px; text-align: center; margin-bottom: 40px; }
        .logo span { color: var(--primary); text-shadow: 0 0 15px var(--primary); }
        
        .nav-links { list-style: none; display: flex; flex-direction: column; gap: 10px; flex-grow: 1; }
        .nav-links li a { display: flex; align-items: center; gap: 15px; padding: 15px 20px; color: var(--text-muted); text-decoration: none; border-radius: 12px; font-weight: 500; transition: all 0.3s; border: 1px solid transparent; }
        .nav-links li a:hover, .nav-links li a.active { background: rgba(139, 92, 246, 0.15); color: #fff; border: 1px solid rgba(139, 92, 246, 0.3); box-shadow: 0 5px 15px rgba(139, 92, 246, 0.2); transform: translateX(5px); }
        .nav-links li a i { font-size: 18px; width: 20px; text-align: center; }

        .main-content { margin-left: 280px; padding: 30px 40px; width: calc(100% - 280px); }
        .top-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .search-bar { background: var(--glass-bg); border: 1px solid var(--glass-border); padding: 12px 25px; border-radius: 30px; display: flex; align-items: center; gap: 10px; width: 350px; box-shadow: inset 0 2px 5px rgba(0,0,0,0.2); backdrop-filter: blur(10px); }
        .search-bar input { background: none; border: none; color: #fff; outline: none; width: 100%; font-size: 14px; }
        
        .user-profile { display: flex; align-items: center; gap: 20px; }
        .notification { position: relative; cursor: pointer; padding: 10px; background: var(--glass-bg); border-radius: 50%; border: 1px solid var(--glass-border); transition: 0.3s; }
        .notification:hover { background: rgba(139, 92, 246, 0.2); transform: scale(1.1); }
        .badge { position: absolute; top: -2px; right: -2px; background: var(--danger); width: 10px; height: 10px; border-radius: 50%; box-shadow: 0 0 10px var(--danger); }
        .profile-info { display: flex; align-items: center; gap: 12px; background: var(--glass-bg); padding: 8px 20px 8px 8px; border-radius: 30px; border: 1px solid var(--glass-border); }
        .profile-info img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }

        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 25px; margin-bottom: 30px; }
        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; margin-bottom: 30px; }

        .glass-card { background: linear-gradient(135deg, rgba(255,255,255,0.05), rgba(0,0,0,0.3)); backdrop-filter: blur(20px); border: 1px solid var(--glass-border); border-top: 1px solid rgba(255,255,255,0.15); border-radius: 20px; padding: 25px; box-shadow: 0 15px 35px rgba(0,0,0,0.3); position: relative; overflow: hidden; transform-style: preserve-3d; }
        
        .card-link { text-decoration: none; color: inherit; display: block; cursor: pointer; transition: transform 0.3s ease; }
        .card-link:hover { transform: translateY(-5px); }
        
        .stat-card { display: flex; align-items: center; justify-content: space-between; }
        .stat-info h4 { color: var(--text-muted); font-size: 14px; font-weight: 500; margin-bottom: 5px; }
        .stat-info h2 { font-size: 28px; font-weight: 700; color: #fff; }
        .stat-icon { width: 60px; height: 60px; border-radius: 15px; display: flex; justify-content: center; align-items: center; font-size: 24px; box-shadow: 0 10px 20px rgba(0,0,0,0.2); transform: translateZ(20px); }
        
        .icon-purple { background: linear-gradient(135deg, var(--primary), #5b21b6); color: #fff; }
        .icon-green { background: linear-gradient(135deg, var(--success), #047857); color: #fff; }
        .icon-blue { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: #fff; }
        .icon-orange { background: linear-gradient(135deg, var(--warning), #b45309); color: #fff; }

        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .section-header h3 { font-size: 18px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .section-header h3 i { color: var(--primary); }

        .order-item { display: flex; justify-content: space-between; align-items: center; padding: 15px; background: rgba(0,0,0,0.2); border-radius: 12px; margin-bottom: 12px; border: 1px solid transparent; transition: 0.3s; }
        .order-item:hover { background: rgba(255,255,255,0.03); border-color: var(--glass-border); transform: translateX(5px); }
        .order-product { display: flex; align-items: center; gap: 15px; }
        .order-product img { width: 45px; height: 45px; border-radius: 10px; object-fit: cover; }
        .order-product h5 { font-size: 15px; color: #fff; }
        .order-product p { font-size: 12px; color: var(--text-muted); }
        
        .badge-status { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase; }
        .badge-Pending { background: rgba(245, 158, 11, 0.15); color: var(--warning); border: 1px solid rgba(245, 158, 11, 0.3); }
        .badge-Shipped { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
        .badge-Delivered { background: rgba(16, 185, 129, 0.15); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.3); }
        .badge-Critical { background: rgba(239, 68, 68, 0.15); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.3); animation: pulse 2s infinite; }

        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); } 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }

        .alert-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; border-left: 3px solid var(--danger); background: rgba(239, 68, 68, 0.05); margin-bottom: 10px; border-radius: 0 8px 8px 0; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <?php if(!empty($retailer_logo)): ?>
            <div style="text-align: center; margin-bottom: 40px;">
                <img src="<?php echo htmlspecialchars($retailer_logo); ?>" alt="Retailer Logo" style="max-width: 80%; max-height: 80px; object-fit: contain;">
            </div>
        <?php else: ?>
            <div class="logo">Glow<span>Link</span></div>
        <?php endif; ?>
        
        <ul class="nav-links">
            <li><a href="retailer_dashboard.php" class="active"><i class="fa-solid fa-border-all"></i> Dashboard</a></li>
            <li><a href="products.php"><i class="fa-solid fa-box-open"></i> Products</a></li>
            <li><a href="orders.php"><i class="fa-solid fa-clipboard-list"></i> Orders</a></li>
            <li><a href="customers.php"><i class="fa-solid fa-users"></i> Customers</a></li>
            <li><a href="admin_analysis.php"><i class="fa-solid fa-chart-line"></i> Analytics</a></li>
            <li><a href="setting.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
        </ul>
        <a href="logout.php" style="margin-top: auto; padding: 12px 20px; background: linear-gradient(135deg, var(--danger), #b91c1c); color: white; text-align: center; border-radius: 12px; text-decoration: none; font-weight: 600; box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4);"><i class="fa-solid fa-power-off"></i> Logout</a>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass" style="color: var(--text-muted);"></i>
                <input type="text" placeholder="Search orders, products...">
            </div>
            <div class="user-profile">
                <div class="notification">
                    <i class="fa-regular fa-bell"></i>
                    <div class="badge"></div>
                </div>
                <div class="profile-info">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=8b5cf6&color=fff" alt="User">
                    <div>
                        <p style="font-size: 14px; font-weight: 600;"><?php echo htmlspecialchars($user_name); ?></p>
                        <p style="font-size: 11px; color: var(--text-muted);">Retailer Admin</p>
                    </div>
                </div>
            </div>
        </header>

        <div class="stats-grid">
            <a href="analytics.php" class="card-link">
                <div class="glass-card stat-card tilt-element">
                    <div class="stat-info">
                        <h4>Total Revenue</h4>
                        <h2>$<?php echo number_format($stats['total_revenue'], 2); ?></h2>
                    </div>
                    <div class="stat-icon icon-purple"><i class="fa-solid fa-wallet"></i></div>
                </div>
            </a>
            
            <a href="orders.php" class="card-link">
                <div class="glass-card stat-card tilt-element">
                    <div class="stat-info">
                        <h4>Total Orders</h4>
                        <h2><?php echo $stats['total_orders']; ?></h2>
                    </div>
                    <div class="stat-icon icon-blue"><i class="fa-solid fa-cart-shopping"></i></div>
                </div>
            </a>

            <a href="products.php" class="card-link">
                <div class="glass-card stat-card tilt-element">
                    <div class="stat-info">
                        <h4>Products Sold</h4>
                        <h2><?php echo $stats['products_sold']; ?></h2>
                    </div>
                    <div class="stat-icon icon-green"><i class="fa-solid fa-box"></i></div>
                </div>
            </a>

            <a href="orders.php?status=pending" class="card-link">
                <div class="glass-card stat-card tilt-element">
                    <div class="stat-info">
                        <h4>Pending Orders</h4>
                        <h2><?php echo $stats['pending_orders']; ?></h2>
                    </div>
                    <div class="stat-icon icon-orange"><i class="fa-solid fa-clock-rotate-left"></i></div>
                </div>
            </a>
        </div>

        <div class="dashboard-grid">
            <div class="glass-card tilt-element" style="height: 400px;">
                <div class="section-header">
                    <h3><i class="fa-solid fa-chart-area"></i> Revenue Overview</h3>
                    <select style="background: rgba(0,0,0,0.3); border: 1px solid var(--glass-border); color: white; padding: 5px 10px; border-radius: 8px; outline: none;">
                        <option>Last 7 Days</option>
                        <option>This Month</option>
                    </select>
                </div>
                <canvas id="revenueChart"></canvas>
            </div>

            <div class="glass-card tilt-element">
                <div class="section-header">
                    <h3><i class="fa-solid fa-triangle-exclamation" style="color: var(--danger);"></i> Low Stock Alerts</h3>
                </div>
                <div>
                    <?php if(!empty($low_stock_products)): ?>
                        <?php foreach ($low_stock_products as $alert): ?>
                            <div class="alert-item">
                                <div>
                                    <h5 style="font-size: 14px; font-weight: 500;"><?php echo htmlspecialchars($alert['name']); ?></h5>
                                    <p style="font-size: 12px; color: var(--text-muted);">Only <?php echo $alert['stock']; ?> left in stock</p>
                                </div>
                                <span class="badge-status badge-Critical">Restock</span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: var(--text-muted); font-size: 14px;">All stocks are fine! <i class="fa-solid fa-face-smile-beam"></i></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="glass-card tilt-element">
            <div class="section-header">
                <h3><i class="fa-solid fa-list-check"></i> Recent Orders</h3>
                <a href="orders.php" style="background: transparent; border: 1px solid var(--primary); color: var(--primary); padding: 5px 15px; border-radius: 8px; cursor: pointer; transition: 0.3s; text-decoration: none;">View All</a>
            </div>
            <div class="order-list">
                <?php if(!empty($recent_orders)): ?>
                    <?php foreach ($recent_orders as $order): ?>
                        <div class="order-item">
                            <div class="order-product">
                                <img src="<?php echo !empty($order['image']) ? htmlspecialchars($order['image']) : 'https://via.placeholder.com/45'; ?>" alt="Product">
                                <div>
                                    <h5><?php echo htmlspecialchars($order['product_name']); ?></h5>
                                    <p>Customer: <?php echo htmlspecialchars($order['customer_name']); ?> | #ORD-<?php echo $order['id']; ?></p>
                                </div>
                            </div>
                            <div style="font-weight: 600;">$<?php echo number_format($order['price'], 2); ?></div>
                            <div class="badge-status badge-<?php echo htmlspecialchars($order['status']); ?>"><?php echo htmlspecialchars($order['status']); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: var(--text-muted); text-align: center; padding: 20px;">No recent orders found.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.0/vanilla-tilt.min.js"></script>
    <script>
        VanillaTilt.init(document.querySelectorAll(".tilt-element"), { max: 2, speed: 400, glare: true, "max-glare": 0.15, perspective: 1000 });

        const ctx = document.getElementById('revenueChart').getContext('2d');
        let gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(139, 92, 246, 0.5)'); 
        gradient.addColorStop(1, 'rgba(139, 92, 246, 0.0)'); 

        const chartLabels = <?php echo json_encode($chart_labels); ?>;
        const chartData = <?php echo json_encode($chart_data); ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Revenue ($)',
                    data: chartData,
                    borderColor: '#8b5cf6',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#8b5cf6',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(255, 255, 255, 0.05)', drawBorder: false }, ticks: { color: '#94a3b8' } },
                    x: { grid: { display: false, drawBorder: false }, ticks: { color: '#94a3b8' } }
                }
            }
        });
    </script>
</body>
</html>