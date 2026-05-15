<?php
session_start();
include 'connection.php';

mysqli_report(MYSQLI_REPORT_OFF);

// Role check
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

$user_name = $_SESSION['user_name'] ?? 'Retailer Admin';
$product_id = $_GET['id'] ?? null;
$message = '';

if (!$product_id) {
    die("Invalid Product ID! Please go back to the products page.");
}

// Handle Form Submission (Update Product)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $brand = trim($_POST['brand']);
    $sku = trim($_POST['sku']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $main_image_url = trim($_POST['main_image_url']);
    $description = trim($_POST['description']);

    try {
        $stmt = $db->prepare(
            "UPDATE products 
             SET name = ?, brand = ?, sku = ?, price = ?, stock = ?, main_image_url = ?, description = ? 
             WHERE id = ?"
        );
        $stmt->execute([$name, $brand, $sku, $price, $stock, $main_image_url, $description, $product_id]);
        $message = "<div class='alert success'><i class='fa-solid fa-circle-check'></i> Product updated successfully!</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert error'><i class='fa-solid fa-circle-exclamation'></i> Failed to update: " . $e->getMessage() . "</div>";
    }
}

// Fetch Current Product Details
try {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        die("Product not found in the database.");
    }
} catch (PDOException $e) {
    die("Error fetching product: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product | GlowLink</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            --danger:       #ef4444;
            --radius:       16px;
            --radius-sm:    10px;
            --shadow:       0 4px 24px rgba(232,51,109,0.08);
        }
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); display:flex; height:100vh; overflow:hidden; }

        /* Sidebar Styles */
        .sidebar { width:260px; background:var(--sidebar-bg); display:flex; flex-direction:column; padding:32px 16px 24px; position:fixed; top:0; left:0; bottom:0; z-index:100; }
        .logo { font-family:'Playfair Display',serif; font-size:26px; color:#fff; text-align:center; margin-bottom:40px; letter-spacing:.5px; }
        .logo span { color:var(--accent); }
        .nav-links { list-style:none; display:flex; flex-direction:column; gap:4px; flex:1; }
        .nav-links a { display:flex; align-items:center; gap:12px; padding:12px 16px; color:var(--sidebar-text); text-decoration:none; border-radius:var(--radius-sm); font-size:14px; font-weight:500; transition:all .2s; }
        .nav-links a i { width:18px; text-align:center; font-size:15px; }
        .nav-links a:hover, .nav-links a.active { background:var(--sidebar-hover); color:var(--accent); }
        .nav-links a.active { border-left:3px solid var(--primary); }
        .sidebar-logout { display:flex; align-items:center; gap:10px; padding:12px 16px; color:#f87171; text-decoration:none; border-radius:var(--radius-sm); font-size:14px; font-weight:500; background:rgba(239,68,68,.08); transition:all .2s; }
        .sidebar-logout:hover { background:rgba(239,68,68,.18); }

        /* Main Content */
        .main-wrapper { margin-left:260px; flex:1; display:flex; flex-direction:column; overflow:hidden; }
        .top-header { background:var(--card); border-bottom:1px solid var(--border); padding:0 40px; height:70px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 2px 12px rgba(0,0,0,.04); flex-shrink:0; }
        .header-title { font-size:18px; font-weight:600; color:var(--text); }
        .header-right { display:flex; align-items:center; gap:16px; margin-left:auto; }
        .user-pill { display:flex; align-items:center; gap:10px; padding:6px 16px 6px 6px; background:var(--primary-soft); border-radius:999px; }
        .user-pill img { width:34px; height:34px; border-radius:50%; border:2px solid var(--primary); }
        .user-pill div p:first-child { font-size:13px; font-weight:600; color:var(--primary-dark); }
        .user-pill div p:last-child  { font-size:11px; color:var(--muted); }

        .content { padding:32px 40px 40px; overflow-y:auto; flex:1; }

        /* Alerts */
        .alert { padding:14px 20px; border-radius:10px; margin-bottom:24px; font-size:14px; font-weight:500; display:flex; align-items:center; gap:10px; }
        .alert.success { background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }
        .alert.error { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }

        /* Form Card */
        .form-card { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); padding:30px; box-shadow:var(--shadow); display:grid; grid-template-columns:1fr 280px; gap:40px; }
        
        .form-section-title { font-family:'Playfair Display',serif; font-size:18px; margin-bottom:24px; border-bottom:2px solid var(--primary-soft); padding-bottom:10px; color:var(--primary-dark); }
        
        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        .form-group { margin-bottom:20px; }
        .form-group.full-width { grid-column:span 2; }
        .form-group label { display:block; font-size:13px; font-weight:600; color:var(--muted); margin-bottom:8px; text-transform:uppercase; letter-spacing:0.5px; }
        .form-control { width:100%; padding:12px 16px; border:1.5px solid var(--border); border-radius:8px; font-size:14px; font-family:inherit; transition:all 0.2s; background:#fdfafb; }
        .form-control:focus { outline:none; border-color:var(--primary); background:#fff; box-shadow:0 0 0 4px var(--primary-soft); }
        textarea.form-control { resize:vertical; min-height:100px; }
        
        /* Image Preview Side */
        .image-preview-container { display:flex; flex-direction:column; align-items:center; text-align:center; padding:20px; background:#fdfafb; border-radius:12px; border:1px dashed var(--muted); }
        .image-preview-container img { width:100%; max-width:200px; border-radius:8px; object-fit:cover; margin-bottom:16px; box-shadow:0 4px 12px rgba(0,0,0,0.1); }
        .image-preview-container p { font-size:12px; color:var(--muted); }

        /* Buttons */
        .btn-group { display:flex; gap:12px; margin-top:20px; justify-content:flex-end; grid-column:span 2; }
        .btn { padding:12px 24px; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; transition:all 0.2s; text-decoration:none; display:inline-flex; align-items:center; gap:8px; border:none; }
        .btn-primary { background:var(--primary); color:#fff; }
        .btn-primary:hover { background:var(--primary-dark); transform:translateY(-2px); box-shadow:0 4px 12px rgba(232,51,109,0.3); }
        .btn-secondary { background:#f1e4e8; color:var(--text); }
        .btn-secondary:hover { background:#e2d1d7; }

        @media (max-width: 900px) {
            .form-card { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
            .form-group.full-width { grid-column: span 1; }
            .btn-group { grid-column: span 1; }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="logo">Glow<span>Link</span></div>
    <ul class="nav-links">
        <li><a href="retailer_dashboard.php"><i class="fa-solid fa-border-all"></i> Dashboard</a></li>
        <li><a href="products.php"><i class="fa-solid fa-box-open"></i> Products</a></li>
        <li><a href="orders.php"><i class="fa-solid fa-clipboard-list"></i> Orders</a></li>
        <li><a href="my_products.php" class="active"><i class="fa-solid fa-tags"></i> My Products</a></li>
        <li><a href="admin_analysis.php"><i class="fa-solid fa-chart-line"></i> Analytics</a></li>
        <li><a href="setting.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
    </ul>
    <a class="sidebar-logout" href="logout.php">
        <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
    </a>
</aside>

<div class="main-wrapper">
    <header class="top-header">
        <div class="header-title">
            <a href="my_products.php" style="color:var(--muted); text-decoration:none; margin-right:10px;"><i class="fa-solid fa-arrow-left"></i> Back</a> 
            Edit Product
        </div>
        <div class="header-right">
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
        <?= $message ?>

        <form method="POST" action="" class="form-card">
            <div class="form-main">
                <h3 class="form-section-title">Product Details</h3>
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Product Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($product['name'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Brand</label>
                        <input type="text" name="brand" class="form-control" value="<?= htmlspecialchars($product['brand'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>SKU (Stock Keeping Unit)</label>
                        <input type="text" name="sku" class="form-control" value="<?= htmlspecialchars($product['sku'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Price (৳)</label>
                        <input type="number" step="0.01" name="price" class="form-control" value="<?= htmlspecialchars($product['price'] ?? '0') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Stock Quantity</label>
                        <input type="number" name="stock" class="form-control" value="<?= htmlspecialchars($product['stock'] ?? '0') ?>" required>
                    </div>

                    <div class="form-group full-width">
                        <label>Main Image URL</label>
                        <input type="url" name="main_image_url" id="img_url_input" class="form-control" value="<?= htmlspecialchars($product['main_image_url'] ?? '') ?>" oninput="updatePreview()">
                    </div>

                    <div class="form-group full-width">
                        <label>Description</label>
                        <textarea name="description" class="form-control"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                    </div>

                    <div class="btn-group">
                        <a href="my_products.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
                    </div>
                </div>
            </div>

            <div class="image-preview-container">
                <h3 class="form-section-title" style="border:none; margin-bottom:10px;">Image Preview</h3>
                <?php 
                    $imgUrl = !empty($product['main_image_url']) ? htmlspecialchars($product['main_image_url']) : 'https://placehold.co/400x400/fce4ec/E8336D?text=No+Image';
                ?>
                <img id="img_preview" src="<?= $imgUrl ?>" alt="Product Preview" onerror="this.src='https://placehold.co/400x400/fce4ec/E8336D?text=Invalid+Image'">
                <p>If you change the Image URL, the preview will update automatically.</p>
            </div>
        </form>

    </div>
</div>

<script>
    // Live update for image preview
    function updatePreview() {
        const urlInput = document.getElementById('img_url_input').value;
        const imgPreview = document.getElementById('img_preview');
        if(urlInput.trim() !== '') {
            imgPreview.src = urlInput;
        } else {
            imgPreview.src = 'https://placehold.co/400x400/fce4ec/E8336D?text=No+Image';
        }
    }
</script>
</body>
</html>