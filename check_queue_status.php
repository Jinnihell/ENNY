<?php
/**
 * Queue Status Checker API
 * Students can poll this to check if their queue number is being served
 * No phone number or SMS required!
 */

include 'db_config.php';

// Get the queue number from request
$queue_number = isset($_GET['queue_number']) ? mysqli_real_escape_string($conn, $_GET['queue_number']) : '';

if (empty($queue_number)) {
    echo json_encode(['status' => 'error', 'message' => 'Queue number required']);
    exit;
}

// Check queue status
$stmt = $conn->prepare("SELECT queue_number, document_type, status, created_at FROM queue WHERE queue_number = ? LIMIT 1");
$stmt->bind_param("s", $queue_number);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $queue_number_db = $row['queue_number'];
    $document_type = $row['document_type'];
    $status = $row['status'];
    $created_at = $row['created_at'];
    
    // Get current serving number
    $serving_stmt = $conn->prepare("SELECT queue_number FROM queue WHERE status = 'Serving' AND document_type = ? LIMIT 1");
    $serving_stmt->bind_param("s", $document_type);
    $serving_stmt->execute();
    $serving_result = $serving_stmt->get_result();
    $serving_row = $serving_result->fetch_assoc();
    $serving_number = $serving_row ? $serving_row['queue_number'] : null;
    $serving_stmt->close();
    
    // Calculate position (how many pending before this one)
    $position_stmt = $conn->prepare("SELECT COUNT(*) as count FROM queue WHERE document_type = ? AND status = 'Pending' AND id < (SELECT id FROM queue WHERE queue_number = ?)");
    $position_stmt->bind_param("ss", $document_type, $queue_number);
    $position_stmt->execute();
    $position_result = $position_stmt->get_result();
    $position_row = $position_result->fetch_assoc();
    $position = $position_row['count'] + 1;
    $position_stmt->close();
    
    // Determine window
    $window_number = 1;
    switch($document_type) {
        case 'Assessments': $window_number = 1; break;
        case 'Enrollment': $window_number = 2; break;
        case 'Payments': $window_number = 3; break;
        case 'Other Concerns': $window_number = 4; break;
    }
    
    // Check if this is the next to be served
    $is_next = ($position == 1);
    
    // Build response
    $response = [
        'status' => 'success',
        'queue_number' => $queue_number_db,
        'document_type' => $document_type,
        'queue_status' => $status,
        'position' => $position,
        'window' => $window_number,
        'is_next' => $is_next,
        'is_serving' => ($status === 'Serving'),
        'serving_number' => $serving_number,
        'created_at' => $created_at
    ];
    
    echo json_encode($response);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Queue number not found']);
}

$stmt->close();
$conn->close();
?>
