<?php
session_start();
include 'connection.php'; 
mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, $port);

function fetchAllData($conn, $query) {
    $result = $conn->query($query);
    $data = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}


$revenue_data = fetchAllData($conn, "SELECT DATE(created_at) as date, SUM(total_amount) as revenue FROM orders GROUP BY DATE(created_at) ORDER BY date DESC LIMIT 30");

$revenue_data = array_reverse($revenue_data); 

$order_status = fetchAllData($conn, "SELECT status, COUNT(*) as count FROM orders GROUP BY status");
$top_products = fetchAllData($conn, "SELECT p.name, SUM(oi.quantity) as sold FROM order_items oi JOIN products p ON oi.product_id = p.id GROUP BY p.id ORDER BY sold DESC LIMIT 5");


$total_revenue = array_sum(array_column($revenue_data, 'revenue'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Business Analytics - GlowLink</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
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
                    <a href="admin_analysis.php" class="flex items-center px-4 py-3 bg-purple-600/10 text-purple-400 rounded-xl transition shadow-[inset_4px_0_0_0_#a855f7]">
                        <i class="fas fa-chart-pie w-6 mr-3"></i> Analytics
                    </a>
                </nav>
            </div>
            <div class="p-4 border-t border-gray-800">
                <a href="logout.php" class="flex items-center justify-center w-full px-4 py-3 bg-red-600/10 text-red-500 hover:bg-red-600 hover:text-white rounded-xl transition font-medium">
                    <i class="fas fa-power-off mr-2"></i> Logout
                </a>
            </div>
        </div>

        <div class="flex-1 p-10 overflow-y-auto bg-gradient-to-br from-gray-950 to-gray-900">
            
            <header class="mb-10 flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-white mb-2">Business Intelligence</h1>
                    <p class="text-gray-400">Track your store's performance and analytics.</p>
                </div>
                <div class="flex items-center space-x-4">
                    <button class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition border border-gray-700">
                        <i class="fas fa-download mr-2"></i> Export Report
                    </button>
                </div>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-gray-900/50 backdrop-blur-xl border border-purple-500/30 p-8 rounded-3xl relative overflow-hidden group hover:border-purple-500/60 transition duration-300">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-purple-600/20 rounded-full blur-2xl group-hover:bg-purple-600/30 transition"></div>
                    <p class="text-purple-300 font-medium mb-1 flex items-center"><i class="fas fa-wallet mr-2"></i> Total Revenue (30 Days)</p>
                    <p class="text-4xl font-bold text-white mt-2">$<?= number_format($total_revenue, 2) ?></p>
                </div>
                <div class="bg-gray-900/50 backdrop-blur-xl border border-blue-500/30 p-8 rounded-3xl relative overflow-hidden group hover:border-blue-500/60 transition duration-300">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-blue-600/20 rounded-full blur-2xl group-hover:bg-blue-600/30 transition"></div>
                    <p class="text-blue-300 font-medium mb-1 flex items-center"><i class="fas fa-shopping-cart mr-2"></i> Total Orders</p>
                    <p class="text-4xl font-bold text-white mt-2"><?= array_sum(array_column($order_status, 'count')) ?></p>
                </div>
                <div class="bg-gray-900/50 backdrop-blur-xl border border-emerald-500/30 p-8 rounded-3xl relative overflow-hidden group hover:border-emerald-500/60 transition duration-300">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-emerald-600/20 rounded-full blur-2xl group-hover:bg-emerald-600/30 transition"></div>
                    <p class="text-emerald-300 font-medium mb-1 flex items-center"><i class="fas fa-chart-line mr-2"></i> Avg. Order Value</p>
                    <?php $avg_order = $total_revenue > 0 ? $total_revenue / array_sum(array_column($order_status, 'count')) : 0; ?>
                    <p class="text-4xl font-bold text-white mt-2">$<?= number_format($avg_order, 2) ?></p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="bg-gray-900 rounded-3xl p-8 border border-gray-800 lg:col-span-2 shadow-lg">
                    <h3 class="text-lg font-medium text-gray-300 mb-6 flex items-center justify-between">
                        <span>Revenue Trend</span>
                        <span class="text-xs font-normal bg-gray-800 px-3 py-1 rounded-full text-gray-400">Last 30 Days</span>
                    </h3>
                    <div class="relative h-72">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <div class="bg-gray-900 rounded-3xl p-8 border border-gray-800 shadow-lg flex flex-col">
                    <h3 class="text-lg font-medium text-gray-300 mb-6">Order Status</h3>
                    <div class="relative flex-1 flex items-center justify-center min-h-[250px]">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>

                <div class="bg-gray-900 rounded-3xl p-8 border border-gray-800 lg:col-span-3 shadow-lg">
                    <h3 class="text-lg font-medium text-gray-300 mb-6">Top Performing Products</h3>
                    <div class="relative h-64">
                        <canvas id="topProductsChart"></canvas>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
    
    Chart.defaults.color = '#9ca3af';
    Chart.defaults.font.family = "'Inter', sans-serif";

  
    const revCtx = document.getElementById('revenueChart').getContext('2d');
    const gradientRev = revCtx.createLinearGradient(0, 0, 0, 400);
    gradientRev.addColorStop(0, 'rgba(168, 85, 247, 0.5)'); // Purple
    gradientRev.addColorStop(1, 'rgba(168, 85, 247, 0.0)');

    new Chart(revCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($revenue_data, 'date')) ?: '[]' ?>,
            datasets: [{
                label: 'Revenue ($)',
                data: <?= json_encode(array_column($revenue_data, 'revenue')) ?: '[]' ?>,
                borderColor: '#a855f7',
                backgroundColor: gradientRev,
                borderWidth: 3,
                pointBackgroundColor: '#111827',
                pointBorderColor: '#a855f7',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.4 
            }]
        },
        options: { 
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.9)',
                    titleColor: '#fff',
                    bodyColor: '#e5e7eb',
                    borderColor: '#374151',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                }
            },
            scales: {
                y: { grid: { color: '#374151', drawBorder: false }, beginAtZero: true },
                x: { grid: { display: false, drawBorder: false } }
            }
        }
    });

  
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($order_status, 'status')) ?: '[]' ?>,
            datasets: [{ 
                data: <?= json_encode(array_column($order_status, 'count')) ?: '[]' ?>, 
                backgroundColor: ['#eab308', '#3b82f6', '#10b981', '#ef4444', '#8b5cf6'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '75%',
            plugins: {
                legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true } }
            }
        }
    });

    
    const barCtx = document.getElementById('topProductsChart').getContext('2d');
    const gradientBar = barCtx.createLinearGradient(0, 0, 800, 0);
    gradientBar.addColorStop(0, '#8b5cf6');
    gradientBar.addColorStop(1, '#c084fc');

    new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($top_products, 'name')) ?: '[]' ?>,
            datasets: [{
                label: 'Units Sold',
                data: <?= json_encode(array_column($top_products, 'sold')) ?: '[]' ?>,
                backgroundColor: gradientBar,
                borderRadius: 8,
                barPercentage: 0.6
            }]
        },
        options: { 
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: '#374151', drawBorder: false }, beginAtZero: true },
                y: { grid: { display: false, drawBorder: false } }
            }
        }
    });
    </script>
</body>
</html>