<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GlowLink - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: #0f172a; color: #fff; min-height: 100vh; display: flex; overflow-x: hidden; }
        
        /* 3D Sidebar */
        .sidebar { width: 260px; background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(15px); border-right: 1px solid rgba(255, 255, 255, 0.1); padding: 30px; display: flex; flex-direction: column; perspective: 1000px; z-index: 10; box-shadow: 5px 0 25px rgba(0,0,0,0.5); }
        .sidebar h2 { color: #3b82f6; font-size: 24px; font-weight: 700; margin-bottom: 40px; text-align: center; text-shadow: 0 0 10px rgba(59, 130, 246, 0.5); letter-spacing: 2px; }
        .nav-links { list-style: none; flex: 1; }
        .nav-links li { margin-bottom: 20px; transform-style: preserve-3d; transition: 0.3s; }
        .nav-links a { display: flex; align-items: center; color: #9ca3af; text-decoration: none; font-size: 16px; padding: 12px 15px; border-radius: 10px; transition: 0.3s; background: rgba(255,255,255,0.05); border: 1px solid transparent; }
        .nav-links a i { margin-right: 15px; font-size: 18px; color: #3b82f6; transition: 0.3s; }
        .nav-links li:hover { transform: translateZ(20px) scale(1.05); }
        .nav-links a:hover, .nav-links a.active { background: rgba(59, 130, 246, 0.2); color: #fff; border-color: rgba(59, 130, 246, 0.4); box-shadow: 0 10px 20px rgba(0,0,0,0.3); }
        
        /* Main Area */
        .main-content { flex: 1; padding: 40px; background: radial-gradient(circle at top left, #1e293b, #0f172a); overflow-y: auto; perspective: 1200px; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        header h1 { font-size: 28px; font-weight: 600; text-shadow: 0 5px 15px rgba(0,0,0,0.5); }
        .user-info { display: flex; align-items: center; gap: 20px; }
        .logout-btn { padding: 10px 25px; background: linear-gradient(135deg, #ef4444, #b91c1c); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: transform 0.3s, box-shadow 0.3s; box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4); }
        .logout-btn:hover { transform: translateY(-3px) translateZ(10px); box-shadow: 0 10px 25px rgba(239, 68, 68, 0.6); }

        /* 3D Dashboard Stats */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-bottom: 40px; transform-style: preserve-3d; }
        .stat-card { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); padding: 30px; border-radius: 20px; display: flex; align-items: center; gap: 20px; transition: 0.4s; position: relative; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.3); }
        .stat-card::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%); opacity: 0; transition: 0.5s; pointer-events: none; }
        .stat-card:hover::before { opacity: 1; transform: rotate(45deg); }
        .stat-card:hover { transform: translateY(-10px) rotateX(5deg) rotateY(-5deg); box-shadow: -10px 20px 40px rgba(0,0,0,0.5); border-color: rgba(59, 130, 246, 0.4); }
        .stat-icon { background: linear-gradient(135deg, #3b82f6, #1d4ed8); width: 60px; height: 60px; border-radius: 15px; display: flex; justify-content: center; align-items: center; font-size: 24px; color: white; box-shadow: 0 10px 20px rgba(59, 130, 246, 0.4); }
        .stat-card h3 { font-size: 14px; color: #9ca3af; font-weight: 400; text-transform: uppercase; letter-spacing: 1px; }
        .stat-card p { font-size: 28px; font-weight: 700; color: #fff; margin-top: 5px; }

        /* 3D Animated Table Layer */
        .table-container { background: rgba(255,255,255,0.03); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 20px; padding: 30px; transform: translateZ(10px); box-shadow: 0 20px 50px rgba(0,0,0,0.4); transition: transform 0.4s; }
        .table-container:hover { transform: translateZ(30px) translateY(-5px); box-shadow: 0 30px 60px rgba(0,0,0,0.6); }
        .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .table-header h2 { font-size: 20px; font-weight: 600; color: #e2e8f0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid rgba(255, 255, 255, 0.05); color: #cbd5e1; }
        th { font-weight: 600; color: #94a3b8; text-transform: uppercase; font-size: 13px; letter-spacing: 1px; }
        tr { transition: 0.3s; }
        tr:hover { background: rgba(255,255,255,0.05); transform: scale(1.01); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .badge.active { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>GLOWLINK</h2>
        <ul class="nav-links">
            <li><a href="#" class="active"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
            <li><a href="#"><i class="fa-solid fa-users"></i> Manage Users</a></li>
            <li><a href="#"><i class="fa-solid fa-box"></i> Products</a></li>
            <li><a href="admin_analysis.php"><i class="fa-solid fa-chart-line"></i> Analytics</a></li>
            <li><a href="setting.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <header>
            <h1>System Administrator</h1>
            <div class="user-info">
                <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></span>
                <a href="logout.php" class="logout-btn">Logout <i class="fa-solid fa-right-from-bracket"></i></a>
            </div>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                <div>
                    <h3>Total Users</h3>
                    <p>8,249</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #047857); box-shadow: 0 10px 20px rgba(16, 185, 129, 0.4);"><i class="fa-solid fa-store"></i></div>
                <div>
                    <h3>Active Retailers</h3>
                    <p>142</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #b45309); box-shadow: 0 10px 20px rgba(245, 158, 11, 0.4);"><i class="fa-solid fa-wallet"></i></div>
                <div>
                    <h3>Total Sales</h3>
                    <p>$124,500</p>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h2>Recent User Registrations</h2>
                <button class="logout-btn" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); padding: 8px 20px;">View All</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Sarah Connor</td>
                        <td>sarah@example.com</td>
                        <td>Customer</td>
                        <td><span class="badge active">Active</span></td>
                    </tr>
                    <tr>
                        <td>TechGadget Ltd</td>
                        <td>sales@techgadgets.com</td>
                        <td>Retailer</td>
                        <td><span class="badge active">Active</span></td>
                    </tr>
                    <tr>
                        <td>John Doe driver</td>
                        <td>john.driver@xyz.com</td>
                        <td>Driver</td>
                        <td><span class="badge active">Active</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>