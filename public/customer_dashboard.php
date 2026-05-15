<?php
session_start();

$servername = "localhost:3308";
$username = "root";
$password = "";
$dbname = "glowlinkp_db";

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

$user_id = $_SESSION['user_id'];

$conn = null;
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
} catch (Exception $e) {}

if (isset($_POST['add_game_points']) && $conn) {
    $points_to_add = (int)$_POST['points'];

    $stmt = $conn->prepare("UPDATE users SET glow_points = glow_points + ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $points_to_add, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    $res = $conn->query("SELECT glow_points FROM users WHERE id = $user_id");
    echo ($res && $row = $res->fetch_assoc()) ? $row['glow_points'] : 0;
    exit;
}

$user_name = 'Joy Saha';
$user_points = 0;

if ($conn) {
    $user_res = $conn->query("SELECT name, glow_points FROM users WHERE id = $user_id");
    if ($user_res && $row = $user_res->fetch_assoc()) {
        $user_name = $row['name'];
        $user_points = $row['glow_points'];
    }
}

$ads = [];
if ($conn) {
    $ad_res = $conn->query("SELECT * FROM advertisements WHERE status='approved' ORDER BY RAND() LIMIT 3");
    if ($ad_res) {
        while ($row = $ad_res->fetch_assoc()) {
            $ads[] = $row;
        }
    }
}

if (empty($ads)) {
    $ads[] = [
        'title' => 'Buy 1 Get 1 Free on Vitamin C Serum!',
        'description' => 'Offer valid till weekend. Exclusively for premium members.',
        'bg_color_start' => 'from-rose-500',
        'bg_color_end' => 'to-pink-400',
        'image_url' => 'https://cdn-icons-png.flaticon.com/512/2884/2884813.png'
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | GlowLink</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background: #faf9f8;
            font-family: sans-serif;
        }
        .glass-panel {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255,255,255,0.7);
            box-shadow: 0 10px 40px rgba(0,0,0,0.05);
        }
        .game-area {
            position: relative;
            height: 250px;
            background: #0f172a;
            border-radius: 1.5rem;
            overflow: hidden;
            cursor: crosshair;
        }
        .glow-drop {
            position: absolute;
            width: 52px;
            height: 52px;
            cursor: pointer;
            transition: all 0.3s ease;
            animation: pulseGlow 1.1s infinite;
            z-index: 10;
        }
        @keyframes pulseGlow {
            0% { transform: scale(1); filter: drop-shadow(0 0 10px #fcd34d); }
            50% { transform: scale(1.2); filter: drop-shadow(0 0 25px #fcd34d); }
            100% { transform: scale(1); filter: drop-shadow(0 0 10px #fcd34d); }
        }
        .ai-card {
            background: linear-gradient(135deg, #6b21a8, #db2777);
            color: white;
        }
        .slide-fade {
            transition: opacity 1s ease-in-out;
        }
    </style>
</head>
<body class="text-slate-800 flex h-screen overflow-hidden">


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
        <a href="customer_dashboard.php" class="flex items-center gap-4 px-4 py-3.5 rounded-2xl font-bold text-rose-600 bg-rose-50">
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
        <a href="my_orders.php" class="flex items-center gap-4 px-4 py-3.5 text-slate-500 hover:bg-slate-50 rounded-2xl font-bold">
            <i class="fa-solid fa-box-open text-lg w-5"></i>
            My Orders
        </a>
        <a href="rewards.php" class="flex items-center justify-between px-4 py-3.5 text-slate-500 hover:bg-slate-50 rounded-2xl font-bold">
            <div class="flex items-center gap-4">
                <i class="fa-solid fa-gift text-lg w-5"></i>
                Rewards
            </div>
            <span class="bg-amber-100 text-amber-600 text-xs px-3 py-1 rounded-full font-black" id="sidebar-points">
                <?php echo $user_points; ?>
            </span>
        </a>
    </nav>
    <div class="p-6 border-t border-gray-100">
        <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-red-500 font-bold hover:bg-red-50 rounded-xl">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            Logout
        </a>
    </div>
</aside>

<main class="flex-1 overflow-y-auto relative p-8 lg:p-12">
    <div class="max-w-6xl mx-auto space-y-8">
        <h1 class="text-3xl font-serif font-bold text-slate-900 mb-6">
            Welcome back, <?php echo htmlspecialchars($user_name); ?> ✨
        </h1>

        <!-- Ad Slider -->
        <div id="ad-slider-container" class="relative w-full h-40 rounded-3xl overflow-hidden shadow-lg">
            <?php foreach($ads as $index => $ad): ?>
                <div class="ad-slide slide-fade absolute inset-0 w-full h-full bg-gradient-to-r <?php echo htmlspecialchars($ad['bg_color_start'] . ' ' . $ad['bg_color_end']); ?> flex items-center p-8 <?php echo $index === 0 ? 'opacity-100 z-10' : 'opacity-0 z-0'; ?>">
                    <div class="relative z-10 text-white w-2/3">
                        <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-widest mb-3 inline-block">
                            SPECIAL OFFER
                        </span>
                        <h2 class="text-2xl font-bold mb-1">
                            <?php echo htmlspecialchars($ad['title']); ?>
                        </h2>
                        <p class="text-white/80">
                            <?php echo htmlspecialchars($ad['description']); ?>
                        </p>
                    </div>
                    <img src="<?php echo htmlspecialchars($ad['image_url']); ?>" 
                         class="absolute right-10 -bottom-5 w-48 opacity-40 transform rotate-12">
                </div>
            <?php endforeach; ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Game Section -->
            <div class="glass-panel p-6 rounded-3xl border border-gray-100">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-slate-800">
                        <i class="fa-solid fa-gamepad text-indigo-500 mr-2"></i>
                        Catch the Glow Drop!
                    </h3>
                    <span class="text-xs bg-indigo-50 text-indigo-600 px-3 py-1.5 rounded-full font-bold">
                        Win Rewards
                    </span>
                </div>
                <p class="text-sm text-slate-500 mb-2">Click the floating drops to earn points!</p>
                <p class="text-xs text-amber-500 font-bold mb-4">* Reach 10,000 points for a flat 5% discount!</p>
                
                <div class="game-area shadow-inner" id="gameArea">
                    <img src="https://cdn-icons-png.flaticon.com/512/3257/3257805.png"
                         id="glowDrop"
                         class="glow-drop"
                         onclick="catchDrop()">
                </div>
            </div>

            <!-- AI Assistant - More Attractive -->
            <div class="glass-panel p-6 rounded-3xl border border-gray-100 ai-card flex flex-col justify-between h-full">
                <div>
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <div class="flex items-center gap-3">
                                <i class="fa-solid fa-robot text-4xl"></i>
                                <div>
                                    <h3 class="text-xl font-bold">Glow AI Assistant</h3>
                                    <p class="text-sm opacity-90">Your Skincare Expert</p>
                                </div>
                            </div>
                        </div>
                        <span class="px-3 py-1 bg-white/20 text-xs font-medium rounded-full flex items-center gap-1">
                            <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                            Online
                        </span>
                    </div>
                    
                    <p class="text-white/90 text-sm leading-relaxed mb-6">
                        Ask anything about skincare, ingredients, routines, or product recommendations. 
                        Our AI is trained specifically for glowing skin!
                    </p>
                </div>

                <button onclick="openChat()" 
                        class="w-full py-4 rounded-2xl bg-white text-purple-700 font-bold text-lg shadow-xl hover:scale-105 transition-all duration-300 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-comments"></i>
                    START CHATTING
                </button>
            </div>
        </div>
    </div>
</main>

<!-- AI Chat Modal -->
<div id="aiModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="bg-white w-full max-w-2xl rounded-3xl overflow-hidden shadow-2xl" style="height: 720px;">
        <div class="p-5 border-b flex justify-between items-center bg-gradient-to-r from-purple-600 to-pink-600 text-white">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-robot text-2xl"></i>
                <div>
                    <span class="font-bold text-lg">Glow AI Assistant</span>
                    <p class="text-xs opacity-75">Powered by Gemini</p>
                </div>
            </div>
            <button onclick="closeChat()" class="text-white/80 hover:text-white text-2xl transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <iframe src="gemini.html" class="w-full h-full border-none"></iframe>
    </div>
</div>

<script>
const slides = document.querySelectorAll('.ad-slide');
let currentSlide = 0;

if (slides.length > 1) {
    setInterval(() => {
        slides[currentSlide].classList.replace('opacity-100', 'opacity-0');
        slides[currentSlide].classList.replace('z-10', 'z-0');
        currentSlide = (currentSlide + 1) % slides.length;
        slides[currentSlide].classList.replace('opacity-0', 'opacity-100');
        slides[currentSlide].classList.replace('z-0', 'z-10');
    }, 4000);
}

// Game Logic
const drop = document.getElementById('glowDrop');
const gameArea = document.getElementById('gameArea');

if (drop && gameArea) {
    function moveDrop() {
        const maxX = gameArea.clientWidth - 70;
        const maxY = gameArea.clientHeight - 70;
        drop.style.left = Math.floor(Math.random() * maxX) + 'px';
        drop.style.top = Math.floor(Math.random() * maxY) + 'px';
    }

    setInterval(moveDrop, 1350);
    moveDrop();

    window.catchDrop = function() {
        moveDrop();
        const formData = new FormData();
        formData.append('add_game_points', true);
        formData.append('points', 10);

        fetch(window.location.href, { method: 'POST', body: formData })
        .then(r => r.text())
        .then(newTotal => {
            document.getElementById('sidebar-points').innerText = newTotal;
            showFloatingText('+10 Points!', drop.style.left, drop.style.top);
        });
    };

    function showFloatingText(msg, x, y) {
        const txt = document.createElement('div');
        txt.innerText = msg;
        txt.style.position = 'absolute';
        txt.style.left = x;
        txt.style.top = y;
        txt.style.color = '#34d399';
        txt.style.fontWeight = 'bold';
        txt.style.fontSize = '19px';
        txt.style.zIndex = '30';
        txt.style.transition = 'all 1s ease';
        gameArea.appendChild(txt);

        setTimeout(() => {
            txt.style.transform = 'translateY(-60px)';
            txt.style.opacity = '0';
        }, 50);
        setTimeout(() => txt.remove(), 1100);
    }
}

function openChat() {
    const modal = document.getElementById('aiModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeChat() {
    const modal = document.getElementById('aiModal');
    modal.classList.remove('flex');
    modal.classList.add('hidden');
}

window.onclick = function(event) {
    const modal = document.getElementById('aiModal');
    if (event.target === modal) closeChat();
};
</script>
</body>
</html>