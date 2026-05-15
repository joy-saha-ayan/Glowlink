<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_server = 'localhost';
$db_user = 'root';
$port = '3308';
$db_pass = '';
$db_name = 'glowlinkp_db';

$con = false;
try {
    $con = mysqli_connect($db_server, $db_user, $db_pass, $db_name, $port);
} catch(mysqli_sql_exception $e) {
    die("<div style='background: #ff4444; color: white; padding: 15px; text-align: center;'>Database connection failed.</div>");
}

$is_logged_in = isset($_SESSION['user_id']);

$products = [];
if($con) {
    $query = "SELECT * FROM products ORDER BY id DESC LIMIT 24";
    try {
        $result = mysqli_query($con, $query);
        if($result && mysqli_num_rows($result) > 0) {
            while($row = mysqli_fetch_assoc($result)) {
                $products[] = $row;
            }
        }
    } catch(Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GlowLink | Premium Light</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { 
            --accent: #d4a373; 
            --accent-glow: rgba(212, 163, 115, 0.4);
            --bg-light: #ffffff;
            --bg-sec: #f8f9fa;
            --text-main: #1a1a1a;
            --text-muted: #64748b;
            --card-shadow: 0 15px 35px rgba(0,0,0,0.06);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; scroll-behavior: smooth; }
        body { background: var(--bg-light); color: var(--text-main); overflow-x: hidden; }

        /* ===== NAVBAR ===== */
        .navbar {
            display: flex; justify-content: space-between; align-items: center;
            padding: 18px 5%; background: rgba(255,255,255,0.98);
            backdrop-filter: blur(15px); position: fixed; width: 100%; top: 0; z-index: 1000;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        }
        .logo { font-size: 24px; font-weight: 800; color: var(--text-main); letter-spacing: 2px; text-transform: uppercase; }
        .logo span { color: var(--accent); }

        /* Desktop nav */
        .nav-links { display: flex; align-items: center; }
        .nav-links a { color: var(--text-muted); text-decoration: none; margin-left: 30px; font-weight: 600; font-size: 13px; transition: 0.3s; }
        .nav-links a:hover { color: var(--accent); }
        .btn-action { 
            background: var(--text-main); color: white !important; 
            padding: 10px 24px; border-radius: 50px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: 0.3s; 
        }
        .btn-action:hover { background: var(--accent) !important; transform: translateY(-2px); }

        /* Mobile hamburger */
        .hamburger { display: none; background: none; border: none; cursor: pointer; padding: 5px; }
        .hamburger span { display: block; width: 25px; height: 2px; background: var(--text-main); margin: 5px 0; transition: 0.3s; }

        /* Mobile nav menu */
        .mobile-menu {
            display: none; position: fixed; top: 65px; left: 0; width: 100%;
            background: white; padding: 20px; z-index: 999;
            border-bottom: 1px solid rgba(0,0,0,0.08);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .mobile-menu a { display: block; padding: 14px 0; color: var(--text-main); text-decoration: none; font-weight: 600; font-size: 15px; border-bottom: 1px solid rgba(0,0,0,0.05); }
        .mobile-menu a:last-child { border-bottom: none; }
        .mobile-menu.open { display: block; }

        /* ===== HERO ===== */
        .hero {
            min-height: 100vh; display: flex; flex-direction: column;
            justify-content: center; align-items: center; text-align: center;
            background: linear-gradient(135deg, #fffaf5 0%, #ffffff 100%);
            padding: 100px 20px 60px; position: relative;
        }
        .hero h1 { font-size: clamp(32px, 6vw, 72px); margin-bottom: 20px; font-weight: 800; line-height: 1.1; color: var(--text-main); }
        .hero p { color: var(--text-muted); max-width: 580px; font-size: clamp(14px, 2vw, 18px); line-height: 1.6; margin-bottom: 10px; padding: 0 10px; }
        
        .search-box { position: relative; width: 90%; max-width: 620px; margin-top: 35px; z-index: 10; }
        .search-box input {
            width: 100%; padding: 18px 25px 18px 55px; border-radius: 100px;
            border: 1px solid rgba(0,0,0,0.1); background: white;
            color: var(--text-main); font-size: 15px; outline: none;
            box-shadow: 0 15px 35px rgba(0,0,0,0.05); transition: 0.3s;
        }
        .search-box input:focus { border-color: var(--accent); }
        .search-box i { position: absolute; left: 22px; top: 50%; transform: translateY(-50%); color: var(--accent); font-size: 18px; }

        .scroll-down { margin-top: 50px; font-weight: 600; letter-spacing: 1px; }

        /* ===== PRODUCTS ===== */
        .scroll-section { padding: 80px 5%; background: var(--bg-sec); }
        .section-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 50px; flex-wrap: wrap; gap: 15px; }
        .section-header h2 { font-size: clamp(26px, 4vw, 40px); font-weight: 800; color: var(--text-main); }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 25px; }
        
        .card {
            background: white; border: 1px solid rgba(0,0,0,0.05);
            border-radius: 20px; overflow: hidden; position: relative; 
            box-shadow: var(--card-shadow); transition: 0.3s; display: flex; flex-direction: column;
        }
        .card:hover { transform: translateY(-8px); box-shadow: 0 25px 45px rgba(0,0,0,0.1); }
        
        .card-img-wrapper {
            width: 100%; height: 240px; background: #fff; 
            display: flex; justify-content: center; align-items: center;
            border-bottom: 1px solid rgba(0,0,0,0.03); padding: 15px; overflow: hidden;
        }
        .card-img-wrapper img { 
            max-width: 100%; max-height: 100%; object-fit: contain; transition: 0.5s;
        }
        .card:hover .card-img-wrapper img { transform: scale(1.05); }

        .card-content { padding: 18px; display: flex; flex-direction: column; flex-grow: 1; }
        .card-tag { 
            position: absolute; top: 13px; right: 13px; background: rgba(255,255,255,0.92); 
            color: var(--text-main); padding: 4px 11px; border-radius: 50px; 
            font-size: 10px; font-weight: 800; box-shadow: 0 5px 15px rgba(0,0,0,0.08); 
            z-index: 2; backdrop-filter: blur(5px);
        }
        .card-title { font-size: 16px; margin-bottom: 7px; font-weight: 800; color: var(--text-main); line-height: 1.3; }
        .card-desc { color: var(--text-muted); margin-bottom: 18px; font-size: 12px; line-height: 1.5; flex-grow: 1; }
        .card-footer { display: flex; justify-content: space-between; align-items: center; margin-top: auto; }
        .card-price { font-size: 20px; font-weight: 800; color: var(--accent); }
        .card-btn { background: var(--bg-sec); color: var(--text-main); border: 1px solid rgba(0,0,0,0.07); padding: 8px 18px; border-radius: 50px; font-weight: 800; font-size: 12px; text-decoration: none; transition: 0.3s; }
        .card-btn:hover { background: var(--text-main); color: white; }

        .empty-state { text-align: center; padding: 50px; color: var(--text-muted); font-size: 16px; grid-column: 1 / -1; }

        /* ===== FOOTER ===== */
        .footer-section { background: white; padding: 60px 5% 30px; border-top: 1px solid rgba(0,0,0,0.05); }
        .footer-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 40px; margin-bottom: 50px; }
        .footer-col h4 { color: var(--text-main); font-size: 16px; font-weight: 800; margin-bottom: 20px; letter-spacing: 1px; }
        .footer-col p { color: var(--text-muted); font-size: 13px; line-height: 1.8; margin-bottom: 12px; }
        .footer-col ul { list-style: none; }
        .footer-col ul li { margin-bottom: 10px; }
        .footer-col ul li a { color: var(--text-muted); text-decoration: none; font-size: 13px; transition: 0.3s; }
        .footer-col ul li a:hover { color: var(--accent); padding-left: 5px; }

        /* ===== MOBILE RESPONSIVE ===== */
        @media (max-width: 768px) {
            .nav-links { display: none; }
            .hamburger { display: block; }
            .grid { grid-template-columns: 1fr 1fr; gap: 15px; }
            .card-img-wrapper { height: 180px; }
            .card-title { font-size: 13px; }
            .card-price { font-size: 16px; }
            .card-btn { padding: 6px 12px; font-size: 11px; }
            .scroll-section { padding: 60px 4%; }
            .section-header { margin-bottom: 30px; }
        }

        @media (max-width: 400px) {
            .grid { grid-template-columns: 1fr; }
            .card-img-wrapper { height: 220px; }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="logo">GLOW<span>LINK</span></div>
        
        <!-- Desktop nav -->
        <div class="nav-links">
            <a href="#outlook">COLLECTIONS</a>
            <?php if ($is_logged_in): ?>
                <a href="index.php">DASHBOARD</a>
                <a href="login.php" class="btn-action">Sign In</a>
            <?php else: ?>
                <a href="login.php">LOGIN</a>
                <a href="register.php" class="btn-action">REGISTER</a>
            <?php endif; ?>
        </div>

        <!-- Mobile hamburger -->
        <button class="hamburger" onclick="toggleMenu()" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </nav>

    <!-- Mobile menu -->
    <div class="mobile-menu" id="mobileMenu">
        <a href="#outlook" onclick="toggleMenu()">COLLECTIONS</a>
        <?php if ($is_logged_in): ?>
            <a href="index.php">DASHBOARD</a>
            <a href="logout.php">LOGOUT</a>
        <?php else: ?>
            <a href="login.php">LOGIN</a>
            <a href="register.php">REGISTER</a>
        <?php endif; ?>
    </div>

    <header class="hero">
        <h1>Discover the Science <br> of <span style="color:var(--accent)">Radiance.</span></h1>
        <p>Premium aesthetics and skincare curated for the modern lifestyle. Elevate your daily routine with pure, science-backed luxury.</p>
        
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchInput" placeholder="Search for luxury beauty brands or products..." oninput="filterProducts()">
        </div>
        
        <div class="scroll-down">
            <a href="#outlook" style="color:var(--text-muted); text-decoration:none;">
                <i class="fa-solid fa-chevron-down" style="display:block; margin-bottom:5px; color:var(--accent);"></i> SCROLL DOWN
            </a>
        </div>
    </header>

    <section id="outlook" class="scroll-section">
        <div class="section-header">
            <div>
                <p style="color:var(--accent); font-weight:800; letter-spacing:3px; font-size:13px; margin-bottom:8px;">SHOP THE LOOK</p>
                <h2>Premium Collection</h2>
            </div>
            <a href="#" style="color:var(--text-muted); text-decoration:none; font-weight:600; font-size:14px;">View All →</a>
        </div>

        <div class="grid" id="productGrid">
            <?php 
            if (count($products) > 0):
                foreach ($products as $item): 
                    $p_name  = isset($item['name']) ? $item['name'] : 'Unknown Product';
                    $p_desc  = isset($item['description']) ? substr($item['description'], 0, 70) . '...' : 'Premium skincare product.';
                    $p_price = isset($item['price']) ? number_format($item['price'], 2) : '0.00';
                    $p_id    = isset($item['id']) ? $item['id'] : '#';

                    $p_img = !empty($item['main_image_url']) 
                            ? $item['main_image_url'] 
                            : 'https://via.placeholder.com/400x400?text=No+Image';
                
            ?>
            <div class="card" data-name="<?php echo strtolower(htmlspecialchars($p_name)); ?>">
                <div class="card-tag">NEW</div>
                <div class="card-img-wrapper">
                    <img 
                        src="<?php echo htmlspecialchars($p_img); ?>" 
                        alt="<?php echo htmlspecialchars($p_name); ?>" 
                        loading="lazy"
                        onerror="this.src='https://via.placeholder.com/400x400?text=No+Image'"
                    >
                </div>
                <div class="card-content">
                    <h3 class="card-title"><?php echo htmlspecialchars($p_name); ?></h3>
                    <p class="card-desc"><?php echo htmlspecialchars($p_desc); ?></p>
                    <div class="card-footer">
                        <span class="card-price">৳<?php echo $p_price; ?></span>
                        <a href="detls.php?id=<?php echo $p_id; ?>" class="card-btn">DETAILS</a>
                    </div>
                </div>
            </div>
            <?php 
                endforeach; 
            else: 
            ?>
                <div class="empty-state">
                    <i class="fa-solid fa-box-open" style="font-size: 40px; color: var(--accent); margin-bottom: 15px; display:block;"></i>
                    <p>No products found.<br>Please add products from the admin panel.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer class="footer-section">
        <div class="footer-grid">
            <div class="footer-col">
                <div class="logo" style="margin-bottom: 18px; font-size: 20px;">GLOW<span>LINK</span></div>
                <p>Redefining luxury skincare with scientifically proven ingredients.</p>
            </div>
            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="#">Shop All</a></li>
                    <li><a href="#">Best Sellers</a></li>
                    <li><a href="about.php">About Us</a></li>
                </ul>
            </div>
        </div>
        <div style="text-align:center; padding-top:25px; border-top:1px solid rgba(0,0,0,0.05); color:var(--text-muted); font-size:13px;">
            <p>&copy; <?php echo date("Y"); ?> GlowLink Premium Beauty Network.</p>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        function toggleMenu() {
            document.getElementById('mobileMenu').classList.toggle('open');
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            const menu = document.getElementById('mobileMenu');
            const hamburger = document.querySelector('.hamburger');
            if (!menu.contains(e.target) && !hamburger.contains(e.target)) {
                menu.classList.remove('open');
            }
        });

        // Search/filter products
        function filterProducts() {
            const query = document.getElementById('searchInput').value.toLowerCase();
            const cards = document.querySelectorAll('#productGrid .card');
            cards.forEach(card => {
                const name = card.getAttribute('data-name') || '';
                card.style.display = name.includes(query) ? 'flex' : 'none';
            });
        }
    </script>

</body>
</html>