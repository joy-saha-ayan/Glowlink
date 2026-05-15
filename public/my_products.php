<?php
session_start();
include 'connection.php';

mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, 3308);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'retailer') {
    header("Location: login.php");
    exit;
}

$retailer_id = $_SESSION['user_id'] ?? 1;
$user_name   = $_SESSION['user_name'] ?? 'Retailer Admin';

$delete_msg = '';
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $stmt   = $conn->prepare("DELETE FROM products WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $del_id);
        $stmt->execute() ? ($delete_msg = 'success') : ($delete_msg = 'error');
        $stmt->close();
    }
    header("Location: my_products.php?deleted=" . $delete_msg);
    exit;
}

$search = trim($_GET['search'] ?? '');

$sql    = "SELECT * FROM products";
$params = [];
$types  = "";

if ($search !== '') {
    $sql   .= " WHERE name LIKE ? OR brand LIKE ? OR sku LIKE ?";
    $like   = "%$search%";
    $params = [$like, $like, $like];
    $types  = "sss";
}
$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Query Prepare failed: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$products = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products | GlowLink</title>
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
            --danger:       #ef4444;
            --radius:       16px;
            --radius-sm:    10px;
            --shadow:       0 4px 24px rgba(232,51,109,0.08);
        }
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'DM Sans',sans-serif;
            background:var(--bg); color:var(--text);
            display:flex; min-height:100vh;
        }

        /* ── Sidebar ── */
        .sidebar {
            width:260px; background:var(--sidebar-bg);
            display:flex; flex-direction:column;
            padding:32px 16px 24px;
            position:fixed; top:0; left:0; bottom:0; z-index:100;
        }
        .logo { font-family:'Playfair Display',serif; font-size:26px; color:#fff; text-align:center; margin-bottom:40px; letter-spacing:.5px; }
        .logo span { color:var(--accent); }
        .nav-links { list-style:none; display:flex; flex-direction:column; gap:4px; flex:1; }
        .nav-links a {
            display:flex; align-items:center; gap:12px;
            padding:12px 16px; color:var(--sidebar-text);
            text-decoration:none; border-radius:var(--radius-sm);
            font-size:14px; font-weight:500; transition:all .2s;
        }
        .nav-links a i { width:18px; text-align:center; font-size:15px; }
        .nav-links a:hover, .nav-links a.active { background:var(--sidebar-hover); color:var(--accent); }
        .nav-links a.active { border-left:3px solid var(--primary); }
        .sidebar-logout {
            display:flex; align-items:center; gap:10px;
            padding:12px 16px; color:#f87171;
            text-decoration:none; border-radius:var(--radius-sm);
            font-size:14px; font-weight:500;
            background:rgba(239,68,68,.08); transition:all .2s;
        }
        .sidebar-logout:hover { background:rgba(239,68,68,.18); }

        /* ── Main ── */
        .main-wrapper { margin-left:260px; flex:1; display:flex; flex-direction:column; }

        .top-header {
            background:var(--card); border-bottom:1px solid var(--border);
            padding:0 40px; height:70px;
            display:flex; align-items:center; justify-content:space-between;
            box-shadow:0 2px 12px rgba(0,0,0,.04); flex-shrink:0;
            position:sticky; top:0; z-index:50;
        }
        .page-title { font-family:'Playfair Display',serif; font-size:20px; color:var(--text); }
        .page-title span { font-family:'DM Sans',sans-serif; font-size:13px; font-weight:500; color:var(--muted); margin-left:8px; }

        .header-actions { display:flex; align-items:center; gap:12px; }
        .search-form { display:flex; gap:0; }
        .search-input {
            padding:10px 18px; border:1.5px solid var(--border);
            border-radius:999px 0 0 999px; width:260px; font-size:13px;
            background:#fff; outline:none; color:var(--text);
            transition:border-color .2s;
        }
        .search-input:focus { border-color:var(--primary); }
        .search-btn {
            padding:10px 16px;
            background:var(--primary); color:#fff; border:none;
            border-radius:0 999px 999px 0; cursor:pointer;
            font-size:13px; transition:background .2s;
        }
        .search-btn:hover { background:var(--primary-dark); }
        .btn-add {
            display:flex; align-items:center; gap:8px;
            padding:10px 20px; background:var(--primary); color:#fff;
            border-radius:999px; text-decoration:none;
            font-size:13px; font-weight:600; white-space:nowrap;
            transition:background .2s, transform .15s;
            box-shadow:0 4px 14px rgba(232,51,109,.28);
        }
        .btn-add:hover { background:var(--primary-dark); transform:translateY(-1px); }

        .user-pill {
            display:flex; align-items:center; gap:10px;
            padding:6px 16px 6px 6px;
            background:var(--primary-soft); border-radius:999px;
        }
        .user-pill img { width:34px; height:34px; border-radius:50%; border:2px solid var(--primary); }
        .user-pill span { font-size:13px; font-weight:600; color:var(--primary-dark); }

        /* ── Content ── */
        .content { padding:32px 40px 60px; flex:1; }

        /* ── Alert ── */
        .alert {
            padding:13px 18px; border-radius:var(--radius-sm);
            margin-bottom:20px; font-size:13px; font-weight:500;
            display:flex; align-items:center; gap:10px;
            animation:slideDown .3s ease;
        }
        @keyframes slideDown { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:translateY(0)} }
        .alert-success { background:#edfaf3; color:#1a7a45; border:1px solid #a7f3c8; }
        .alert-error   { background:#fef2f2; color:#9b1c1c; border:1px solid #fca5a5; }

        /* ── Product Grid ── */
        .product-grid {
            display:grid;
            grid-template-columns:repeat(auto-fill,minmax(240px,1fr));
            gap:20px;
        }
        .product-card {
            background:var(--card); border:1px solid var(--border);
            border-radius:var(--radius); overflow:hidden;
            box-shadow:var(--shadow); transition:all .25s;
            display:flex; flex-direction:column;
        }
        .product-card:hover { transform:translateY(-5px); box-shadow:0 16px 40px rgba(232,51,109,.14); }

        .product-img-wrap {
            position:relative; height:190px; overflow:hidden;
            background:#fdf6f8;
        }
        .product-img-wrap img {
            width:100%; height:100%; object-fit:cover;
            transition:transform .35s;
        }
        .product-card:hover .product-img-wrap img { transform:scale(1.05); }

        .cat-badge {
            position:absolute; top:10px; left:10px;
            background:rgba(232,51,109,.85); color:#fff;
            font-size:10px; font-weight:700; padding:3px 10px;
            border-radius:20px; text-transform:uppercase; letter-spacing:.04em;
        }

        .stock-indicator {
            position:absolute; top:10px; right:10px;
            font-size:10px; font-weight:700; padding:3px 10px;
            border-radius:20px;
        }
        .stock-ok  { background:rgba(16,185,129,.9);  color:#fff; }
        .stock-low { background:rgba(239,68,68,.9);   color:#fff; }
        .stock-out { background:rgba(100,116,139,.85);color:#fff; }

        .product-body { padding:16px; flex:1; display:flex; flex-direction:column; }
        .product-brand { font-size:11px; font-weight:700; color:var(--primary); text-transform:uppercase; letter-spacing:.05em; margin-bottom:5px; }
        .product-name  { font-size:14px; font-weight:600; color:var(--text); line-height:1.4; margin-bottom:12px; flex:1; }

        .product-meta {
            display:flex; justify-content:space-between; align-items:center;
            padding:10px 0; border-top:1px solid var(--border); border-bottom:1px solid var(--border);
            margin-bottom:12px;
        }
        .product-price { font-size:18px; font-weight:700; color:var(--primary); }
        .product-stock { font-size:12px; color:var(--muted); }
        .product-stock strong { color:var(--text); }
        .product-sku { font-size:11px; color:var(--muted); margin-bottom:12px; }

        .card-actions { display:flex; gap:8px; }
        .btn-edit, .btn-delete {
            flex:1; padding:9px; border:none; border-radius:8px;
            font-size:13px; font-weight:600; cursor:pointer;
            display:flex; align-items:center; justify-content:center;
            gap:6px; text-decoration:none; transition:all .2s;
        }
        .btn-edit   { background:rgba(59,130,246,.1); color:#2563eb; }
        .btn-edit:hover   { background:#2563eb; color:#fff; }
        .btn-delete { background:rgba(239,68,68,.1); color:var(--danger); }
        .btn-delete:hover { background:var(--danger); color:#fff; }

        /* ── Empty state ── */
        .empty-state {
            text-align:center; padding:80px 20px;
            background:var(--card); border-radius:var(--radius);
            border:1px solid var(--border);
        }
        .empty-state i { font-size:64px; color:var(--border); margin-bottom:16px; display:block; }
        .empty-state h3 { font-size:18px; color:var(--muted); margin-bottom:20px; }

        /* ── Confirm modal ── */
        .modal-overlay {
            position:fixed; inset:0; background:rgba(26,10,18,.5);
            backdrop-filter:blur(4px); z-index:200;
            display:none; align-items:center; justify-content:center;
        }
        .modal-overlay.open { display:flex; }
        .modal-box {
            background:#fff; border-radius:var(--radius);
            padding:32px; width:380px; text-align:center;
            box-shadow:0 24px 60px rgba(0,0,0,.18);
            animation:popIn .25s ease;
        }
        @keyframes popIn { from{opacity:0;transform:scale(.9)} to{opacity:1;transform:scale(1)} }
        .modal-box i { font-size:48px; color:var(--danger); margin-bottom:16px; }
        .modal-box h3 { font-size:18px; font-weight:700; margin-bottom:8px; }
        .modal-box p  { font-size:13px; color:var(--muted); margin-bottom:24px; }
        .modal-actions { display:flex; gap:12px; }
        .modal-cancel {
            flex:1; padding:12px; background:var(--bg);
            border:1.5px solid var(--border); border-radius:var(--radius-sm);
            font-size:14px; font-weight:600; cursor:pointer; color:var(--muted);
        }
        .modal-confirm {
            flex:1; padding:12px; background:var(--danger);
            border:none; border-radius:var(--radius-sm);
            font-size:14px; font-weight:600; cursor:pointer; color:#fff;
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="logo">Glow<span>Link</span></div>
    <ul class="nav-links">
        <li><a href="retailer_dashboard.php"><i class="fa-solid fa-border-all"></i> Dashboard</a></li>
        <li><a href="products.php"><i class="fa-solid fa-box-open"></i> Add Product</a></li>
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
        <div class="page-title">
            My Products
            <span><?= count($products) ?> items<?= $search ? ' found for "'.htmlspecialchars($search).'"' : '' ?></span>
        </div>
        <div class="header-actions">
            <form class="search-form" method="GET">
                <input class="search-input" type="text" name="search"
                       value="<?= htmlspecialchars($search) ?>"
                       placeholder="Search name, brand, SKU…">
                <button class="search-btn" type="submit"><i class="fas fa-search"></i></button>
            </form>
            <a href="products.php" class="btn-add"><i class="fas fa-plus"></i> Add New</a>
            <div class="user-pill">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=E8336D&color=fff" alt="avatar">
                <span><?= htmlspecialchars($user_name) ?></span>
            </div>
        </div>
    </header>

    <div class="content">

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert <?= $_GET['deleted'] === 'success' ? 'alert-success' : 'alert-error' ?>">
                <i class="fa-solid <?= $_GET['deleted'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <?= $_GET['deleted'] === 'success' ? 'Product deleted successfully.' : 'Failed to delete product.' ?>
            </div>
        <?php endif; ?>

        <?php if (empty($products)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-box-open"></i>
                <h3><?= $search ? 'No products match your search.' : 'You haven\'t added any products yet.' ?></h3>
                <?php if (!$search): ?>
                    <a href="products.php" style="display:inline-flex;align-items:center;gap:8px;padding:12px 28px;background:var(--primary);color:#fff;border-radius:999px;text-decoration:none;font-weight:600;">
                        <i class="fas fa-plus"></i> Add Your First Product
                    </a>
                <?php else: ?>
                    <a href="my_products.php" style="color:var(--primary);font-weight:600;">← Clear search</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $p):
                    // ── Image: DB stores full URL or local path ──
                    $img = '';
                    if (!empty($p['main_image_url'])) {
                        $url = trim($p['main_image_url']);
                        // If it's already an http URL use it directly
                        if (str_starts_with($url, 'http')) {
                            $img = $url;
                        } else {
                            // Local file — prepend uploads/ if needed
                            $local = str_starts_with($url, 'uploads/') ? $url : 'uploads/' . $url;
                            $img   = file_exists($local) ? $local : '';
                        }
                    }
                    $fallback = 'https://placehold.co/300x190/fce4ec/E8336D?text=No+Image';

                    // Stock indicator
                    if ($p['stock'] <= 0)       { $si_class='stock-out'; $si_label='Out of stock'; }
                    elseif ($p['stock'] <= 10)  { $si_class='stock-low'; $si_label='Low stock'; }
                    else                         { $si_class='stock-ok';  $si_label='In stock'; }
                ?>
                <div class="product-card">
                    <div class="product-img-wrap">
                        <img src="<?= $img ?: $fallback ?>"
                             alt="<?= htmlspecialchars($p['name']) ?>"
                             onerror="this.src='<?= $fallback ?>'">
                        <?php if (!empty($p['category_name'])): ?>
                            <span class="cat-badge"><?= htmlspecialchars($p['category_name']) ?></span>
                        <?php endif; ?>
                        <span class="stock-indicator <?= $si_class ?>"><?= $si_label ?></span>
                    </div>
                    <div class="product-body">
                        <div class="product-brand"><?= htmlspecialchars($p['brand'] ?? 'No Brand') ?></div>
                        <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                        <div class="product-meta">
                            <div class="product-price">৳<?= number_format($p['price'], 2) ?></div>
                            <div class="product-stock">Stock: <strong><?= intval($p['stock']) ?></strong></div>
                        </div>
                        <?php if (!empty($p['sku'])): ?>
                            <div class="product-sku">SKU: <?= htmlspecialchars($p['sku']) ?></div>
                        <?php endif; ?>
                        <div class="card-actions">
                            <a href="edit_product.php?id=<?= $p['id']; ?>" class="btn-edit">
                                <i class="fa-solid fa-pen"></i> Edit
                            </a>
                            <button class="btn-delete" onclick="confirmDelete(<?= $p['id'] ?>, '<?= addslashes(htmlspecialchars($p['name'])) ?>')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <h3>Delete Product?</h3>
        <p id="deleteModalText">This action cannot be undone.</p>
        <div class="modal-actions">
            <button class="modal-cancel" onclick="closeModal()">Cancel</button>
            <a id="deleteConfirmBtn" href="#" class="modal-confirm">Yes, Delete</a>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('deleteModalText').textContent = 'Delete "' + name + '"? This cannot be undone.';
    document.getElementById('deleteConfirmBtn').href = '?delete=' + id;
    document.getElementById('deleteModal').classList.add('open');
}
function closeModal() {
    document.getElementById('deleteModal').classList.remove('open');
}
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
</body>
</html>