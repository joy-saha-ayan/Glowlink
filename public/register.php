<?php
// glowlinkp/public/register.php
session_start();
require_once '../config/Database.php';
require_once '../classes/User.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        $user = new User($db);
        $user->name = trim($_POST['name'] ?? '');
        $user->email = trim($_POST['email'] ?? '');
        $user->password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Securely map user role, denying forced 'admin' through frontend
        $allowed_roles = ['customer', 'retailer', 'driver'];
        $posted_role = $_POST['role'] ?? 'customer';
        $user->role = in_array($posted_role, $allowed_roles) ? $posted_role : 'customer';

        try {
            if (empty($user->name) || empty($user->email) || empty($user->password) || empty($confirm_password)) {
                $message = "<div style='color: #ef4444; text-align: center; margin-bottom: 15px; font-size: 14px; background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 8px;'>All fields are required!</div>";
            } elseif (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                $message = "<div style='color: #ef4444; text-align: center; margin-bottom: 15px; font-size: 14px; background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 8px;'>Invalid email format!</div>";
            } elseif ($user->password !== $confirm_password) {
                $message = "<div style='color: #ef4444; text-align: center; margin-bottom: 15px; font-size: 14px; background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 8px;'>Passwords do not match!</div>";
            } elseif ($user->emailExists()) {
                $message = "<div style='color: #ef4444; text-align: center; margin-bottom: 15px; font-size: 14px; background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 8px;'>Email is already registered!</div>";
            } else {
                if ($user->register()) {
                    $message = "<div style='color: #10b981; text-align: center; margin-bottom: 15px; font-size: 14px; background: rgba(16, 185, 129, 0.1); padding: 10px; border-radius: 8px;'>Registration successful! <a href='login.php' style='color:#10b981; font-weight: bold; text-decoration: underline;'>Login here</a></div>";
                } else {
                    $message = "<div style='color: #ef4444; text-align: center; margin-bottom: 15px; font-size: 14px; background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 8px;'>Unable to register. Please try again.</div>";
                }
            }
        } catch (PDOException $e) {
            $message = "<div style='color: #ef4444; text-align: center; margin-bottom: 15px; font-size: 14px; background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 8px;'>DB Error: " . $e->getMessage() . "</div>";
        } catch (Exception $e) {
            $message = "<div style='color: #ef4444; text-align: center; margin-bottom: 15px; font-size: 14px; background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 8px;'>Error: " . $e->getMessage() . "</div>";
        }
    } else {
        $message = "<div style='color: #ef4444; text-align: center; margin-bottom: 15px; font-size: 14px; background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 8px;'>Database connection failed.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GlowLink - Register</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <div class="auth-container">
        <h2>Create an Account</h2>
        
        <div class="auth-slider">
            <div class="auth-slide-track">
                <img src="https://images.unsplash.com/photo-1616683693504-3ea7e9ad6fec?auto=format&fit=crop&w=600&q=80" alt="GlowLink 1">
                <img src="https://images.unsplash.com/photo-1617897903246-719242758050?auto=format&fit=crop&w=600&q=80" alt="GlowLink 2">
                <img src="https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?auto=format&fit=crop&w=600&q=80" alt="GlowLink 3">
            </div>
        </div>

        <p class="subtitle">Join GlowLink and start your premium skincare journey!</p>
        
        <?php echo $message; ?>
        
        <form action="register.php" method="POST">
            
            <div class="input-group">
                <label>Full Name</label>
                <div class="input-with-icon">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" name="name" required placeholder="John Doe">
                </div>
            </div>

            <div class="input-group">
                <label>Email Address</label>
                <div class="input-with-icon">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="email" required placeholder="you@example.com">
                </div>
            </div>

            <div class="input-group">
                <label>Register As</label>
                <div class="input-with-icon">
                    <i class="fa-solid fa-id-badge"></i>
                    <select name="role" required>
                        <option value="customer">Customer (Buy Products)</option>
                        <option value="retailer">Retailer (Sell Products)</option>
                        <option value="driver">Driver (Delivery)</option>
                    </select>
                </div>
            </div>

            <div class="input-group">
                <label>Password</label>
                <div class="input-with-icon">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" required placeholder="Create a strong password">
                </div>
            </div>

            <div class="input-group">
                <label>Confirm Password</label>
                <div class="input-with-icon">
                    <i class="fa-solid fa-check-circle"></i>
                    <input type="password" name="confirm_password" required placeholder="Repeat your password">
                </div>
            </div>

            <div class="btn-wrapper">
                <button type="submit" class="sci-fi-btn">
                    <span class="btn-text">SIGN UP NOW</span>
                    <div id="clip">
                        <div id="leftTop" class="corner"></div>
                        <div id="rightBottom" class="corner"></div>
                        <div id="rightTop" class="corner"></div>
                        <div id="leftBottom" class="corner"></div>
                    </div>
                    <span id="rightArrow" class="arrow"></span>
                    <span id="leftArrow" class="arrow"></span>
                </button>
            </div>

        </form>

        <a href="login.php" class="switch-link">Already have an account? Login</a>
    </div>

</body>
</html>