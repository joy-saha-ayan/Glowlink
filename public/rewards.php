<?php
session_start();
include 'connection.php'; 

$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$user_id = $_SESSION['user_id'] ?? 1;
$current_page = 'rewards';
$message = '';
$msg_type = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'redeem') {
    $reward_cost = intval($_POST['reward_cost']);
    $reward_name = $_POST['reward_name'];
    
    $stmt = $conn->prepare("SELECT glow_points FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $user_data = $res->fetch_assoc();
    
    if ($user_data && $user_data['glow_points'] >= $reward_cost) {
       
        $new_points = $user_data['glow_points'] - $reward_cost;
        $update_stmt = $conn->prepare("UPDATE users SET glow_points = ? WHERE id = ?");
        $update_stmt->bind_param("ii", $new_points, $user_id);
        
        if ($update_stmt->execute()) {
            $message = "Congratulations! You successfully redeemed '$reward_name'.";
            $msg_type = "success";
        
        } else {
            $message = "Something went wrong. Please try again.";
            $msg_type = "error";
        }
    } else {
        $message = "Not enough Glow Points to redeem this reward!";
        $msg_type = "error";
    }
}

$stmt = $conn->prepare("SELECT glow_points FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$glow_points = $user['glow_points'] ?? 0;

$rewards = [
    [
        'id' => 1,
        'name' => '10% Off Coupon',
        'cost' => 10000,
        'icon' => 'fa-ticket',
        'color' => 'bg-blue-500',
        'desc' => 'Get 10% discount on your next skincare purchase.'
    ],
    [
        'id' => 2,
        'name' => 'Free Jade Roller',
        'cost' => 1500,
        'icon' => 'fa-gem',
        'color' => 'bg-emerald-500',
        'desc' => 'Add a premium Jade Roller to your next order for free.'
    ],
    [
        'id' => 3,
        'name' => 'Free Shipping',
        'cost' => 500,
        'icon' => 'fa-truck-fast',
        'color' => 'bg-purple-500',
        'desc' => 'Enjoy free home delivery on any order amount.'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rewards | GlowLink</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }
        .glass-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); }
        .reward-card { transition: all 0.3s ease; }
        .reward-card:hover { transform: translateY(-4px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800">
    
    <aside class="w-72 bg-white border-r border-slate-200 flex flex-col shadow-[4px_0_24px_rgba(0,0,0,0.02)] z-20">
        <div class="p-8 border-b border-slate-100">
            <div class="text-3xl font-serif font-black text-slate-900 tracking-tight">Glow<span class="text-rose-500 italic">Link</span></div>
        </div>
        <nav class="space-y-1.5 px-4 flex-1 mt-6 overflow-y-auto">
            <a href="customer_dashboard.php" class="flex items-center gap-3 px-4 py-3.5 <?php echo $current_page == 'dashboard' ? 'text-rose-600 bg-rose-50' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?> rounded-xl font-semibold transition-colors"> <i class="fa-solid fa-border-all text-lg w-6 text-center"></i> Dashboard </a>
            <a href="skin_profile.php" class="flex items-center gap-3 px-4 py-3.5 <?php echo $current_page == 'profile' ? 'text-rose-600 bg-rose-50' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?> rounded-xl font-semibold transition-colors"> <i class="fa-solid fa-sparkles text-lg w-6 text-center"></i> My Skin Profile </a>
            <a href="shop.php" class="flex items-center gap-3 px-4 py-3.5 <?php echo $current_page == 'shop' ? 'text-rose-600 bg-rose-50' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?> rounded-xl font-semibold transition-colors"> <i class="fa-solid fa-bag-shopping text-lg w-6 text-center"></i> Shop Products </a>
            <a href="my_orders.php" class="flex items-center gap-3 px-4 py-3.5 <?php echo $current_page == 'orders' ? 'text-rose-600 bg-rose-50' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?> rounded-xl font-semibold transition-colors"> <i class="fa-solid fa-box-open text-lg w-6 text-center"></i> My Orders </a>
            <a href="rewards.php" class="flex items-center justify-between px-4 py-3.5 <?php echo $current_page == 'rewards' ? 'text-rose-600 bg-rose-50 shadow-sm border border-rose-100' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?> rounded-xl font-semibold transition-all"> 
                <div class="flex items-center gap-3"><i class="fa-solid fa-gift text-lg w-6 text-center"></i> Rewards</div> 
                <span class="bg-amber-100 text-amber-600 text-[11px] px-2.5 py-1 rounded-full font-black shadow-sm"><?php echo number_format($glow_points); ?></span> 
            </a>
        </nav>
    </aside>

    <main class="flex-1 overflow-y-auto bg-slate-50 relative">
        <div class="absolute top-0 left-0 w-full h-80 bg-gradient-to-br from-rose-500 via-rose-400 to-orange-400 opacity-90 z-0"></div>

        <div class="relative z-10 p-8 lg:p-12 max-w-6xl mx-auto space-y-8">
            
            <?php if(!empty($message)): ?>
                <div class="p-4 rounded-xl font-semibold flex items-center gap-3 shadow-md <?php echo $msg_type == 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'; ?>">
                    <i class="fa-solid <?php echo $msg_type == 'success' ? 'fa-circle-check text-green-500' : 'fa-circle-exclamation text-red-500'; ?> text-xl"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="glass-card border border-white/40 shadow-xl p-10 rounded-[32px] text-center max-w-2xl mx-auto relative overflow-hidden">
                <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-white/20 rounded-full blur-2xl"></div>
                <div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-32 h-32 bg-amber-300/20 rounded-full blur-xl"></div>
                
                <div class="relative z-10">
                    <div class="w-24 h-24 mx-auto bg-gradient-to-tr from-amber-300 to-yellow-500 rounded-full flex items-center justify-center shadow-lg shadow-amber-500/30 mb-6 border-4 border-white">
                        <i class="fa-solid fa-star text-4xl text-white"></i>
                    </div>
                    <h2 class="text-xl font-bold mb-1 text-slate-500 uppercase tracking-widest">Available Glow Points</h2>
                    <p class="text-6xl font-black text-transparent bg-clip-text bg-gradient-to-r from-rose-500 to-orange-500 mb-6 drop-shadow-sm">
                        <?php echo number_format($glow_points); ?>
                    </p>
                    <p class="text-slate-600 font-medium bg-slate-100 inline-block px-6 py-3 rounded-full border border-slate-200 shadow-sm">
                        <i class="fa-solid fa-gamepad mr-2 text-rose-500"></i> Play the Mini-Game to earn more points!
                    </p>
                </div>
            </div>

            <div class="pt-8">
                <h3 class="text-2xl font-bold text-slate-900 mb-6 flex items-center gap-3">
                    <i class="fa-solid fa-award text-amber-500"></i> Redeem Rewards
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach($rewards as $reward): 
                        $can_afford = $glow_points >= $reward['cost'];
                        $progress = min(100, ($glow_points / $reward['cost']) * 100);
                    ?>
                    <div class="bg-white rounded-2xl p-6 border border-slate-200 reward-card shadow-sm flex flex-col relative overflow-hidden">
                        
                        <?php if(!$can_afford): ?>
                            <div class="absolute top-4 right-4 bg-slate-100 text-slate-400 w-8 h-8 rounded-full flex items-center justify-center border border-slate-200">
                                <i class="fa-solid fa-lock text-xs"></i>
                            </div>
                        <?php endif; ?>

                        <div class="w-14 h-14 rounded-xl <?php echo $reward['color']; ?> flex items-center justify-center text-white mb-5 shadow-inner">
                            <i class="fa-solid <?php echo $reward['icon']; ?> text-2xl"></i>
                        </div>
                        
                        <h4 class="text-lg font-bold text-slate-900 mb-2"><?php echo $reward['name']; ?></h4>
                        <p class="text-sm text-slate-500 flex-1 mb-6"><?php echo $reward['desc']; ?></p>
                        
                        <div class="mt-auto">
                            <div class="flex justify-between text-xs font-bold mb-2">
                                <span class="<?php echo $can_afford ? 'text-green-500' : 'text-rose-500'; ?>"><?php echo number_format($reward['cost']); ?> Pts</span>
                                <span class="text-slate-400"><?php echo floor($progress); ?>%</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2 mb-6 overflow-hidden">
                                <div class="<?php echo $can_afford ? 'bg-green-500' : 'bg-amber-400'; ?> h-2 rounded-full transition-all duration-1000" style="width: <?php echo $progress; ?>%"></div>
                            </div>

                            <form action="" method="POST" onsubmit="return confirm('Are you sure you want to redeem <?php echo $reward['name']; ?> for <?php echo $reward['cost']; ?> points?');">
                                <input type="hidden" name="action" value="redeem">
                                <input type="hidden" name="reward_cost" value="<?php echo $reward['cost']; ?>">
                                <input type="hidden" name="reward_name" value="<?php echo htmlspecialchars($reward['name']); ?>">
                                
                                <?php if($can_afford): ?>
                                    <button type="submit" class="w-full py-3 bg-slate-900 text-white rounded-xl font-bold hover:bg-slate-800 transition-colors shadow-md shadow-slate-900/20">
                                        Redeem Now
                                    </button>
                                <?php else: ?>
                                    <button type="button" disabled class="w-full py-3 bg-slate-100 text-slate-400 rounded-xl font-bold cursor-not-allowed border border-slate-200">
                                        Need <?php echo number_format($reward['cost'] - $glow_points); ?> more points
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </main>
</body>
</html>