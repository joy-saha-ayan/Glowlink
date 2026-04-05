<?php
// glowlinkp/public/admin_dashboard.php
session_start();

// Security Check: Only Admins allowed
if(!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch quick statistics for the Admin
$stats = [
    'customers' => 0,
    'retailers' => 0,
    'products' => 0
];

try {
    // Count Customers
    $stmt = $db->query("SELECT COUNT(*) FROM customers");
    $stats['customers'] = $stmt->fetchColumn();

    // Count Retailers
    $stmt = $db->query("SELECT COUNT(*) FROM retailers");
    $stats['retailers'] = $stmt->fetchColumn();

    // Count Products
    $stmt = $db->query("SELECT COUNT(*) FROM products");
    $stats['products'] = $stmt->fetchColumn();
} catch(PDOException $e) {
    echo "Error fetching stats: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GlowLink - Admin Control Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #1e1e2f; /* Dark premium theme for admin */
            color: #fff;
            margin: 0;
            padding: 20px;
            font-family: 'Poppins', sans-serif;
        }
        .dashboard-wrapper { max-width: 1200px; margin: 0 auto; }
        
        .top-nav {
            display: flex; justify-content: space-between; align-items: center;
            background: #2a2a40; padding: 15px 30px;
            border-radius: 15px; margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .top-nav h1 { margin: 0; font-size: 24px; color: #fff; }
        .logout-btn { 
            background: #ff4757; color: white; text-decoration: none; 
            padding: 10px 20px; border-radius: 25px; font-weight: bold; 
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px;
        }
        
        .stat-card {
            background: #2a2a40; padding: 30px; border-radius: 15px;
            display: flex; align-items: center; gap: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            border-left: 5px solid #3498db;
        }
        .stat-card:nth-child(2) { border-left-color: #2ecc71; }
        .stat-card:nth-child(3) { border-left-color: #f1c40f; }
        
        .stat-icon {
            font-size: 40px; color: #a4b0be;
        }
        
        .stat-info h3 { margin: 0; font-size: 16px; color: #a4b0be; text-transform: uppercase; letter-spacing: 1px; }
        .stat-info p { margin: 5px 0 0 0; font-size: 32px; font-weight: bold; color: #fff; }
    </style>
</head>
<body>

    <div class="dashboard-wrapper">
        <header class="top-nav">
            <h1><i class="fa-solid fa-shield-halved" style="color: #3498db;"></i> GlowLink Admin Hub</h1>
            <a href="logout.php" class="logout-btn"><i class="fa-solid fa-power-off"></i> Logout</a>
        </header>

        <h2 style="margin-bottom: 20px;">Platform Overview</h2>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                <div class="stat-info">
                    <h3>Total Customers</h3>
                    <p><?php echo $stats['customers']; ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-store"></i></div>
                <div class="stat-info">
                    <h3>Active Retailers</h3>
                    <p><?php echo $stats['retailers']; ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-box-open"></i></div>
                <div class="stat-info">
                    <h3>Products Tracked</h3>
                    <p><?php echo $stats['products']; ?></p>
                </div>
            </div>
        </div>
    </div>

</body>
</html>