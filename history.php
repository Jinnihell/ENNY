<?php 
include 'auth_check.php'; 
include 'db_config.php'; 

// Authorization Check
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') {
    header("Location: login.php?error=unauthorized");
    exit();
}

// Handle reset action
if (isset($_GET['reset']) && $_GET['reset'] === 'true') {
    // Clear all filter parameters by redirecting to clean URL
    header("Location: history.php");
    exit();
}

/**
 * FETCH TRANSACTIONS WITH DATE FILTER - Using Prepared Statements
 */
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$window_filter = isset($_GET['window']) ? $_GET['window'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query with prepared statement parameters
$params = [];
$types = "";
$query = "SELECT * FROM transaction_history WHERE 1=1";

// Date filter
if ($start_date != '' && $end_date != '') {
    $query .= " AND served_at BETWEEN ? AND ?";
    $params[] = $start_date . " 00:00:00";
    $params[] = $end_date . " 23:59:59";
    $types .= "ss";
}

// Window filter
if ($window_filter != '') {
    $query .= " AND window_number = ?";
    $params[] = $window_filter;
    $types .= "s";
}

// Search filter
if ($search != '') {
    $query .= " AND (student_name LIKE ? OR queue_number LIKE ?)";
    $search_param = "%" . $search . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$query .= " ORDER BY served_at DESC";

// Execute with prepared statement if there are parameters
if (count($params) > 0) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $history_res = $stmt->get_result();
} else {
    $history_res = $conn->query($query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> 🔔 Transaction History - ESCR DQMS</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="responsive.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #b7ffd8 0%, #e1f5fe 50%, #90caf9 100%); margin: 0; }
        .logod{ size: 80px; display: block; margin: 20px auto; }
        .sidebar { width: 250px; background-color: #1a2a4d; color: white; height: 100vh; padding: 20px; position: fixed; top: 0; left: 0; display: flex; flex-direction: column; box-sizing: border-box; overflow-y: auto; }
        .sidebar-header { text-align: center; margin-bottom: 30px; }
        .sidebar-header img { width: clamp(40px, 8vw, 50px); margin-bottom: 10px; }
        .sidebar h2 { font-size: clamp(14px, 3vw, 18px); text-align: center; margin: 10px 0; line-height: 1.4; }
        .sidebar .switch-link { color: #a1c4fd; text-decoration: none; font-size: 12px; border-bottom: 1px solid; transition: 0.3s; display: block; margin-top: 10px; text-align: center; }
        .nav-links { list-style: none; padding: 0; margin-top: 30px; margin-bottom: 20px; }
        .nav-links li { margin: 10px 0; }
        .nav-links a { color: white; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 12px; font-size: clamp(14px, 2.5vw, 18px); transition: 0.3s; padding: 12px 15px; border-radius: 8px; text-align: center; }
        .nav-links a:hover { color: #a1c4fd; background: rgba(255,255,255,0.1); }
        .nav-links a.active { color: #a1c4fd; background: rgba(255,255,255,0.1); font-weight: bold; }
        .sidebar-footer { margin-top: auto; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.2); }
        .sidebar-footer a { color: white; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 10px; font-size: clamp(12px, 2vw, 16px); padding: 12px 15px; border-radius: 8px; transition: 0.3s; margin-bottom: 10px; }
        .sidebar-footer a:hover { background: rgba(255,255,255,0.1); }

        .main-content { margin-left: 250px; padding: clamp(15px, 4vw, 40px); width: calc(100% - 250px); box-sizing: border-box; background: linear-gradient(135deg, #b7ffd8 0%, #e1f5fe 50%, #90caf9 100%); min-height: 100vh; overflow-x: hidden; text-align: left; }
        
        /* Filter Section Styling */
        .filter-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 25px; text-align: left; }
        .filter-row { display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        .filter-group label { font-size: 13px; font-weight: bold; color: #555; }
        input[type="date"], .search-input { padding: 10px; border: 1px solid #ddd; border-radius: 5px; outline: none; }
        
        .btn-filter { background: #1a2a4d; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .btn-reset { background: #f8f9fa; color: #333; border: 1px solid #ddd; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-size: 14px; cursor: pointer; font-weight: bold; transition: 0.3s; }
        .btn-reset:hover { background: #e2e6ea; border-color: #bbb; }
        .btn-filter:hover { opacity: 0.9; }

        .history-table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05); overflow-x: auto; max-width: 100%; }
        .history-table { text-align: left; }
        .history-table th { background-color: #1a2a4d; color: white; padding: 15px; text-align: left; white-space: nowrap; }
        .history-table td { padding: 15px; border-bottom: 1px solid #eee; color: #333; white-space: nowrap; }
        
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .badge-assess { background: #e3f2fd; color: #1976d2; }
        .badge-enroll { background: #f1f8e9; color: #388e3c; }
        .badge-pay { background: #fff3e0; color: #f57c00; }

        @media print {
            .sidebar, .filter-card, .btn-print { display: none !important; }
            .main-content { margin-left: 0; padding: 0; width: 100%; }
        }

        /* Responsive Styles */
        @media screen and (max-width: 1024px) {
            .main-content {
                margin-left: 250px;
                width: calc(100% - 250px);
                padding: 20px;
                overflow-x: hidden;
            }
        }

        @media screen and (max-width: 768px) {
            body {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 15px;
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .sidebar > div {
                margin-right: 15px;
            }
            
            .nav-links {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
                margin: 0;
            }
            
            .nav-links li {
                margin: 0;
            }
            
            .nav-links a {
                padding: 8px 12px;
                font-size: 14px;
            }
            
            .sidebar-footer {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
                padding-top: 10px;
                border-top: none;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 15px;
                overflow-x: hidden;
            }
            
            .filter-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .filter-group[style*="margin-left"] {
                margin-left: 0 !important;
            }
            
            .history-table {
                display: block;
                overflow-x: auto;
            }
            
            .history-table th,
            .history-table td {
                white-space: nowrap;
            }
        }

        @media screen and (max-width: 480px) {
            .sidebar {
                padding: 10px;
            }
            
            .sidebar img {
                width: 40px;
            }
            
            .nav-links a {
                font-size: 12px;
                padding: 6px 10px;
            }
            
            .content h1 {
                font-size: 20px;
            }
            
            .history-table th,
            .history-table td {
                padding: 10px 8px;
                font-size: 12px;
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
        <h2>ESCR DQMS<br>Window No: <?php echo isset($_SESSION['active_window']) ? $_SESSION['active_window'] : '1'; ?></h2>
        <a href="window_selection.php" class="switch-link">Switch Window</a>
    </div>
    <ul class="nav-links">
        <li><a href="staff_dashboard.php"><i class="fa fa-user"></i> Dashboard</a></li>
        <li><a href="active_queues.php"><i class="fa fa-history"></i> Active Queues</a></li>
        <li><a href="history.php" class="active"><i class="fa fa-paperclip"></i> History</a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="admin_selection.php"><i class="fa fa-arrow-left"></i> Back to Selection</a>
        <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
    </div>
</div>
<div class="main-content">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h1>🔔Transaction History</h1>
        <div style="display:flex; gap:10px;">
            <button onclick="exportPDF()" class="btn-filter" style="background:#e74c3c;">
                <i class="fa fa-file-pdf"></i> Export PDF
            </button>
            <button onclick="window.print()" class="btn-filter" style="background:#28a745;">
                <i class="fa fa-print"></i> Print
            </button>
        </div>
    </div>

    <div class="filter-card">
        <form method="GET" action="history.php" class="filter-row">
            <div class="filter-group">
                <label>From Date:</label>
                <input type="date" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="filter-group">
                <label>To Date:</label>
                <input type="date" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <div class="filter-group">
                <label>Window:</label>
                <select name="window" style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; outline: none;">
                    <option value="">All Windows</option>
                    <option value="1" <?php echo $window_filter == '1' ? 'selected' : ''; ?>>Window 1 - Assessments</option>
                    <option value="2" <?php echo $window_filter == '2' ? 'selected' : ''; ?>>Window 2 - Enrollment</option>
                    <option value="3" <?php echo $window_filter == '3' ? 'selected' : ''; ?>>Window 3 - Payments</option>
                    <option value="4" <?php echo $window_filter == '4' ? 'selected' : ''; ?>>Window 4 - Other Concerns</option>
                </select>
            </div>
            <button type="submit" class="btn-filter"><i class="fa fa-filter"></i> Filter</button>
            <button type="button" class="btn-reset" onclick="window.location.href='history.php'"><i class="fa fa-refresh"></i> Reset</button>
            
            <div class="filter-group" style="margin-left:auto;">
                <label>Quick Search:</label>
                <input type="text" name="search" class="search-input" placeholder="Name or Ticket..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
        </form>
    </div>

    <table class="history-table" id="historyTable">
        <thead>
            <tr>
                <th>Queue No.</th>
                <th>Student Name</th>
                <th>BLK & Course</th>
                <th>Year</th>
                <th>Transaction Type</th>
                <th>Window</th>
                <th>Date & Time Served</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = mysqli_fetch_assoc($history_res)): 
                $badge_class = 'badge-other';
                if($row['transaction_type'] == 'Assessments') $badge_class = 'badge-assess';
                if($row['transaction_type'] == 'Enrollment') $badge_class = 'badge-enroll';
                if($row['transaction_type'] == 'Payments') $badge_class = 'badge-pay';
            ?>
            <tr>
                <td><strong><?php echo $row['queue_number']; ?></strong></td>
                <td><?php echo htmlspecialchars($row['student_name'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['blk_course'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['year'] ?? ''); ?></td>
                <td><span class="badge <?php echo $badge_class; ?>"><?php echo $row['transaction_type']; ?></span></td>
                <td>Window <?php echo $row['window_number']; ?></td>
                <td><?php echo date('M d, Y | h:i A', strtotime($row['served_at'])); ?></td>
            </tr>
            <?php endwhile; ?>
            
            <?php if(mysqli_num_rows($history_res) == 0): ?>
            <tr><td colspan="7" style="text-align: center; padding: 40px; color: #999;">No transactions found for the selected filters.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    // Live Search Logic (client-side filtering as you type)
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('keyup', function(e) {
            // If Enter is pressed, let the form submit for server-side search
            if (e.key === 'Enter') return;
            
            let filter = this.value.toUpperCase();
            let rows = document.querySelector("#historyTable tbody").rows;
            for (let i = 0; i < rows.length; i++) {
                let text = rows[i].textContent.toUpperCase();
                rows[i].style.display = text.indexOf(filter) > -1 ? "" : "none";
            }
        });
    }
    
    // PDF Export Function
    function exportPDF() {
        const element = document.querySelector('.main-content');
        const opt = {
            margin: 10,
            filename: 'ESCR_History_<?php echo date("Y-m-d"); ?>.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
        };
        html2pdf().set(opt).from(element).save();
    }
</script>

</body>
</html>