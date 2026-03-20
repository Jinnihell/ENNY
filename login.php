<?php 
session_start();

// Check if user is already logged in, redirect accordingly
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'staff') {
        header("Location: admin_selection.php");
    } else {
        header("Location: landing.php");
    }
    exit();
}

include 'db_config.php'; 
include 'csrf_protection.php'; 

$error = "";

// Rate limiting check
$max_attempts = 5;
$lockout_time = 300; // 5 minutes
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['lockout_until'] = 0;
}

// Check if user is locked out
if (time() < $_SESSION['lockout_until']) {
    $remaining = $_SESSION['lockout_until'] - time();
    $error = "Too many failed attempts. Please try again in " . ceil($remaining / 60) . " minutes.";
}

// Check for error messages from URL (only if no other error)
if (empty($error) && isset($_GET['error'])) {
    if ($_GET['error'] === 'please_login') {
        $error = "Please login to access this page.";
    } elseif ($_GET['error'] === 'unauthorized') {
        $error = "You are not authorized to access this page.";
    }
}

// Check for success messages from URL
if (isset($_GET['message']) && $_GET['message'] === 'logged_out') {
    $error = "You have been logged out successfully.";
}

if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $error = "Password has been reset. Please login with your new password.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = "Invalid request. Please try again.";
    } else {
    $user_input = trim($_POST['username']);
    $password = $_POST['password'];

    // Search for user by either username or email
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username=? OR email=?");
    $stmt->bind_param("ss", $user_input, $user_input);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // Verify the hashed password
        if (password_verify($password, $user['password'])) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            // Set Session Variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['login_time'] = time();

            // If an intended URL was saved by auth_check, redirect there (internal only)
            if (isset($_SESSION['intended_url']) && !empty($_SESSION['intended_url'])) {
                $target = $_SESSION['intended_url'];
                unset($_SESSION['intended_url']);

                // Prevent open-redirects: only allow internal paths (no scheme/host)
                if (strpos($target, 'http://') === false && strpos($target, 'https://') === false) {
                    header("Location: $target");
                    exit();
                }
            }

            // Role-Based Redirection (fallback)
            if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'staff') {
                header("Location: admin_selection.php");
            } else {
                // Students go to landing page first, then to transaction selection
                header("Location: landing.php");
            }
            exit();
            
        } else {
            $error = "Invalid password.";
            // Increment failed attempts
            $_SESSION['login_attempts']++;
            if ($_SESSION['login_attempts'] >= $max_attempts) {
                $_SESSION['lockout_until'] = time() + $lockout_time;
                $error = "Too many failed attempts. Please try again in 5 minutes.";
            }
        }
    } else {
        $error = "Account not found.";
    }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ESCR DQMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="responsive.css">
    <style>
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, #b7ffd8 0%, #e1f5fe 50%, #90caf9 100%); 
            display: flex; justify-content: center; align-items: center; 
            min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box;
        }
        .container { 
            background: white; 
            padding: clamp(25px, 6vw, 50px) clamp(20px, 5vw, 45px); 
            border-radius: 25px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.12); 
            width: 100%; 
            max-width: 420px; 
            text-align: center; 
            border-top: 5px solid #1a2a4d; 
            animation: fadeIn 0.5s ease;
            box-sizing: border-box;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .logo { width: clamp(60px, 15vw, 90px); margin-bottom: 15px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1)); }
        h2 { margin-bottom: 8px; font-weight: 800; color: #1a2a4d; font-size: clamp(20px, 5vw, 26px); letter-spacing: 1px; }
        .subtitle { color: #888; font-size: clamp(11px, 2vw, 13px); margin-bottom: clamp(20px, 5vw, 30px); }
        .form-group { text-align: left; margin-bottom: 20px; position: relative; }
        label { font-size: clamp(12px, 2vw, 14px); font-weight: 700; color: #1a2a4d; display: block; margin-bottom: 8px; }
        input { 
            width: 100%; padding: 14px 16px; 
            border: 2px solid #e0e0e0; border-radius: 12px; 
            box-sizing: border-box; font-size: clamp(13px, 2vw, 15px); 
            background: #fafafa; transition: all 0.3s; outline: none;
        }
        input:focus { border-color: #1a2a4d; background: white; box-shadow: 0 0 0 3px rgba(26,42,77,0.1); }
        .toggle-password { position: absolute; right: 14px; top: 42px; cursor: pointer; color: #999; transition: color 0.3s; }
        .toggle-password:hover { color: #1a2a4d; }
        .btn-submit { 
            width: 100%; 
            background: linear-gradient(135deg, #1a2a4d 0%, #2d4a8d 100%); 
            color: white; padding: 15px; border: none; border-radius: 12px; 
            cursor: pointer; font-weight: bold; font-size: clamp(14px, 2vw, 17px); 
            margin-top: 15px; transition: all 0.3s; 
            box-shadow: 0 4px 15px rgba(26,42,77,0.3); 
            letter-spacing: 1px;
            box-sizing: border-box;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(40,167,69,0.4); background: linear-gradient(135deg, #28a745 0%, #218838 100%); }
        .btn-submit:active { transform: translateY(0); }
        .links { margin-top: 25px; font-size: clamp(12px, 2vw, 14px); display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
        .links a { text-decoration: none; color: #1a2a4d; font-weight: 600; transition: color 0.3s; }
        .links a:hover { color: #ff8c00; }
        .error-msg { 
            color: #d9534f; font-size: clamp(11px, 2vw, 13px); margin-bottom: 15px; 
            background: #ffeaea; padding: 10px 15px; border-radius: 8px; 
            border-left: 4px solid #d9534f;
        }

        /* Responsive adjustments */
        @media screen and (max-width: 480px) {
            body {
                padding: 15px;
            }
            
            .container {
                padding: 25px 20px;
            }
            
            .links {
                justify-content: center;
            }
        }

        @media screen and (max-height: 500px) and (orientation: landscape) {
            body {
                min-height: auto;
                padding: 10px;
            }
            
            .container {
                padding: 20px 15px;
            }
            
            .logo {
                width: 50px;
                margin-bottom: 10px;
            }
            
            h2 {
                margin-bottom: 5px;
            }
            
            .subtitle {
                margin-bottom: 15px;
            }
            
            .form-group {
                margin-bottom: 12px;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <img src="escr-logo.png" class="logo" alt="ESCR Logo">
        <h2>ESCR DQMS</h2>
        <p class="subtitle">Digital Queue Management System</p>

        <?php if($error): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Email or Username</label>
                <input type="text" name="username" placeholder="Enter your email or username" required>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" id="pass" placeholder="Enter your password" required>
                <i class="fa-solid fa-eye toggle-password" onclick="toggle('pass', this)"></i>
            </div>

            <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">

            <button type="submit" class="btn-submit">Login</button>
        </form>

        <div class="links">
            <a href="forgot_password.php">Forgot Password</a>
            <a href="signup.php">Sign Up</a>
        </div>
    </div>

    <script>
        function toggle(id, el) {
            const x = document.getElementById(id);
            if (x.type === "password") {
                x.type = "text";
                el.classList.replace("fa-eye", "fa-eye-slash");
            } else {
                x.type = "password";
                el.classList.replace("fa-eye-slash", "fa-eye");
            }
        }
    </script>
</body>
</html>
