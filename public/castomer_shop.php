<?php
session_start();
$servername = "localhost:3308"; $username = "root"; $password = ""; $dbname = "glowlinkp_db";
try { $conn = new mysqli($servername, $username, $password, $dbname); } catch (Exception $e) {}

$user = ['glow_points' => $_SESSION['glow_points'] ?? 1250];
$current_page = 'shop';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shop | GlowLink</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { background-color: #faf9f8; font-family: sans-serif; }</style>
</head>
<body class="flex h-screen overflow-hidden">
    
    <aside class="w-72 bg-white border-r border-gray-100 flex flex-col shadow-sm z-20">
        <div class="p-8"><div class="text-3xl font-serif font-bold text-slate-900 mb-1">Glow<span class="text-rose-500 italic">Link</span></div></div>
        <nav class="space-y-2 px-4 flex-1 mt-4">
            <a href="customer_dashboard.php" class="flex items-center gap-4 px-4 py-3.5 <?php echo $current_page == 'dashboard' ? 'text-rose-600 bg-rose-50' : 'text-slate-500 hover:bg-slate-50'; ?> rounded-2xl font-bold"> <i class="fa-solid fa-border-all text-lg w-5"></i> Dashboard </a>
            <a href="skin_profile.php" class="flex items-center gap-4 px-4 py-3.5 <?php echo $current_page == 'profile' ? 'text-rose-600 bg-rose-50' : 'text-slate-500 hover:bg-slate-50'; ?> rounded-2xl font-bold"> <i class="fa-solid fa-sparkles text-lg w-5"></i> My Skin Profile </a>
            <a href="shop.php" class="flex items-center gap-4 px-4 py-3.5 <?php echo $current_page == 'shop' ? 'text-rose-600 bg-rose-50' : 'text-slate-500 hover:bg-slate-50'; ?> rounded-2xl font-bold"> <i class="fa-solid fa-bag-shopping text-lg w-5"></i> Shop Products </a>
            <a href="my_orders.php" class="flex items-center gap-4 px-4 py-3.5 <?php echo $current_page == 'orders' ? 'text-rose-600 bg-rose-50' : 'text-slate-500 hover:bg-slate-50'; ?> rounded-2xl font-bold"> <i class="fa-solid fa-box-open text-lg w-5"></i> My Orders </a>
            <a href="rewards.php" class="flex items-center justify-between px-4 py-3.5 <?php echo $current_page == 'rewards' ? 'text-rose-600 bg-rose-50' : 'text-slate-500 hover:bg-slate-50'; ?> rounded-2xl font-bold"> <div><i class="fa-solid fa-gift text-lg w-5 mr-3"></i> Rewards</div> <span class="bg-amber-100 text-amber-600 text-xs px-2 py-1 rounded-full font-black"><?php echo $user['glow_points']; ?></span> </a>
        </nav>
    </aside>

    <main class="flex-1 overflow-y-auto p-8 lg:p-12">
        <div class="max-w-6xl mx-auto space-y-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-serif font-bold text-slate-900">Shop Products ✨</h1>
                <a href="customer_dashboard.php" class="bg-rose-500 text-white px-4 py-2 rounded-lg font-bold text-sm">View Cart</a>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php 
                $products = [
                    ['id'=>1, 'name'=>'Vitamin C Serum', 'price'=>1250, 'img'=>'https://cdn-icons-png.flaticon.com/512/2884/2884813.png'],
                    ['id'=>2, 'name'=>'Clay Mask', 'price'=>850, 'img'=>'https://cdn-icons-png.flaticon.com/512/2553/2553591.png'],
                    ['id'=>3, 'name'=>'SPF 50 Sunscreen', 'price'=>1500, 'img'=>'https://cdn-icons-png.flaticon.com/512/825/825555.png'],
                    ['id'=>4, 'name'=>'Night Cream', 'price'=>1800, 'img'=>'https://cdn-icons-png.flaticon.com/512/3034/3034178.png'],
                ];
                foreach($products as $p): ?>
                <div class="bg-white/80 border border-white shadow-sm p-6 rounded-3xl text-center group hover:shadow-md transition">
                    <div class="bg-slate-50 rounded-2xl mb-4 p-4 flex justify-center group-hover:scale-105 transition">
                        <img src="<?php echo $p['img']; ?>" class="h-24 object-contain opacity-80">
                    </div>
                    <h3 class="font-bold text-slate-800 mb-1"><?php echo $p['name']; ?></h3>
                    <p class="text-rose-600 font-bold mb-4">৳<?php echo number_format($p['price'], 2); ?></p>
                    <form action="customer_dashboard.php" method="POST">
                        <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                        <input type="hidden" name="product_name" value="<?php echo $p['name']; ?>">
                        <input type="hidden" name="product_price" value="<?php echo $p['price']; ?>">
                        <button name="add_to_cart" class="w-full py-2 bg-slate-100 hover:bg-slate-900 hover:text-white rounded-xl font-bold text-sm transition-colors">
                            Add to Cart
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</body>
</html>