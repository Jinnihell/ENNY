<?php 
include 'auth_check.php'; // Proteksyon para sa login
include 'db_config.php'; 
include 'csrf_protection.php'; 

// Security: Ensure only logged-in students can generate a ticket
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid request. Please try again.";
        header("Location: transaction_selection.php");
        exit();
    }

    // Validate phone number FIRST before any processing (now optional - removed, using in-app notifications)
    // Phone number field has been removed from form

    // 1. Capture and Sanitize Form Data
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $student_name = mysqli_real_escape_string($conn, $_POST['student_name']);
    $blk_course = mysqli_real_escape_string($conn, $_POST['blk_course']);
    $year = mysqli_real_escape_string($conn, $_POST['year']);
    $user_id = $_SESSION['user_id'];

    // 2. Map Category to Letter Prefix
    $prefixes = [
        'Assessments' => 'A',
        'Enrollment' => 'E',
        'Payments' => 'P',
        'Other Concerns' => 'O'
    ];
    $prefix = $prefixes[$category] ?? 'X';

    // 3. Get or Initialize Counter for this category (persistent, not daily)
    // First, check if counter table exists, if not create it
    $conn->query("CREATE TABLE IF NOT EXISTS queue_counters (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category VARCHAR(50) UNIQUE NOT NULL,
        last_number INT DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Get current counter for this category
    $counter_stmt = $conn->prepare("SELECT last_number FROM queue_counters WHERE category = ?");
    $counter_stmt->bind_param("s", $category);
    $counter_stmt->execute();
    $counter_result = $counter_stmt->get_result();
    
    if ($counter_row = $counter_result->fetch_assoc()) {
        $num = $counter_row['last_number'] + 1;
    } else {
        // First ticket for this category
        $num = 1;
        $insert_counter = $conn->prepare("INSERT INTO queue_counters (category, last_number) VALUES (?, 1)");
        $insert_counter->bind_param("s", $category);
        $insert_counter->execute();
        $insert_counter->close();
    }
    $counter_stmt->close();
    
    // Update the counter
    $update_counter = $conn->prepare("UPDATE queue_counters SET last_number = ? WHERE category = ?");
    $update_counter->bind_param("is", $num, $category);
    $update_counter->execute();
    $update_counter->close();
    
    // Generate queue number with the prefix (e.g., A001, E001, P001, O001)
    $new_ticket = $prefix . str_pad($num, 3, '0', STR_PAD_LEFT);

    // 4. Insert into Database
    $sql = "INSERT INTO queue (user_id, student_name, blk_course, year, queue_number, document_type, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssss", $user_id, $student_name, $blk_course, $year, $new_ticket, $category);
    
if ($stmt->execute()) {
    // Get the position in queue (how many pending tickets before this one for this category)
    $position_stmt = $conn->prepare("SELECT COUNT(*) as count FROM queue WHERE document_type = ? AND status = 'Pending' AND id < (SELECT id FROM queue WHERE queue_number = ?)");
    $position_stmt->bind_param("ss", $category, $new_ticket);
    $position_stmt->execute();
    $position_result = $position_stmt->get_result();
    $position_data = $position_result->fetch_assoc();
    $position = $position_data['count'] + 1; // +1 because current ticket is also pending
    $position_stmt->close();
    
    // Determine which window handles this transaction type
    $window_number = 1;
    switch($category) {
        case 'Assessments': $window_number = 1; break;
        case 'Enrollment': $window_number = 2; break;
        case 'Payments': $window_number = 3; break;
        case 'Other Concerns': $window_number = 4; break;
    }
    
    // Send SMS notification to student (only if phone number provided)
    // Note: SMS functionality removed - using in-app notifications instead
    
    // Redirect back to transaction_selection.php with ticket details
    header("Location: transaction_selection.php?ticket=$new_ticket&name=" . urlencode($student_name) . "&blk_course=" . urlencode($blk_course) . "&category=" . urlencode($category) . "&year=" . urlencode($year) . "&position=$position&window=$window_number");
    exit();
}
}
?>
