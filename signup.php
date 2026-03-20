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

$message = "";

// Rate limiting for signup - prevent mass account creation
$max_signups = 10;
$signup_timeout = 3600; // 1 hour
if (!isset($_SESSION['signup_attempts'])) {
    $_SESSION['signup_attempts'] = 0;
    $_SESSION['first_signup_time'] = 0;
}

// Reset attempts after timeout
if ($_SESSION['first_signup_time'] > 0 && time() - $_SESSION['first_signup_time'] > $signup_timeout) {
    $_SESSION['signup_attempts'] = 0;
    $_SESSION['first_signup_time'] = 0;
}

if ($_SESSION['signup_attempts'] >= $max_signups) {
    $message = "Too many accounts created. Please try again later.";
} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $message = "Invalid request. Please try again.";
    } else {
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // 1. Basic Validation
    if (strlen($password) < 8) {
        $message = "Password must be at least 8 characters.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $message = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $message = "Password must contain at least one lowercase letter.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $message = "Password must contain at least one number.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // 2. Check for duplicates - Using Prepared Statement
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $check->bind_param("ss", $email, $username);
        $check->execute();
        $check_result = $check->get_result();
        if ($check_result->num_rows > 0) {
            $message = "Username or Email already exists!";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'student')");
            $stmt->bind_param("sss", $username, $email, $hashed_password);
            
            if ($stmt->execute()) {
                // Track successful signups for rate limiting
                if ($_SESSION['first_signup_time'] == 0) {
                    $_SESSION['first_signup_time'] = time();
                }
                $_SESSION['signup_attempts']++;
                
                // Log them in automatically after signup
                $_SESSION['user_id'] = $conn->insert_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'student';
                header("Location: landing.php");
                exit();
            } else {
                $message = "Database error. Please try again.";
            }
            $stmt->close();
        }
        $check->close();
    }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - ESCR digital Queue Management System</title>

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
        .container { 
            background: white; 
            padding: clamp(25px, 5vw, 50px) clamp(20px, 4vw, 45px); 
            border-radius: clamp(15px, 3vw, 25px); 
            box-shadow: 0 20px 60px rgba(0,0,0,0.12); 
            width: min(420px, 95%); max-width: 420px;
            text-align: center; 
            border-top: 5px solid #1a2a4d; 
            animation: fadeIn 0.5s ease;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .logo { width: clamp(50px, 10vw, 70px); margin-bottom: 10px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1)); }
        h2 { margin-bottom: 8px; font-weight: 800; color: #1a2a4d; font-size: clamp(20px, 4vw, 26px); letter-spacing: 1px; }
        .subtitle { color: #888; font-size: clamp(12px, 2vw, 13px); margin-bottom: clamp(15px, 3vw, 25px); }
        .form-group { text-align: left; margin-bottom: 18px; position: relative; }
        label { font-weight: 700; font-size: clamp(12px, 2vw, 14px); display: block; margin-bottom: 8px; color: #1a2a4d; }
        input { 
            width: 100%; padding: clamp(10px, 2vw, 14px) clamp(12px, 2.5vw, 16px); 
            border: 2px solid #e0e0e0; border-radius: 12px; 
            box-sizing: border-box; font-size: clamp(14px, 2vw, 15px); 
            background: #fafafa; transition: all 0.3s; outline: none;
        }
        input:focus { border-color: #1a2a4d; background: white; box-shadow: 0 0 0 3px rgba(26,42,77,0.1); }
        
        .terms-group { display: flex; align-items: center; font-size: clamp(11px, 1.5vw, 13px); margin-bottom: 20px; color: #666; text-align: left; }
        .terms-group input[type="checkbox"] { width: auto; margin-right: 10px; cursor: pointer; accent-color: #1a2a4d; }
        .terms-link { color: #1a2a4d; cursor: pointer; text-decoration: underline; font-weight: 600; }
        .terms-link:hover { color: #ff8c00; }

        .toggle-password { position: absolute; right: 14px; top: 42px; cursor: pointer; color: #999; transition: color 0.3s; }
        .toggle-password:hover { color: #1a2a4d; }
        .btn-submit { 
            width: 100%; 
            background: linear-gradient(135deg, #1a2a4d 0%, #2d4a8d 100%); 
            color: white; padding: clamp(12px, 2.5vw, 15px); border: none; border-radius: 12px; 
            cursor: pointer; font-size: clamp(15px, 2.5vw, 17px); font-weight: bold; 
            transition: all 0.3s; box-shadow: 0 4px 15px rgba(26,42,77,0.3); 
            letter-spacing: 1px;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(40,167,69,0.4); background: linear-gradient(135deg, #28a745 0%, #218838 100%); }
        .btn-submit:disabled { background: #ccc; cursor: not-allowed; box-shadow: none; transform: none; }
        
        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(5px); justify-content: center; align-items: center; padding: 20px; }
        .modal.active { display: flex; }
        .modal-content { background-color: #fff; padding: clamp(20px, 5vw, 35px); border-radius: clamp(15px, 3vw, 20px); width: 100%; max-width: 500px; text-align: left; border-top: 5px solid #1a2a4d; box-shadow: 0 20px 60px rgba(0,0,0,0.2); animation: fadeIn 0.3s ease; }
        .modal-content h3 { color: #1a2a4d; font-size: clamp(18px, 3vw, 22px); }
        .modal-content p { color: #555; line-height: 1.8; font-size: clamp(13px, 2vw, 15px); }
        .close-btn { background: linear-gradient(135deg, #1a2a4d, #2d4a8d); color: white; padding: 10px 25px; border: none; border-radius: 8px; cursor: pointer; margin-top: 15px; float: right; font-weight: bold; transition: all 0.3s; }
        .close-btn:hover { transform: translateY(-2px); }

        .links { margin-top: 25px; font-size: clamp(12px, 2vw, 14px); color: #666; }
        .links a { text-decoration: none; color: #1a2a4d; font-weight: 600; transition: color 0.3s; }
        .links a:hover { color: #ff8c00; }
        .error-msg { 
            color: #d9534f; font-size: clamp(11px, 2vw, 13px); margin-bottom: 15px; 
            background: #ffeaea; padding: 10px 15px; border-radius: 8px; 
            border-left: 4px solid #d9534f;
        }
        
        /* Responsive for smaller screens */
        @media screen and (max-width: 480px) {
            .container {
                padding: 25px 20px;
            }
            
            .form-group {
                margin-bottom: 15px;
            }
            
            .toggle-password {
                top: 38px;
                right: 12px;
            }
            
            .terms-group {
                flex-wrap: wrap;
            }
            
            .close-btn {
                width: 100%;
                float: none;
                text-align: center;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <img src="escr-logo.png" class="logo" alt="ESCR Logo">
        <h2>Create Account</h2>
        <p class="subtitle">Join ESCR Digital Queue Management System</p>
        <?php if($message) echo "<div class='error-msg'>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</div>"; ?>

        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="student@escr.edu.ph" required>
            </div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Student ID or Name" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" id="regPass" placeholder="Min. 8 characters" required>
                <i class="fa-solid fa-eye toggle-password" onclick="togglePass('regPass', this)"></i>
            </div>

            <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">

            <div class="terms-group">
                <input type="checkbox" id="terms" onclick="toggleBtn()">
                <label for="terms">I agree to the <span class="terms-link" onclick="openModal()">Terms & Conditions</span></label>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn" disabled>Sign Up</button>
        </form>

        <div class="links">
            Already have an account? │ <a href="login.php">Log In</a>
        </div>
    </div>

    <div id="termsModal" class="modal">
        <div class="modal-content">
            <h3>🔰Terms and Conditions</h3>
            <p>1. This account is for official ESCR DQMS use only.</p>
            <p>2. Keep your credentials secure.</p>
            <p>3. One account per student only.</p>
            <button class="close-btn" onclick="closeModal()">Close</button>
            <div style="clear:both;"></div>
        </div>
    </div>

    <script>
        function togglePass(id, icon) {
            const input = document.getElementById(id);
            if (input.type === "password") {
                input.type = "text";
                icon.classList.replace("fa-eye", "fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.replace("fa-eye-slash", "fa-eye");
            }
        }

        function toggleBtn() {
            document.getElementById("submitBtn").disabled = !document.getElementById("terms").checked;
        }

        function openModal() { document.getElementById("termsModal").classList.add('active'); }
        function closeModal() { document.getElementById("termsModal").classList.remove('active'); }
        
        // Close modal when clicking outside
        document.getElementById('termsModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
        
        // Close with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
    </script>
</body>
</html>