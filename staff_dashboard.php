<?php 
include 'auth_check.php'; 
include 'db_config.php'; 

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') {
    header("Location: login.php?error=unauthorized");
    exit();
}

// Handle adding/removing transaction types - now handles toggling multiple types
if (isset($_GET['toggle_type'])) {
    $toggle_type = $_GET['toggle_type'];
    $valid_types = ['Assessments', 'Enrollment', 'Payments', 'Other Concerns'];
    
    if (in_array($toggle_type, $valid_types)) {
        if (!isset($_SESSION['additional_types'])) {
            $_SESSION['additional_types'] = [];
        }
        
        // Toggle: if exists, remove; if not, add
        if (in_array($toggle_type, $_SESSION['additional_types'])) {
            $_SESSION['additional_types'] = array_diff($_SESSION['additional_types'], [$toggle_type]);
        } else {
            $_SESSION['additional_types'][] = $toggle_type;
        }
    }
    
    $window = isset($_SESSION['active_window']) ? $_SESSION['active_window'] : 1;
    header("Location: staff_dashboard.php?window=$window");
    exit();
}

// Legacy handlers kept for backward compatibility but now work as toggles
if (isset($_GET['add_type'])) {
    $add_type = $_GET['add_type'];
    $valid_types = ['Assessments', 'Enrollment', 'Payments', 'Other Concerns'];
    
    if (in_array($add_type, $valid_types)) {
        if (!isset($_SESSION['additional_types'])) {
            $_SESSION['additional_types'] = [];
        }
        // Add to the array if not already there
        if (!in_array($add_type, $_SESSION['additional_types'])) {
            $_SESSION['additional_types'][] = $add_type;
        }
    }
    
    $window = isset($_SESSION['active_window']) ? $_SESSION['active_window'] : 1;
    header("Location: staff_dashboard.php?window=$window");
    exit();
}

if (isset($_GET['remove_type'])) {
    $remove_type = $_GET['remove_type'];
    $valid_types = ['Assessments', 'Enrollment', 'Payments', 'Other Concerns'];
    
    if (in_array($remove_type, $valid_types) && isset($_SESSION['additional_types'])) {
        $_SESSION['additional_types'] = array_diff($_SESSION['additional_types'], [$remove_type]);
    }
    
    $window = isset($_SESSION['active_window']) ? $_SESSION['active_window'] : 1;
    header("Location: staff_dashboard.php?window=$window");
    exit();
}

if (isset($_GET['clear_types'])) {
    unset($_SESSION['additional_types']);
    
    $window = isset($_SESSION['active_window']) ? $_SESSION['active_window'] : 1;
    header("Location: staff_dashboard.php?window=$window");
    exit();
}

if (isset($_GET['window'])) {
    $_SESSION['active_window'] = intval($_GET['window']);
}

$window_id = isset($_SESSION['active_window']) ? $_SESSION['active_window'] : 1;

// Track window activity - mark this window as active
if (!isset($_SESSION['window_activity'])) {
    $_SESSION['window_activity'] = [];
}
$_SESSION['window_activity'][$window_id] = time();

// Clean up old activity (windows not active in the last 30 minutes)
$cutoff_time = time() - 1800;
foreach ($_SESSION['window_activity'] as $win => $last_active) {
    if ($last_active < $cutoff_time) {
        unset($_SESSION['window_activity'][$win]);
    }
}

// Get the default transaction type for this window
$default_type = "";
switch($window_id) {
    case 1: $default_type = "Assessments"; break;
    case 2: $default_type = "Enrollment"; break;
    case 3: $default_type = "Payments"; break;
    case 4: $default_type = "Other Concerns"; break;
    default: $default_type = "Assessments";
}

// Get all active transaction types (default + additional)
$active_types = [$default_type];
if (isset($_SESSION['additional_types']) && is_array($_SESSION['additional_types'])) {
    $active_types = array_merge($active_types, $_SESSION['additional_types']);
}

// For the filter, use the first active type for displaying purposes
$filter = $active_types[0];

// Build the IN clause for active transaction types
$types_placeholders = implode(',', array_fill(0, count($active_types), '?'));

/**
 * DATABASE QUERIES - Using Prepared Statements
 */

// Fetch the student currently being served - from ANY active transaction type
$serving_stmt = $conn->prepare("SELECT id, queue_number, student_name, student_id, blk_course, `year`, document_type 
                  FROM queue 
                  WHERE status = 'Serving' 
                  AND document_type IN ($types_placeholders)
                  LIMIT 1");
$serving_stmt->bind_param(str_repeat('s', count($active_types)), ...$active_types);
$serving_stmt->execute();
$serving_res = $serving_stmt->get_result();

// Debugging check: If this fails, the column name 'year' definitely doesn't exist in DB
if (!$serving_res) {
    die("Database Error: " . mysqli_error($conn));
}

$serving_data = mysqli_fetch_assoc($serving_res);
$serving_now = $serving_data ? $serving_data['queue_number'] : '---';
$current_id = $serving_data ? $serving_data['id'] : null;

// Fetch waiting students count - from ANY active transaction type
$waiting_stmt = $conn->prepare("SELECT COUNT(*) as count FROM queue WHERE status = 'Pending' AND document_type IN ($types_placeholders)");
$waiting_stmt->bind_param(str_repeat('s', count($active_types)), ...$active_types);
$waiting_stmt->execute();
$waiting_res = $waiting_stmt->get_result();
$waiting_data = $waiting_res->fetch_assoc();
$waiting_count = $waiting_data['count'];

// Fetch completed today count
$today = date('Y-m-d');
$completed_stmt = $conn->prepare("SELECT COUNT(*) as count FROM transaction_history WHERE DATE(served_at) = ? AND window_number = ?");
$completed_stmt->bind_param("si", $today, $window_id);
$completed_stmt->execute();
$completed_res = $completed_stmt->get_result();
$completed_data = $completed_res->fetch_assoc();
$completed_count = $completed_data['count'];

// Fetch waiting list - from ANY active transaction type
$waiting_list_stmt = $conn->prepare("SELECT queue_number, student_name, document_type FROM queue WHERE status = 'Pending' AND document_type IN ($types_placeholders) ORDER BY created_at ASC LIMIT 5");
$waiting_list_stmt->bind_param(str_repeat('s', count($active_types)), ...$active_types);
$waiting_list_stmt->execute();
$waiting_list_res = $waiting_list_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESCR DQMS - Window <?php echo $window_id; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="responsive.css">
    <style>
        body { margin: 0; display: flex; height: 100vh; font-family: 'Segoe UI', sans-serif; overflow: hidden; }
        .sidebar { width: 250px; background-color: #1a2a4d; color: white; display: flex; flex-direction: column; padding: 20px; flex-shrink: 0; box-sizing: border-box; overflow-y: auto; height: 100vh; position: sticky; top: 0; }
        .sidebar-header { text-align: center; margin-bottom: 30px; }
        .sidebar-header img { width: clamp(40px, 8vw, 50px); margin-bottom: 10px; }
        .sidebar h2 { font-size: clamp(14px, 3vw, 18px); margin: 10px 0; line-height: 1.4; text-align: center; }
        .sidebar .switch-link { color: #a1c4fd; text-decoration: none; font-size: 12px; border-bottom: 1px solid; transition: 0.3s; display: block; margin-top: 10px; text-align: center; }
        .sidebar .switch-link:hover { color: white; }
        .nav-links { margin-top: 30px; margin-bottom: 20px; list-style: none; padding: 0; }
        .nav-links li { margin: 10px 0; }
        .nav-links a { color: white; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 12px; font-size: clamp(14px, 2.5vw, 18px); transition: 0.3s; padding: 12px 15px; border-radius: 8px; text-align: center; }
        .nav-links a:hover { color: #a1c4fd; transform: translateX(5px); background: rgba(255,255,255,0.1); }
        .sidebar-footer { margin-top: auto; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.2); }
        .sidebar-footer a { color: white; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 10px; font-size: clamp(12px, 2vw, 16px); padding: 12px 15px; border-radius: 8px; transition: 0.3s; margin-bottom: 10px; }
        .sidebar-footer a:hover { background: rgba(255,255,255,0.1); }
        .main-content { flex: 1; background: linear-gradient(135deg, #b7ffd8 0%, #e1f5fe 50%, #90caf9 100%); position: relative; padding: clamp(15px, 4vw, 40px); display: flex; flex-direction: row; align-items: center; justify-content: flex-start; gap: clamp(10px, 3vw, 30px); box-sizing: border-box; overflow-y: auto; height: 100vh; }
        .left-section { flex: 1; display: flex; flex-direction: column; align-items: center; padding: 10px; min-width: 300px; }
        .center-section { flex: 0 0 auto; display: flex; flex-direction: column; align-items: center; justify-content: flex-start; min-width: 0; padding-top: 20px; }
        .right-section { flex: 0.7; display: flex; flex-direction: column; align-items: stretch; justify-content: center; gap: clamp(15px, 3vw, 25px); min-width: 280px; padding-top: 20px; }
        .serving-card { 
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%); 
            width: 100%; 
            max-width: 500px; 
            padding: clamp(20px, 4vw, 40px); 
            border-radius: 24px; 
            text-align: center; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.15); 
            box-sizing: border-box;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .serving-card:hover {
            box-shadow: 0 15px 50px rgba(0,0,0,0.2);
            transform: translateY(-2px);
        }
        
        .serving-card img { 
            width: clamp(40px, 10vw, 80px); 
            height: auto; 
            margin-bottom: 8px; 
            border-radius: 10px; 
            transition: transform 0.3s ease;
        }
        
        .serving-card img:hover {
            transform: scale(1.1) rotate(5deg);
        }
        .queue-box { 
            border: 3px solid #1a2a4d; 
            border-radius: 20px; 
            padding: clamp(15px, 4vw, 30px); 
            margin: 15px 0; 
            font-size: clamp(60px, 18vw, 140px); 
            font-weight: bold; 
            color: #1a2a4d; 
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); 
            line-height: 1.2; 
            text-align: center;
            background: linear-gradient(145deg, #ffffff 0%, #f0f4f8 100%);
        }
        
        .queue-box:hover {
            box-shadow: 0 8px 30px rgba(26, 42, 77, 0.2);
            transform: scale(1.02);
        }
        
        @keyframes queuePulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .queue-box.serving {
            animation: queuePulse 2s ease-in-out infinite;
            border-color: #28a745;
            color: #28a745;
        }
        .student-info { 
            background: linear-gradient(145deg, #f8f9fa 0%, #e9ecef 100%); 
            border-radius: 16px; 
            padding: clamp(8px, 2vw, 15px); 
            margin-bottom: 15px; 
            text-align: left; 
            border-left: 5px solid #1a2a4d; 
            transition: all 0.3s ease;
        }
        
        .student-info:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .student-info p { 
            margin: 6px 0; 
            color: #333; 
            font-size: clamp(12px, 2vw, 16px); 
        }
        .action-btns { display: flex; flex-direction: column; gap: 15px; width: 100%; max-width: 300px; }
        
        /* Right Sidebar - Next in Line */
        .right-sidebar { width: 100%; max-width: 280px; }
        .waiting-list { 
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%); 
            padding: 15px; 
            border-radius: 16px; 
            max-height: clamp(150px, 40vh, 300px); 
            overflow-y: auto; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
            border-left: 6px solid #ff8c00; 
            box-sizing: border-box;
        }
        .waiting-list h4 { 
            margin: 0 0 10px 0; 
            color: #1a2a4d; 
            font-size: clamp(14px, 3vw, 20px); 
            font-weight: bold; 
            border-bottom: 3px solid #ff8c00; 
            padding-bottom: 8px; 
            text-align: center; 
        }
        .waiting-item { 
            background: linear-gradient(135deg, #1a2a4d 0%, #2d4a8d 100%); 
            color: white; 
            padding: clamp(8px, 2vw, 15px) clamp(10px, 2vw, 18px); 
            border-radius: 10px; 
            margin-bottom: 8px; 
            text-align: center; 
            font-size: clamp(24px, 5vw, 36px); 
            font-weight: bold; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .waiting-item:hover {
            transform: scale(1.03) translateX(5px);
            box-shadow: 0 5px 15px rgba(26, 42, 77, 0.3);
        }
        
        .waiting-item:last-child { border-bottom: none; }
        .waiting-item .ticket { color: #ff8c00; font-size: clamp(26px, 6vw, 40px); }
        .waiting-item .name { color: white; }
        
        /* Stats Container */
        .stats-container {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            width: 100%;
            max-width: 280px;
        }
        
        .stat-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            padding: clamp(15px, 3vw, 25px) clamp(20px, 4vw, 35px);
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            min-width: clamp(100px, 25vw, 150px);
            box-sizing: border-box;
            flex: 1;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.5), transparent);
        }
        
        .stat-card:hover {
            transform: translateY(-5px) scale(1.03);
            box-shadow: 0 12px 30px rgba(0,0,0,0.15);
        }
        
        .stat-card.waiting {
            border-top: 4px solid #ff8c00;
        }
        
        .stat-card.waiting:hover {
            border-top-color: #ffa500;
            box-shadow: 0 12px 30px rgba(255, 140, 0, 0.2);
        }
        
        .stat-card.completed {
            border-top: 4px solid #28a745;
        }
        
        .stat-card.completed:hover {
            border-top-color: #20c997;
            box-shadow: 0 12px 30px rgba(40, 167, 69, 0.2);
        }
        
        .stat-number {
            font-size: clamp(28px, 6vw, 42px);
            font-weight: bold;
            color: #1a2a4d;
            margin-bottom: 5px;
            transition: all 0.3s ease;
        }
        
        .stat-card.waiting .stat-number {
            color: #ff8c00;
        }
        
        .stat-card.completed .stat-number {
            color: #28a745;
        }
        
        .stat-label {
            font-size: clamp(9px, 1.5vw, 12px);
            color: #555;
            font-weight: 600;
        }
        
        /* Add count-up animation */
        @keyframes countUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .stat-number {
            animation: countUp 0.5s ease-out;
        }
        
        /* Action Buttons */
        .action-btns { display: flex; flex-direction: column; gap: 15px; width: 100%; max-width: 280px; align-items: stretch; }
        
        .btn {
            padding: clamp(15px, 4vw, 25px) clamp(20px, 5vw, 40px);
            font-size: clamp(14px, 3vw, 20px);
            font-weight: bold;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            color: white;
            min-width: 150px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            box-sizing: border-box;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }
        
        .btn:active {
            transform: translateY(-1px) scale(0.98);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        
        .btn:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(255,255,255,0.5), 0 8px 25px rgba(0,0,0,0.3);
        }
        
        .btn-next {
            background: linear-gradient(135deg, #1a2a4d 0%, #2d4a8d 100%);
        }
        
        .btn-next:hover {
            background: linear-gradient(135deg, #2d4a8d 0%, #3d5a9d 100%);
        }
        
        .btn-ringer {
            background: linear-gradient(135deg, #ff8c00 0%, #ffa500 100%);
        }
        
        .btn-ringer:hover {
            background: linear-gradient(135deg, #ffa500 0%, #ffb733 100%);
        }
        
        .btn-notify {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
        }
        
        .btn-notify:hover {
            background: linear-gradient(135deg, #138496 0%, #117a8b 100%);
        }
        
        .btn-stop {
            background: linear-gradient(135deg, #d9534f 0%, #c9302c 100%);
        }
        
        .btn-stop:hover {
            background: linear-gradient(135deg, #c9302c 0%, #b52b27 100%);
        }

        /* Transaction Type Dropdown */
        .dropdown-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
            max-width: 280px;
        }
        
        .dropdown-btn {
            width: 100%;
            background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
            color: white;
            padding: clamp(12px, 3vw, 18px) clamp(15px, 4vw, 25px);
            font-size: clamp(13px, 2.5vw, 16px);
            font-weight: bold;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(111, 66, 193, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .dropdown-btn:hover {
            background: linear-gradient(135deg, #5a32a3 0%, #4a2890 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(111, 66, 193, 0.4);
        }
        
        .dropdown-btn:active {
            transform: translateY(0);
        }
        
        .dropdown-btn::after {
            content: "▼";
            font-size: 10px;
            transition: transform 0.3s ease;
        }
        
        .dropdown-btn.active::after {
            content: "▲";
            transform: rotate(180deg);
        }
        
        .dropdown-btn.active {
            background: linear-gradient(135deg, #5a32a3 0%, #4a2890 100%);
            box-shadow: 0 6px 20px rgba(111, 66, 193, 0.4);
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            background: white;
            min-width: 100%;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            z-index: 1000;
            margin-top: 8px;
            overflow: hidden;
            animation: dropdownFade 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes dropdownFade {
            from { opacity: 0; transform: translateY(-15px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        
        .dropdown-content.show {
            display: block;
        }
        
        .dropdown-item {
            padding: clamp(10px, 2.5vw, 15px) clamp(12px, 3vw, 18px);
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: clamp(12px, 2vw, 14px);
            color: #333;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .dropdown-item:last-child {
            border-bottom: none;
        }
        
        .dropdown-item:hover {
            background: linear-gradient(90deg, #f8f9fa 0%, #e8f4fd 100%);
            color: #1a2a4d;
            padding-left: clamp(15px, 3.5vw, 21px);
        }
        
        .dropdown-item.current {
            background: linear-gradient(90deg, #e8f4fd 0%, #d0e8fc 100%);
            color: #1a2a4d;
            font-weight: bold;
        }
        
        .dropdown-item i {
            color: #6f42c1;
            transition: transform 0.2s ease;
        }
        
        .dropdown-item:hover i {
            transform: scale(1.2);
        }
        
        .current-type-badge {
            display: inline-block;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            margin-left: 5px;
            animation: badgePop 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes badgePop {
            0% { transform: scale(0); }
            70% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        /* Left section heading */
        .left-section h3 {
            color: #ff8c00; 
            font-size: clamp(16px, 4vw, 24px); 
            margin-bottom: 15px;
            text-align: center;
        }

        .serving-card p {
            font-size: clamp(12px, 2.5vw, 18px);
            font-weight: bold;
            font-style: italic;
            color: #555;
        }

        .serving-card p.transaction-type {
            font-size: clamp(14px, 3vw, 18px);
            font-weight: bold;
            color: #1a2a4d;
            margin: 8px 0;
        }
        
        .serving-card .no-queue {
            color: #d9534f;
            font-weight: bold;
            font-size: clamp(12px, 2vw, 16px);
        }

        /* Responsive: Tablet */
        @media screen and (max-width: 1200px) {
            .main-content {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .left-section {
                flex: 1 1 100%;
                align-items: center;
            }
            
            .serving-card {
                max-width: 500px;
            }
            
            .center-section,
            .right-section {
                flex: 1 1 auto;
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
                gap: 15px;
                padding-top: 10px;
            }
            
            .right-sidebar,
            .stats-container,
            .action-btns {
                flex: 1 1 45%;
                min-width: 250px;
            }
            
            .waiting-list {
                max-height: 200px;
            }
        }

        /* Responsive: Tablet smaller */
        @media screen and (max-width: 900px) {
            .center-section,
            .right-section {
                flex-direction: column;
                width: 100%;
            }
            
            .right-sidebar,
            .stats-container,
            .action-btns {
                width: 100%;
                max-width: 100%;
            }
            
            .queue-box {
                font-size: clamp(40px, 10vw, 70px);
            }
        }

        /* Responsive: Mobile */
        @media screen and (max-width: 768px) {
            body {
                flex-direction: column;
                height: auto;
                min-height: 100vh;
            }
            
            .sidebar {
                width: 100%;
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
                align-items: center;
                padding: 10px;
                gap: 10px;
            }
            
            .sidebar-header {
                margin-bottom: 0;
                margin-right: 15px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .sidebar h2 {
                margin: 0;
                font-size: 14px;
            }
            
            .nav-links {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
                margin: 0;
                justify-content: center;
            }
            
            .nav-links li {
                margin: 0;
            }
            
            .nav-links a {
                padding: 8px 12px;
                font-size: 13px;
                justify-content: center;
            }
            
            .nav-links a i {
                font-size: 14px;
            }
            
            .sidebar-footer {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
                padding-top: 10px;
                border-top: none;
                justify-content: center;
            }
            
            .sidebar-footer a {
                padding: 8px 12px;
                font-size: 12px;
                margin-bottom: 0;
                justify-content: center;
            }
            
            .main-content {
                padding: 15px;
                flex-direction: column;
            }
            
            .left-section h3 {
                text-align: center;
                width: 100%;
            }
            
            .serving-card {
                padding: 20px;
            }
            
            .queue-box {
                font-size: clamp(50px, 20vw, 80px);
                padding: 20px;
            }
            
            .stats-container {
                flex-direction: column;
            }
            
            .stat-card {
                width: 100%;
            }
            
            .action-btns {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
            
            .waiting-list {
                max-height: 250px;
            }
        }

        /* Responsive: Small mobile */
        @media screen and (max-width: 480px) {
            .sidebar {
                padding: 8px;
            }
            
            .sidebar-header img {
                width: 35px;
            }
            
            .sidebar h2 {
                font-size: 14px;
            }
            
            .nav-links a {
                font-size: 12px;
                padding: 6px 10px;
                gap: 8px;
            }
            
            .sidebar-footer a {
                font-size: 11px;
                padding: 6px 10px;
            }
            
            .queue-box {
                font-size: 48px;
                padding: 15px;
            }
            
            .student-info {
                padding: 12px;
            }
            
            .student-info p {
                font-size: 13px;
            }
            
            .btn {
                padding: 15px 20px;
                font-size: 14px;
            }
            
            .waiting-item {
                font-size: 28px;
            }
            
            .waiting-item .ticket {
                font-size: 32px;
            }
        }
        
        /* Fixed Bottom Container for Next in Line - Always visible */
        .next-in-line-fixed {
            display: block;
            position: fixed;
            bottom: 0;
            left: 250px;
            right: 0;
            background: white;
            padding: 15px 20px;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.15);
            z-index: 1000;
            border-top: 4px solid #ff8c00;
        }
        
        .next-in-line-fixed .waiting-list {
            max-height: none;
            background: transparent;
            box-shadow: none;
            border: none;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .next-in-line-fixed .waiting-list h4 {
            margin: 10px;
            padding: 0;
            border: none;
            font-size: 20px;
            min-width: 120px;
            text-align: left;
        }
        
        .next-in-line-fixed .waiting-item {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 20px 30px;
            font-size: 24px;
            margin: 0;
        }
        
        .next-in-line-fixed .waiting-item .ticket {
            font-size: 28px;
        }
        
        .next-in-line-fixed .waiting-item .name {
            font-size: 14px;
            margin-left: 10px;
        }
        
        /* Show fixed bottom container on all screen sizes - replaces center-section */
        .center-section {
            display: none;
        }
        
        /* Adjust main content padding for fixed bottom */
        .main-content {
            padding-bottom: 100px;
        }
        
        /* Responsive adjustments for fixed bottom container */
        @media screen and (max-width: 768px) {
            .next-in-line-fixed {
                left: 0;
            }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <img src="escr-logo.png" alt="Logo">
            <p style="font-size: 12px; margin: 0;">East Systems Colleges of Rizal</p>
            <br>
            <h2>ESCR DQMS<br>Window No: <?php echo $window_id; ?></h2>
            <?php if(isset($_SESSION['active_window_type']) && $_SESSION['active_window_type'] !== $default_type): ?>
            <span style="background: #6f42c1; color: white; padding: 3px 8px; border-radius: 10px; font-size: 10px;">
                <i class="fa fa-star"></i> <?php echo htmlspecialchars($filter); ?>
            </span>
            <?php endif; ?>
            <a href="window_selection.php" class="switch-link">Switch Window</a>
        </div>
        <ul class="nav-links">
            <li><a href="staff_dashboard.php"><i class="fa fa-user"></i> Dashboard</a></li>
            <li><a href="active_queues.php"><i class="fa fa-history"></i> Active Queues</a></li>
            <li><a href="history.php"><i class="fa fa-paperclip"></i> History</a></li>
        </ul>
        <div class="sidebar-footer">
             <a href="admin_selection.php"><i class="fa fa-arrow-left"></i> Back to Selection</a>
            <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <?php if(isset($_SESSION['notification_sent'])): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                <i class="fa fa-check-circle"></i> <?php echo $_SESSION['notification_sent']; unset($_SESSION['notification_sent']); ?>
            </div>
        <?php endif; ?>
        <?php if(isset($_SESSION['notification_error'])): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                <i class="fa fa-exclamation-circle"></i> <?php echo $_SESSION['notification_error']; unset($_SESSION['notification_error']); ?>
            </div>
        <?php endif; ?>
        <div class="left-section">
            <h3>📌Now Serving!</h3>
            
            <div class="serving-card">
                <img src="escr-logo.png" alt="Logo">
                <p>Current Queue Number</p>
                <div class="queue-box"><?php echo $serving_now; ?></div>
                <hr>
                
                <?php if($serving_data): ?>
                    <div class="student-info">
                        <p><strong><i class="fa fa-user-graduate"></i> Name:</strong> <?php echo htmlspecialchars($serving_data['student_name'] ?? ''); ?></p>
                        <p><strong><i class="fa fa-book"></i> Course:</strong> <?php echo htmlspecialchars($serving_data['blk_course'] ?? ''); ?></p>
                        <p><strong><i class="fa fa-calendar"></i> Year:</strong> <?php echo htmlspecialchars($serving_data['year'] ?? ''); ?></p>
                    </div>
                <?php else: ?>
                    <p class="no-queue">No student currently in line.</p>
                <?php endif; ?>

                <p class="transaction-type"><?php echo $filter; ?></p>
            </div>
        </div>

        <div class="center-section">
            <!-- Next in Line -->
            <?php if($waiting_count > 0): ?>
            <div class="right-sidebar">
                <div class="waiting-list">
                    <h4><i class="fa fa-users"></i> Next in Line</h4>
                    <?php while($wait = mysqli_fetch_assoc($waiting_list_res)): ?>
                    <div class="waiting-item">
                        <span class="ticket"><?php echo $wait['queue_number']; ?></span>
                        <span style="font-size: 10px; color: #ff8c00; margin-left: 5px;"><?php echo htmlspecialchars($wait['document_type'] ?? ''); ?></span>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="right-section">
            <!-- Stats Container -->
            <div class="stats-container">
                <div class="stat-card waiting">
                    <div class="stat-number"><?php echo $waiting_count; ?></div>
                    <div class="stat-label">Waiting in Line</div>
                </div>
                <div class="stat-card completed">
                    <div class="stat-number"><?php echo $completed_count; ?></div>
                    <div class="stat-label">Completed Today</div>
                </div>
            </div>

            <?php 
            // Display active windows info
            $window_activity = isset($_SESSION['window_activity']) ? $_SESSION['window_activity'] : [];
            $active_windows = [];
            $type_names = [1 => 'Assessments', 2 => 'Enrollment', 3 => 'Payments', 4 => 'Other Concerns'];
            foreach ($window_activity as $win => $time) {
                if ($time > (time() - 1800)) {
                    $active_windows[] = $win;
                }
            }
            if (!empty($active_windows)):
            ?>
            <div style="background: white; padding: 10px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 15px;">
                <div style="font-size: 11px; color: #28a745; font-weight: bold; margin-bottom: 5px;">
                    <i class="fa fa-desktop"></i> Active Windows: 
                    <?php echo implode(', ', $active_windows); ?>
                </div>
                <div style="font-size: 10px; color: #666;">
                    <?php foreach($active_windows as $aw): ?>
                    <span style="background: #e8f4fd; padding: 2px 6px; border-radius: 4px; margin-right: 3px;">
                        W<?php echo $aw; ?>: <?php echo $type_names[$aw] ?? 'N/A'; ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="action-btns">
                <button class="btn btn-next" onclick="nextQueue()"><i class="fa fa-play"></i> Start/Next</button>
                <button class="btn btn-ringer" onclick="ringBell()"><i class="fa fa-bell"></i> Ringer</button>
                
                <!-- Transaction Type Dropdown - Multi-select -->
                <div class="dropdown-wrapper">
                    <button class="dropdown-btn" id="transactionDropdown" onclick="toggleDropdown()">
                        <i class="fa fa-cog"></i> 
                        <?php 
                        if (count($active_types) === 1) {
                            echo htmlspecialchars($active_types[0]);
                        } else {
                            echo count($active_types) . ' Types Selected';
                        }
                        ?>
                        <?php if(count($active_types) > 1): ?>
                        <span class="current-type-badge"><?php echo count($active_types); ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-content" id="dropdownContent">
                        <div class="dropdown-item" style="background: #e8f4fd; color: #1a2a4d; font-size: 11px; font-weight: bold;">
                            <i class="fa fa-info-circle"></i> TOGGLE TO ADD/REMOVE TYPES
                        </div>
                        <div style="padding: 8px 15px; background: #fff3cd; font-size: 11px; color: #856404; border-bottom: 1px solid #eee;">
                            <i class="fa fa-lightbulb"></i> Select multiple types to serve queues from ALL selected types
                        </div>
                        <?php
                        $all_categories = ['Assessments', 'Enrollment', 'Payments', 'Other Concerns'];
                        foreach ($all_categories as $cat):
                            $isActive = in_array($cat, $active_types);
                            $isDefault = ($cat === $default_type);
                        ?>
                        <div class="dropdown-item <?php echo $isActive ? 'current' : ''; ?>" 
                             onclick="toggleTransactionType('<?php echo urlencode($cat); ?>')">
                            <i class="fa <?php echo $isActive ? 'fa-check-square' : 'fa-square'; ?>"></i>
                            <?php echo htmlspecialchars($cat); ?>
                            <?php if($isActive): ?>
                            <span class="current-type-badge">Active</span>
                            <?php elseif($isDefault): ?>
                            <span class="current-type-badge" style="background: #6f42c1;">Default</span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        <div class="dropdown-item" style="border-top: 2px dashed #ddd; margin-top: 5px; padding-top: 12px;" onclick="clearAllTypes()">
                            <i class="fa fa-times-circle"></i> Clear All (serve default only)
                        </div>
                    </div>
                </div>
                
                <button class="btn btn-stop" onclick="resetQueue()"><i class="fa fa-stop-circle"></i> Stop/Reset</button>
            </div>
        </div>
    </div>

    <!-- Fixed Bottom Container for Next in Line -->
    <?php if($waiting_count > 0): ?>
    <div class="next-in-line-fixed">
        <div class="waiting-list">
            <h4><i class="fa fa-users"></i> Upcoming Queues</h4>
            <?php 
            // Reset the result pointer to get waiting list again
            mysqli_data_seek($waiting_list_res, 0);
            while($wait = mysqli_fetch_assoc($waiting_list_res)): ?>
            <div class="waiting-item">
                <span class="ticket"><?php echo $wait['queue_number']; ?></span>
                <span style="font-size: 10px; color: #ff8c00; margin-left: 5px;"><?php echo htmlspecialchars($wait['document_type'] ?? ''); ?></span>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

<script>
    // Loud and clear bell sound using Web Audio API with harmonics
    let bellAudioContext = null;
    
    function playBellSound() {
        try {
            // Create or get audio context
            if (!bellAudioContext) {
                bellAudioContext = new (window.AudioContext || window.webkitAudioContext)();
            }
            
            // Handle browser audio policy
            if (bellAudioContext.state === 'suspended') {
                bellAudioContext.resume();
            }
            
            const ctx = bellAudioContext;
            const now = ctx.currentTime;
            
            // Play loud bell with harmonics (like a real desk bell)
            playLoudBell(ctx, now);
            
            // Play second ring after 250ms for emphasis
            setTimeout(function() {
                if (bellAudioContext && bellAudioContext.state === 'running') {
                    playLoudBell(ctx, bellAudioContext.currentTime);
                }
            }, 250);
            
        } catch(e) {
            console.log('Bell error:', e.message);
        }
    }
    
    function playLoudBell(ctx, startTime) {
        // Main tone - loud and clear (1000Hz)
        var osc1 = ctx.createOscillator();
        var gain1 = ctx.createGain();
        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(1000, startTime); // Main bell frequency
        gain1.gain.setValueAtTime(0, startTime);
        gain1.gain.linearRampToValueAtTime(0.9, startTime + 0.01); // Loud attack
        gain1.gain.exponentialRampToValueAtTime(0.01, startTime + 1.2); // Long decay
        osc1.connect(gain1);
        gain1.connect(ctx.destination);
        osc1.start(startTime);
        osc1.stop(startTime + 1.2);
        
        // Harmonic overtone (stronger bell sound)
        var osc2 = ctx.createOscillator();
        var gain2 = ctx.createGain();
        osc2.type = 'sine';
        osc2.frequency.setValueAtTime(2000, startTime); // 2nd harmonic
        gain2.gain.setValueAtTime(0, startTime);
        gain2.gain.linearRampToValueAtTime(0.4, startTime + 0.01);
        gain2.gain.exponentialRampToValueAtTime(0.01, startTime + 0.8);
        osc2.connect(gain2);
        gain2.connect(ctx.destination);
        osc2.start(startTime);
        osc2.stop(startTime + 0.8);
        
        // Third harmonic for shimmer
        var osc3 = ctx.createOscillator();
        var gain3 = ctx.createGain();
        osc3.type = 'sine';
        osc3.frequency.setValueAtTime(3500, startTime); // 3rd harmonic
        gain3.gain.setValueAtTime(0, startTime);
        gain3.gain.linearRampToValueAtTime(0.2, startTime + 0.005);
        gain3.gain.exponentialRampToValueAtTime(0.01, startTime + 0.4);
        osc3.connect(gain3);
        gain3.connect(ctx.destination);
        osc3.start(startTime);
        osc3.stop(startTime + 0.4);
    } 

    function nextQueue() {
        const currentId = "<?php echo $current_id; ?>";
        const windowNum = "<?php echo $window_id; ?>";
        const category = "<?php echo urlencode($filter); ?>";
        
        window.location.href = `process_next.php?complete_id=${currentId}&window=${windowNum}&category=${category}`;
    }

    function ringBell() {
        playBellSound();
        const queueBox = document.querySelector('.queue-box');
        queueBox.style.backgroundColor = '#fff3cd';
        setTimeout(() => queueBox.style.backgroundColor = 'transparent', 500);
    }

    function resetQueue() {
        if(confirm("Are you sure you want to stop serving? This will clear the current ticket.")) {
            window.location.href = "stop_queue.php?window=<?php echo $window_id; ?>";
        }
    }
    
    // Transaction Type Dropdown Functions - Multi-select
    function toggleDropdown() {
        const dropdown = document.getElementById('dropdownContent');
        const btn = document.getElementById('transactionDropdown');
        dropdown.classList.toggle('show');
        btn.classList.toggle('active');
    }
    
    // Toggle individual transaction type (add/remove from selection)
    function toggleTransactionType(type) {
        // No confirmation needed - just toggle
        window.location.href = "staff_dashboard.php?window=<?php echo $window_id; ?>&toggle_type=" + type;
    }
    
    // Clear all additional types - serve only default
    function clearAllTypes() {
        if (confirm("Clear all additional transaction types? This window will serve only the default type (<?php echo htmlspecialchars($default_type); ?>).")) {
            window.location.href = "staff_dashboard.php?window=<?php echo $window_id; ?>&clear_types=1";
        }
    }
    
    // Legacy functions kept for backward compatibility
    function changeTransactionType(type) {
        toggleTransactionType(type);
    }
    
    function resetToDefault() {
        clearAllTypes();
    }
    
    // Close dropdown when clicking outside
    window.onclick = function(event) {
        if (!event.target.matches('.dropdown-btn') && !event.target.closest('.dropdown-wrapper')) {
            const dropdowns = document.getElementsByClassName('dropdown-content');
            const btns = document.getElementsByClassName('dropdown-btn');
            for (let i = 0; i < dropdowns.length; i++) {
                dropdowns[i].classList.remove('show');
            }
            for (let i = 0; i < btns.length; i++) {
                btns[i].classList.remove('active');
            }
        }
    }
</script>
</body>
</html>