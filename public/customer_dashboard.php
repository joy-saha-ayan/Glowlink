<?php
session_start();

// Authentication Check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// ====================== DATABASE CONNECTION ======================
$servername = "localhost:3308"; 
$username   = "root";             
$password   = "";                 
$dbname     = "glowlinkp_db";     

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
} catch (Exception $e) {
    die("Database Connection Error: " . $e->getMessage());
}

$user_id = $_SESSION['user_id'] ?? null;

// --- 1. HANDLE ADD TO CART FROM SHOP (AJAX/POST) ---
if (isset($_POST['add_to_cart'])) {
    $p_id = $_POST['product_id'];
    $p_name = $_POST['product_name'];
    $p_price = $_POST['product_price'];

    if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }

    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['id'] == $p_id) {
            $item['qty']++;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $_SESSION['cart'][] = ['id' => $p_id, 'name' => $p_name, 'price' => (float)$p_price, 'qty' => 1];
    }
    exit; 
}

// --- 2. HANDLE QUANTITY UPDATES (+/-) ---
if (isset($_POST['update_qty'])) {
    $index = $_POST['cart_index'];
    if ($_POST['action'] === 'plus') {
        $_SESSION['cart'][$index]['qty']++;
    } elseif ($_POST['action'] === 'minus' && $_SESSION['cart'][$index]['qty'] > 1) {
        $_SESSION['cart'][$index]['qty']--;
    }
}

// --- 3. CLEAR CART ---
if (isset($_POST['clear_cart'])) {
    $_SESSION['cart'] = [];
}

// --- 4. FETCH USER DATA (For your original Header) ---
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Calculate Cart Total
$cart_total = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_total += ($item['price'] * $item['qty']);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GlowLink Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-slate-900">
    <div class="flex min-h-screen">
        
        <aside class="w-72 bg-white border-r border-gray-200 p-8 flex flex-col">
            <div class="text-2xl font-black text-indigo-600 tracking-tight mb-12">GLOWLINK</div>
            <nav class="space-y-2 flex-1">
                <a href="customer_dashboard.php" class="flex items-center gap-3 px-4 py-3 text-indigo-600 bg-indigo-50 rounded-xl font-bold">
                    <i class="fa-solid fa-house"></i> Overview
                </a>
                <a href="shop.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-gray-50 rounded-xl font-semibold">
                    <i class="fa-solid fa-store"></i> Browse Shop
                </a>
                <a href="customer_dashboard.php?page=orders" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-gray-50 rounded-xl font-semibold">
                    <i class="fa-solid fa-bag-shopping"></i> My Orders
                </a>
            </nav>
            <div class="pt-6 border-t">
                <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-red-500 font-bold hover:bg-red-50 rounded-xl">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
                </a>
            </div>
        </aside>

        <main class="flex-1 p-12">
            
            <header class="flex justify-between items-center mb-10">
                <div>
                    <h1 class="text-3xl font-extrabold">Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</h1>
                    <p class="text-gray-500 font-medium mt-1">Manage your skincare journey and orders.</p>
                </div>
            </header>

            <?php if (isset($_GET['page']) && $_GET['page'] === 'orders'): ?>
                <div class="bg-white rounded-3xl p-8 shadow-sm border border-gray-200">
                    <h2 class="text-xl font-bold mb-6">Order History</h2>
                    <p class="text-gray-400 italic">No orders found.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="p-6 border-b flex justify-between items-center">
                                <h2 class="text-xl font-bold">Your Shopping Cart</h2>
                                <form method="POST"><button name="clear_cart" class="text-red-500 text-sm font-bold">Clear Cart</button></form>
                            </div>
                            <div class="p-6">
                                <?php if (!empty($_SESSION['cart'])): ?>
                                    <div class="space-y-6">
                                        <?php foreach ($_SESSION['cart'] as $index => $item): ?>
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-4">
                                                    <div class="w-12 h-12 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600">
                                                        <i class="fa-solid fa-bottle-droplet"></i>
                                                    </div>
                                                    <div>
                                                        <h4 class="font-bold"><?php echo htmlspecialchars($item['name']); ?></h4>
                                                        <p class="text-xs text-gray-400">৳<?php echo number_format($item['price'], 2); ?></p>
                                                    </div>
                                                </div>

                                                <div class="flex items-center gap-6">
                                                    <form method="POST" class="flex items-center bg-gray-50 rounded-lg border">
                                                        <input type="hidden" name="cart_index" value="<?php echo $index; ?>">
                                                        <button name="update_qty" class="px-3 py-1 font-bold text-indigo-600"><input type="hidden" name="action" value="minus">-</button>
                                                        <span class="px-2 font-bold text-sm"><?php echo $item['qty']; ?></span>
                                                        <button name="update_qty" class="px-3 py-1 font-bold text-indigo-600"><input type="hidden" name="action" value="plus">+</button>
                                                    </form>
                                                    <p class="font-bold min-w-[80px] text-right">৳<?php echo number_format($item['price'] * $item['qty'], 2); ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-center py-10 text-gray-400">Cart is empty.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-1">
                        <div class="bg-indigo-600 rounded-3xl p-8 text-white shadow-xl">
                            <h2 class="text-xl font-bold mb-6">Payment & Checkout</h2>
                            <div class="flex justify-between mb-8 border-b border-indigo-500 pb-4">
                                <span class="font-bold">Total Amount</span>
                                <span class="text-2xl font-black">৳<?php echo number_format($cart_total, 2); ?></span>
                            </div>

                            <form action="process_payment.php" method="POST">
                                <p class="text-xs font-bold text-indigo-200 mb-4 uppercase tracking-widest">Select Provider</p>
                                <div class="space-y-3 mb-8">
                                    <label class="flex items-center gap-3 bg-white/10 p-4 rounded-2xl cursor-pointer hover:bg-white/20">
                                        <input type="radio" name="method" value="bkash" required> <b>bKash</b>
                                    </label>
                                    <label class="flex items-center gap-3 bg-white/10 p-4 rounded-2xl cursor-pointer hover:bg-white/20">
                                        <input type="radio" name="method" value="nagad"> <b>Nagad</b>
                                    </label>
                                    <label class="flex items-center gap-3 bg-white/10 p-4 rounded-2xl cursor-pointer hover:bg-white/20">
                                        <input type="radio" name="method" value="bank"> <b>Bank Transfer</b>
                                    </label>
                                </div>
                                <button type="submit" class="w-full bg-white text-indigo-600 py-4 rounded-2xl font-black hover:bg-indigo-50 transition-all">
                                    PAY NOW
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>