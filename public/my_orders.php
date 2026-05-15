<?php
session_start();
$servername = "localhost:3308"; 
$username = "root"; 
$password = ""; 
$dbname = "glowlinkp_db";

$user_id = $_SESSION['user_id'] ?? 1; 
$glow_points = $_SESSION['glow_points'] ?? 1250;

try { 
    $conn = new mysqli($servername, $username, $password, $dbname); 
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch Orders
$orders = [];
if ($conn) {
    // Ekhane ensure korun column names thik ache
    $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Order items fetch kora (Join query use kora better, kintu apnar structure maintain korlam)
        $item_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $item_stmt->bind_param("i", $row['id']);
        $item_stmt->execute();
        $item_result = $item_stmt->get_result();
        
        $items = [];
        while($item = $item_result->fetch_assoc()) {
            $items[] = $item;
        }
        $row['items'] = $items;
        $orders[] = $row;
    }
}

function getStatusColor($status) {
    switch(strtolower($status)) {
        case 'delivered': return 'bg-green-100 text-green-700 border-green-200';
        case 'processing': return 'bg-blue-100 text-blue-700 border-blue-200';
        case 'shipped': return 'bg-purple-100 text-purple-700 border-purple-200';
        case 'picked': return 'bg-indigo-100 text-indigo-700 border-indigo-200';
        case 'cancelled': return 'bg-red-100 text-red-700 border-red-200';
        default: return 'bg-amber-100 text-amber-700 border-amber-200'; // Pending
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Orders | GlowLink</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #faf9f8; font-family: 'Inter', sans-serif; }
        .receipt-modal { display: none; }
        .receipt-modal.active { display: flex; }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <aside class="w-72 bg-white border-r border-gray-100 flex flex-col shadow-sm z-20">
        <div class="p-8">
            <div class="text-3xl font-serif font-bold text-slate-900 mb-1">
             Glow<span class="text-rose-500 italic">Link</span>
            </div>
            <div class="text-xs font-bold uppercase tracking-widest text-slate-400">
                Skincare Universe
            </div>
        </div>
     <nav class="space-y-2 px-4 flex-1 mt-4">
          <a href="customer_dashboard.php" class="flex items-center gap-4 px-4 py-3.5 text-slate-500 hover:bg-slate-50 rounded-2xl font-bold">
              <i class="fa-solid fa-border-all text-lg w-5"></i>
                Dashboard
            </a>
         <a href="skin_profile.php" class="flex items-center gap-4 px-4 py-3.5 text-slate-500 hover:bg-slate-50 rounded-2xl font-bold">
                <i class="fa-solid fa-sparkles text-lg w-5"></i>
                My Skin Profile
            </a>
         <a href="shop.php" class="flex items-center gap-4 px-4 py-3.5 text-slate-500 hover:bg-slate-50 rounded-2xl font-bold">
             <i class="fa-solid fa-bag-shopping text-lg w-5"></i>
                Shop Products
            </a>
         <a href="my_orders.php" class="flex items-center gap-4 px-4 py-3.5 text-rose-600 bg-rose-50 rounded-2xl font-bold"> <i class="fa-solid fa-box-open text-lg w-5"></i> My Orders </a>
         <a href="rewards.php" class="flex items-center justify-between px-4 py-3.5 text-slate-500 hover:bg-slate-50 rounded-2xl font-bold">
                <div class="flex items-center gap-4">
                    <i class="fa-solid fa-gift text-lg w-5"></i>
                    Rewards
             </div>
            
         </a>
        </nav>
     <div class="p-6 border-t border-gray-100">
            <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-red-500 font-bold hover:bg-red-50 rounded-xl">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                Logout
            </a>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto p-8 lg:p-12 relative">
        <div class="max-w-4xl mx-auto space-y-8">
            <div class="flex justify-between items-end">
                <div>
                    <h1 class="text-3xl font-serif font-bold text-slate-900">Order History</h1>
                    <p class="text-slate-500 mt-1">Manage and track your beauty essentials</p>
                </div>
                <div class="text-right">
                    <span class="text-sm font-bold text-slate-400 uppercase tracking-widest">Total Orders</span>
                    <p class="text-2xl font-black text-slate-800"><?php echo count($orders); ?></p>
                </div>
            </div>
            
            <?php if(empty($orders)): ?>
                <div class="bg-white border border-gray-100 shadow-sm rounded-3xl p-12 text-center">
                    <i class="fa-solid fa-cart-arrow-down text-5xl text-gray-200 mb-4"></i>
                    <h2 class="text-xl font-bold text-slate-800 mb-2">No orders found</h2>
                    <a href="shop.php" class="text-rose-500 font-bold hover:underline">Start shopping now</a>
                </div>
            <?php else: ?>
                <div class="space-y-8">
                    <?php foreach($orders as $order): ?>
                        <div class="bg-white border border-gray-100 shadow-sm rounded-[2rem] overflow-hidden transition-all hover:shadow-md">
                            <!-- Order Header -->
                            <div class="bg-slate-50/80 p-6 border-b border-gray-100 flex flex-wrap justify-between items-center gap-6">
                                <div class="flex gap-8">
                                    <div>
                                        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-1">Order ID</p>
                                        <p class="font-bold text-slate-800 text-sm">#<?php echo htmlspecialchars($order['order_number']); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-1">Placed On</p>
                                        <p class="font-bold text-slate-800 text-sm"><?php echo date("d M, Y", strtotime($order['created_at'])); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-1">Total</p>
                                        <p class="font-bold text-rose-600 text-sm">৳<?php echo number_format($order['total_amount'], 2); ?></p>
                                    </div>
                                </div>
                                <span class="<?php echo getStatusColor($order['status']); ?> px-4 py-1.5 rounded-full font-black text-[10px] uppercase tracking-widest border shadow-sm">
                                    <i class="fa-solid fa-circle text-[6px] mr-1.5 align-middle"></i>
                                    <?php echo htmlspecialchars($order['status']); ?>
                                </span>
                            </div>

                            <!-- Order Content -->
                            <div class="p-8">
                                <div class="divide-y divide-gray-50">
                                    <?php if(!empty($order['items'])): ?>
                                        <?php foreach($order['items'] as $item): ?>
                                            <div class="py-4 first:pt-0 last:pb-0 flex items-center justify-between group">
                                                <div class="flex items-center gap-5">
                                                    <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center border border-gray-100 p-2">
                                                        <img src="uploads/<?php echo $item['product_image'] ?? 'default.png'; ?>" 
                                                             onerror="this.src='https://ui-avatars.com/api/?name=Product&background=fff1f2&color=f43f5e'"
                                                             class="w-full h-full object-contain mix-blend-multiply">
                                                    </div>
                                                    <div>
                                                        <p class="font-bold text-slate-800 group-hover:text-rose-600 transition-colors"><?php echo htmlspecialchars($item['product_name']); ?></p>
                                                        <p class="text-xs font-medium text-slate-400">Unit Price: ৳<?php echo number_format($item['price'], 2); ?> • Qty: <?php echo $item['quantity']; ?></p>
                                                    </div>
                                                </div>
                                                <p class="font-bold text-slate-700 text-right">৳<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Footer Actions -->
                                <div class="mt-8 pt-6 border-t border-slate-50 flex justify-between items-center">
                                    <p class="text-xs text-slate-400 font-medium">Estimated Delivery: <span class="text-slate-600">3-5 Working Days</span></p>
                                    <div class="flex gap-3">
                                        <button onclick='openReceipt(<?php echo json_encode($order); ?>)' class="px-6 py-2.5 rounded-xl font-bold text-xs bg-slate-900 text-white hover:shadow-lg hover:shadow-slate-200 transition-all">
                                            View Receipt
                                        </button>
                                        <?php if(strtolower($order['status']) != 'delivered'): ?>
                                            <button class="px-6 py-2.5 rounded-xl font-bold text-xs bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-all">
                                                Track Shipment
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Receipt Modal -->
    <div id="receiptModal" class="receipt-modal fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 items-center justify-center p-4">
        <div class="bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300">
            <div class="p-8 text-center border-b border-dashed border-gray-200 relative">
                <button onclick="closeReceipt()" class="absolute right-6 top-6 text-slate-300 hover:text-slate-600"><i class="fa-solid fa-xmark text-xl"></i></button>
                <div class="text-2xl font-serif font-bold text-slate-900 mb-1">Glow<span class="text-rose-500 italic">Link</span></div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Official Purchase Receipt</p>
            </div>
            <div id="receiptContent" class="p-8 space-y-4">
                <!-- Content injected via JS -->
            </div>
            <div class="p-8 bg-slate-50 flex gap-3">
                <button onclick="window.print()" class="flex-1 py-3 bg-white border border-slate-200 rounded-2xl font-bold text-slate-700 text-sm hover:bg-gray-50 transition-all">Print PDF</button>
                <button onclick="closeReceipt()" class="flex-1 py-3 bg-rose-500 rounded-2xl font-bold text-white text-sm hover:bg-rose-600 transition-all">Close</button>
            </div>
        </div>
    </div>

    <script>
        function openReceipt(order) {
            const modal = document.getElementById('receiptModal');
            const content = document.getElementById('receiptContent');
            
            let itemsHtml = order.items.map(item => `
                <div class="flex justify-between text-sm py-1">
                    <span class="text-slate-500">${item.product_name} x ${item.quantity}</span>
                    <span class="font-bold text-slate-800">৳${parseFloat(item.price * item.quantity).toFixed(2)}</span>
                </div>
            `).join('');

            content.innerHTML = `
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase">Order Number</p>
                        <p class="font-bold text-slate-800">#${order.order_number}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-black text-slate-400 uppercase">Status</p>
                        <p class="font-black text-rose-500 uppercase text-xs">${order.status}</p>
                    </div>
                </div>
                <div class="space-y-2 border-y border-slate-50 py-4">
                    ${itemsHtml}
                </div>
                <div class="pt-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-400">Subtotal</span>
                        <span class="font-bold text-slate-600">৳${parseFloat(order.total_amount - 50).toFixed(2)}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-400">Shipping</span>
                        <span class="font-bold text-slate-600">৳60.00</span>
                    </div>
                    <div class="flex justify-between text-lg pt-2 border-t border-gray-100">
                        <span class="font-serif font-bold text-slate-900">Total Amount</span>
                        <span class="font-bold text-rose-600">৳${parseFloat(order.total_amount).toFixed(2)}</span>
                    </div>
                </div>
            `;
            modal.classList.add('active');
        }

        function closeReceipt() {
            document.getElementById('receiptModal').classList.remove('active');
        }
    </script>
</body>
</html>