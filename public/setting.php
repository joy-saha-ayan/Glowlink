<?php

session_start();
include 'connection.php';

mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, $port);
$alert_message = "";
$current_user_id = $_SESSION['user_id'] ?? 1; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $store_name = mysqli_real_escape_string($conn, $_POST['store_name'] ?? '');
    $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');

    $logo_insert_col = "";
    $logo_insert_val = "";
    $logo_update_val = "";

    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $target_dir = "uploads/";
        if(!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $file_name = time() . '_' . basename($_FILES["logo"]["name"]);
        $target_file = $target_dir . $file_name;
        
        if(move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
            $logo_insert_col = ", logo";
            $logo_insert_val = ", '$target_file'";
            $logo_update_val = ", logo='$target_file'";
        }
    }

    $save_query = "INSERT INTO retailers (user_id, company_name, email, phone $logo_insert_col) 
                   VALUES ('$current_user_id', '$store_name', '$email', '$phone' $logo_insert_val)
                   ON DUPLICATE KEY UPDATE 
                   company_name='$store_name', email='$email', phone='$phone' $logo_update_val";

    if (mysqli_query($conn, $save_query)) {
        $alert_message = "<script>alert('Settings saved successfully!');</script>";
    } else {
        $alert_message = "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
    }
}

$db_store_name = "";
$db_email = "";
$db_phone = "";
$db_logo = "";

$fetch_query = "SELECT r.company_name, r.email as retail_email, r.phone, r.logo, u.email as user_email 
                FROM users u 
                LEFT JOIN retailers r ON u.id = r.user_id 
                WHERE u.id = '$current_user_id'";
                
$result = mysqli_query($conn, $fetch_query);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $db_store_name = $row['company_name'] ?? '';
    $db_phone = $row['phone'] ?? '';
    $db_logo = $row['logo'] ?? '';
    $db_email = !empty($row['retail_email']) ? $row['retail_email'] : ($row['user_email'] ?? '');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings - GlowLink</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-950 text-gray-100">
    
    <?php echo $alert_message; ?>

    <div class="flex h-screen overflow-hidden">
        
        <div class="w-64 bg-gray-900 border-r border-gray-800 flex flex-col justify-between">
            <div>
                <div class="h-20 flex items-center px-8 border-b border-gray-800">
                    <h1 class="text-2xl font-bold text-white tracking-wider">GLOW<span class="text-purple-500">LINK</span></h1>
                </div>
                <nav class="p-4 space-y-2 mt-4">
                    <a href="retailer_dashboard.php" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-800 hover:text-white rounded-xl transition">
                        <i class="fas fa-border-all w-6 mr-3"></i> Dashboard
                    </a>
                    <a href="setting.php" class="flex items-center px-4 py-3 bg-purple-600/10 text-purple-400 rounded-xl transition">
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
        
        <div class="flex-1 p-10 overflow-y-auto">
            
            <a href="javascript:history.back()" class="inline-flex items-center text-gray-400 hover:text-purple-400 transition mb-6">
                <i class="fas fa-arrow-left mr-2"></i> Back
            </a>

            <h1 class="text-3xl font-semibold mb-10">Store & Account Settings</h1>

            <div class="max-w-4xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-8">
                
                <div class="md:col-span-2 bg-gray-900 rounded-3xl p-10 border border-gray-700">
                    <h2 class="text-2xl font-medium mb-8">General Information</h2>
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="space-y-8">
                            <div>
                                <label class="block text-sm mb-2">Store Name</label>
                                <input type="text" name="store_name" value="<?php echo htmlspecialchars($db_store_name); ?>" 
                                       class="w-full bg-gray-800 border border-gray-600 rounded-2xl px-6 py-4 focus:outline-none focus:border-purple-500 transition">
                            </div>
                            
                            <div>
                                <label class="block text-sm mb-2">Store Logo</label>
                                <div class="flex items-center space-x-6">
                                    <?php if(!empty($db_logo) && file_exists($db_logo)): ?>
                                        <img src="<?php echo $db_logo; ?>" alt="Logo" class="w-16 h-16 rounded-xl object-cover border border-gray-600 bg-gray-800">
                                    <?php else: ?>
                                        <div class="w-16 h-16 rounded-xl bg-gray-800 flex items-center justify-center border border-gray-600 text-gray-500">
                                            <i class="fas fa-image text-2xl"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <input type="file" name="logo" accept="image/*" class="block w-full text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-purple-600 file:text-white hover:file:bg-purple-700 transition cursor-pointer">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm mb-2">Contact Email</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($db_email); ?>" 
                                           class="w-full bg-gray-800 border border-gray-600 rounded-2xl px-6 py-4 focus:outline-none focus:border-purple-500 transition">
                                </div>
                                <div>
                                    <label class="block text-sm mb-2">Phone</label>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($db_phone); ?>" 
                                           class="w-full bg-gray-800 border border-gray-600 rounded-2xl px-6 py-4 focus:outline-none focus:border-purple-500 transition">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="mt-10 w-full bg-gradient-to-r from-purple-600 to-violet-600 py-4 rounded-2xl font-semibold hover:brightness-110 transition">
                            Save Store Settings
                        </button>
                    </form>
                </div>

                <div class="bg-gray-900 rounded-3xl p-10 border border-gray-700 h-fit">
                    <h2 class="text-2xl font-medium mb-8">Account Security</h2>
                    <div class="space-y-6">
                        <button onclick="alert('Change Password feature ashbe')" type="button"
                                class="w-full text-left bg-gray-800 hover:bg-gray-700 p-6 rounded-2xl transition flex items-center">
                            <i class="fas fa-lock mr-4 text-purple-400 text-xl"></i> 
                            <div>
                                <span class="block font-medium">Change</span>
                                <span class="block font-medium">Password</span>
                            </div>
                        </button>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</body>
</html>