<?php
session_start();
include 'connection.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($db_server, $db_user, $db_pass, $db_name, $port ?? 3308);
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'retailer') {
    header("Location: login.php");
    exit;
}

$user_name     = $_SESSION['user_name'] ?? 'Retailer Admin';
$success_msg   = '';
$error_msg     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    $order_id        = intval($_POST['order_id']);
    $order_status    = $_POST['order_status'] ?? 'Pending'; 
    $delivery_man_id = !empty($_POST['delivery_man_id']) ? intval($_POST['delivery_man_id']) : null;

    if ($order_status === 'Pending' && $delivery_man_id !== null) {
        $order_status = 'Assigned';
    }

    try {
    
        $check_col = $conn->query("SHOW COLUMNS FROM orders LIKE 'delivery_man_id'");
        $has_delivery_col = ($check_col && $check_col->num_rows > 0);

        if ($has_delivery_col) {
            
            if ($delivery_man_id !== null) {
                $stmt = $conn->prepare("UPDATE orders SET order_status=?, delivery_man_id=? WHERE id=?");
                $stmt->bind_param("sii", $order_status, $delivery_man_id, $order_id);
            } else {
                $stmt = $conn->prepare("UPDATE orders SET order_status=?, delivery_man_id=NULL WHERE id=?");
                $stmt->bind_param("si", $order_status, $order_id);
            }
        } else {
            
            $stmt = $conn->prepare("UPDATE orders SET order_status=? WHERE id=?");
            $stmt->bind_param("si", $order_status, $order_id);
        }
        
        if ($stmt->execute()) {
            $success_msg = "Order #$order_id updated successfully!";
        } else {
            $error_msg = "Update Failed!";
        }
        $stmt->close();
    } catch (Exception $e) {
        $error_msg = "Database Error: " . $e->getMessage(); 
    }
}

$orders = [];
try {
 
    $check_col = $conn->query("SHOW COLUMNS FROM orders LIKE 'delivery_man_id'");
    if ($check_col && $check_col->num_rows > 0) {
        $query = "SELECT o.*, 
                         c.name AS customer_name, 
                         u_drv.name AS delivery_man_name 
                  FROM orders o 
                  LEFT JOIN users c ON o.user_id = c.id 
                  LEFT JOIN delivery_men dm ON o.delivery_man_id = dm.id 
                  LEFT JOIN users u_drv ON dm.user_id = u_drv.id 
                  ORDER BY o.created_at DESC";
    } else {
        $query = "SELECT o.*, 
                         c.name AS customer_name, 
                         NULL AS delivery_man_name 
                  FROM orders o 
                  LEFT JOIN users c ON o.user_id = c.id 
                  ORDER BY o.created_at DESC";
    }
    
    $result = $conn->query($query);
    if ($result) $orders = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error_msg = "Fetch Error: " . $e->getMessage();
}

// ── Fetch delivery men ────────────────────────────────────────────────────────
$delivery_men = [];
try {
    $dm_result = $conn->query(
        "SELECT u.id AS user_id, u.name, u.email, dm.id AS dm_id 
         FROM users u 
         LEFT JOIN delivery_men dm ON u.id = dm.user_id 
         WHERE u.role = 'driver'"
    );
    if ($dm_result) {
        while ($row = $dm_result->fetch_assoc()) {
            if (empty($row['dm_id'])) {
                $conn->query("INSERT INTO delivery_men (user_id) VALUES ({$row['user_id']})");
                $row['dm_id'] = $conn->insert_id;
            }
            $delivery_men[] = ['delivery_man_id' => $row['dm_id'], 'name' => $row['name'], 'email' => $row['email']];
        }
    }
} catch (Exception $e) {
    
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders | GlowLink</title>
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
        body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); display:flex; height:100vh; overflow:hidden; }

        /* ── Sidebar ── */
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

        /* ── Main ── */
        .main-wrapper { margin-left:260px; flex:1; display:flex; flex-direction:column; overflow:hidden; }
        .top-header { background:var(--card); border-bottom:1px solid var(--border); padding:0 40px; height:70px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 2px 12px rgba(0,0,0,.04); flex-shrink:0; }
        .page-title { font-family:'Playfair Display',serif; font-size:20px; }
        .header-right { display:flex; align-items:center; gap:16px; }
        .user-pill { display:flex; align-items:center; gap:10px; padding:6px 16px 6px 6px; background:var(--primary-soft); border-radius:999px; }
        .user-pill img { width:34px; height:34px; border-radius:50%; border:2px solid var(--primary); }
        .user-pill span { font-size:13px; font-weight:600; color:var(--primary-dark); }
        .content { padding:32px 40px 40px; overflow-y:auto; flex:1; }

        /* ── Alerts ── */
        .alert { padding:13px 18px; border-radius:var(--radius-sm); margin-bottom:20px; font-size:13px; font-weight:500; display:flex; align-items:center; gap:10px; animation:slideDown .3s ease; }
        @keyframes slideDown { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:translateY(0)} }
        .alert-success { background:#edfaf3; color:#1a7a45; border:1px solid #a7f3c8; }
        .alert-error   { background:#fef2f2; color:#9b1c1c; border:1px solid #fca5a5; }

        /* ── Card & Table ── */
        .card { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow); overflow:hidden; }
        .card-header { padding:20px 24px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid var(--border); }
        .card-heading { font-family:'Playfair Display',serif; font-size:16px; display:flex; align-items:center; gap:8px; }
        .card-heading i { color:var(--primary); }
        .order-count { background:var(--primary-soft); color:var(--primary); font-size:12px; font-weight:700; padding:4px 12px; border-radius:20px; }
        table { width:100%; border-collapse:collapse; }
        thead th { padding:12px 16px; text-align:left; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--muted); background:#fdf6f8; border-bottom:1px solid var(--border); }
        tbody tr { border-bottom:1px solid #f5eaef; transition:background .15s; }
        tbody tr:hover { background:#fdf6f8; }
        td { padding:14px 16px; font-size:13px; vertical-align:top; }
        .order-no { font-weight:700; font-size:12px; background:#f5eaef; color:var(--primary-dark); padding:4px 10px; border-radius:6px; display:inline-block; }
        .customer-name { font-weight:600; font-size:13px; }
        .customer-addr { font-size:11px; color:var(--muted); margin-top:4px; }
        .amount { font-weight:700; color:var(--primary); }
        .pay-method { font-size:10px; font-weight:700; text-transform:uppercase; background:#f5eaef; color:var(--muted); padding:2px 8px; border-radius:4px; display:inline-block; margin-top:3px; }
        .driver-name { font-size:12px; color:var(--muted); margin-top:4px; }
        .driver-name i { color:var(--primary); }

        /* ── Product Items ── */
        .items-container { display: flex; flex-direction: column; gap: 6px; max-width: 220px; }
        .item-pill { display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid var(--border); padding: 4px 6px; border-radius: 6px; }
        .item-pill img { width: 26px; height: 26px; border-radius: 4px; object-fit: cover; flex-shrink: 0; border: 1px solid #f5eaef; }
        .item-pill-info { display: flex; flex-direction: column; overflow: hidden; }
        .item-pill-info span.name { font-size: 11px; font-weight: 600; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .item-pill-info span.qty { font-size: 10px; color: var(--muted); font-weight: bold; }

        /* ── Badges & Buttons ── */
        .badge { display:inline-block; padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700; text-transform:uppercase; }
        .badge-pending   { background:rgba(245,158,11,.12);  color:#d97706; border:1px solid rgba(245,158,11,.3);  }
        .badge-assigned  { background:rgba(59,130,246,.12);  color:#2563eb; border:1px solid rgba(59,130,246,.3);  }
        .badge-picked    { background:rgba(249,115,22,.12);  color:#ea580c; border:1px solid rgba(249,115,22,.3);  }
        .badge-delivered { background:rgba(16,185,129,.12);  color:#059669; border:1px solid rgba(16,185,129,.3);  }
        .badge-cancelled { background:rgba(239,68,68,.12);   color:#dc2626; border:1px solid rgba(239,68,68,.3);   }
        .badge-default   { background:rgba(100,116,139,.10); color:#475569; border:1px solid rgba(100,116,139,.2); }

        .btn-edit-row { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; border-radius:8px; background:var(--primary-soft); color:var(--primary); border:1.5px solid rgba(232,51,109,.25); font-size:12px; font-weight:600; cursor:pointer; transition:all .2s; }
        .btn-edit-row:hover { background:var(--primary); color:#fff; }
        .empty-row td { text-align:center; padding:60px; color:var(--muted); }
        .empty-row i  { font-size:48px; color:var(--border); margin-bottom:12px; display:block; }

        /* ── Modal ── */
        .modal-overlay { position:fixed; inset:0; background:rgba(26,10,18,.55); backdrop-filter:blur(6px); z-index:200; display:none; align-items:center; justify-content:center; }
        .modal-overlay.open { display:flex; }
        .modal-box { background:#fff; border-radius:var(--radius); width:420px; overflow:hidden; box-shadow:0 24px 60px rgba(0,0,0,.2); animation:popIn .25s ease; }
        @keyframes popIn { from{opacity:0;transform:scale(.92)} to{opacity:1;transform:scale(1)} }
        .modal-head { background:var(--primary); padding:20px 24px; display:flex; align-items:center; justify-content:space-between; }
        .modal-head h3 { color:#fff; font-size:15px; font-weight:700; }
        .modal-head span { color:rgba(255,255,255,.7); font-size:13px; }
        .modal-close { background:rgba(255,255,255,.2); border:none; color:#fff; width:30px; height:30px; border-radius:50%; font-size:14px; cursor:pointer; transition:background .2s; display:flex; align-items:center; justify-content:center; }
        .modal-close:hover { background:rgba(255,255,255,.35); }
        .modal-body { padding:24px; }
        .modal-label { display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--muted); margin-bottom:7px; }
        .modal-select { width:100%; padding:11px 14px; border:1.5px solid var(--border); border-radius:var(--radius-sm); font-family:'DM Sans',sans-serif; font-size:14px; color:var(--text); margin-bottom:20px; background:#fff; cursor:pointer; transition:border-color .2s; }
        .modal-select:focus { outline:none; border-color:var(--primary); }
        .modal-footer { display:flex; gap:10px; padding:0 24px 24px; }
        .modal-cancel-btn { flex:1; padding:12px; background:var(--bg); color:var(--muted); border:1.5px solid var(--border); border-radius:var(--radius-sm); font-size:14px; font-weight:600; cursor:pointer; transition:all .2s; }
        .modal-save-btn { flex:1; padding:12px; background:var(--primary); color:#fff; border:none; border-radius:var(--radius-sm); font-size:14px; font-weight:700; cursor:pointer; transition:background .2s; box-shadow:0 4px 14px rgba(232,51,109,.3); }
        .modal-save-btn:hover { background:var(--primary-dark); }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="logo">Glow<span>Link</span></div>
    <ul class="nav-links">
        <li><a href="retailer_dashboard.php"><i class="fa-solid fa-border-all"></i> Dashboard</a></li>
        <li><a href="products.php"><i class="fa-solid fa-box-open"></i> Add Product</a></li>
        <li><a href="orders.php" class="active"><i class="fa-solid fa-clipboard-list"></i> Orders</a></li>
        <li><a href="my_products.php"><i class="fa-solid fa-tags"></i> My Products</a></li>
        <li><a href="admin_analysis.php"><i class="fa-solid fa-chart-line"></i> Analytics</a></li>
        <li><a href="setting.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
    </ul>
    <a class="sidebar-logout" href="logout.php">
        <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
    </a>
</aside>

<div class="main-wrapper">
    <header class="top-header">
        <div class="page-title">Orders Management</div>
        <div class="header-right">
            <div class="user-pill">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=E8336D&color=fff" alt="avatar">
                <span><?= htmlspecialchars($user_name) ?></span>
            </div>
        </div>
    </header>

    <div class="content">
        <?php if ($success_msg): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success_msg) ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <div class="card-heading"><i class="fa-solid fa-clipboard-list"></i> All Orders</div>
                <span class="order-count"><?= count($orders) ?> orders</span>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Order No</th>
                        <th>Customer</th>
                        <th>Products</th> 
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Driver</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($orders)): ?>
                    <tr class="empty-row">
                        <td colspan="8"> 
                            <i class="fa-solid fa-clipboard-list"></i>
                            <p>No orders found.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $o):
                        $current_status = !empty($o['order_status']) ? $o['order_status'] : 'Pending';
                        $status_key = strtolower(trim($current_status));
                        
                        $badge_cls  = match(true) {
                            $status_key === 'pending'               => 'badge-pending',
                            $status_key === 'assigned'              => 'badge-assigned',
                            str_contains($status_key,'preparing')   => 'badge-assigned', 
                            str_contains($status_key,'picked')      => 'badge-picked',
                            $status_key === 'delivered'             => 'badge-delivered',
                            $status_key === 'cancelled'             => 'badge-cancelled',
                            default                                  => 'badge-default',
                        };

                        $cart_items = !empty($o['cart_items']) ? json_decode($o['cart_items'], true) : [];
                    ?>
                    <tr>
                        <td>
                            <span class="order-no"><?= htmlspecialchars($o['order_number'] ?? '#'.$o['id']) ?></span>
                        </td>
                        <td>
                            <div class="customer-name"><?= htmlspecialchars($o['customer_name'] ?? 'Guest') ?></div>
                            <div class="customer-addr"><i class="fa-solid fa-location-dot" style="color:var(--primary)"></i>
                                <?= htmlspecialchars($o['address'] ?? '—') ?>
                            </div>
                        </td>
                        
                        <td>
                            <?php if (!empty($cart_items) && is_array($cart_items)): ?>
                                <div class="items-container">
                                    <?php foreach ($cart_items as $item): 
                                        // Quantity Fixed Here
                                        $item_qty = isset($item['quantity']) ? $item['quantity'] : (isset($item['qty']) ? $item['qty'] : 1);
                                    ?>
                                        <div class="item-pill" title="<?= htmlspecialchars($item['name'] ?? '') ?>">
                                            <img src="<?= htmlspecialchars($item['image'] ?? 'https://via.placeholder.com/50') ?>" alt="Product">
                                            <div class="item-pill-info">
                                                <span class="name"><?= htmlspecialchars($item['name'] ?? 'Unknown') ?></span>
                                                <span class="qty">Qty: <?= htmlspecialchars($item_qty) ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span style="color:var(--muted); font-size:12px; font-style:italic;">No items</span>
                            <?php endif; ?>
                        </td>
                        
                        <td>
                            <div class="amount">৳<?= number_format($o['total_amount'] ?? 0, 2) ?></div>
                            <div class="pay-method"><?= htmlspecialchars($o['payment_method'] ?? '—') ?></div>
                        </td>
                        <td><span class="badge <?= $badge_cls ?>"><?= htmlspecialchars($current_status) ?></span></td>
                        <td>
                            <?php if (!empty($o['delivery_man_name'])): ?>
                                <div class="driver-name"><i class="fa-solid fa-motorcycle"></i> <?= htmlspecialchars($o['delivery_man_name']) ?></div>
                            <?php else: ?>
                                <span style="font-size:12px;color:var(--muted);font-style:italic;">Unassigned</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:12px;color:var(--muted);">
                            <?= date('M d, Y', strtotime($o['created_at'] ?? 'now')) ?>
                        </td>
                        <td>
                            <button class="btn-edit-row"
                                onclick="openModal(
                                    <?= $o['id'] ?>,
                                    '<?= addslashes($current_status) ?>',
                                    '<?= $o['delivery_man_id'] ?? '' ?>',
                                    '<?= addslashes($o['order_number'] ?? '#'.$o['id']) ?>'
                                )">
                                <i class="fas fa-pen"></i> Edit
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <div class="modal-head">
            <div>
                <h3>Update Order</h3>
                <span id="modal_order_no"></span>
            </div>
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="order_id" id="modal_order_id">
            <div class="modal-body">
                <label class="modal-label">Order Status</label>
                <select name="order_status" id="modal_status" class="modal-select">
                    <option value="Pending">Pending</option>
                    <option value="Assigned">Assigned</option>
                    <option value="Preparing for delivery">Preparing for delivery</option>
                    <option value="Picked Up">Picked Up</option>
                    <option value="Delivered">Delivered</option>
                    <option value="Cancelled">Cancelled</option>
                </select>

                <label class="modal-label">Assign Delivery Driver</label>
                <select name="delivery_man_id" id="modal_driver" class="modal-select">
                    <option value="">— Leave Unassigned —</option>
                    <?php foreach ($delivery_men as $dm): ?>
                        <option value="<?= $dm['delivery_man_id'] ?>">
                            <?= htmlspecialchars($dm['name']) ?> (<?= htmlspecialchars($dm['email']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="modal-cancel-btn" onclick="closeModal()">Cancel</button>
                <button type="submit" name="update_order" class="modal-save-btn">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const modal = document.getElementById('editModal');
function openModal(id, status, driverId, orderNo) {
    document.getElementById('modal_order_id').value = id;
    
    let statusSelect = document.getElementById('modal_status');
    let optionExists = Array.from(statusSelect.options).some(opt => opt.value === status);
    if (!optionExists && status) {
        let newOption = new Option(status, status);
        statusSelect.add(newOption);
    }
    statusSelect.value   = status || 'Pending';
    document.getElementById('modal_order_no').textContent = orderNo;
    document.getElementById('modal_driver').value   = driverId || '';
    modal.classList.add('open');
}
function closeModal() { modal.classList.remove('open'); }
modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
</script>
</body>
</html>