<?php
session_start();
require_once '../config/Database.php';
require_once '../classes/User.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $db = $database->getConnection();
   
    if ($db) {
        $user = new User($db);
        $user->email = trim($_POST['email'] ?? '');
        $user->password = $_POST['password'] ?? '';
        
        try {
            if (!empty($user->email) && !empty($user->password)) {
                if ($user->login()) {
                    // Prevent session fixation
                    session_regenerate_id(true);
                    
                    // Store session data
                    $_SESSION['user_id']   = $user->id;
                    $_SESSION['user_name'] = $user->name;
                    $_SESSION['user_role'] = $user->role;
                    $_SESSION['role']      = $user->role;     // ← Added for dashboard compatibility
                    $_SESSION['logged_in'] = true;

                    // Redirect based on role
                    if ($user->role === 'admin') {
                        header("Location: admin_dashboard.php");
                    } elseif ($user->role === 'retailer') {
                        header("Location: retailer_dashboard.php");
                    } elseif ($user->role === 'driver') {
                        header("Location: driver-dashboard.php");   // ← FIXED
                    } else {
                        header("Location: customer_dashboard.php");
                    }
                    exit;
                } else {
                    $message = "<div style='color: #ef4444; text-align: center; margin-bottom: 15px; font-size: 14px; background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 8px;'>Invalid email or password!</div>";
                }
            } else {
                $message = "<div style='color: #ef4444; text-align: center; margin-bottom: 15px; font-size: 14px; background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 8px;'>Please enter both email and password!</div>";
            }
        } catch (PDOException $e) {
            $message = "<div style='color: #ef4444; text-align: center; margin-bottom: 15px; font-size: 14px; background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 8px;'>DB Error: " . $e->getMessage() . "</div>";
        } catch (Exception $e) {
            $message = "<div style='color: #ef4444; text-align: center; margin-bottom: 15px; font-size: 14px; background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 8px;'>Error: " . $e->getMessage() . "</div>";
        }
    } else {
        $message = "<div style='color: #ef4444; text-align: center; margin-bottom: 15px; font-size: 14px; background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 8px;'>Database connection failed!</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GlowLink - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
   
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: url('/glowlinkp/public/images/bk.jpg') center/cover no-repeat fixed; min-height: 100vh; display: flex; justify-content: center; align-items: center; position: relative; }
        body::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 0; }
        .auth-container { position: relative; z-index: 1; background: linear-gradient(135deg, rgba(255, 255, 255, 0.15), rgba(15, 35, 75, 0.7)); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 15px; padding: 35px 30px; width: 90%; max-width: 400px; box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6); }
        .auth-container h2 { text-align: center; color: #ffffff; font-size: 24px; font-weight: 700; letter-spacing: 1px; margin-bottom: 5px; text-transform: uppercase; }
        .auth-container p.subtitle { text-align: center; color: #d1d5db; font-size: 13px; margin-bottom: 25px; }
        .auth-slider { width: 100%; height: 140px; border-radius: 10px; overflow: hidden; margin-bottom: 20px; border: 2px solid rgba(255, 255, 255, 0.2); position: relative; }
        .auth-slide-track { display: flex; width: 300%; height: 100%; animation: slideAnimation 9s infinite ease-in-out; }
        .auth-slide-track img { width: 33.333%; height: 100%; object-fit: cover; }
        @keyframes slideAnimation { 0%, 25% { transform: translateX(0%); } 33%, 58% { transform: translateX(-33.333%); } 66%, 91% { transform: translateX(-66.666%); } 100% { transform: translateX(0%); } }
        .input-group { margin-bottom: 15px; width: 100%; }
        .input-with-icon { position: relative; width: 100%; }
        .input-with-icon i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #93c5fd; font-size: 14px; }
        .input-with-icon input { width: 100%; padding: 14px 15px 14px 40px; background: rgba(0, 0, 0, 0.4); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; font-size: 14px; color: #ffffff; outline: none; transition: 0.3s; }
        .input-with-icon input::placeholder { color: #9ca3af; }
        .input-with-icon input:focus { border-color: #3b82f6; background: rgba(0, 0, 0, 0.6); box-shadow: 0 0 10px rgba(59, 130, 246, 0.3); }
        .forgot-pass { display: block; text-align: right; margin-top: -5px; margin-bottom: 25px; font-size: 12px; color: #93c5fd; text-decoration: none; transition: 0.3s; }
        .forgot-pass:hover { color: #60a5fa; text-decoration: underline; }
        .btn-wrapper { display: flex; justify-content: center; margin-bottom: 20px; width: 100%; overflow: visible; }
        button.sci-fi-btn { position: relative; width: 180px; height: 45px; background-color: transparent; border: none; font-size: 14px; font-weight: 700; color: #ffffff; cursor: pointer; text-transform: uppercase; letter-spacing: 1px; display: flex; justify-content: center; align-items: center; }
        .btn-text { position: relative; z-index: 10; }
        #clip { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 2px solid #3b82f6; box-shadow: inset 0px 0px 10px rgba(59, 130, 246, 0.4); clip-path: polygon(15% 0%, 85% 0%, 100% 30%, 100% 70%, 85% 100%, 15% 100%, 0% 70%, 0% 30%); -webkit-clip-path: polygon(15% 0%, 85% 0%, 100% 30%, 100% 70%, 85% 100%, 15% 100%, 0% 70%, 0% 30%); z-index: 1; transition: 0.3s; background: linear-gradient(90deg, rgba(30, 58, 138, 0.8), rgba(15, 23, 42, 0.8)); }
        .arrow { position: absolute; background-color: #3b82f6; top: 35%; width: 15px; height: 30%; z-index: 5; transition: 0.3s; }
        #leftArrow { left: -15px; clip-path: polygon(100% 0, 100% 100%, 0 50%); -webkit-clip-path: polygon(100% 0, 100% 100%, 0 50%); }
        #rightArrow { right: -15px; clip-path: polygon(100% 50%, 0 0, 0 100%); -webkit-clip-path: polygon(100% 50%, 0 0, 0 100%); }
        button.sci-fi-btn:hover #clip { box-shadow: inset 0px 0px 20px rgba(59, 130, 246, 0.8); background: linear-gradient(90deg, #2563eb, #1e3a8a); }
        button.sci-fi-btn:hover #leftArrow { left: -20px; background-color: #60a5fa; }
        button.sci-fi-btn:hover #rightArrow { right: -20px; background-color: #60a5fa; }
        .switch-link { display: block; text-align: center; font-size: 13px; color: #e2e8f0; text-decoration: none; transition: 0.3s; }
        .switch-link:hover { color: #ffffff; }
        .switch-link span { color: #60a5fa; font-weight: 600; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="auth-container">
        <h2>WELCOME BACK</h2>
        <p class="subtitle">Login to your GlowLink account</p>
        <div class="auth-slider">
            <div class="auth-slide-track">
                <img src="/glowlinkp/public/images/bg1.jpg" alt="Slide 1">
                <img src="/glowlinkp/public/images/bg2.jpg" alt="Slide 2">
                <img src="/glowlinkp/public/images/bg3.jpg" alt="Slide 3">
            </div>
        </div>
        <?php echo $message; ?>
        <form action="login.php" method="POST">
            <div class="input-group">
                <div class="input-with-icon">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email Address" required>
                </div>
            </div>
            <div class="input-group">
                <div class="input-with-icon">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
            </div>
            <a href="#" class="forgot-pass">Forgot Password?</a>
            <div class="btn-wrapper">
                <button type="submit" class="sci-fi-btn">
                    <div id="clip"></div>
                    <span class="btn-text">LOGIN NOW</span>
                    <span id="rightArrow" class="arrow"></span>
                    <span id="leftArrow" class="arrow"></span>
                </button>
            </div>
        </form>
        <a href="register.php" class="switch-link">Don't have an account? <span>Register</span></a>
    </div>
</body>
</html>