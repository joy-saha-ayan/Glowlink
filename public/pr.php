<?php
session_start();
include 'connection.php';



$delete_msg = '';
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $del_id);
        $stmt->execute() ? ($delete_msg = 'success') : ($delete_msg = 'error');
        $stmt->close();
    }
    header("Location: products.php?deleted=" . $delete_msg);
    exit;
}

$search = trim($_GET['search'] ?? '');
$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, 3308);

$sql = "SELECT * FROM products";

if ($search !== '') {
    $sql .= " WHERE name LIKE ? OR brand LIKE ? OR sku LIKE ?";
    $like = "%$search%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $like, $like, $like);
} else {
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();
$products = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Products | GlowLink Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #E8336D;
            --primary-soft: #fce4ec;
            --sidebar-bg: #1a0a12;
            --bg: #f7f0f3;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'DM Sans',sans-serif;
            background:var(--bg);
            display:flex;
            min-height:100vh;
        }
        .sidebar {
            width:260px; background:var(--sidebar-bg); color:white;
            position:fixed; top:0; bottom:0; padding:30px 20px;
            display:flex; flex-direction:column;
        }
        .logo { font-size:32px; font-weight:700; margin-bottom:50px; text-align:center; }
        .logo span { color:#ff8fab; }
        .nav a {
            display:flex; align-items:center; gap:12px;
            padding:14px 20px; color:#e0b4c9; text-decoration:none;
            border-radius:12px; margin-bottom:6px;
        }
        .nav a:hover, .nav a.active { background:rgba(232,51,109,0.25); color:white; }

        .main { margin-left:260px; flex:1; padding:25px; }
        .topbar {
            background:white; border-radius:20px; padding:15px 25px;
            display:flex; justify-content:space-between; align-items:center;
            margin-bottom:30px; box-shadow:0 5px 20px rgba(0,0,0,0.05);
        }
        .search-input {
            padding:12px 20px; width:380px; border:1.5px solid #eddde5;
            border-radius:999px; outline:none;
        }

        .product-grid {
            display:grid;
            grid-template-columns:repeat(auto-fill, minmax(260px, 1fr));
            gap:24px;
        }
        .product-card {
            background:white; border-radius:20px; overflow:hidden;
            box-shadow:0 8px 25px rgba(232,51,109,0.08);
            transition:all .3s;
        }
        .product-card:hover { transform:translateY(-8px); }
        .product-img-wrap {
            height:200px; overflow:hidden; position:relative;
        }
        .product-img-wrap img {
            width:100%; height:100%; object-fit:cover;
            transition:transform .4s;
        }
        .product-card:hover img { transform:scale(1.08); }
        .product-body { padding:18px; }
        .product-name { font-weight:600; font-size:15px; margin:8px 0; line-height:1.4; }
        .product-price { font-size:18px; font-weight:700; color:var(--primary); }
        .stock { 
            font-size:13px; padding:4px 12px; border-radius:20px; 
            display:inline-block; margin-top:8px; 
        }
        .stock-ok { background:#d1fae5; color:#059669; }
        .stock-low { background:#fef3c7; color:#d97706; }
        .stock-out { background:#fee2e2; color:#dc2626; }

        .actions {
            margin-top:12px;
            display:flex; gap:8px;
        }
        .btn {
            flex:1; padding:10px; border-radius:8px; text-align:center;
            text-decoration:none; font-size:13px; font-weight:600;
        }
        .btn-edit { background:#3b82f6; color:white; }
        .btn-delete { background:#ef4444; color:white; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="logo">Glow<span>Link</span></div>
    <div class="nav">
        <a href="admin_dashboard.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>
        <a href="users.php"><i class="fa-solid fa-users"></i> Users</a>
        <a href="products.php" class="active"><i class="fa-solid fa-box"></i> Products</a>
        <a href="commission.php"><i class="fa-solid fa-wallet"></i> Commission</a>
        <a href="analytics.php"><i class="fa-solid fa-chart-line"></i> Analytics</a>
    </div>
    <a href="logout.php" style="margin-top:auto; color:#f87171; text-align:center; padding:14px; background:rgba(239,68,68,0.1); border-radius:12px; text-decoration:none;">
        <i class="fa-solid fa-right-from-bracket"></i> Logout
    </a>
</div>

<!-- Main Content -->
<div class="main">
    <div class="topbar">
        <h2 style="color:#E8336D;">All Products (System Admin)</h2>
        <form method="GET" style="display:flex; gap:10px;">
            <input type="text" name="search" class="search-input" 
                   value="<?= htmlspecialchars($search) ?>" 
                   placeholder="Search by name, brand or SKU...">
            <button type="submit" style="background:#E8336D; color:white; border:none; padding:0 24px; border-radius:999px; cursor:pointer;">
                <i class="fa-solid fa-search"></i>
            </button>
        </form>
    </div>

    <?php if (isset($_GET['deleted'])): ?>
        <div style="padding:15px 20px; margin-bottom:25px; border-radius:12px; background:<?= $_GET['deleted']=='success' ? '#d1fae5' : '#fee2e2' ?>; color:<?= $_GET['deleted']=='success' ? '#166534' : '#991b1b' ?>;">
            <?= $_GET['deleted']=='success' ? '✅ Product deleted successfully!' : '❌ Failed to delete product.' ?>
        </div>
    <?php endif; ?>

    <?php if (empty($products)): ?>
        <div style="text-align:center; padding:100px 20px; background:white; border-radius:20px;">
            <h3>No products found.</h3>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $p): 
                $stock_class = ($p['stock'] <= 0) ? 'stock-out' : ($p['stock'] <= 10 ? 'stock-low' : 'stock-ok');
                $stock_text  = ($p['stock'] <= 0) ? 'Out of Stock' : $p['stock'] . ' in stock';
            ?>
            <div class="product-card">
                <div class="product-img-wrap">
                    <img src="<?= !empty($p['main_image_url']) ? htmlspecialchars($p['main_image_url']) : 'https://placehold.co/300x200/fce4ec/E8336D?text=No+Image' ?>" 
                         alt="<?= htmlspecialchars($p['name']) ?>">
                </div>
                <div class="product-body">
                    <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                    <div class="product-price">৳<?= number_format($p['price'], 2) ?></div>
                    <div class="stock <?= $stock_class ?>"><?= $stock_text ?></div>

                    <div class="actions">
                        <a href="edit_product.php?id=<?= $p['id'] ?>" class="btn btn-edit">
                            <i class="fa-solid fa-pen"></i> Edit
                        </a>
                        <a href="?delete=<?= $p['id'] ?>" onclick="return confirm('Are you sure you want to delete this product?')" 
                           class="btn btn-delete">
                            <i class="fa-solid fa-trash"></i> Delete
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>