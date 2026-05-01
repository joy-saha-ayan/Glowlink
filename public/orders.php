<?php
session_start();
include 'connection.php'; 
mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, $port);

$page_title = "Orders Management - GlowLink";

// Handle status & delivery man update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_order'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];
    $delivery_man_id = !empty($_POST['delivery_man_id']) ? intval($_POST['delivery_man_id']) : null;

    $stmt = $conn->prepare("UPDATE orders SET status = ?, delivery_man_id = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("sii", $status, $delivery_man_id, $order_id);
    $stmt->execute();

    echo "<script>alert('Order #$order_id updated successfully!');</script>";
}

// Fetch all orders
$orders = [];
$result = $conn->query("SELECT o.*, c.name as customer_name, c.phone as customer_phone, 
                             dm.name as delivery_man_name 
                      FROM orders o 
                      LEFT JOIN customers c ON o.customer_id = c.id 
                      LEFT JOIN delivery_men dm ON o.delivery_man_id = dm.id 
                      ORDER BY o.created_at DESC");

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Fetch delivery men
$delivery_men = [];
$dm_result = $conn->query("SELECT id, name, phone FROM delivery_men WHERE status = 'active'");

if ($dm_result && $dm_result->num_rows > 0) {
    while($row = $dm_result->fetch_assoc()) {
        $delivery_men[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-950 text-gray-100 font-sans">
    <div class="flex h-screen overflow-hidden">
        
        <div class="w-64 bg-gray-900 border-r border-gray-800 flex flex-col justify-between shrink-0">
            <div>
                <div class="h-20 flex items-center px-8 border-b border-gray-800">
                    <h1 class="text-2xl font-bold text-white tracking-wider">GLOW<span class="text-purple-500">LINK</span></h1>
                </div>
                <nav class="p-4 space-y-2 mt-4">
                    <a href="retailer_dashboard.php" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-800 hover:text-white rounded-xl transition">
                        <i class="fas fa-border-all w-6 mr-3"></i> Dashboard
                    </a>
                    <a href="products.php" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-800 hover:text-white rounded-xl transition">
                        <i class="fas fa-box w-6 mr-3"></i> Products
                    </a>
                    <a href="orders.php" class="flex items-center px-4 py-3 bg-purple-600/10 text-purple-400 rounded-xl transition shadow-[inset_4px_0_0_0_#a855f7]">
                        <i class="fas fa-clipboard-list w-6 mr-3"></i> Orders
                    </a>
                </nav>
            </div>
            <div class="p-4 border-t border-gray-800">
                <a href="logout.php" class="flex items-center justify-center w-full px-4 py-3 bg-red-600/10 text-red-500 hover:bg-red-600 hover:text-white rounded-xl transition font-medium">
                    <i class="fas fa-power-off mr-2"></i> Logout
                </a>
            </div>
        </div>

        <div class="flex-1 flex flex-col overflow-hidden bg-gradient-to-br from-gray-950 to-gray-900">
            
            <header class="h-20 bg-gray-900/50 backdrop-blur-md border-b border-gray-800 flex items-center justify-between px-8 shrink-0">
                <div class="text-gray-400">
                    <span class="font-medium text-gray-200">Admin Panel</span> / Orders
                </div>
                <div class="flex items-center space-x-6">
                    <button class="text-gray-400 hover:text-white transition"><i class="fas fa-bell text-xl"></i></button>
                    <div class="flex items-center space-x-3 border-l border-gray-700 pl-6">
                        <div class="w-10 h-10 rounded-full bg-purple-600 flex items-center justify-center text-white font-bold">
                            A
                        </div>
                        <span class="font-medium text-sm hidden md:block">Admin User</span>
                    </div>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-8">
                <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
                    <h1 class="text-3xl font-bold text-white">Orders Management</h1>
                    <div class="relative">
                        <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500"></i>
                        <input type="text" id="searchOrders" placeholder="Search orders by ID or Name..." 
                               class="bg-gray-900 border border-gray-700 rounded-xl pl-12 pr-4 py-3 w-full md:w-80 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition shadow-lg">
                    </div>
                </div>

                <div class="bg-gray-900 rounded-3xl overflow-hidden border border-gray-800 shadow-xl">
                    <div class="overflow-x-auto">
                        <table class="w-full whitespace-nowrap">
                            <thead class="bg-gray-800/50">
                                <tr>
                                    <th class="px-6 py-5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Order ID</th>
                                    <th class="px-6 py-5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Customer Info</th>
                                    <th class="px-6 py-5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Date & Time</th>
                                    <th class="px-6 py-5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Delivery Man</th>
                                    <th class="px-6 py-5 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="ordersTable" class="divide-y divide-gray-800/50">
                                <?php if(empty($orders)): ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-10 text-center text-gray-500">No orders found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $order): ?>
                                    <tr class="hover:bg-gray-800/50 transition-colors group">
                                        <td class="px-6 py-4 font-medium text-purple-400">#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></td>
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-gray-200"><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($order['customer_phone'] ?? 'N/A') ?></div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-400"><?= date('M d, Y', strtotime($order['created_at'])) ?><br><span class="text-xs"><?= date('h:i A', strtotime($order['created_at'])) ?></span></td>
                                        <td class="px-6 py-4 font-bold text-white">$<?= number_format($order['total_amount'], 2) ?></td>
                                        <td class="px-6 py-4">
                                            <?php
                                                $status_class = "bg-gray-500/20 text-gray-400";
                                                if($order['status'] == 'delivered') $status_class = "bg-emerald-500/20 text-emerald-400 border border-emerald-500/20";
                                                if($order['status'] == 'shipped') $status_class = "bg-blue-500/20 text-blue-400 border border-blue-500/20";
                                                if($order['status'] == 'pending') $status_class = "bg-yellow-500/20 text-yellow-400 border border-yellow-500/20";
                                                if($order['status'] == 'processing') $status_class = "bg-purple-500/20 text-purple-400 border border-purple-500/20";
                                                if($order['status'] == 'cancelled') $status_class = "bg-red-500/20 text-red-400 border border-red-500/20";
                                            ?>
                                            <span class="px-3 py-1 rounded-full text-xs font-medium <?= $status_class ?>">
                                                <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?= $order['delivery_man_name'] ? '<span class="text-gray-300"><i class="fas fa-motorcycle text-xs mr-2 text-gray-500"></i>'.htmlspecialchars($order['delivery_man_name']).'</span>' : '<span class="text-gray-600 text-sm italic">Not Assigned</span>' ?>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <button onclick="openUpdateModal(<?= $order['id'] ?>, '<?= $order['status'] ?>', <?= $order['delivery_man_id'] ?? 'null' ?>)" 
                                                    class="bg-purple-600/10 text-purple-400 hover:bg-purple-600 hover:text-white px-4 py-2 rounded-lg text-sm font-medium transition duration-300">
                                                Manage Order
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
        </div>
    </div>

    <div id="updateModal" class="hidden fixed inset-0 bg-gray-950/80 backdrop-blur-sm flex items-center justify-center z-50 transition-all duration-300">
        <div class="bg-gray-900 rounded-3xl p-8 w-full max-w-md border border-gray-700 shadow-2xl transform scale-95 transition-transform duration-300" id="modalContent">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-white">Update Order <span id="display_order_id" class="text-purple-400 text-lg"></span></h2>
                <button onclick="closeModal()" class="text-gray-500 hover:text-white transition"><i class="fas fa-times text-xl"></i></button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="order_id" id="modal_order_id">
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-400 mb-2">Order Status</label>
                    <select name="status" id="modal_status" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition appearance-none">
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="out_for_delivery">Out for Delivery</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="mb-8">
                    <label class="block text-sm font-medium text-gray-400 mb-2">Assign Delivery Man</label>
                    <select name="delivery_man_id" id="modal_delivery_man" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition appearance-none">
                        <option value="">-- No Delivery Man Assigned --</option>
                        <?php foreach ($delivery_men as $dm): ?>
                        <option value="<?= $dm['id'] ?>"><?= htmlspecialchars($dm['name']) ?> (<?= htmlspecialchars($dm['phone']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex gap-4">
                    <button type="button" onclick="closeModal()" 
                            class="flex-1 bg-gray-800 hover:bg-gray-700 text-white py-3 rounded-xl font-medium transition border border-gray-700">Cancel</button>
                    <button type="submit" name="update_order" 
                            class="flex-1 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white py-3 rounded-xl font-medium transition shadow-lg shadow-purple-500/20">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openUpdateModal(orderId, currentStatus, deliveryManId) {
        document.getElementById('modal_order_id').value = orderId;
        document.getElementById('display_order_id').innerText = '#' + String(orderId).padStart(6, '0');
        document.getElementById('modal_status').value = currentStatus || 'pending';
        document.getElementById('modal_delivery_man').value = deliveryManId || '';
        
        const modal = document.getElementById('updateModal');
        const modalContent = document.getElementById('modalContent');
        
        modal.classList.remove('hidden');
        // Simple animation
        setTimeout(() => {
            modalContent.classList.remove('scale-95');
            modalContent.classList.add('scale-100');
        }, 10);
    }

    function closeModal() {
        const modal = document.getElementById('updateModal');
        const modalContent = document.getElementById('modalContent');
        
        modalContent.classList.remove('scale-100');
        modalContent.classList.add('scale-95');
        
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // Live search functionality
    document.getElementById('searchOrders').addEventListener('keyup', function() {
        const term = this.value.toLowerCase();
        const rows = document.querySelectorAll('#ordersTable tr');
        rows.forEach(row => {
            // Skip the "No orders found" row
            if(row.cells.length === 1) return;
            
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(term) ? '' : 'none';
        });
    });
    </script>
</body>
</html>