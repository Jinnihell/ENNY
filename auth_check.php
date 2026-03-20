<?php
// Session Security Enhancements - must be set BEFORE session_start()
// These can also be set in php.ini or .htaccess
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Uncomment when using HTTPS
ini_set('session.use_strict_mode', 1);

session_start(); 

// Regenerate session ID to prevent session fixation
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// Set session timeout (30 minutes)
$session_timeout = 1800; // 30 minutes in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    // Session expired
    session_unset();
    session_destroy();
    session_start();
    session_regenerate_id(true);
    header("Location: login.php?error=session_expired");
    exit();
}
$_SESSION['last_activity'] = time();

// Check if the user ID exists in the session
// Allow certain public pages to be accessed without login
$public_pages = [
    'login.php',
    'signup.php',
    'forgot_password.php',
    'public_monitor.php',
    'fetch_monitor_data.php'
];

$current = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION['user_id'])) {
    // Save intended URL so we can redirect back after successful login
    if (!in_array($current, $public_pages)) {
        // Store the full request URI (path + query)
        $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
    }

    // If not logged in, force them to the login page
    header("Location: login.php?error=please_login");
    exit();
}
?>
