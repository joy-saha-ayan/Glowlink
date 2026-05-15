<?php
session_start();
include 'connection.php'; 

// ডাটাবেস কানেকশন ফলব্যাক
if (!isset($conn)) {
    $port = 3308; // আপনার পোর্ট
    $conn = new mysqli($db_server, $db_user, $db_pass, $db_name, $port);
}

// [TESTING] Driver er User ID 4
if(!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 4; 
}

$user_id = $_SESSION['user_id'];
$driver_info = [];

$d_query = $conn->query("SELECT u.name, u.email, dm.id as dm_id, dm.total_earnings, dm.wallet_balance 
                         FROM users u 
                         LEFT JOIN delivery_men dm ON u.id = dm.user_id 
                         WHERE u.id = '$user_id'");

if($d_query && $d_query->num_rows > 0) {
    $row = $d_query->fetch_assoc();
    if(empty($row['dm_id'])) {
        $conn->query("INSERT INTO delivery_men (user_id) VALUES ('$user_id')");
        $dm_id = $conn->insert_id;
        $driver_info = ['name' => $row['name'], 'email' => $row['email'], 'dm_id' => $dm_id, 'total_earnings' => 0, 'wallet_balance' => 0];
    } else {
        $driver_info = $row;
        $dm_id = $row['dm_id'];
    }
} else {
    $driver_info = ['name' => 'Driver', 'email' => '', 'dm_id' => 0, 'total_earnings' => 0, 'wallet_balance' => 0];
    $dm_id = 0;
}

// 2. Handle Status Updates & Commission Logic by Driver
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['new_status'];
    
    if ($new_status == 'Delivered') {
        $stmt_order = $conn->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt_order->bind_param("i", $order_id);
        $stmt_order->execute();
        $res = $stmt_order->get_result();
        
        if($res->num_rows > 0) {
            $order_data = $res->fetch_assoc();
            
            $payment_method = strtolower($order_data['payment_method'] ?? 'cod');
            $total_amount = floatval($order_data['total_amount']);
            $delivery_fee = floatval($order_data['delivery_fee'] ?? 0);
            
            // Driver earns 95% of delivery fee (5% to admin)
            $driver_earning = $delivery_fee * 0.95; 
            
            if ($payment_method == 'cod' || $payment_method == 'cash on delivery') {
                // COD: Driver collects all cash. He keeps his earning, owes the rest to Admin/Retailer.
                $cash_collected = $total_amount + $delivery_fee;
                $amount_owed_to_system = $cash_collected - $driver_earning; 
                
                // Wallet Balance decreases because driver is holding system's cash
                $wallet_change = -$amount_owed_to_system; 
            } else {
                // Online: Driver collects no cash. System owes driver his earnings.
                $wallet_change = $driver_earning;
            }
            
            // Update Driver Wallet and Earnings
            $conn->query("UPDATE delivery_men SET total_earnings = total_earnings + $driver_earning, wallet_balance = wallet_balance + $wallet_change WHERE id = '$dm_id'");
            
            // Update Order Status
            $conn->query("UPDATE orders SET status = 'Delivered' WHERE id = '$order_id'");
        }
    } else {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ? AND (delivery_man_id = ? OR delivery_man_id = ?)");
        $stmt->bind_param("siii", $new_status, $order_id, $dm_id, $user_id);
        $stmt->execute();
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// 3. Fetch Active Orders
$active_orders = [];
$active_query = "SELECT o.*, u.name as customer_name, u.email as customer_phone 
                 FROM orders o 
                 LEFT JOIN users u ON o.user_id = u.id 
                 WHERE (o.delivery_man_id = '$dm_id' OR o.delivery_man_id = '$user_id') 
                 AND LOWER(IFNULL(o.status, 'pending')) NOT IN ('delivered', 'completed', 'canceled', 'cancelled', 'rejected') 
                 ORDER BY o.created_at ASC";
$result = $conn->query($active_query);
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $active_orders[] = $row;
    }
}

// 4. Fetch Completed Orders
$completed_orders = [];
$completed_query = "SELECT o.*, u.name as customer_name 
                    FROM orders o 
                    LEFT JOIN users u ON o.user_id = u.id 
                    WHERE (o.delivery_man_id = '$dm_id' OR o.delivery_man_id = '$user_id') 
                    AND LOWER(IFNULL(o.status, '')) IN ('delivered', 'completed') 
                    ORDER BY o.created_at DESC LIMIT 10";
$c_result = $conn->query($completed_query);
if ($c_result && $c_result->num_rows > 0) {
    while($row = $c_result->fetch_assoc()) {
        $completed_orders[] = $row;
    }
}

$total_active = count($active_orders);
$total_completed = count($completed_orders);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Workspace | GlowLink</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .glass-card { background: #ffffff; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid rgba(229, 231, 235, 0.5); }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body class="text-slate-800 h-screen overflow-hidden flex flex-col">

    <nav class="bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center shadow-sm z-10 relative">
        <div class="flex items-center gap-3">
            <div class="bg-indigo-600 p-2 rounded-lg text-white">
                <i class="fas fa-truck-fast text-xl"></i>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-slate-800">Glow<span class="text-indigo-600">Link</span> Driver</h1>
        </div>
        
        <div class="flex items-center gap-6">
            <div class="text-right hidden md:block">
                <p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($driver_info['name']) ?></p>
                <p class="text-xs text-slate-500">ID: GL-DRV-<?= $dm_id ?></p>
            </div>
            <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold border-2 border-indigo-200">
                <?= strtoupper(substr($driver_info['name'], 0, 1)) ?>
            </div>
            <a href="logout.php" class="text-red-500 hover:text-red-700 bg-red-50 p-2 rounded-lg transition"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </nav>

    <div class="flex-1 overflow-y-auto p-4 md:p-8 custom-scrollbar">
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="glass-card p-5 border-l-4 border-l-indigo-500">
                <p class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Total Earnings</p>
                <h3 class="text-2xl font-black text-slate-800">৳<?= number_format($driver_info['total_earnings'], 2) ?></h3>
            </div>
            <div class="glass-card p-5 border-l-4 <?= $driver_info['wallet_balance'] < 0 ? 'border-l-red-500 bg-red-50' : 'border-l-emerald-500' ?>">
                <p class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">
                    <?= $driver_info['wallet_balance'] < 0 ? 'Payable to Admin (Debt)' : 'Current Wallet' ?>
                </p>
                <h3 class="text-2xl font-black <?= $driver_info['wallet_balance'] < 0 ? 'text-red-600' : 'text-emerald-600' ?>">
                    ৳<?= number_format(abs($driver_info['wallet_balance']), 2) ?>
                </h3>
            </div>
            <div class="glass-card p-5 border-l-4 border-l-amber-400">
                <p class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Active Deliveries</p>
                <h3 class="text-2xl font-black text-slate-800"><?= $total_active ?></h3>
            </div>
            <div class="glass-card p-5 border-l-4 border-l-blue-500">
                <p class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Completed</p>
                <h3 class="text-2xl font-black text-slate-800"><?= $total_completed ?></h3>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 h-full pb-10">
            
            <div class="lg:col-span-2 flex flex-col">
                <div class="flex justify-between items-end mb-4 px-1">
                    <h2 class="text-lg font-black text-slate-800"><i class="fas fa-route text-indigo-600 mr-2"></i> Current Deliveries</h2>
                    <span class="bg-indigo-100 text-indigo-700 text-xs font-bold px-3 py-1 rounded-full"><?= $total_active ?> Active</span>
                </div>

                <div class="space-y-5">
                    <?php if(empty($active_orders)): ?>
                        <div class="glass-card p-12 text-center flex flex-col items-center justify-center border-dashed border-2">
                            <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
                            <h3 class="text-lg font-bold text-slate-600">No Active Deliveries</h3>
                            <p class="text-sm text-slate-400">You are currently free. Waiting for new assignments...</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($active_orders as $order): 
                            $delivery_fee_display = isset($order['delivery_fee']) ? floatval($order['delivery_fee']) : 0;
                            $driver_earning_display = $delivery_fee_display * 0.95;
                            $payment_method = strtolower($order['payment_method'] ?? 'cod');
                            $is_cod = ($payment_method == 'cod' || $payment_method == 'cash on delivery');
                            $amount_to_collect = $is_cod ? ($order['total_amount'] + $delivery_fee_display) : 0;
                            
                            $current_status = strtolower($order['status'] ?? 'pending');
                        ?>
                        <div class="glass-card overflow-hidden hover:shadow-lg transition duration-300 border-2 <?= $is_cod ? 'border-orange-100' : 'border-blue-100' ?>">
                            <div class="bg-slate-50 px-5 py-3 border-b border-gray-100 flex justify-between items-center">
                                <div>
                                    <span class="text-xs font-bold text-slate-500">ORDER NO:</span>
                                    <span class="text-sm font-black text-indigo-600 ml-1"><?= htmlspecialchars($order['order_number'] ?? '#'.$order['id']) ?></span>
                                </div>
                                <div>
                                    <?php if($current_status == 'picked up'): ?>
                                        <span class="bg-amber-100 text-amber-700 px-3 py-1 rounded-full text-xs font-bold"><i class="fas fa-motorcycle mr-1"></i> On The Way</span>
                                    <?php elseif($current_status == 'processing' || $current_status == 'preparing'): ?>
                                        <span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-xs font-bold"><i class="fas fa-spinner fa-spin mr-1"></i> Preparing</span>
                                    <?php else: ?>
                                        <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-bold"><i class="fas fa-bell mr-1"></i> New Request</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h3 class="font-bold text-slate-800 text-lg"><?= htmlspecialchars($order['customer_name'] ?? 'Customer') ?></h3>
                                    <p class="text-sm text-slate-500 mt-2 flex items-start gap-2">
                                        <i class="fas fa-map-marker-alt text-red-400 mt-1"></i>
                                        <span><?= htmlspecialchars($order['address'] ?? 'No address provided') ?></span>
                                    </p>
                                    <p class="text-sm text-slate-500 mt-2 flex items-center gap-2">
                                        <i class="fas fa-phone-alt text-blue-400"></i>
                                        <span><?= htmlspecialchars($order['customer_phone'] ?? 'N/A') ?></span>
                                    </p>
                                </div>

                                <div class="bg-slate-50 rounded-xl p-4 border <?= $is_cod ? 'border-orange-200' : 'border-blue-200' ?> relative">
                                    <h4 class="text-xs font-bold text-slate-500 uppercase mb-3 text-center">Financial Overview</h4>
                                    
                                    <div class="flex justify-between items-center mb-1 text-sm">
                                        <span class="text-slate-600">Product Price:</span>
                                        <span class="font-bold">৳<?= number_format($order['total_amount'], 2) ?></span>
                                    </div>
                                    <div class="flex justify-between items-center mb-3 text-sm pb-2 border-b border-gray-200">
                                        <span class="text-slate-600">Delivery Fee:</span>
                                        <span class="font-bold">৳<?= number_format($delivery_fee_display, 2) ?></span>
                                    </div>
                                    
                                    <div class="flex justify-between items-center mb-1 text-sm">
                                        <span class="text-emerald-600 font-bold">Your Earning (95%):</span>
                                        <span class="font-bold text-emerald-600">+ ৳<?= number_format($driver_earning_display, 2) ?></span>
                                    </div>

                                    <div class="mt-3 p-2 rounded <?= $is_cod ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800' ?> text-center">
                                        <p class="text-xs font-bold uppercase mb-1"><?= $is_cod ? 'Cash to Collect (COD)' : 'Already Paid Online' ?></p>
                                        <p class="text-xl font-black">৳<?= number_format($amount_to_collect, 2) ?></p>
                                    </div>
                                    <?php if($is_cod): ?>
                                        <p class="text-[10px] text-center text-red-500 mt-1 font-bold">* ৳<?= number_format($amount_to_collect - $driver_earning_display, 2) ?> will be deducted from your wallet</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="px-5 py-4 bg-white border-t border-gray-50">
                                <form method="POST" action="">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    
                                    <?php if(in_array($current_status, ['pending', 'assigned', ''])): ?>
                                        <input type="hidden" name="new_status" value="Processing">
                                        <button type="submit" name="update_status" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold transition shadow-md flex justify-center items-center gap-2">
                                            <i class="fas fa-check"></i> Accept Order
                                        </button>

                                    <?php elseif($current_status == 'processing' || $current_status == 'preparing'): ?>
                                        <input type="hidden" name="new_status" value="Picked Up">
                                        <button type="submit" name="update_status" class="w-full py-3 bg-slate-800 hover:bg-slate-900 text-white rounded-xl font-bold transition shadow-md flex justify-center items-center gap-2">
                                            <i class="fas fa-box-open text-amber-400"></i> Mark as Picked Up
                                        </button>

                                    <?php elseif($current_status == 'picked up'): ?>
                                        <input type="hidden" name="new_status" value="Delivered">
                                        <button type="submit" name="update_status" onclick="return confirm('<?= $is_cod ? 'Did you collect ৳'.number_format($amount_to_collect, 2).' from the customer?' : 'Confirm delivery?' ?>');" class="w-full py-3 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white rounded-xl font-bold transition shadow-md shadow-emerald-500/30 flex justify-center items-center gap-2">
                                            <i class="fas fa-check-circle"></i> Complete Delivery
                                        </button>
                                    <?php endif; ?>

                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="lg:col-span-1 flex flex-col h-full">
                <div class="flex justify-between items-end mb-4 px-1">
                    <h2 class="text-lg font-black text-slate-800"><i class="fas fa-history text-slate-400 mr-2"></i> Recent History</h2>
                </div>

                <div class="glass-card p-4 h-[600px] overflow-y-auto custom-scrollbar">
                    <?php if(empty($completed_orders)): ?>
                        <div class="h-full flex flex-col items-center justify-center text-center opacity-60">
                            <i class="fas fa-clipboard-list text-4xl text-slate-300 mb-3"></i>
                            <p class="text-sm font-medium text-slate-500">No previous deliveries found.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach($completed_orders as $order): 
                                $fee_display = isset($order['delivery_fee']) ? floatval($order['delivery_fee']) : 0;
                                $earned = $fee_display * 0.95;
                            ?>
                            <div class="bg-slate-50 border border-slate-100 rounded-xl p-4 hover:bg-indigo-50 transition cursor-default">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="text-xs font-bold text-slate-800 bg-white px-2 py-1 rounded border shadow-sm"><?= htmlspecialchars($order['order_number'] ?? '#'.$order['id']) ?></span>
                                    <span class="text-[10px] font-black uppercase text-emerald-600 bg-emerald-100 px-2 py-1 rounded">Delivered</span>
                                </div>
                                <h4 class="font-bold text-slate-700 text-sm mb-1"><?= htmlspecialchars($order['customer_name'] ?? 'Customer') ?></h4>
                                <div class="flex justify-between items-center mt-3 pt-3 border-t border-slate-200">
                                    <span class="text-xs text-slate-500"><i class="far fa-calendar-alt"></i> <?= date('d M', strtotime($order['created_at'])) ?></span>
                                    <span class="font-black text-sm text-emerald-600">Earned: ৳<?= number_format($earned, 2) ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</body>
</html>