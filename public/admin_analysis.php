<?php
session_start();
include 'connection.php';

mysqli_report(MYSQLI_REPORT_OFF);

$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, $port);

function fetchAllData($conn, $query)
{
    $result = $conn->query($query);
    $data = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }

    return $data;
}

$revenue_data = fetchAllData(
    $conn,
    "SELECT DATE(created_at) as date,
    SUM(total_amount) as revenue
    FROM orders
    GROUP BY DATE(created_at)
    ORDER BY date DESC
    LIMIT 30"
);

$revenue_data = array_reverse($revenue_data);

$order_status = fetchAllData(
    $conn,
    "SELECT status, COUNT(*) as count
    FROM orders
    GROUP BY status"
);

$top_products = fetchAllData(
    $conn,
    "SELECT p.name,
    SUM(oi.quantity) as sold
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    GROUP BY p.id
    ORDER BY sold DESC
    LIMIT 5"
);

$total_revenue = array_sum(array_column($revenue_data, 'revenue'));
$total_orders = array_sum(array_column($order_status, 'count'));

$avg_order = $total_orders > 0
    ? $total_revenue / $total_orders
    : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GlowLink Analytics</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>

<body class="bg-[#fcf5f8] text-gray-700">

    <div class="flex h-screen overflow-hidden">

        <aside class="w-[260px] bg-[#24091A] text-white flex flex-col justify-between">

            <div>
                <div class="px-8 py-8">
                    <h1 class="text-3xl font-bold tracking-wide">
                        Glow<span class="text-pink-500">Link</span>
                    </h1>
                </div>

                <nav class="mt-2 space-y-1.5 px-5">

                    <a href="retailer_dashboard.php"
                        class="flex items-center gap-4 px-4 py-3 rounded-2xl text-[#f0a8c9] hover:bg-[#3a1027] hover:text-white transition font-medium">
                        <i class="fas fa-border-all w-5 text-center"></i>
                        Dashboard
                    </a>

                    <a href="products.php"
                        class="flex items-center gap-4 px-4 py-3 rounded-2xl text-[#f0a8c9] hover:bg-[#3a1027] hover:text-white transition font-medium">
                        <i class="fas fa-box w-5 text-center"></i>
                        Products
                    </a>

                    <a href="orders.php"
                        class="flex items-center gap-4 px-4 py-3 rounded-2xl text-[#f0a8c9] hover:bg-[#3a1027] hover:text-white transition font-medium">
                        <i class="fas fa-shopping-cart w-5 text-center"></i>
                        Orders
                    </a>

                    <a href="admin_analysis.php"
                        class="flex items-center gap-4 px-4 py-3 rounded-2xl bg-[#741943] text-white shadow-md font-medium">
                        <i class="fas fa-chart-line w-5 text-center"></i>
                        Analytics
                    </a>

                    <a href="setting.php"
                        class="flex items-center gap-4 px-4 py-3 rounded-2xl text-[#f0a8c9] hover:bg-[#3a1027] hover:text-white transition font-medium">
                        <i class="fas fa-cog w-5 text-center"></i>
                        Settings
                    </a>

                </nav>

            </div>

            <div class="p-5">
                <a href="logout.php"
                    class="flex items-center justify-center gap-3 bg-[#3a1027] hover:bg-pink-600 transition rounded-2xl py-3.5 font-medium">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>

        </aside>

        <main class="flex-1 overflow-y-auto">

            <div class="h-24 bg-transparent flex items-center justify-between px-8">

                <div class="relative w-[400px]">
                    <input type="text"
                        placeholder="Search orders, products..."
                        class="w-full bg-white rounded-full py-3 pl-12 pr-5 shadow-[0_2px_10px_-3px_rgba(0,0,0,0.05)] border border-gray-100 outline-none focus:ring-2 focus:ring-pink-300 text-sm font-medium">
                    <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-pink-400"></i>
                </div>

                <div class="flex items-center gap-5">

                    <button class="w-11 h-11 rounded-full bg-[#fae8f0] text-pink-500 hover:bg-pink-200 transition flex items-center justify-center">
                        <i class="far fa-bell text-lg"></i>
                    </button>

                    <div class="flex items-center gap-3 bg-[#fae8f0] rounded-full p-1.5 pr-5 cursor-pointer hover:bg-pink-100 transition">
                        <div class="w-9 h-9 rounded-full bg-pink-500 flex items-center justify-center text-white text-sm font-bold">
                            JS
                        </div>
                        <div>
                            <h4 class="font-bold text-pink-900 text-sm leading-tight">Joy Saha</h4>
                            <p class="text-xs text-pink-600 font-medium leading-tight">Retailer Admin</p>
                        </div>
                    </div>

                </div>

            </div>

            <div class="px-8 pb-8">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">

                    <div class="bg-white rounded-[24px] border border-gray-100 p-6 shadow-sm">
                        <p class="text-pink-500 text-sm font-bold mb-3 flex items-center gap-2">
                            <i class="fas fa-wallet text-base"></i> Total Revenue
                        </p>
                        <h2 class="text-[32px] font-extrabold text-gray-800">
                            $<?= number_format($total_revenue, 2) ?>
                        </h2>
                    </div>

                    <div class="bg-white rounded-[24px] border border-gray-100 p-6 shadow-sm">
                        <p class="text-pink-500 text-sm font-bold mb-3 flex items-center gap-2">
                            <i class="fas fa-shopping-bag text-base"></i> Total Orders
                        </p>
                        <h2 class="text-[32px] font-extrabold text-gray-800">
                            <?= $total_orders ?>
                        </h2>
                    </div>

                    <div class="bg-white rounded-[24px] border border-gray-100 p-6 shadow-sm">
                        <p class="text-pink-500 text-sm font-bold mb-3 flex items-center gap-2">
                            <i class="fas fa-chart-line text-base"></i> Avg Order Value
                        </p>
                        <h2 class="text-[32px] font-extrabold text-gray-800">
                            $<?= number_format($avg_order, 2) ?>
                        </h2>
                    </div>

                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <div class="bg-white rounded-[24px] border border-gray-100 p-7 shadow-sm lg:col-span-2">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-[17px] font-bold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-chart-area text-pink-500"></i> Revenue Overview
                            </h3>
                            <span class="text-xs font-bold bg-[#fae8f0] text-pink-600 px-4 py-1.5 rounded-full">
                                Last 30 Days
                            </span>
                        </div>
                        <div class="h-[280px] w-full">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>

                    <div class="bg-white rounded-[24px] border border-gray-100 p-7 shadow-sm">
                        <h3 class="text-[17px] font-bold text-gray-800 mb-6 flex items-center gap-2">
                            <i class="fas fa-chart-pie text-pink-500"></i> Order Status
                        </h3>
                        <div class="h-[280px] w-full flex items-center justify-center relative">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>

                    <div class="bg-white rounded-[24px] border border-gray-100 p-7 shadow-sm lg:col-span-3">
                        <h3 class="text-[17px] font-bold text-gray-800 mb-6 flex items-center gap-2">
                            <i class="fas fa-fire text-pink-500"></i> Top Selling Products
                        </h3>
                        <div class="h-[250px] w-full">
                            <canvas id="topProductsChart"></canvas>
                        </div>
                    </div>

                </div>

            </div>

        </main>

    </div>

    <script>
        // Global Chart Defaults
        Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
        Chart.defaults.color = '#718096';

        // --- Revenue Line Chart ---
        const revCtx = document.getElementById('revenueChart').getContext('2d');
        const gradientRev = revCtx.createLinearGradient(0, 0, 0, 300);
        gradientRev.addColorStop(0, 'rgba(236, 72, 153, 0.4)');
        gradientRev.addColorStop(1, 'rgba(236, 72, 153, 0.01)');

        new Chart(revCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($revenue_data, 'date')) ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?= json_encode(array_column($revenue_data, 'revenue')) ?>,
                    borderColor: '#ec4899',
                    backgroundColor: gradientRev,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4, // Smooth curves
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#ec4899',
                    pointBorderWidth: 2,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#fdf2f6',
                            drawBorder: false
                        },
                        ticks: {
                            font: { weight: '500' }
                        }
                    },
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            font: { weight: '500' }
                        }
                    }
                }
            }
        });

        // --- Order Status Doughnut Chart ---
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($order_status, 'status')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($order_status, 'count')) ?>,
                    backgroundColor: [
                        '#e83e8c', // Dark Pink
                        '#f06292', // Medium Pink
                        '#f8bbd0', // Light Pink
                        '#f472b6',
                        '#be185d'
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%', // Makes the ring thinner
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: { weight: '600', size: 12 }
                        }
                    }
                }
            }
        });

        // --- Top Products Bar Chart ---
        const barCtx = document.getElementById('topProductsChart').getContext('2d');
        const gradientBar = barCtx.createLinearGradient(0, 0, 600, 0);
        gradientBar.addColorStop(0, '#f472b6');
        gradientBar.addColorStop(1, '#e83e8c');

        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($top_products, 'name')) ?>,
                datasets: [{
                    label: 'Units Sold',
                    data: <?= json_encode(array_column($top_products, 'sold')) ?>,
                    backgroundColor: gradientBar,
                    borderRadius: 6,
                    barPercentage: 0.5
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: '#fdf2f6', drawBorder: false },
                        ticks: { font: { weight: '500' } }
                    },
                    y: {
                        grid: { display: false, drawBorder: false },
                        ticks: { font: { weight: '500' } }
                    }
                }
            }
        });
    </script>

</body>

</html>