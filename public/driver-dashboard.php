<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GlowLink • Driver Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap');
        
        :root {
            --glow: #00ff9d;
        }
        
        * {
            transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 150ms;
        }
        
        .dashboard-bg {
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 100%);
        }
        
        .glow-text {
            text-shadow: 
                0 0 10px var(--glow),
                0 0 20px var(--glow),
                0 0 40px var(--glow);
        }
        
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card-hover:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 25px 50px -12px rgb(0 255 157 / 0.2);
        }
        
        .sidebar-link {
            position: relative;
        }
        
        .sidebar-link.active::after {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: #00ff9d;
            box-shadow: 0 0 15px #00ff9d;
        }
        
        .three-canvas {
            filter: drop-shadow(0 0 30px rgba(0, 255, 157, 0.5));
        }
        
        .neon-button {
            position: relative;
            overflow: hidden;
        }
        
        .neon-button::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 40%;
            height: 300%;
            background: linear-gradient(
                120deg,
                transparent,
                rgba(255,255,255,0.4),
                transparent
            );
            transform: skewX(-25deg);
            animation: shimmer 4s infinite linear;
        }
        
        @keyframes shimmer {
            100% {
                transform: translateX(200%);
            }
        }
        
        .status-dot {
            animation: ping 2s cubic-bezier(0, 0, 0.2, 1) infinite;
        }
    </style>
</head>
<body class="dashboard-bg text-white font-sans">

<?php
// ==================== FULL DRIVER DASHBOARD WITH PROFILE PIC UPLOAD ====================
session_start();
require_once '../config/Database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// ==================== HANDLE PROFILE PICTURE UPLOAD ====================
$upload_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {
    $file = $_FILES['profile_pic'];
    if ($file['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $newName = 'driver_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
            $uploadDir = '../public/uploads/profile/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fullPath = $uploadDir . $newName;
            if (move_uploaded_file($file['tmp_name'], $fullPath)) {
                $stmt = $db->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
                $stmt->execute(['/uploads/profile/' . $newName, $_SESSION['user_id']]);
                $upload_message = "<div style='color:#10b981; background:rgba(16,185,129,0.2); padding:8px 12px; border-radius:8px; margin:10px 0; font-size:13px;'>✅ Profile picture updated successfully!</div>";
            }
        } else {
            $upload_message = "<div style='color:#ef4444; background:rgba(239,68,68,0.2); padding:8px 12px; border-radius:8px; margin:10px 0; font-size:13px;'>Only JPG, PNG, GIF files allowed.</div>";
        }
    }
}

// ==================== FETCH REAL DRIVER DATA FROM DATABASE ====================
$stmt = $db->prepare("SELECT id, name, email, role, status, created_at, profile_pic FROM users WHERE id = ? AND role = 'driver' LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$currentDriver = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentDriver) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Default avatar if no picture yet
if (empty($currentDriver['profile_pic'])) {
    $currentDriver['profile_pic'] = 'https://i.pravatar.cc/128?img=68';
}

// Real stats (placeholders - you can replace with queries from orders table later)
$totalDeliveriesToday = 12;
$weeklyEarnings = 8450;
$rating = 4.9;

// Fetch users for Activity Log
$users = [];
$error = null;
try {
    $stmtUsers = $db->query("SELECT id, name, email, role, status, created_at FROM users ORDER BY created_at DESC");
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="flex h-screen overflow-hidden">
    <!-- ==================== SIDEBAR ==================== -->
    <div class="w-72 bg-[#111827] border-r border-[#00ff9d]/20 flex flex-col">
        <!-- Logo -->
        <div class="px-8 py-6 border-b border-[#00ff9d]/10 flex items-center gap-3">
            <div class="w-10 h-10 bg-[#00ff9d] rounded-2xl flex items-center justify-center text-black font-bold text-2xl rotate-12">G</div>
            <h1 class="text-3xl font-bold tracking-tighter" style="font-family: 'Space Grotesk', sans-serif;">GlowLink</h1>
            <span class="px-3 py-1 text-xs font-medium bg-[#00ff9d]/10 text-[#00ff9d] rounded-full">DRIVER</span>
        </div>

        <!-- Driver Info + Profile Picture Upload -->
        <div class="px-8 py-6 border-b border-[#00ff9d]/10">
            <div class="flex items-center gap-4">
                <img src="<?php echo htmlspecialchars($currentDriver['profile_pic']); ?>" 
                     class="w-12 h-12 rounded-2xl ring-2 ring-[#00ff9d]/50 object-cover" alt="Profile">
                <div class="flex-1">
                    <h3 class="font-semibold text-lg"><?php echo htmlspecialchars($currentDriver['name']); ?></h3>
                    <p class="text-sm text-zinc-400"><?php echo htmlspecialchars($currentDriver['email']); ?></p>
                    <div class="flex items-center gap-2 text-sm mt-1">
                        <span class="text-[#00ff9d] flex items-center">
                            <i class="fa-solid fa-circle status-dot text-xs mr-1"></i> ONLINE
                        </span>
                        <span class="text-zinc-400">• Driver</span>
                    </div>
                </div>
            </div>

            <!-- Profile Picture Upload -->
            <form method="POST" enctype="multipart/form-data" class="mt-4">
                <label class="cursor-pointer flex items-center justify-center gap-2 bg-white/10 hover:bg-white/20 py-3 px-5 rounded-3xl text-xs font-medium">
                    <i class="fa-solid fa-camera"></i>
                    CHANGE PROFILE PICTURE
                    <input type="file" name="profile_pic" accept="image/*" onchange="this.form.submit()" style="display:none;">
                </label>
            </form>
            <?php if ($upload_message) echo $upload_message; ?>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-3 py-6 space-y-1 overflow-y-auto">
            <a onclick="navigateTo('dashboard')" class="sidebar-link active flex items-center gap-3 px-6 py-4 text-base font-medium hover:bg-white/5 rounded-3xl mx-2">
                <i class="fa-solid fa-house w-5"></i>
                <span>Dashboard</span>
            </a>
            <a onclick="navigateTo('my-deliveries')" class="sidebar-link flex items-center gap-3 px-6 py-4 text-base font-medium hover:bg-white/5 rounded-3xl mx-2">
                <i class="fa-solid fa-motorcycle w-5"></i>
                <span>My Deliveries</span>
                <span class="ml-auto bg-[#00ff9d] text-black text-xs font-bold px-2.5 rounded-full">12</span>
            </a>
            <a onclick="navigateTo('available')" class="sidebar-link flex items-center gap-3 px-6 py-4 text-base font-medium hover:bg-white/5 rounded-3xl mx-2">
                <i class="fa-solid fa-boxes-packing w-5"></i>
                <span>Available Jobs</span>
            </a>
            <a onclick="navigateTo('earnings')" class="sidebar-link flex items-center gap-3 px-6 py-4 text-base font-medium hover:bg-white/5 rounded-3xl mx-2">
                <i class="fa-solid fa-dollar-sign w-5"></i>
                <span>Earnings</span>
            </a>
            <a onclick="navigateTo('analytics')" class="sidebar-link flex items-center gap-3 px-6 py-4 text-base font-medium hover:bg-white/5 rounded-3xl mx-2">
                <i class="fa-solid fa-chart-line w-5"></i>
                <span>Analytics</span>
            </a>
            <a onclick="navigateTo('map')" class="sidebar-link flex items-center gap-3 px-6 py-4 text-base font-medium hover:bg-white/5 rounded-3xl mx-2">
                <i class="fa-solid fa-map-location-dot w-5"></i>
                <span>Live Map (3D)</span>
            </a>

            <div class="px-6 pt-8 text-xs uppercase tracking-widest text-zinc-400 font-medium">Account</div>
            <a onclick="navigateTo('profile')" class="sidebar-link flex items-center gap-3 px-6 py-4 text-base font-medium hover:bg-white/5 rounded-3xl mx-2">
                <i class="fa-solid fa-user w-5"></i>
                <span>Profile</span>
            </a>
            <a onclick="navigateTo('settings')" class="sidebar-link flex items-center gap-3 px-6 py-4 text-base font-medium hover:bg-white/5 rounded-3xl mx-2">
                <i class="fa-solid fa-gear w-5"></i>
                <span>Settings</span>
            </a>
        </nav>

        <!-- Footer -->
        <div class="p-6 border-t border-[#00ff9d]/10 text-xs flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-battery-three-quarters text-[#00ff9d]"></i>
                <span id="battery">92%</span>
            </div>
            <button onclick="logout()" class="flex items-center gap-2 text-zinc-400 hover:text-white">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <span>Logout</span>
            </button>
            <div class="text-zinc-500">v24.4 • GlowLink</div>
        </div>
    </div>

    <!-- ==================== MAIN CONTENT ==================== -->
    <div class="flex-1 flex flex-col overflow-hidden">
        
        <!-- Top Navbar -->
        <header class="h-16 bg-white/5 backdrop-blur-xl border-b border-white/10 px-8 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <h2 id="page-title" class="text-2xl font-semibold tracking-tight">Driver Dashboard</h2>
                <div class="bg-[#00ff9d]/10 text-[#00ff9d] text-sm px-4 py-1 rounded-3xl flex items-center">
                    <i class="fa-solid fa-location-dot mr-1"></i>
                    Chittagong, BD • Live
                </div>
            </div>

            <div class="flex items-center gap-6">
                <div id="mini-3d" class="w-9 h-9 cursor-pointer" onclick="toggleMini3D()"></div>
                <div class="relative">
                    <input type="text" id="search-input" class="bg-white/10 border border-white/20 focus:border-[#00ff9d] w-80 rounded-3xl pl-12 py-3 text-sm outline-none" placeholder="Search order # or customer...">
                    <i class="fa-solid fa-magnifying-glass absolute left-5 top-4 text-zinc-400"></i>
                </div>
                <div class="relative cursor-pointer" onclick="showNotifications()">
                    <i class="fa-solid fa-bell text-2xl"></i>
                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center">7</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="text-right">
                        <p class="font-semibold"><?php echo htmlspecialchars($currentDriver['name']); ?></p>
                        <p class="text-xs text-[#00ff9d]">ID #<?php echo $currentDriver['id']; ?></p>
                    </div>
                    <img src="<?php echo htmlspecialchars($currentDriver['profile_pic']); ?>" 
                         class="w-9 h-9 rounded-2xl ring-2 ring-offset-2 ring-offset-[#0a0a0a] ring-[#00ff9d] object-cover" alt="">
                </div>
            </div>
        </header>

        <!-- ==================== DASHBOARD BODY ==================== -->
        <div class="flex-1 overflow-auto p-8" id="main-content">
            
            <!-- HERO / 3D SECTION -->
            <div class="grid grid-cols-12 gap-8">
                <div class="col-span-12 lg:col-span-8 bg-gradient-to-br from-[#111827] to-[#1e2937] rounded-3xl p-8 flex items-center justify-between relative overflow-hidden">
                    <div>
                        <h1 class="text-5xl font-bold tracking-tighter glow-text" style="font-family: 'Space Grotesk', sans-serif;">
                            Good evening, <?php echo explode(' ', $currentDriver['name'])[0]; ?>! 👋
                        </h1>
                        <p class="text-2xl mt-3 text-zinc-300">You have <span class="text-[#00ff9d] font-semibold"><?php echo $totalDeliveriesToday; ?> active deliveries</span> today.</p>
                        <div class="mt-8 flex items-center gap-6">
                            <button onclick="acceptRandomOrder()" 
                                    class="neon-button bg-[#00ff9d] text-black font-semibold px-8 py-4 rounded-3xl flex items-center gap-3 text-lg shadow-xl shadow-[#00ff9d]/40">
                                <i class="fa-solid fa-hand-pointer"></i>
                                ACCEPT NEW JOB
                            </button>
                            <div onclick="startNavigation()" class="cursor-pointer flex items-center gap-3 text-white/70 hover:text-white">
                                <i class="fa-solid fa-route text-3xl"></i>
                                <div>
                                    <div class="text-sm font-medium">START NAVIGATION</div>
                                    <div class="text-xs">3.2 km to next drop-off</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="hero-3d" class="w-80 h-80 three-canvas"></div>
                </div>

                <!-- Quick Stats -->
                <div class="col-span-12 lg:col-span-4 space-y-6">
                    <div class="bg-white/5 backdrop-blur-xl rounded-3xl p-6 card-hover flex justify-between items-center">
                        <div>
                            <p class="text-zinc-400 text-sm">Deliveries Today</p>
                            <p class="text-6xl font-bold mt-1"><?php echo $totalDeliveriesToday; ?></p>
                        </div>
                        <i class="fa-solid fa-motorcycle text-6xl text-[#00ff9d]/30"></i>
                    </div>
                    <div class="bg-white/5 backdrop-blur-xl rounded-3xl p-6 card-hover flex justify-between items-center">
                        <div>
                            <p class="text-zinc-400 text-sm">Earnings This Week</p>
                            <p class="text-6xl font-bold mt-1">৳<?php echo $weeklyEarnings; ?></p>
                            <p class="text-[#00ff9d] text-sm flex items-center"><i class="fa-solid fa-arrow-trend-up mr-1"></i>+18% from last week</p>
                        </div>
                        <i class="fa-solid fa-taka-sign text-6xl text-[#00ff9d]/30"></i>
                    </div>
                    <div class="bg-white/5 backdrop-blur-xl rounded-3xl p-6 card-hover flex justify-between items-center">
                        <div>
                            <p class="text-zinc-400 text-sm">Rating</p>
                            <p class="text-6xl font-bold mt-1"><?php echo $rating; ?></p>
                        </div>
                        <div class="flex text-4xl text-amber-400">★★★★★</div>
                    </div>
                </div>
            </div>

            <!-- TABS -->
            <div class="mt-12">
                <div class="flex border-b border-white/10 text-sm font-medium mb-6">
                    <button onclick="switchTab(0)" id="tab-0" class="tab-button active px-8 pb-4 border-b-2 border-[#00ff9d]">Live Deliveries</button>
                    <button onclick="switchTab(1)" id="tab-1" class="tab-button px-8 pb-4">Available Jobs</button>
                    <button onclick="switchTab(2)" id="tab-2" class="tab-button px-8 pb-4">Earnings Breakdown</button>
                    <button onclick="switchTab(3)" id="tab-3" class="tab-button px-8 pb-4">Activity Log</button>
                </div>

                <!-- TAB 0: LIVE DELIVERIES -->
                <div id="content-0">
                    <table class="w-full">
                        <thead>
                            <tr class="text-xs uppercase text-zinc-400 border-b border-white/10">
                                <th class="text-left py-4 px-6">Order ID</th>
                                <th class="text-left py-4 px-6">Customer</th>
                                <th class="text-left py-4 px-6">Address</th>
                                <th class="text-left py-4 px-6">Distance</th>
                                <th class="text-left py-4 px-6">Status</th>
                                <th class="text-right py-4 px-6">Action</th>
                            </tr>
                        </thead>
                        <tbody id="live-deliveries-body" class="divide-y divide-white/10 text-sm"></tbody>
                    </table>
                </div>

                <!-- TAB 1: AVAILABLE JOBS -->
                <div id="content-1" class="hidden">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6" id="available-jobs-grid"></div>
                </div>

                <!-- TAB 2: EARNINGS CHART -->
                <div id="content-2" class="hidden bg-white/5 backdrop-blur-xl rounded-3xl p-8">
                    <canvas id="earnings-chart" height="110"></canvas>
                </div>

                <!-- TAB 3: ACTIVITY LOG -->
                <div id="content-3" class="hidden">
                    <div class="bg-white/5 rounded-3xl p-6">
                        <h4 class="font-semibold mb-4 flex items-center gap-2"><i class="fa-solid fa-clock"></i> Recent Activity (from database)</h4>
                        <?php if ($error): ?>
                            <div class="bg-red-500/10 border border-red-500 text-red-400 p-6 rounded-3xl"><?php echo $error; ?></div>
                        <?php else: ?>
                            <div class="max-h-96 overflow-auto">
                                <table class="w-full text-sm">
                                    <thead class="sticky top-0 bg-[#111827]">
                                        <tr>
                                            <th class="text-left py-3 px-6">User</th>
                                            <th class="text-left py-3 px-6">Role</th>
                                            <th class="text-left py-3 px-6">Status</th>
                                            <th class="text-right py-3 px-6">Joined</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-white/10">
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td class="px-6 py-4"><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td class="px-6 py-4">
                                                <span class="px-3 py-1 text-xs rounded-3xl 
                                                    <?php echo $user['role'] === 'retailer' ? 'bg-orange-400 text-black' : ($user['role'] === 'customer' ? 'bg-blue-400 text-black' : 'bg-[#00ff9d] text-black'); ?>">
                                                    <?php echo htmlspecialchars($user['role'] ?: '—'); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center gap-1.5">
                                                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                                    <?php echo htmlspecialchars($user['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right text-zinc-400 text-xs">
                                                <?php echo date('d M Y • H:i', strtotime($user['created_at'])); ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notification Modal -->
<div onclick="if(event.target.id === 'notification-modal')hideNotifications()" 
     id="notification-modal"
     class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-[9999]">
    <div onclick="event.stopImmediatePropagation()" 
         class="bg-[#111827] w-[420px] rounded-3xl p-6">
        <h3 class="font-semibold text-xl mb-6">Notifications</h3>
        <div class="space-y-4">
            <div class="flex gap-4 bg-white/5 p-4 rounded-2xl">
                <i class="fa-solid fa-box text-[#00ff9d] text-2xl mt-1"></i>
                <div class="flex-1">
                    <p class="font-medium">New order #GL-8742 assigned</p>
                    <p class="text-xs text-zinc-400">2 minutes ago • Pickup in 8 min</p>
                </div>
            </div>
            <div class="flex gap-4 bg-white/5 p-4 rounded-2xl">
                <i class="fa-solid fa-star text-amber-400 text-2xl mt-1"></i>
                <div class="flex-1">
                    <p class="font-medium">Customer gave 5-star rating!</p>
                    <p class="text-xs text-zinc-400">Earlier today</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // ====================== JAVASCRIPT (FULL) ======================
    function initTailwind() {
        tailwind.config = { content: [], theme: { extend: {} } };
    }

    let heroScene, heroCamera, heroRenderer, heroPackage;
    function initHero3D() {
        const container = document.getElementById('hero-3d');
        heroScene = new THREE.Scene();
        heroScene.fog = new THREE.Fog(0x1a1a2e, 5, 30);
        heroCamera = new THREE.PerspectiveCamera(60, container.clientWidth / container.clientHeight, 0.1, 100);
        heroCamera.position.set(0, 2, 8);
        heroRenderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        heroRenderer.setSize(container.clientWidth, container.clientHeight);
        heroRenderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        container.appendChild(heroRenderer.domElement);

        const geometry = new THREE.BoxGeometry(2.5, 2.5, 2.5);
        const material = new THREE.MeshPhongMaterial({ color: 0x00ff9d, emissive: 0x00ff9d, emissiveIntensity: 0.8, shininess: 100, specular: 0xffffff });
        heroPackage = new THREE.Mesh(geometry, material);
        heroScene.add(heroPackage);

        const edges = new THREE.EdgesGeometry(geometry);
        const lineMat = new THREE.LineBasicMaterial({ color: 0xffffff, linewidth: 3 });
        heroPackage.add(new THREE.LineSegments(edges, lineMat));

        heroScene.add(new THREE.AmbientLight(0x00ff9d, 1.2));
        const dirLight = new THREE.DirectionalLight(0xffffff, 1.5);
        dirLight.position.set(10, 10, 5);
        heroScene.add(dirLight);

        for (let i = 0; i < 8; i++) {
            const sGeo = new THREE.SphereGeometry(0.15, 16, 16);
            const sphere = new THREE.Mesh(sGeo, new THREE.MeshBasicMaterial({ color: 0x00ff9d }));
            sphere.userData = { angle: i * (Math.PI * 2 / 8), radius: 4.5 };
            heroScene.add(sphere);
        }
        animateHero3D();
    }

    function animateHero3D() {
        requestAnimationFrame(animateHero3D);
        if (heroPackage) {
            heroPackage.rotation.y += 0.012;
            heroPackage.rotation.x = Math.sin(Date.now() / 1200) * 0.2;
        }
        heroScene.children.forEach(child => {
            if (child.userData && child.userData.radius) {
                child.userData.angle += 0.018;
                child.position.x = Math.cos(child.userData.angle) * child.userData.radius;
                child.position.z = Math.sin(child.userData.angle) * child.userData.radius;
                child.position.y = Math.sin(child.userData.angle * 1.8) * 1.5;
            }
        });
        heroRenderer.render(heroScene, heroCamera);
    }

    let miniScene, miniRenderer, miniMesh;
    function initMini3D() {
        const container = document.getElementById('mini-3d');
        miniScene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(60, 1, 0.1, 100);
        camera.position.z = 5;
        miniRenderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        miniRenderer.setSize(36, 36);
        container.appendChild(miniRenderer.domElement);

        const geo = new THREE.IcosahedronGeometry(1.6, 0);
        const mat = new THREE.MeshPhongMaterial({ color: 0x00ff9d, emissive: 0x00ff9d, shininess: 100 });
        miniMesh = new THREE.Mesh(geo, mat);
        miniScene.add(miniMesh);

        miniScene.add(new THREE.DirectionalLight(0xffffff, 2));
        miniScene.add(new THREE.AmbientLight(0x00ff9d, 1));

        function animateMini() {
            requestAnimationFrame(animateMini);
            miniMesh.rotation.y += 0.035;
            miniMesh.rotation.x += 0.015;
            miniRenderer.render(miniScene, camera);
        }
        animateMini();
    }

    function toggleMini3D() {
        alert("🚀 3D Globe expanded! (Full interactive map coming soon)");
    }

    let liveDeliveries = [
        {id: "GL-8742", customer: "Rahim Khan", address: "Halishahar, Chittagong", distance: "2.8 km", status: "picked_up", time: "12 min"},
        {id: "GL-8739", customer: "Nadia Islam", address: "Agrabad C/A, Chittagong", distance: "4.1 km", status: "en_route", time: "28 min"},
        {id: "GL-8735", customer: "Karim Uddin", address: "GEC Circle, Chittagong", distance: "1.9 km", status: "delivered", time: "complete"}
    ];

    let availableJobs = [
        { id: "GL-8745", price: "৳420", time: "18 min", pickup: "New Market", drop: "CDA Avenue" },
        { id: "GL-8746", price: "৳310", time: "11 min", pickup: "Bahaddarhat", drop: "Nasirabad" },
        { id: "GL-8747", price: "৳580", time: "35 min", pickup: "Chawkbazar", drop: "Khulshi" }
    ];

    function renderLiveDeliveries() {
        const tbody = document.getElementById('live-deliveries-body');
        tbody.innerHTML = '';
        liveDeliveries.forEach(d => {
            let statusHTML = d.status === 'picked_up' ? `<span class="px-4 py-1 bg-amber-400 text-black text-xs font-bold rounded-3xl">PICKED UP</span>` :
                            d.status === 'en_route' ? `<span class="px-4 py-1 bg-blue-400 text-black text-xs font-bold rounded-3xl">EN ROUTE</span>` :
                            `<span class="px-4 py-1 bg-emerald-400 text-black text-xs font-bold rounded-3xl">DELIVERED</span>`;
            const row = document.createElement('tr');
            row.className = 'hover:bg-white/5';
            row.innerHTML = `
                <td class="px-6 py-5 font-mono">${d.id}</td>
                <td class="px-6 py-5">${d.customer}</td>
                <td class="px-6 py-5">${d.address}</td>
                <td class="px-6 py-5 font-medium">${d.distance}</td>
                <td class="px-6 py-5">${statusHTML}</td>
                <td class="px-6 py-5 text-right">
                    <button onclick="completeDelivery('${d.id}')" class="px-5 py-2 text-xs font-semibold border border-[#00ff9d] text-[#00ff9d] hover:bg-[#00ff9d] hover:text-black rounded-3xl">Mark Complete</button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    function renderAvailableJobs() {
        const container = document.getElementById('available-jobs-grid');
        container.innerHTML = '';
        availableJobs.forEach(job => {
            const card = document.createElement('div');
            card.className = 'bg-white/5 hover:bg-white/10 backdrop-blur-xl rounded-3xl p-6 card-hover cursor-pointer';
            card.innerHTML = `
                <div class="flex justify-between items-start">
                    <div>
                        <div class="font-mono text-[#00ff9d]">${job.id}</div>
                        <div class="text-3xl font-bold mt-3">৳${job.price}</div>
                    </div>
                    <div class="text-right"><span class="px-3 py-1 bg-white/10 text-xs rounded-3xl">${job.time}</span></div>
                </div>
                <div class="mt-8 text-xs flex items-center gap-2">
                    <i class="fa-solid fa-store"></i> ${job.pickup}
                    <i class="fa-solid fa-arrow-right mx-3"></i>
                    <i class="fa-solid fa-house"></i> ${job.drop}
                </div>
                <button onclick="acceptJob('${job.id}');event.stopImmediatePropagation()" class="mt-6 w-full neon-button bg-[#00ff9d] text-black py-4 text-sm font-bold rounded-3xl">ACCEPT THIS JOB</button>
            `;
            container.appendChild(card);
        });
    }

    let earningsChartInstance;
    function createEarningsChart() {
        const ctx = document.getElementById('earnings-chart');
        if (earningsChartInstance) earningsChartInstance.destroy();
        earningsChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Earnings (৳)',
                    data: [1240, 980, 2150, 1840, 3100, 2650, 4200],
                    borderColor: '#00ff9d',
                    backgroundColor: 'rgba(0, 255, 157, 0.15)',
                    borderWidth: 4,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 6
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#888' } },
                    x: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#888' } }
                }
            }
        });
    }

    function switchTab(n) {
        document.querySelectorAll('.tab-button').forEach(el => el.classList.remove('active', 'border-[#00ff9d]'));
        document.getElementById('tab-' + n).classList.add('active', 'border-[#00ff9d]');
        document.querySelectorAll('#content-0, #content-1, #content-2, #content-3').forEach(el => el.classList.add('hidden'));
        document.getElementById('content-' + n).classList.remove('hidden');
        if (n === 2) setTimeout(createEarningsChart, 100);
    }

    function acceptRandomOrder() {
        const newOrder = {
            id: "GL-" + Math.floor(8700 + Math.random() * 100),
            customer: "New Customer",
            address: "Random Road, Chittagong",
            distance: (Math.random() * 5 + 1).toFixed(1) + " km",
            status: "pending",
            time: Math.floor(Math.random() * 30) + " min"
        };
        liveDeliveries.unshift(newOrder);
        renderLiveDeliveries();
        const msg = document.createElement('div');
        msg.style.cssText = 'position:fixed; top:30px; right:30px; background:#00ff9d; color:#000; padding:16px 24px; border-radius:9999px; font-weight:700; box-shadow: 0 0 30px #00ff9d;';
        msg.textContent = '🎉 New order accepted!';
        document.body.appendChild(msg);
        setTimeout(() => msg.remove(), 2800);
    }

    function completeDelivery(id) {
        liveDeliveries = liveDeliveries.filter(d => d.id !== id);
        renderLiveDeliveries();
        alert('✅ Delivery #' + id + ' marked as completed. Earnings updated!');
    }

    function acceptJob(id) {
        alert('🚀 Job ' + id + ' accepted!');
        const job = availableJobs.find(j => j.id === id);
        if (job) {
            liveDeliveries.unshift({id: job.id, customer: "New Pickup", address: job.drop, distance: "—", status: "en_route", time: job.time});
            availableJobs = availableJobs.filter(j => j.id !== id);
            renderLiveDeliveries();
            renderAvailableJobs();
            switchTab(0);
        }
    }

    function startNavigation() {
        alert('🗺️ Opening 3D navigation mode...');
    }

    function showNotifications() {
        document.getElementById('notification-modal').classList.remove('hidden');
        document.getElementById('notification-modal').classList.add('flex');
    }

    function hideNotifications() {
        const modal = document.getElementById('notification-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function navigateTo(page) {
        const title = document.getElementById('page-title');
        if (page === 'my-deliveries') { title.textContent = 'My Deliveries'; switchTab(0); }
        else if (page === 'available') { title.textContent = 'Available Jobs'; switchTab(1); }
        else if (page === 'earnings' || page === 'analytics') { title.textContent = 'Earnings & Analytics'; switchTab(2); }
        else if (page === 'map') { title.textContent = 'Live 3D Map'; alert('🌍 Full 3D map activated (demo)'); }
        else if (page === 'profile') { title.textContent = 'Profile'; alert('👤 Profile editor opened'); }
        else { title.textContent = 'Driver Dashboard'; switchTab(0); }
    }

    function logout() {
        if (confirm('Logout from GlowLink Driver Dashboard?')) {
            window.location.href = 'logout.php';
        }
    }

    // ====================== START DASHBOARD ======================
    window.onload = function() {
        initTailwind();
        initHero3D();
        initMini3D();
        renderLiveDeliveries();
        renderAvailableJobs();

        let battery = 92;
        setInterval(() => {
            battery = Math.max(12, battery - Math.random() * 2);
            document.getElementById('battery').textContent = Math.floor(battery) + '%';
        }, 15000);

        console.log('%c✅ GlowLink Driver Dashboard Loaded Successfully!', 'background:#00ff9d;color:#000;font-size:13px;font-weight:bold;padding:2px 6px;border-radius:3px');
        console.log('Logged in as Driver ID:', <?php echo $currentDriver['id']; ?>);
    };
</script>
</body>
</html>