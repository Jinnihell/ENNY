<?php
// Database Configuration
// IMPORTANT: Change these values for production!

$host = getenv('DB_HOST') ?: "localhost";
$dbname = getenv('DB_NAME') ?: "escr_dqms";
$username = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASS') ?: ""; // CHANGE THIS - Set a strong password!

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Log error internally but show generic message to users
    error_log("Database connection failed: " . $conn->connect_error);
    die("Unable to connect to the database. Please contact the administrator.");
}

// Set charset to avoid issues with special characters
$conn->set_charset("utf8mb4");
?>