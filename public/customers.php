<?php
session_start();
include 'connection.php';

mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, $port);


$customers = [];
$query = "SELECT c.*, COUNT(o.id) as total_orders 
          FROM customers c 
          LEFT JOIN orders o ON c.id = o.customer_id 
          GROUP BY c.id 
          ORDER BY c.created_at DESC";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers Directory - GlowLink</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-950 text-gray-100 font-sans selection:bg-purple-500 selection:text-white">
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
                    <a href="#" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-800 hover:text-white rounded-xl transition">
                        <i class="fas fa-box w-6 mr-3"></i> Products
                    </a>
                    <a href="orders.php" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-800 hover:text-white rounded-xl transition">
                        <i class="fas fa-clipboard-list w-6 mr-3"></i> Orders
                    </a>
                    <a href="customers.php" class="flex items-center px-4 py-3 bg-purple-600/10 text-purple-400 rounded-xl transition shadow-[inset_4px_0_0_0_#a855f7]">
                        <i class="fas fa-users w-6 mr-3"></i> Customers
                    </a>
                    <a href="admin_analysis.php" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-800 hover:text-white rounded-xl transition">
                        <i class="fas fa-chart-pie w-6 mr-3"></i> Analytics
                    </a>
                    <a href="setting.php" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-800 hover:text-white rounded-xl transition">
                        <i class="fas fa-cog w-6 mr-3"></i> Settings
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
                    <span class="font-medium text-gray-200">Admin Panel</span> / Customers
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
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-2">Customer Directory</h1>
                        <p class="text-gray-400 text-sm">Manage and view all registered customers.</p>
                    </div>
                    <div class="relative">
                        <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500"></i>
                        <input type="text" id="searchCustomers" placeholder="Search by name, email or phone..." 
                               class="bg-gray-900 border border-gray-700 rounded-xl pl-12 pr-4 py-3 w-full md:w-80 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition shadow-lg text-sm">
                    </div>
                </div>

                <div class="bg-gray-900 rounded-3xl overflow-hidden border border-gray-800 shadow-xl">
                    <div class="overflow-x-auto">
                        <table class="w-full whitespace-nowrap">
                            <thead class="bg-gray-800/50">
                                <tr>
                                    <th class="px-6 py-5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Customer Info</th>
                                    <th class="px-6 py-5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Contact</th>
                                    <th class="px-6 py-5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Joined Date</th>
                                    <th class="px-6 py-5 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Orders</th>
                                    <th class="px-6 py-5 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="customersTable" class="divide-y divide-gray-800/50">
                                <?php if(empty($customers)): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-10 text-center text-gray-500">No customers found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($customers as $cust): ?>
                                    <tr class="hover:bg-gray-800/50 transition-colors group">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 to-indigo-500 flex items-center justify-center text-white font-bold mr-4 shadow-lg">
                                                    <?= strtoupper(substr($cust['name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="font-medium text-gray-200"><?= htmlspecialchars($cust['name']) ?></div>
                                                    <div class="text-xs text-purple-400">ID: #<?= str_pad($cust['id'], 5, '0', STR_PAD_LEFT) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-300 mb-1"><i class="fas fa-envelope text-gray-500 mr-2"></i><?= htmlspecialchars($cust['email']) ?></div>
                                            <div class="text-sm text-gray-400"><i class="fas fa-phone text-gray-500 mr-2"></i><?= htmlspecialchars($cust['phone']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-400">
                                            <?= date('M d, Y', strtotime($cust['created_at'])) ?>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <?php $orderCount = intval($cust['total_orders']); ?>
                                            <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-medium <?= $orderCount > 0 ? 'bg-blue-500/20 text-blue-400 border border-blue-500/30' : 'bg-gray-700/50 text-gray-400' ?>">
                                                <?= $orderCount ?> <?= $orderCount == 1 ? 'Order' : 'Orders' ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <button onclick="viewCustomer('<?= htmlspecialchars($cust['name']) ?>', '<?= htmlspecialchars($cust['email']) ?>', '<?= htmlspecialchars($cust['phone']) ?>', <?= $orderCount ?>)" 
                                                    class="text-gray-400 hover:text-purple-400 transition bg-gray-800 hover:bg-gray-700 px-3 py-2 rounded-lg" title="View Details">
                                                <i class="fas fa-eye"></i>
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

    <div id="customerModal" class="hidden fixed inset-0 bg-gray-950/80 backdrop-blur-sm flex items-center justify-center z-50 transition-all duration-300">
        <div class="bg-gray-900 rounded-3xl p-8 w-full max-w-md border border-gray-700 shadow-2xl transform scale-95 transition-transform duration-300" id="modalContent">
            <div class="flex justify-between items-start mb-6">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-gradient-to-br from-purple-500 to-indigo-500 flex items-center justify-center text-white text-xl font-bold shadow-lg" id="modalInitial">
                        N
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white" id="modalName">Customer Name</h2>
                        <span class="bg-blue-500/20 text-blue-400 text-xs px-2 py-1 rounded-md mt-1 inline-block" id="modalOrders">0 Orders</span>
                    </div>
                </div>
                <button onclick="closeModal()" class="text-gray-500 hover:text-white transition"><i class="fas fa-times text-xl"></i></button>
            </div>
            
            <div class="space-y-4 bg-gray-800/50 p-5 rounded-2xl border border-gray-700/50">
                <div>
                    <label class="text-xs text-gray-500 uppercase font-semibold">Email Address</label>
                    <p class="text-gray-300 font-medium" id="modalEmail">email@example.com</p>
                </div>
                <div>
                    <label class="text-xs text-gray-500 uppercase font-semibold">Phone Number</label>
                    <p class="text-gray-300 font-medium" id="modalPhone">+880 1XXX XXXXXX</p>
                </div>
            </div>

            <div class="mt-6">
                <button onclick="closeModal()" class="w-full bg-gray-800 hover:bg-gray-700 text-white py-3 rounded-xl font-medium transition border border-gray-700">Close</button>
            </div>
        </div>
    </div>

    <script>
    // Live Search Functionality
    document.getElementById('searchCustomers').addEventListener('keyup', function() {
        const term = this.value.toLowerCase();
        const rows = document.querySelectorAll('#customersTable tr');
        rows.forEach(row => {
            if(row.cells.length === 1) return; // Skip "No customers found" row
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(term) ? '' : 'none';
        });
    });

    // Modal Logic
    function viewCustomer(name, email, phone, totalOrders) {
        document.getElementById('modalName').innerText = name;
        document.getElementById('modalInitial').innerText = name.charAt(0).toUpperCase();
        document.getElementById('modalEmail').innerText = email;
        document.getElementById('modalPhone').innerText = phone;
        document.getElementById('modalOrders').innerText = totalOrders + (totalOrders === 1 ? ' Order' : ' Orders');
        
        const modal = document.getElementById('customerModal');
        const modalContent = document.getElementById('modalContent');
        
        modal.classList.remove('hidden');
        setTimeout(() => {
            modalContent.classList.remove('scale-95');
            modalContent.classList.add('scale-100');
        }, 10);
    }

    function closeModal() {
        const modal = document.getElementById('customerModal');
        const modalContent = document.getElementById('modalContent');
        
        modalContent.classList.remove('scale-100');
        modalContent.classList.add('scale-95');
        
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }
    </script>
</body>
</html>