<?php 
include 'db_config.php'; 

// Set proper headers
header('Content-Type: application/json');

// Check database connection
if (!$conn || $conn->connect_error) {
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

$windows = [
    1 => "Assessments",
    2 => "Enrollment",
    3 => "Payments",
    4 => "Other Concerns"
];

$monitor_data = [];

foreach ($windows as $num => $cat) {
    // Get currently serving - Using Prepared Statement
    $stmt = $conn->prepare("SELECT queue_number FROM queue WHERE status = 'Serving' AND document_type = ? LIMIT 1");
    
    if (!$stmt) {
        echo json_encode(["error" => "Prepare failed: " . $conn->error]);
        exit;
    }
    
    $stmt->bind_param("s", $cat);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res->fetch_assoc();
    $stmt->close();
    
    // Get waiting queue numbers (next 3) - Using Prepared Statement
    $wait_stmt = $conn->prepare("SELECT queue_number FROM queue WHERE status = 'Pending' AND document_type = ? ORDER BY id ASC LIMIT 3");
    
    if (!$wait_stmt) {
        echo json_encode(["error" => "Prepare failed: " . $conn->error]);
        exit;
    }
    
    $wait_stmt->bind_param("s", $cat);
    $wait_stmt->execute();
    $waiting_res = $wait_stmt->get_result();
    $waiting_numbers = [];
    while ($w = $waiting_res->fetch_assoc()) {
        $waiting_numbers[] = $w['queue_number'];
    }
    $wait_stmt->close();
    
    $monitor_data[] = [
        "window" => $num,
        "category" => $cat,
        "number" => $data ? $data['queue_number'] : "---",
        "waiting" => $waiting_numbers
    ];
}

echo json_encode($monitor_data);
?>