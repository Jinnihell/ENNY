<?php 
include 'auth_check.php'; // Proteksyon para sa login
include 'db_config.php'; 

// Proteksyon para sa ROLE (Para hindi ma-access ng basta-bastang user ang admin page)
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') {
    header("Location: login.php?error=unauthorized");
    exit();
}

// Get window
$window = isset($_GET['window']) ? intval($_GET['window']) : 0;

// Get the default transaction type for this window
$default_type = "";
switch($window) {
    case 1: $default_type = "Assessments"; break;
    case 2: $default_type = "Enrollment"; break;
    case 3: $default_type = "Payments"; break;
    case 4: $default_type = "Other Concerns"; break;
    default: $default_type = "Assessments";
}

// Get all active transaction types (default + additional from session)
$active_types = [$default_type];
if (isset($_SESSION['additional_types']) && is_array($_SESSION['additional_types'])) {
    $active_types = array_merge($active_types, $_SESSION['additional_types']);
}

// Clean up old window activity (windows not active in the last 30 minutes)
if (isset($_SESSION['window_activity'])) {
    $cutoff_time = time() - 1800;
    foreach ($_SESSION['window_activity'] as $win => $last_active) {
        if ($last_active < $cutoff_time) {
            unset($_SESSION['window_activity'][$win]);
        }
    }
}

// Function to check if a window is active
function isWindowActive($window_num) {
    return isset($_SESSION['window_activity'][$window_num]) && 
           ($_SESSION['window_activity'][$window_num] > (time() - 1800));
}

// Function to get default window for a transaction type
function getDefaultWindow($type) {
    switch($type) {
        case 'Assessments': return 1;
        case 'Enrollment': return 2;
        case 'Payments': return 3;
        case 'Other Concerns': return 4;
        default: return 1;
    }
}

// Check which default windows are active
$active_default_windows = [];
$type_to_window = [
    'Assessments' => 1,
    'Enrollment' => 2,
    'Payments' => 3,
    'Other Concerns' => 4
];

foreach ($type_to_window as $type => $default_win) {
    if (isWindowActive($default_win)) {
        $active_default_windows[] = $type;
    }
}

// If no default windows are active for certain types, this window can serve those types too
$fallback_types = [];
foreach ($active_types as $type) {
    if (!in_array($type, $active_default_windows)) {
        $fallback_types[] = $type;
    }
}

// Combine: serve from active default windows + fallback types (where default window is inactive)
$serve_types = array_unique(array_merge($active_default_windows, $fallback_types));

// If we have fallback types, it means we're serving as backup for inactive windows
$is_serving_fallback = count($fallback_types) > 0 && count($active_default_windows) > 0;

// Build the IN clause for types we can serve
if (empty($serve_types)) {
    $serve_types = $active_types; // Fallback to all active types if nothing else
}

$types_placeholders = implode(',', array_fill(0, count($serve_types), '?'));

$complete_id = isset($_GET['complete_id']) ? intval($_GET['complete_id']) : null;
$serve_id = isset($_GET['serve_id']) ? intval($_GET['serve_id']) : null;

/**
 * STEP 1: MOVE CURRENT STUDENT TO HISTORY (when complete_id is provided)
 */
if ($complete_id && $complete_id > 0) {
    // Get details before deleting using prepared statement
    $stmt = $conn->prepare("SELECT * FROM queue WHERE id = ?");
    $stmt->bind_param("i", $complete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($data = $result->fetch_assoc()) {
        $name = $data['student_name'];
        $sid = $data['student_id'];
        $type = $data['document_type'];
        $blk_course = $data['blk_course'];
        $year = $data['year'];

        // Insert into history using prepared statement
        $history_stmt = $conn->prepare("INSERT INTO transaction_history (student_name, student_id, blk_course, year, transaction_type, window_number, served_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $history_stmt->bind_param("sssssi", $name, $sid, $blk_course, $year, $type, $window);
        $history_stmt->execute();
        $history_stmt->close();
        
        // Delete from active queue using prepared statement
        $delete_stmt = $conn->prepare("DELETE FROM queue WHERE id = ?");
        $delete_stmt->bind_param("i", $complete_id);
        $delete_stmt->execute();
        $delete_stmt->close();
    }
    $stmt->close();
}

/**
 * STEP 2: CALL THE NEXT STUDENT (when serve_id is provided from active_queues)
 */
if ($serve_id && $serve_id > 0) {
    // Get details before marking as serving using prepared statement
    $stmt = $conn->prepare("SELECT * FROM queue WHERE id = ?");
    $stmt->bind_param("i", $serve_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($data = $result->fetch_assoc()) {
        // Mark as serving using prepared statement
        $update_stmt = $conn->prepare("UPDATE queue SET status = 'Serving' WHERE id = ?");
        $update_stmt->bind_param("i", $serve_id);
        $update_stmt->execute();
        $update_stmt->close();
    }
    $stmt->close();
}

/**
 * STEP 3: AUTO-CALL THE NEXT STUDENT (from pending queue)
 * Now serves from ANY of the active transaction types
 * Also serves as backup when default window is inactive
 */
$next_stmt = $conn->prepare("SELECT id, document_type FROM queue WHERE document_type IN ($types_placeholders) AND status = 'Pending' ORDER BY id ASC LIMIT 1");
$next_stmt->bind_param(str_repeat('s', count($serve_types)), ...$serve_types);
$next_stmt->execute();
$next_result = $next_stmt->get_result();

if ($next_row = $next_result->fetch_assoc()) {
    $next_id = $next_row['id'];
    $mark_stmt = $conn->prepare("UPDATE queue SET status = 'Serving' WHERE id = ?");
    $mark_stmt->bind_param("i", $next_id);
    $mark_stmt->execute();
    $mark_stmt->close();
}
$next_stmt->close();

// Redirect back to dashboard
header("Location: staff_dashboard.php?window=$window");
exit();
?>
