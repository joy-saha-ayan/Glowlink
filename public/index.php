<?php
session_start();
require_once '../config/Database.php';

$is_logged_in = isset($_SESSION['user_id']);

// Dummy dynamic images for "Outlook" (Replace with your database logic later)
$beauty_images = [
    "https://images.unsplash.com/photo-1596462502278-27bfdc4033c8?auto=format&fit=crop&q=80&w=800",
    "https://images.unsplash.com/photo-1556228720-195a672e8a03?auto=format&fit=crop&q=80&w=800",
    "https://images.unsplash.com/photo-1612817288484-6f916006741a?auto=format&fit=crop&q=80&w=800",
    "https://images.unsplash.com/photo-1570172619380-2126adbc8940?auto=format&fit=crop&q=80&w=800"
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GlowLink | Premium Experience</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    
    <style>
        :root { --accent: #3b82f6; --glass: rgba(255, 255, 255, 0.03); }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; scroll-behavior: smooth; }
        
        body { background: #020617; color: white; overflow-x: hidden; }

        /* Navbar */
        .navbar {
            display: flex; justify-content: space-between; align-items: center;
            padding: 20px 5%; background: rgba(2, 6, 23, 0.85);
            backdrop-filter: blur(20px); position: fixed; width: 100%; top: 0; z-index: 1000;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .logo { font-size: 24px; font-weight: 800; color: var(--accent); letter-spacing: 2px; }
        .nav-links a { color: #94a3b8; text-decoration: none; margin-left: 30px; font-weight: 600; font-size: 14px; transition: 0.3s; }
        .nav-links a:hover { color: white; }
        .btn-action { background: var(--accent); color: white !important; padding: 12px 25px; border-radius: 50px; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3); }

        /* Hero Section (Search) */
        .hero {
            height: 100vh; display: flex; flex-direction: column;
            justify-content: center; align-items: center; text-align: center;
            background: radial-gradient(circle at center, #1e293b 0%, #020617 100%);
            padding-top: 80px;
        }
        .hero h1 { font-size: clamp(40px, 8vw, 70px); margin-bottom: 20px; font-weight: 800; line-height: 1.1; }
        .search-box { position: relative; width: 90%; max-width: 700px; margin-top: 40px; }
        .search-box input {
            width: 100%; padding: 22px 30px 22px 65px; border-radius: 100px;
            border: 1px solid rgba(255,255,255,0.1); background: var(--glass);
            color: white; font-size: 18px; outline: none; backdrop-filter: blur(15px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }
        .search-box i { position: absolute; left: 25px; top: 50%; transform: translateY(-50%); color: var(--accent); font-size: 22px; }

        /* Dynamic Outlook Section (The Scrollable Part) */
        .scroll-section { padding: 100px 5%; background: #020617; }
        .section-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 50px; }
        .section-header h2 { font-size: 36px; font-weight: 800; }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 40px; }
        .card {
            background: var(--glass); border: 1px solid rgba(255,255,255,0.05);
            border-radius: 30px; padding: 10px; transition: 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative; overflow: hidden;
        }
        .card:hover { transform: translateY(-15px) scale(1.02); border-color: var(--accent); background: rgba(255,255,255,0.06); }
        .card img { width: 100%; height: 350px; object-fit: cover; border-radius: 25px; }
        .card-content { padding: 25px; }
        .card-tag { position: absolute; top: 25px; right: 25px; background: var(--accent); padding: 5px 15px; border-radius: 50px; font-size: 12px; font-weight: bold; }

        /* Modal styling */
        .login-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.9); backdrop-filter: blur(10px); z-index: 2000;
            justify-content: center; align-items: center;
        }
        .modal { background: #0f172a; padding: 50px; border-radius: 30px; text-align: center; border: 1px solid rgba(255,255,255,0.1); max-width: 450px; }

        footer { padding: 100px 5%; text-align: center; border-top: 1px solid rgba(255,255,255,0.05); color: #64748b; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="logo">GLOWLINK</div>
        <div class="nav-links">
            <a href="#outlook">OUTLOOK</a>
            <a href="javascript:void(0)" onclick="checkAccess()">ADD PRODUCT</a>
            <?php if ($is_logged_in): ?>
                <a href="index.php" class="btn-action">DASHBOARD</a>
            <?php else: ?>
                <a href="login.php">LOGIN</a>
                <a href="register.php" class="btn-action">GET STARTED</a>
            <?php endif; ?>
        </div>
    </nav>

    <header class="hero">
        <h1 data-aos="fade-down">Discover the Science <br> of <span style="color:var(--accent)">Radiance.</span></h1>
        <p style="color:#94a3b8; max-width: 500px;" data-aos="fade-up">Premium aesthetics and skincare curated for the modern lifestyle.</p>
        
        <div class="search-box" data-aos="zoom-in" data-aos-delay="200">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" placeholder="Search for luxury beauty brands...">
        </div>
        
        <div style="margin-top: 50px; animation: bounce 2s infinite;">
            <a href="#outlook" style="color:var(--accent); text-decoration:none;"><i class="fa-solid fa-chevron-down"></i> SCROLL DOWN</a>
        </div>
    </header>

    <section id="outlook" class="scroll-section">
        <div class="section-header">
            <div data-aos="fade-right">
                <p style="color:var(--accent); font-weight:bold; letter-spacing:2px;">DYNAMISM</p>
                <h2>Product Outlook</h2>
            </div>
            <a href="products.php" style="color:#94a3b8; text-decoration:none;">View All Collections →</a>
        </div>

        <div class="grid">
            <?php foreach ($beauty_images as $index => $url): ?>
            <div class="card" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                <div class="card-tag">NEW DROP</div>
                <img src="<?php echo $url; ?>" alt="Beauty Product">
                <div class="card-content">
                    <h3>Premium Skin Essence</h3>
                    <p style="color:#64748b; margin: 10px 0;">Ultra-hydration for daily glow.</p>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="font-size:24px; font-weight:800; color:var(--accent);">$89.00</span>
                        <button onclick="checkAccess()" style="background:white; border:none; padding:10px 20px; border-radius:50px; font-weight:bold; cursor:pointer;">DETAILS</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <footer>
        <div class="logo" style="margin-bottom: 20px;">GLOWLINK</div>
        <p>&copy; 2026 Premium Beauty Network. All rights reserved.</p>
    </footer>

    <div id="loginOverlay" class="login-overlay">
        <div class="modal">
            <i class="fa-solid fa-lock" style="font-size: 60px; color: var(--accent); margin-bottom: 25px;"></i>
            <h2>Secure Access</h2>
            <p style="color: #94a3b8; margin: 20px 0;">Please log in to your account to view full specifications or add new products to the inventory.</p>
            <a href="login.php" class="btn-action" style="display: block; text-decoration: none; margin-bottom: 15px;">LOGIN NOW</a>
            <a href="javascript:void(0)" onclick="document.getElementById('loginOverlay').style.display='none'" style="color: #475569; text-decoration: none;">Maybe later</a>
        </div>
    </div>

    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 1000, once: true });

        function checkAccess() {
            const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
            if (!isLoggedIn) {
                document.getElementById('loginOverlay').style.display = 'flex';
                document.body.style.overflow = 'hidden'; // Stop scrolling when modal open
            } else {
                window.location.href = 'add_product.php';
            }
        }
    </script>
</body>
</html>