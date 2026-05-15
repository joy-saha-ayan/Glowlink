<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'retailer') {
    header("Location: login.php");
    exit;
}

$retailer_id = $_SESSION['user_id'];

// Database Connection
$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, 3308);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Stats - Using subquery (safe method)
$stats = $conn->query("
    SELECT 
        COALESCE(SUM(admin_commission), 0) as total_earned,
        COALESCE(SUM(CASE WHEN commission_status = 'Unpaid' OR commission_status IS NULL THEN admin_commission ELSE 0 END), 0) as pending_commission,
        COALESCE(SUM(CASE WHEN commission_status = 'Paid' THEN admin_commission ELSE 0 END), 0) as paid_commission
    FROM orders 
    WHERE user_id = $retailer_id
")->fetch_assoc();

// Commission History
$orders = $conn->query("
    SELECT id, order_number, total_amount, admin_commission, 
           commission_status, created_at 
    FROM orders 
    WHERE user_id = $retailer_id 
    ORDER BY created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commission & Payment | GlowLink</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-950 text-gray-100">

<div class="flex h-screen">
    <!-- Sidebar -->
    <div class="w-64 bg-gray-900 border-r border-gray-800 p-6">
        <h1 class="text-2xl font-bold mb-10">GLOW<span class="text-purple-500">LINK</span></h1>
        <nav class="space-y-2">
            <a href="retailer_dashboard.php" class="block px-4 py-3 rounded-xl hover:bg-gray-800">Dashboard</a>
            <a href="my_products.php" class="block px-4 py-3 rounded-xl hover:bg-gray-800">My Products</a>
            <a href="orders.php" class="block px-4 py-3 rounded-xl hover:bg-gray-800">Orders</a>
            <a href="retailer_commission.php" class="block px-4 py-3 bg-purple-600/20 text-purple-400 rounded-xl">Commission</a>
            <a href="setting.php" class="block px-4 py-3 rounded-xl hover:bg-gray-800">Settings</a>
        </nav>
    </div>

    <div class="flex-1 p-10 overflow-auto">
        <h1 class="text-3xl font-bold mb-8">💰 My Commission</h1>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="bg-gray-900 p-6 rounded-3xl border border-gray-700">
                <p class="text-gray-400">Total Earned</p>
                <h2 class="text-4xl font-bold text-green-400">৳<?= number_format($stats['total_earned'], 2) ?></h2>
            </div>
            <div class="bg-gray-900 p-6 rounded-3xl border border-gray-700">
                <p class="text-gray-400">Pending</p>
                <h2 class="text-4xl font-bold text-yellow-400">৳<?= number_format($stats['pending_commission'], 2) ?></h2>
            </div>
            <div class="bg-gray-900 p-6 rounded-3xl border border-gray-700">
                <p class="text-gray-400">Paid</p>
                <h2 class="text-4xl font-bold text-purple-400">৳<?= number_format($stats['paid_commission'], 2) ?></h2>
            </div>
        </div>

        <!-- Withdraw Section -->
        <?php if ($stats['pending_commission'] > 0): ?>
        <div class="bg-gradient-to-r from-purple-600 to-pink-600 p-8 rounded-3xl mb-10 text-center">
            <h3 class="text-2xl font-semibold mb-4">Withdraw Pending Commission</h3>
            <p class="text-xl mb-6">৳<?= number_format($stats['pending_commission'], 2) ?> is ready</p>
            <button onclick="withdrawCommission()" 
                    class="bg-white text-black px-10 py-4 rounded-2xl font-bold text-lg hover:bg-gray-200 transition">
                Withdraw via bKash
            </button>
        </div>
        <?php endif; ?>

        <!-- Commission History -->
        <h2 class="text-2xl font-semibold mb-6">Commission History</h2>
        <table class="w-full bg-gray-900 rounded-3xl overflow-hidden">
            <thead>
                <tr class="bg-gray-800">
                    <th class="p-5 text-left">Order ID</th>
                    <th class="p-5 text-left">Date</th>
                    <th class="p-5 text-right">Order Amount</th>
                    <th class="p-5 text-right">Your Commission</th>
                    <th class="p-5 text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                <tr><td colspan="5" class="p-10 text-center text-gray-400">No commission history yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                    <tr class="border-t border-gray-800 hover:bg-gray-800/50">
                        <td class="p-5 font-medium"><?= htmlspecialchars($order['order_number']) ?></td>
                        <td class="p-5"><?= date('d M, Y', strtotime($order['created_at'])) ?></td>
                        <td class="p-5 text-right">৳<?= number_format($order['total_amount'], 2) ?></td>
                        <td class="p-5 text-right font-semibold text-green-400">
                            ৳<?= number_format($order['admin_commission'], 2) ?>
                        </td>
                        <td class="p-5 text-center">
                            <span class="px-5 py-1 rounded-full text-sm <?= $order['commission_status'] == 'Paid' ? 'bg-green-500/20 text-green-400' : 'bg-yellow-500/20 text-yellow-400' ?>">
                                <?= $order['commission_status'] ?: 'Unpaid' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function withdrawCommission() {
    if (confirm("Are you sure you want to withdraw all pending commission via bKash?")) {
        alert("✅ Withdrawal request submitted successfully!\nYou will receive payment within 24-48 hours.");
        // You can later connect real bKash API here
        location.reload();
    }
}
</script>

</body>
</html>