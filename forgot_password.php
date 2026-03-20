<?php 
session_start();
include 'db_config.php'; 

$step = 1; 
$msg = "";
$msg_success = "";

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: landing.php");
    exit();
}

if (isset($_POST['find_user'])) {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $msg = "Please enter your email address.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) { 
            $step = 2; 
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_token'] = bin2hex(random_bytes(32)); // Generate secure token
        } else { 
            $msg = "Email address not found in our system."; 
        }
        $stmt->close();
    }
}

if (isset($_POST['reset_pass'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (strlen($new_password) < 8) {
        $msg = "Password must be at least 8 characters.";
        $step = 2;
    } elseif (!preg_match('/[A-Z]/', $new_password)) {
        $msg = "Password must contain at least one uppercase letter.";
        $step = 2;
    } elseif (!preg_match('/[a-z]/', $new_password)) {
        $msg = "Password must contain at least one lowercase letter.";
        $step = 2;
    } elseif (!preg_match('/[0-9]/', $new_password)) {
        $msg = "Password must contain at least one number.";
        $step = 2;
    } elseif ($new_password !== $confirm_password) {
        $msg = "Passwords do not match.";
        $step = 2;
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $email = $_SESSION['reset_email'];
        
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);
        
        if ($stmt->execute()) {
            // Clear session variables
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_token']);
            
            // Redirect to login with success message
            header("Location: login.php?reset=success");
            exit();
        } else {
            $msg = "Failed to update password. Please try again.";
            $step = 2;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - ESCR DQMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="responsive.css">
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, #b7ffd8 0%, #e1f5fe 50%, #90caf9 100%); 
            display: flex; justify-content: center; align-items: center; 
            min-height: 100vh; margin: 0; padding: 20px;
        }
        .card { 
            background: white; padding: clamp(30px, 5vw, 50px) clamp(25px, 4vw, 45px);
            border-radius: clamp(15px, 3vw, 25px); 
            width: min(420px, 95%); max-width: 420px;
            text-align: center; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.12); 
            border-top: 5px solid #1a2a4d; 
            animation: fadeIn 0.5s ease;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .card h2 { color: #1a2a4d; font-size: clamp(20px, 4vw, 24px); margin-bottom: 10px; }
        .card .subtitle { color: #888; font-size: clamp(12px, 2vw, 13px); margin-bottom: clamp(15px, 3vw, 25px); }
        .card .logo { width: clamp(50px, 10vw, 70px); margin-bottom: 15px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1)); }
        input { 
            width: 100%; padding: clamp(10px, 2vw, 14px) clamp(12px, 2.5vw, 16px); margin: 10px 0 20px; 
            border: 2px solid #e0e0e0; border-radius: 12px; 
            box-sizing: border-box; font-size: clamp(14px, 2vw, 15px); 
            background: #fafafa; transition: all 0.3s; outline: none;
        }
        input:focus { border-color: #1a2a4d; background: white; box-shadow: 0 0 0 3px rgba(26,42,77,0.1); }
        button { 
            width: 100%; background: linear-gradient(135deg, #1a2a4d 0%, #2d4a8d 100%); 
            color: white; border: none; padding: clamp(12px, 2.5vw, 15px); border-radius: 12px; 
            cursor: pointer; font-weight: bold; font-size: clamp(14px, 2vw, 16px); 
            transition: all 0.3s; box-shadow: 0 4px 15px rgba(26,42,77,0.3); 
        }
        button:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(40,167,69,0.4); background: linear-gradient(135deg, #28a745 0%, #218838 100%); }
        a { color: #1a2a4d; font-weight: 600; transition: color 0.3s; }
        a:hover { color: #ff8c00; }
        
        /* Responsive for smaller screens */
        @media screen and (max-width: 480px) {
            .card {
                padding: 25px 20px;
            }
            
            .card h2 {
                font-size: 20px;
            }
            
            .card .subtitle {
                font-size: 12px;
            }
            
            input {
                padding: 12px;
                font-size: 14px;
            }
            
            button {
                padding: 12px;
                font-size: 14px;
            }
        }
        
        /* Large screens */
        @media screen and (min-width: 1920px) {
            .card {
                padding: 60px 50px;
            }
            
            .card h2 {
                font-size: 28px;
            }
            
            .card .subtitle {
                font-size: 15px;
            }
            
            input {
                padding: 16px 18px;
                font-size: 16px;
            }
            
            button {
                padding: 18px;
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="card">
        <img src="escr-logo.png" class="logo" alt="ESCR Logo">
        <h2><i class="fa fa-lock"></i> Reset Password</h2>
        <p class="subtitle">Enter your email to reset your password</p>
        <?php if($msg): ?><p style="color:#d9534f; background:#ffeaea; padding:10px; border-radius:8px; border-left:4px solid #d9534f; font-size:13px;"><?php echo $msg; ?></p><?php endif; ?>
        
        <?php if($step == 1): ?>
            <form method="POST">
                <input type="email" name="email" placeholder="Enter your registered email" required>
                <button type="submit" name="find_user"><i class="fa fa-search"></i> Find Account</button>
            </form>
        <?php else: ?>
            <form method="POST">
                <p style="color:#28a745; font-size:13px; margin-bottom:15px;"><i class="fa fa-check-circle"></i> Account found! Enter your new password.</p>
                <input type="password" name="new_password" placeholder="Enter new password (min. 8 characters)" required minlength="8">
                <input type="password" name="confirm_password" placeholder="Confirm new password" required minlength="8">
                <button type="submit" name="reset_pass"><i class="fa fa-save"></i> Update Password</button>
            </form>
        <?php endif; ?>
        <br><a href="login.php" style="color:#5cb85c; text-decoration:none;">Back to Login</a>
    </div>
</body>
</html>