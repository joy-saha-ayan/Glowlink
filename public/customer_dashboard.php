<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'customer') {
    // If it's driver, redirect them to the their own dashboard or custom here.
    if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'driver') {
        // Driver specific code if needed, for now let them access customer dashboard or build separate.
        // I will let drivers pass through here for demonstration, or we can enforce purely customer.
        if ($_SESSION['user_role'] !== 'customer' && $_SESSION['user_role'] !== 'driver') {
             header("Location: login.php");
             exit;
        }
    } else {
        header("Location: login.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GlowLink - Customer Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: url('https://images.unsplash.com/photo-1550684848-fac1c5b4e853?auto=format&fit=crop&w=1920&q=80') center/cover fixed; min-height: 100vh; color: #fff; perspective: 1200px; display: flex; flex-direction: column; align-items: center; padding: 40px 20px; }
        body::before { content: ''; position: absolute; inset: 0; background: radial-gradient(circle at center, rgba(15, 23, 42, 0.7), rgba(0, 0, 0, 0.9)); z-index: -1; }

        .top-bar { display: flex; justify-content: space-between; align-items: center; width: 100%; max-width: 1000px; margin-bottom: 50px; transform: translateZ(10px); }
        .logo-text { font-size: 28px; font-weight: 700; background: linear-gradient(to right, #ec4899, #f43f5e); -webkit-background-clip: text; -webkit-text-fill-color: transparent; filter: drop-shadow(0 0 10px rgba(236, 72, 153, 0.4)); }

        .btn-logout { padding: 10px 25px; border-radius: 30px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white; text-decoration: none; font-weight: 600; backdrop-filter: blur(10px); transition: 0.4s; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .btn-logout:hover { background: #ef4444; border-color: #ef4444; transform: translateY(-3px) scale(1.05); box-shadow: 0 10px 20px rgba(239, 68, 68, 0.5); }

        .dashboard-container { width: 100%; max-width: 1000px; display: grid; grid-template-columns: 1fr 2fr; gap: 30px; transform-style: preserve-3d; }

        .profile-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(15px); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 40px 30px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.5); transform: translateZ(20px); transition: 0.5s; position: relative; overflow: hidden; }
        .profile-card:hover { transform: translateZ(40px) rotateY(5deg); border-color: rgba(236, 72, 153, 0.3); box-shadow: 10px 25px 50px rgba(0,0,0,0.6); }
        
        .avatar { width: 120px; height: 120px; border-radius: 50%; border: 4px solid #ec4899; padding: 5px; margin: 0 auto 20px; box-shadow: 0 0 20px rgba(236, 72, 153, 0.5); }
        .avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
        .profile-name { font-size: 24px; font-weight: 700; margin-bottom: 5px; }
        .profile-role { color: #ec4899; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }

        .purchases-card { background: linear-gradient(145deg, rgba(30, 41, 59, 0.6), rgba(15, 23, 42, 0.8)); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.05); border-radius: 20px; padding: 40px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); transform: translateZ(10px); transition: 0.5s; }
        .purchases-card:hover { transform: translateZ(30px) rotateX(2deg); border-color: rgba(255,255,255,0.1); }
        
        .purchases-card h3 { margin-bottom: 25px; font-size: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; }

        .timeline { position: relative; padding-left: 30px; }
        .timeline::before { content: ''; position: absolute; left: 0; top: 0; width: 2px; height: 100%; background: rgba(236, 72, 153, 0.3); }
        
        .timeline-item { position: relative; margin-bottom: 25px; padding: 15px; background: rgba(255,255,255,0.02); border-radius: 12px; transition: 0.3s; border: 1px solid transparent; }
        .timeline-item::before { content: ''; position: absolute; left: -36px; top: 20px; width: 14px; height: 14px; border-radius: 50%; background: #ec4899; box-shadow: 0 0 10px #ec4899; }
        .timeline-item:hover { background: rgba(255,255,255,0.05); transform: scale(1.02) translateZ(15px); border-color: rgba(236, 72, 153, 0.2); box-shadow: 0 10px 20px rgba(0,0,0,0.3); }
        .timeline-date { font-size: 12px; color: #94a3b8; margin-bottom: 5px; }

        .btn-3d-shop { display: inline-block; margin-top: 30px; padding: 15px 40px; background: linear-gradient(135deg, #ec4899, #e11d48); border-radius: 30px; color: white; text-decoration: none; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; transform-style: preserve-3d; transition: 0.3s; box-shadow: 0 10px 20px rgba(225, 29, 72, 0.4), inset 0 -3px 0 rgba(0,0,0,0.2); }
        .btn-3d-shop:hover { transform: translateY(-5px) translateZ(20px); box-shadow: 0 15px 30px rgba(225, 29, 72, 0.6), inset 0 -3px 0 rgba(0,0,0,0.2); }
    </style>
</head>
<body>
    <div class="top-bar">
        <div class="logo-text">GlowLink</div>
        <a href="logout.php" class="btn-logout"><i class="fa-solid fa-power-off"></i> Sign Out</a>
    </div>

    <div class="dashboard-container">
        <div class="profile-card">
            <div class="avatar">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user_name']); ?>&background=ec4899&color=fff&size=120" alt="Avatar">
            </div>
            <h2 class="profile-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h2>
            <p class="profile-role"><?php echo htmlspecialchars($_SESSION['user_role']); ?> VIP</p>
            <br>
            <p style="color: #94a3b8; font-size: 14px;">Member since 2026</p>
            <a href="shop.php" class="btn-3d-shop" style="margin-top: 40px; padding: 12px 30px;">Shop Now</a>
        </div>

        <div class="purchases-card">
            <h3>Recent Activity & Orders</h3>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-date">Just Now</div>
                    <h4>Logged into GlowLink</h4>
                    <p style="font-size: 13px; color: #cbd5e1; margin-top: 5px;">You have successfully accessed your secure dashboard.</p>
                </div>
                <div style="text-align: center; color: #94a3b8; margin-top: 40px; font-size: 14px;">
                    <i class="fa-solid fa-box-open" style="font-size: 30px; margin-bottom: 15px; opacity: 0.6; color: #ec4899;"></i><br>
                    You haven't placed any orders yet.<br>
                    Click "Shop Now" to explore our products!
                </div>
            </div>
        </div>
    </div>
</body>
</html>