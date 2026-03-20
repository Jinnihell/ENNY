<?php
/**
 * CSRF Protection Token Generator
 * 
 * Add this to the top of any file that handles form submissions
 * Include this file at the beginning of your form-processing files
 */

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Function to verify CSRF token
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Function to get CSRF token for forms
function getCsrfToken() {
    return $_SESSION['csrf_token'] ?? '';
}
?>
