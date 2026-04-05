<?php
// glowlinkp/public/register.php
require_once '../config/Database.php';
require_once '../classes/User.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $db = $database->getConnection();
    
    $user = new User($db);
    $user->name = $_POST['name'];
    $user->email = $_POST['email'];
    $user->password = $_POST['password'];
    $user->role = $_POST['role'];

    // Check if passwords match
    if ($_POST['password'] !== $_POST['confirm_password']) {
        $message = "<div class='alert alert-error'>Passwords do not match!</div>";
    } elseif ($user->emailExists()) {
        $message = "<div class='alert alert-error'>Email is already registered!</div>";
    } else {
        if ($user->register()) {
            $message = "<div class='alert alert-success'>Registration successful! <a href='login.php' style='color:#27c39f;'>Login here</a></div>";
        } else {
            $message = "<div class='alert alert-error'>Unable to register. Please try again.</div>";
        }
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