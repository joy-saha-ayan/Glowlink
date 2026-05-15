<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'retailer') {
    header("Location: login.php");
    exit;
}

$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, 3308);
$current_user_id = $_SESSION['user_id'] ?? 1;
$alert_message = "";

// Handle Store Settings
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_store'])) {
    $store_name = mysqli_real_escape_string($conn, $_POST['store_name'] ?? '');
    $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
    
    $logo_insert_col = $logo_insert_val = $logo_update_val = "";
    
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_name = time() . '_' . basename($_FILES["logo"]["name"]);
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
            $logo_insert_col = ", logo";
            $logo_insert_val = ", '$target_file'";
            $logo_update_val = ", logo='$target_file'";
        }
    }

    $query = "INSERT INTO retailers (user_id, company_name, email, phone $logo_insert_col)
              VALUES ('$current_user_id', '$store_name', '$email', '$phone' $logo_insert_val)
              ON DUPLICATE KEY UPDATE 
              company_name='$store_name', email='$email', phone='$phone' $logo_update_val";
    
    if (mysqli_query($conn, $query)) {
        $alert_message = "<div class='bg-green-50 border border-green-200 text-green-600 p-4 rounded-xl mb-6 shadow-sm'>✅ Store settings updated successfully!</div>";
    } else {
        $alert_message = "<div class='bg-red-50 border border-red-200 text-red-600 p-4 rounded-xl mb-6 shadow-sm'>❌ Error: " . mysqli_error($conn) . "</div>";
    }
}

// Handle Change Password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($new_pass === $confirm_pass) {
        $result = mysqli_query($conn, "SELECT password_hash FROM users WHERE id = $current_user_id");
        $user = mysqli_fetch_assoc($result);

        if (password_verify($current_pass, $user['password_hash'])) {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE users SET password_hash = '$new_hash' WHERE id = $current_user_id");
            $alert_message = "<div class='bg-green-50 border border-green-200 text-green-600 p-4 rounded-xl mb-6 shadow-sm'>✅ Password changed successfully!</div>";
        } else {
            $alert_message = "<div class='bg-red-50 border border-red-200 text-red-600 p-4 rounded-xl mb-6 shadow-sm'>❌ Current password is incorrect!</div>";
        }
    } else {
        $alert_message = "<div class='bg-red-50 border border-red-200 text-red-600 p-4 rounded-xl mb-6 shadow-sm'>❌ New passwords do not match!</div>";
    }
}

// Fetch Current Data
$result = mysqli_query($conn, "SELECT r.*, u.email as user_email 
                               FROM users u 
                               LEFT JOIN retailers r ON u.id = r.user_id 
                               WHERE u.id = $current_user_id");
$row = mysqli_fetch_assoc($result);

$db_store_name = $row['company_name'] ?? '';
$db_email = !empty($row['email']) ? $row['email'] : ($row['user_email'] ?? '');
$db_phone = $row['phone'] ?? '';
$db_logo = $row['logo'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - GlowLink</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Custom scrollbar for a cleaner look */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
    </style>
</head>
<body class="bg-[#fcf8fa] text-gray-800 font-sans">

    <div class="flex h-screen overflow-hidden">
        
        <div class="w-64 bg-[#23111a] flex flex-col justify-between">
            <div>
                <div class="h-20 flex items-center px-8">
                    <h1 class="text-2xl font-bold tracking-wider text-white">Glow<span class="text-[#f15e87]">Link</span></h1>
                </div>
                <nav class="px-4 py-4 space-y-1 mt-2">
                    <a href="retailer_dashboard.php" class="flex items-center px-4 py-3 text-gray-400 hover:text-white transition rounded-xl">
                        <i class="fas fa-border-all w-6 mr-3"></i> Dashboard
                    </a>
                    <a href="products.php" class="flex items-center px-4 py-3 text-gray-400 hover:text-white transition rounded-xl">
                        <i class="fas fa-box w-6 mr-3"></i> Products
                    </a>
                    <a href="orders.php" class="flex items-center px-4 py-3 text-gray-400 hover:text-white transition rounded-xl">
                        <i class="fas fa-clipboard-list w-6 mr-3"></i> Orders
                    </a>
                    <a href="my_products.php" class="flex items-center px-4 py-3 text-gray-400 hover:text-white transition rounded-xl">
                        <i class="fas fa-tags w-6 mr-3"></i> My Products
                    </a>
                    <a href="admin_analysis.php" class="flex items-center px-4 py-3 text-gray-400 hover:text-white transition rounded-xl">
                        <i class="fas fa-chart-line w-6 mr-3"></i> Analytics
                    </a>
                    <a href="setting.php" class="flex items-center px-4 py-3 bg-[#3a192a] text-[#f15e87] rounded-xl transition">
                        <i class="fas fa-cog w-6 mr-3"></i> Settings
                    </a>
                </nav>
            </div>
            
            <div class="p-4 mb-4">
                <a href="logout.php" class="flex items-center px-4 py-3 text-[#f15e87] bg-[#2d151f] hover:bg-[#3d1c2a] rounded-xl transition">
                    <i class="fas fa-sign-out-alt w-6 mr-3"></i> Logout
                </a>
            </div>
        </div>

        <div class="flex-1 p-10 overflow-y-auto">
            
            <?php if($alert_message) echo $alert_message; ?>

            <h1 class="text-3xl font-semibold mb-8 text-gray-800">Store & Account Settings</h1>

            <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="lg:col-span-2 bg-white rounded-3xl p-8 shadow-sm border border-rose-50">
                    <h2 class="text-lg font-bold mb-8 text-[#f15e87] flex items-center gap-3 border-b border-rose-50 pb-4">
                        <i class="fas fa-store"></i> Store Information
                    </h2>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="update_store" value="1">
                        
                        <div class="space-y-6">
                            <div>
                                <label class="block text-xs font-bold mb-2 text-gray-500 uppercase tracking-wider">Store Name</label>
                                <input type="text" name="store_name" value="<?= htmlspecialchars($db_store_name) ?>" 
                                       class="w-full bg-transparent border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-[#f15e87] focus:ring-1 focus:ring-[#f15e87] text-gray-800 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-bold mb-2 text-gray-500 uppercase tracking-wider">Store Logo</label>
                                <div class="flex items-center gap-6 p-4 border border-dashed border-gray-200 rounded-2xl bg-gray-50/50">
                                    <?php if($db_logo && file_exists($db_logo)): ?>
                                        <img src="<?= $db_logo ?>" class="w-20 h-20 rounded-xl object-cover border border-gray-200 shadow-sm">
                                    <?php else: ?>
                                        <div class="w-20 h-20 bg-gray-100 rounded-xl flex items-center justify-center border border-gray-200">
                                            <i class="fas fa-image text-2xl text-gray-400"></i>
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" name="logo" accept="image/*" class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-rose-50 file:text-[#f15e87] hover:file:bg-rose-100 cursor-pointer transition">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-bold mb-2 text-gray-500 uppercase tracking-wider">Contact Email</label>
                                    <input type="email" name="email" value="<?= htmlspecialchars($db_email) ?>"
                                           class="w-full bg-transparent border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-[#f15e87] focus:ring-1 focus:ring-[#f15e87] text-gray-800 transition">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold mb-2 text-gray-500 uppercase tracking-wider">Phone Number</label>
                                    <input type="tel" name="phone" value="<?= htmlspecialchars($db_phone) ?>"
                                           class="w-full bg-transparent border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-[#f15e87] focus:ring-1 focus:ring-[#f15e87] text-gray-800 transition">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="mt-8 px-8 bg-[#f15e87] text-white py-3 rounded-xl font-medium hover:bg-[#d94f75] transition shadow-md shadow-pink-200">
                            Save Store Settings
                        </button>
                    </form>
                </div>

                <div class="bg-white rounded-3xl p-8 shadow-sm border border-rose-50">
                    <h2 class="text-lg font-bold mb-8 text-[#f15e87] flex items-center gap-3 border-b border-rose-50 pb-4">
                        <i class="fas fa-shield-alt"></i> Security
                    </h2>
                    
                    <form method="POST">
                        <input type="hidden" name="change_password" value="1">
                        <div class="space-y-6">
                            <div>
                                <label class="block text-xs font-bold mb-2 text-gray-500 uppercase tracking-wider">Current Password</label>
                                <input type="password" name="current_password" required
                                       class="w-full bg-transparent border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-[#f15e87] focus:ring-1 focus:ring-[#f15e87] text-gray-800 transition">
                            </div>
                            <div>
                                <label class="block text-xs font-bold mb-2 text-gray-500 uppercase tracking-wider">New Password</label>
                                <input type="password" name="new_password" required
                                       class="w-full bg-transparent border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-[#f15e87] focus:ring-1 focus:ring-[#f15e87] text-gray-800 transition">
                            </div>
                            <div>
                                <label class="block text-xs font-bold mb-2 text-gray-500 uppercase tracking-wider">Confirm New Password</label>
                                <input type="password" name="confirm_password" required
                                       class="w-full bg-transparent border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-[#f15e87] focus:ring-1 focus:ring-[#f15e87] text-gray-800 transition">
                            </div>
                        </div>
                        <button type="submit" class="mt-8 w-full bg-gray-800 text-white py-3 rounded-xl font-medium hover:bg-gray-900 transition shadow-md shadow-gray-200">
                            Change Password
                        </button>
                    </form>
                </div>
            </div>
            
        </div>
    </div>
</body>
</html>