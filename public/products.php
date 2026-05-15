<?php
session_start();
include 'connection.php';


$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, 3308);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'retailer') {
    header("Location: login.php");
    exit;
}

$user_name = $_SESSION['user_name'] ?? 'Retailer Admin';

$success_msg = '';
$error_msg   = '';


if (isset($_POST['save_product'])) {
    $name         = trim($_POST['name']         ?? '');
    $brand        = trim($_POST['brand']        ?? '');
    $description  = trim($_POST['description']  ?? '');
    $sku          = trim($_POST['sku']          ?? '');
    $product_url  = trim($_POST['product_url']  ?? '');
    $price        = floatval($_POST['price']    ?? 0);
    $stock        = intval($_POST['stock']      ?? 0);

   
    $category     = trim($_POST['category'] ?? ''); 
    if ($category === '') {
        $category = null;
    }
    $main_image_url = null;
    $upload_dir     = 'uploads/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $tmp_name   = $_FILES['images']['tmp_name'][0];
        $clean_name = preg_replace("/[^A-Za-z0-9_\-\.]/", '_', basename($_FILES['images']['name'][0]));
        $file_name  = time() . "_" . rand(100, 999) . "_" . $clean_name;
        $target     = $upload_dir . $file_name;

        if (move_uploaded_file($tmp_name, $target)) {
            $main_image_url = $target;
        }
    }

    $sql = "INSERT INTO products (category, name, brand, main_image_url, sku, price, stock, description, product_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
       
        $stmt->bind_param("sssssdiis", $category, $name, $brand, $main_image_url, $sku, $price, $stock, $description, $product_url);
        
        if ($stmt->execute()) {
            $success_msg = "Product added successfully!";
        } else {
            $error_msg = "DB Error: " . $stmt->error; 
        }
        $stmt->close();
    } else {
        $error_msg = "Prepare failed: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product | GlowLink</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary:       #E8336D;
            --primary-soft:  #fce4ec;
            --primary-dark:  #c0235a;
            --accent:        #FF8FAB;
            --sidebar-bg:    #1a0a12;
            --sidebar-text:  #c9a0b4;
            --sidebar-hover: rgba(232, 51, 109, 0.18);
            --bg:            #f7f0f3;
            --card:          #ffffff;
            --border:        #eddde5;
            --text:          #1e0d17;
            --muted:         #8a6070;
            --success:       #2ecc71;
            --error:         #e74c3c;
            --radius:        16px;
            --radius-sm:     10px;
            --shadow:        0 4px 24px rgba(232,51,109,0.10);
            --shadow-lg:     0 12px 48px rgba(232,51,109,0.13);
        }

        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }

        /* ── Sidebar ── */
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            position: fixed;
            top: 0; left: 0; bottom: 0;
            display: flex;
            flex-direction: column;
            padding: 32px 16px 24px;
            z-index: 100;
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 26px;
            color: #fff;
            text-align: center;
            margin-bottom: 40px;
            letter-spacing: 0.5px;
        }
        .logo span { color: var(--accent); }

        .nav-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex: 1;
        }
        .nav-links a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--sidebar-text);
            text-decoration: none;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .nav-links a i { width: 18px; text-align: center; font-size: 15px; }
        .nav-links a:hover, .nav-links a.active {
            background: var(--sidebar-hover);
            color: var(--accent);
        }
        .nav-links a.active { border-left: 3px solid var(--primary); }

        .sidebar-logout {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            color: #f87171;
            text-decoration: none;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 500;
            background: rgba(239,68,68,0.08);
            transition: all 0.2s;
        }
        .sidebar-logout:hover { background: rgba(239,68,68,0.18); }

        /* ── Main ── */
        .main-wrapper {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* ── Top header ── */
        .top-header {
            background: var(--card);
            border-bottom: 1px solid var(--border);
            padding: 0 40px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        }
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--muted);
        }
        .breadcrumb strong { color: var(--text); font-size: 18px; font-family: 'Playfair Display', serif; }
        .breadcrumb i { font-size: 10px; }

        .header-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .user-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 16px 6px 6px;
            background: var(--primary-soft);
            border-radius: 999px;
        }
        .user-pill img { width: 34px; height: 34px; border-radius: 50%; border: 2px solid var(--primary); }
        .user-pill span { font-size: 13px; font-weight: 600; color: var(--primary-dark); }

        /* ── Content area ── */
        .content {
            padding: 36px 40px 60px;
            flex: 1;
        }

        /* ── Alert banners ── */
        .alert {
            padding: 14px 20px;
            border-radius: var(--radius-sm);
            margin-bottom: 24px;
            font-weight: 500;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }
        .alert-success { background: #edfaf3; color: #1a7a45; border: 1px solid #a7f3c8; }
        .alert-error   { background: #fef2f2; color: #9b1c1c; border: 1px solid #fca5a5; }

        /* ── Form layout ── */
        .form-layout {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 28px;
            align-items: start;
        }

        /* ── Card ── */
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 28px;
            box-shadow: var(--shadow);
        }
        .card-title {
            font-family: 'Playfair Display', serif;
            font-size: 16px;
            color: var(--text);
            margin-bottom: 24px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-title i { color: var(--primary); font-size: 15px; }

        /* ── Form elements ── */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .form-group { margin-bottom: 20px; }
        .form-group:last-child { margin-bottom: 0; }

        label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 7px;
        }
        .form-control {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            background: #fff;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            color: var(--text);
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(232,51,109,0.10);
        }
        .form-control::placeholder { color: #bba8b0; }
        select.form-control { cursor: pointer; }
        textarea.form-control { resize: vertical; min-height: 100px; }

        /* input with prefix */
        .input-group { position: relative; }
        .input-group .prefix {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 14px;
            pointer-events: none;
        }
        .input-group .form-control { padding-left: 30px; }

        /* ── Upload area ── */
        .upload-area {
            border: 2px dashed var(--border);
            border-radius: var(--radius-sm);
            padding: 32px 20px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
            background: #fdf6f8;
        }
        .upload-area:hover {
            border-color: var(--primary);
            background: var(--primary-soft);
        }
        .upload-area i { font-size: 38px; color: var(--primary); margin-bottom: 10px; display: block; }
        .upload-area p  { font-size: 13px; color: var(--muted); }
        .upload-area small { font-size: 11px; color: #c0a0ac; }

        .preview-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 14px;
        }
        .preview-grid .thumb {
            position: relative;
            width: 72px;
            height: 72px;
        }
        .preview-grid img {
            width: 72px; height: 72px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid var(--border);
        }
        .preview-grid .first-badge {
            position: absolute;
            bottom: 3px; left: 3px;
            background: var(--primary);
            color: #fff;
            font-size: 9px;
            font-weight: 700;
            padding: 1px 5px;
            border-radius: 4px;
        }

        /* ── Buttons ── */
        .btn-primary {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: var(--radius-sm);
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
            box-shadow: 0 4px 14px rgba(232,51,109,0.30);
        }
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(232,51,109,0.38);
        }
        .btn-primary:active { transform: translateY(0); }

        .btn-secondary {
            width: 100%;
            padding: 12px;
            background: transparent;
            color: var(--muted);
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.2s;
        }
        .btn-secondary:hover { border-color: var(--muted); color: var(--text); }

        /* ── Tips card ── */
        .tips-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .tips-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 13px;
            color: var(--muted);
            line-height: 1.5;
        }
        .tips-list li i { color: var(--primary); margin-top: 2px; flex-shrink: 0; }

        /* ── Responsive ── */
        @media (max-width: 1100px) {
            .form-layout { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-wrapper { margin-left: 0; }
            .content { padding: 20px; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- ══ Sidebar ══ -->
<aside class="sidebar">
    <div class="logo">Glow<span>Link</span></div>
    <ul class="nav-links">
        <li><a href="retailer_dashboard.php"><i class="fa-solid fa-border-all"></i> Dashboard</a></li>
        <li><a href="products.php" class="active"><i class="fa-solid fa-box-open"></i> Products</a></li>
        <li><a href="orders.php"><i class="fa-solid fa-clipboard-list"></i> Orders</a></li>
        <li><a href="my_products.php"><i class="fa-solid fa-tags"></i> My Products</a></li>
        <li><a href="admin_analysis.php"><i class="fa-solid fa-chart-line"></i> Analytics</a></li>
        <li><a href="setting.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
    </ul>
    <a class="sidebar-logout" href="logout.php">
        <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
    </a>
</aside>

<!-- ══ Main ══ -->
<div class="main-wrapper">

    <!-- Top header -->
    <header class="top-header">
        <div class="breadcrumb">
            <a href="products.php" style="color:var(--muted);text-decoration:none;">Products</a>
            <i class="fa-solid fa-chevron-right"></i>
            <strong>Add New Product</strong>
        </div>
        <div class="header-right">
            <div class="user-pill">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=E8336D&color=fff" alt="avatar">
                <span><?= htmlspecialchars($user_name) ?></span>
            </div>
        </div>
    </header>

    <!-- Content -->
    <div class="content">

        <?php if ($success_msg): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success_msg) ?>
                — <a href="my_products.php" style="color:inherit;font-weight:700;">View my products →</a>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">

            <div class="form-layout">

                <!-- ── Left column ── -->
                <div>
                    <!-- Basic Info -->
                    <div class="card" style="margin-bottom:24px;">
                        <div class="card-title"><i class="fa-solid fa-tag"></i> Product Information</div>

                        <div class="form-group">
                            <label>Product Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Innisfree Green Tea Serum 30ml" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Brand</label>
                                <input type="text" name="brand" class="form-control" placeholder="e.g. Innisfree" required>
                            </div>
                            <div class="form-group">
                                <label>Category</label>
                                <select name="category_id" class="form-control" required>
                                    <option value="">Select category…</option>
                                    <?php if (!empty($categories)): ?>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <!-- Fallback static options if categories table is empty -->
                                        <option value="1">Cleanser</option>
                                        <option value="2">Moisturizer</option>
                                        <option value="3">Serum</option>
                                        <option value="4">Sunscreen</option>
                                        <option value="5">Toner</option>
                                        <option value="6">Mask</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Price (BDT ৳)</label>
                                <div class="input-group">
                                    <span class="prefix">৳</span>
                                    <input type="number" step="0.01" name="price" class="form-control" placeholder="0.00" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Stock Quantity</label>
                                <input type="number" name="stock" class="form-control" placeholder="e.g. 50" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>SKU</label>
                                <input type="text" name="sku" class="form-control" placeholder="e.g. GLOW-1234" required>
                            </div>
                            <div class="form-group">
                                <label>Product URL <small style="text-transform:none;font-weight:400;">(optional)</small></label>
                                <input type="url" name="product_url" class="form-control" placeholder="https://...">
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="card" style="margin-bottom:24px;">
                        <div class="card-title"><i class="fa-solid fa-align-left"></i> Description & Ingredients</div>
                        <div class="form-group">
                            <label>Product Description</label>
                            <textarea name="description" class="form-control" rows="5"
                                placeholder="Describe the product benefits, how to use, ingredients, etc." required></textarea>
                        </div>
                    </div>

                    <!-- Images -->
                    <div class="card">
                        <div class="card-title"><i class="fa-solid fa-images"></i> Product Images</div>
                        <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                            <i class="fas fa-cloud-arrow-up"></i>
                            <p><strong>Click to upload</strong> or drag & drop images here</p>
                            <small>PNG, JPG, WEBP — First image becomes the main display image</small>
                            <input type="file" name="images[]" id="fileInput" multiple accept="image/*"
                                   style="display:none;" onchange="previewImages(this)">
                        </div>
                        <div class="preview-grid" id="imagePreview"></div>
                    </div>
                </div>

                <!-- ── Right column ── -->
                <div>
                    <!-- Submit card -->
                    <div class="card" style="margin-bottom:20px;">
                        <div class="card-title"><i class="fa-solid fa-paper-plane"></i> Publish</div>
                        <p style="font-size:13px;color:var(--muted);margin-bottom:20px;line-height:1.6;">
                            Review all details before publishing. The product will be visible to customers immediately.
                        </p>
                        <button type="submit" name="save_product" class="btn-primary">
                            <i class="fas fa-check-circle"></i> Save & Publish Product
                        </button>
                        <button type="reset" class="btn-secondary" onclick="document.getElementById('imagePreview').innerHTML=''">
                            <i class="fas fa-rotate-left"></i> Reset Form
                        </button>
                    </div>

                    <!-- Tips card -->
                    <div class="card">
                        <div class="card-title"><i class="fa-solid fa-lightbulb"></i> Tips for Better Listings</div>
                        <ul class="tips-list">
                            <li><i class="fa-solid fa-star"></i> Use a clean, white-background photo as the first (main) image.</li>
                            <li><i class="fa-solid fa-star"></i> Include the full volume/size in the product name.</li>
                            <li><i class="fa-solid fa-star"></i> Accurate SKU helps with inventory tracking.</li>
                            <li><i class="fa-solid fa-star"></i> A detailed description improves search visibility.</li>
                            <li><i class="fa-solid fa-star"></i> Set stock to <strong>0</strong> if the item is currently out of stock.</li>
                        </ul>
                    </div>
                </div>

            </div><!-- /form-layout -->
        </form>

    </div><!-- /content -->
</div><!-- /main-wrapper -->

<script>
function previewImages(input) {
    const container = document.getElementById('imagePreview');
    container.innerHTML = '';
    const files = input.files;

    Array.from(files).forEach((file, idx) => {
        const reader = new FileReader();
        reader.onload = e => {
            const wrap = document.createElement('div');
            wrap.className = 'thumb';

            const img = document.createElement('img');
            img.src = e.target.result;
            wrap.appendChild(img);

            if (idx === 0) {
                const badge = document.createElement('span');
                badge.className = 'first-badge';
                badge.textContent = 'MAIN';
                wrap.appendChild(badge);
            }

            container.appendChild(wrap);
        };
        reader.readAsDataURL(file);
    });
}
</script>
</body>
</html>