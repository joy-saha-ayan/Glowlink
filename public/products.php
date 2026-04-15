<?php
session_start();

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'glowlinkp_db';
$conn = new mysqli("localhost", "root", "", "glowlinkp_db", 3308);

$products = [];
if (!$conn->connect_error) {
    $result = $conn->query("SELECT * FROM products ORDER BY id DESC");
    if ($result) {
        $products = $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GlowLink Pro | Premium Inventory</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.0/vanilla-tilt.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <style>
        /* ================= 1. MASTER VARIABLES ================= */
        :root {
            --bg-main: #0B0E14;
            --bg-panel: #151A23;
            --bg-panel-hover: #1E2532;
            --primary: #6366F1;
            --accent: #EC4899;
            --success: #10B981;
            --danger: #F43F5E;
            --warning: #F59E0B;
            --text-light: #F8FAFC;
            --text-muted: #94A3B8;
            --border-color: rgba(255, 255, 255, 0.06);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        
        body { 
            background-color: var(--bg-main); 
            color: var(--text-light); 
            display: flex; 
            min-height: 100vh; 
            overflow-x: hidden;
            background-image: radial-gradient(circle at 15% 50%, rgba(99, 102, 241, 0.08), transparent 25%), radial-gradient(circle at 85% 30%, rgba(236, 72, 153, 0.08), transparent 25%);
        }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }

        /* ================= 2. SIDEBAR ================= */
        .sidebar { width: 260px; background: var(--bg-panel); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; position: fixed; height: 100vh; z-index: 100; }
        .brand-area { height: 80px; display: flex; align-items: center; padding: 0 25px; border-bottom: 1px solid var(--border-color); }
        .brand-area h2 { font-size: 22px; font-weight: 800; letter-spacing: 0.5px; }
        .brand-area span { color: var(--accent); }
        
        .nav-menu { padding: 25px 15px; flex: 1; display: flex; flex-direction: column; gap: 8px; }
        .nav-item { display: flex; align-items: center; gap: 15px; padding: 14px 20px; border-radius: 10px; color: var(--text-muted); text-decoration: none; font-weight: 600; font-size: 14px; transition: 0.3s; }
        .nav-item i { font-size: 16px; width: 20px; text-align: center; }
        .nav-item:hover { background: rgba(255,255,255,0.03); color: var(--text-light); }
        .nav-item.active { background: rgba(236, 72, 153, 0.1); color: var(--accent); position: relative; }
        
        .logout-btn { margin: 20px 15px; background: rgba(244, 63, 94, 0.1); color: var(--danger); border: 1px solid rgba(244, 63, 94, 0.2); padding: 12px; text-align: center; border-radius: 10px; text-decoration: none; font-weight: 600; }

        /* ================= 3. MAIN CONTENT ================= */
        .main-wrapper { margin-left: 260px; flex: 1; display: flex; flex-direction: column; }
        .topbar { height: 80px; display: flex; justify-content: space-between; align-items: center; padding: 0 40px; background: rgba(21, 26, 35, 0.5); backdrop-filter: blur(10px); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 50; }
        
        .content { padding: 40px; }
        .page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }
        .page-header h1 { font-size: 28px; font-weight: 700; margin-bottom: 5px; }
        .page-header p { color: var(--text-muted); font-size: 14px; }
        
        .header-buttons { display: flex; gap: 15px; }
        .btn { padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 13px; border: none; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-ghost { background: transparent; color: var(--text-muted); border: 1px solid var(--border-color); }
        .btn-accent { background: linear-gradient(to right, #EC4899, #F43F5E); color: #fff; }

        /* ================= 4. INVENTORY GRID ================= */
        .inventory-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        .product-card { background: var(--bg-panel); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; transition: 0.3s; position: relative; }
        .product-card:hover { transform: translateY(-5px); border-color: rgba(255,255,255,0.15); box-shadow: 0 20px 40px rgba(0,0,0,0.5); }
        
        .p-image { height: 200px; background: rgba(0,0,0,0.3); display: flex; justify-content: center; align-items: center; position: relative; padding: 20px; }
        .p-image img { max-width: 100%; max-height: 100%; object-fit: contain; filter: drop-shadow(0 10px 15px rgba(0,0,0,0.5)); transition: 0.5s; }
        .product-card:hover .p-image img { transform: scale(1.1); }
        
        .p-status { position: absolute; top: 15px; left: 15px; font-size: 10px; font-weight: 700; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; }
        .status-in { background: rgba(16, 185, 129, 0.15); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.3); }
        .status-low { background: rgba(244, 63, 94, 0.15); color: var(--danger); border: 1px solid rgba(244, 63, 94, 0.3); }

        .p-details { padding: 20px; }
        .p-title { font-size: 16px; font-weight: 700; margin-bottom: 5px; }
        .p-sku { font-size: 12px; color: var(--text-muted); margin-bottom: 15px; }
        .p-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border-color); }
        .p-price { font-size: 18px; font-weight: 800; color: var(--accent); }
        
        .icon-btn { width: 35px; height: 35px; border-radius: 50%; background: var(--bg-main); border: 1px solid var(--border-color); display: flex; justify-content: center; align-items: center; color: var(--text-muted); cursor: pointer; }

        /* Add New Card */
        .add-new-card { border: 2px dashed var(--border-color); background: transparent; display: flex; flex-direction: column; justify-content: center; align-items: center; color: var(--text-muted); cursor: pointer; min-height: 340px; text-decoration: none; }
        .add-new-card i { font-size: 40px; margin-bottom: 15px; color: rgba(255,255,255,0.1); transition: 0.3s; }
        .add-new-card:hover { background: rgba(255,255,255,0.02); border-color: var(--primary); color: #fff; }
        .add-new-card:hover i { color: var(--primary); transform: scale(1.1); }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="brand-area">
            <h2>GLOW<span>LINK</span></h2>
        </div>
        <div class="nav-menu">
            <a href="dashboard.php" class="nav-item"><i class="fa-solid fa-border-all"></i> Dashboard</a>
            <a href="products.php" class="nav-item active"><i class="fa-solid fa-box-open"></i> Products</a>
            <a href="orders.php" class="nav-item"><i class="fa-solid fa-cart-shopping"></i> Orders</a>
            <a href="settings.php" class="nav-item"><i class="fa-solid fa-gear"></i> Settings</a>
        </div>
    </aside>

    <div class="main-wrapper">
        <header class="topbar">
            <h3 style="color: var(--text-muted);">Inventory Management</h3>
            <div style="display: flex; gap: 10px; align-items: center;">
                <span style="font-weight: 600;">Admin</span>
                <div style="width:35px; height:35px; border-radius:50%; background:var(--primary); display:grid; place-items:center;">A</div>
            </div>
        </header>

        <div class="content">
            <div class="page-header">
                <div>
                    <h1>Products Inventory</h1>
                    <p>Manage your products seamlessly from the database.</p>
                </div>
                <div class="header-buttons">
                    <a href="add_product.php" class="btn btn-accent">
                        <i class="fa-solid fa-plus"></i> Add New Product
                    </a>
                </div>
            </div>

            <div class="inventory-grid">
                <a href="add_product.php" class="product-card add-new-card">
                    <i class="fa-solid fa-plus-circle"></i>
                    <h3 style="font-size: 16px;">Add New Product</h3>
                </a>

                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $row): ?>
                        <div class="product-card">
                            <?php if($row['stock'] > 10): ?>
                                <div class="p-status status-in">In Stock (<?php echo $row['stock']; ?>)</div>
                            <?php else: ?>
                                <div class="p-status status-low">Low Stock (<?php echo $row['stock']; ?>)</div>
                            <?php endif; ?>
                            
                            <div class="p-image">
                                <img src="<?php echo htmlspecialchars($row['image']); ?>" alt="Product">
                            </div>
                            <div class="p-details">
                                <div class="p-sku">SKU: <?php echo htmlspecialchars($row['sku']); ?></div>
                                <div class="p-title"><?php echo htmlspecialchars($row['name']); ?></div>
                                <div class="p-footer">
                                    <div class="p-price">$<?php echo number_format($row['price'], 2); ?></div>
                                    <button class="icon-btn"><i class="fa-solid fa-pen"></i></button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 50px;">
                        No products found. Click "Add New Product" to create one.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>