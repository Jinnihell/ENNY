<?php
// Start session to destroy it - no auth check needed to logout
session_start();

// If user is logged in, destroy the session
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php?message=logged_out");
exit();
?>
