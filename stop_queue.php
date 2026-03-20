<?php
include 'auth_check.php';
include 'db_config.php';

// Authorization check
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') {
    header("Location: login.php?error=unauthorized");
    exit();
}

$window = isset($_GET['window']) ? intval($_GET['window']) : 1;

// Map window to category
$categories = [1 => 'Assessments', 2 => 'Enrollment', 3 => 'Payments', 4 => 'Other Concerns'];
$category = $categories[$window] ?? 'Assessments';

// Use prepared statement to prevent SQL injection
$stmt = $conn->prepare("DELETE FROM queue WHERE status = 'Serving' AND document_type = ?");
$stmt->bind_param("s", $category);
$stmt->execute();
$stmt->close();

// Redirect back to dashboard
header("Location: staff_dashboard.php?window=$window");
exit();
?>
