<?php 
include 'auth_check.php';
include 'db_config.php';

// Security: Ensure only admin/staff can access
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') {
    header("Location: login.php?error=unauthorized");
    exit();
}

// Clear all PENDING tickets from the queue (keeps history intact)
$sql = "DELETE FROM queue WHERE status = 'Pending'";
mysqli_query($conn, $sql);

// Also clear any "Serving" status tickets
$sql2 = "DELETE FROM queue WHERE status = 'Serving'";
mysqli_query($conn, $sql2);

// RESET THE COUNTERS - This will make queue start from A001, E001, P001, O001 again
// Create table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS queue_counters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) UNIQUE NOT NULL,
    last_number INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Reset all counters to 0
$categories = ['Assessments', 'Enrollment', 'Payments', 'Other Concerns'];
foreach ($categories as $cat) {
    // Check if exists
    $check = $conn->prepare("SELECT id FROM queue_counters WHERE category = ?");
    $check->bind_param("s", $cat);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        // Update to 0
        $update = $conn->prepare("UPDATE queue_counters SET last_number = 0 WHERE category = ?");
        $update->bind_param("s", $cat);
        $update->execute();
        $update->close();
    } else {
        // Insert new with 0
        $insert = $conn->prepare("INSERT INTO queue_counters (category, last_number) VALUES (?, 0)");
        $insert->bind_param("s", $cat);
        $insert->execute();
        $insert->close();
    }
    $check->close();
}

// Redirect back to admin settings with success message
header("Location: admin_settings.php?reset=success");
exit();
?>
