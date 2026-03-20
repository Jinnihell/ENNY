<?php 
include 'auth_check.php'; // Proteksyon para sa login
include 'db_config.php'; 

// Proteksyon para sa ROLE (Para hindi ma-access ng basta-bastang user ang admin page)
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') {
    header("Location: login.php?error=unauthorized");
    exit();
}


$window_id = $_SESSION['active_window'] ?? 1;
$categories = [1 => 'Assessments', 2 => 'Enrollment', 3 => 'Payments', 4 => 'Other Concerns'];
$current_category = $categories[$window_id];

/**
 * UPDATED QUERY
 * Using prepared statement to prevent SQL injection
 */
$stmt = $conn->prepare("SELECT * FROM queue WHERE document_type = ? AND status = 'Pending' ORDER BY created_at ASC");
$stmt->bind_param("s", $current_category);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Queues - ESCR DQMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="responsive.css">
    <style>
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #b7ffd8 0%, #e1f5fe 50%, #90caf9 100%); }
        .sidebar { width: 250px; background-color: #1a2a4d; color: white; display: flex; flex-direction: column; padding: 20px; box-sizing: border-box; overflow-y: auto; height: 100vh; position: fixed; top: 0; left: 0; }
        .sidebar-header { text-align: center; margin-bottom: 30px; }
        .sidebar-header img { width: clamp(40px, 8vw, 50px); margin-bottom: 10px; }
        .sidebar h2 { font-size: clamp(14px, 3vw, 18px); margin: 10px 0; line-height: 1.4; text-align: center; }
        .sidebar .switch-link { color: #a1c4fd; text-decoration: none; font-size: 12px; border-bottom: 1px solid; transition: 0.3s; display: block; margin-top: 10px; text-align: center; }
        .sidebar .switch-link:hover { color: white; }
        .nav-links { margin-top: 30px; margin-bottom: 20px; list-style: none; padding: 0; }
        .nav-links li { margin: 10px 0; }
        .nav-links a { color: white; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 12px; font-size: clamp(14px, 2.5vw, 18px); transition: 0.3s; padding: 12px 15px; border-radius: 8px; text-align: center; }
        .nav-links a:hover { color: #a1c4fd; transform: translateX(5px); background: rgba(255,255,255,0.1); }
        .active-link { color: #a1c4fd !important; font-weight: bold; background: rgba(255,255,255,0.1); }
        .sidebar-footer { margin-top: auto; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.2); }
        .sidebar-footer a { color: white; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 10px; font-size: clamp(12px, 2vw, 16px); padding: 12px 15px; border-radius: 8px; transition: 0.3s; margin-bottom: 10px; }
        .sidebar-footer a:hover { background: rgba(255,255,255,0.1); }
        .main-content { margin-left: 250px; padding: clamp(15px, 4vw, 40px); width: calc(100% - 250px); box-sizing: border-box; flex: 1; min-height: 100vh; overflow-x: hidden; }
        .content-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
        .content-header h1 { margin: 0; color: #1a2a4d; font-size: clamp(20px, 5vw, 28px); }
        .table-container { background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        thead { background-color: #1a2a4d; color: white; }
        th { padding: clamp(10px, 2vw, 18px); font-weight: 600; text-transform: uppercase; font-size: clamp(10px, 1.5vw, 13px); letter-spacing: 0.5px; }
        td { padding: clamp(8px, 2vw, 16px); border-bottom: 1px solid #eee; color: #444; font-size: clamp(12px, 1.5vw, 14px); }
        tr:hover { background-color: #f9faff; }
        .badge { background: #fff3cd; color: #856404; padding: 6px 12px; border-radius: 20px; font-size: clamp(10px, 1.5vw, 12px); font-weight: bold; }
        .btn-serve { background: #28a745; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: bold; transition: 0.2s; font-size: clamp(11px, 1.5vw, 13px); }
        .btn-serve:hover { background: #218838; transform: translateY(-1px); }
        .search-input { padding: 10px 15px; border: 1px solid #ddd; border-radius: 8px; width: clamp(150px, 30vw, 300px); outline: none; font-size: clamp(12px, 1.5vw, 14px); }
        .search-input:focus { border-color: #1a2a4d; }

        /* Responsive: Tablet */
        @media screen and (max-width: 1024px) {
            .table-container {
                overflow-x: auto;
            }
            
            table {
                min-width: 600px;
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
                height: auto;
                position: relative;
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
                padding: 10px;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px 15px;
            }
            
            .sidebar-header {
                margin-bottom: 10px;
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
            
            .sidebar-footer a {
                padding: 8px 12px;
                font-size: 12px;
                margin-bottom: 0;
            }
            
            .content-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .search-input {
                width: 100%;
            }
            
            table {
                min-width: 500px;
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
            
            .content-header h1 {
                font-size: 20px;
            }
            
            .btn-serve {
                padding: 6px 10px;
                font-size: 11px;
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
            <br>
            <h2>ESCR DQMS<br>Window No: <?php echo $window_id; ?></h2>
            <a href="window_selection.php" class="switch-link">Switch Window</a>
        </div>
        <ul class="nav-links">
            <li><a href="staff_dashboard.php"><i class="fa fa-user"></i> Dashboard</a></li>
            <li><a href="active_queues.php" class="active-link"><i class="fa fa-history"></i> Active Queues</a></li>
            <li><a href="history.php"><i class="fa fa-paperclip"></i> History</a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="admin_selection.php"><i class="fa fa-arrow-left"></i> Back to Selection</a>
            <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="content-header">
            <h1>⌛Active Queues (<?php echo $current_category; ?>)</h1>
            <input type="text" class="search-input" id="searchQueue" placeholder="Search queue number...">
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Queue No.</th>
                        <th>Student Name</th>
                        <th>BLK & Course</th>
                        <th>Year</th>
                        <th>Status</th>
                        <th>Waiting Since</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="queueTable">
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td style="font-weight: bold; color: #1a2a4d;"><?php echo $row['queue_number']; ?></td>
                            <td><?php echo htmlspecialchars($row['student_name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['blk_course'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['year'] ?? ''); ?></td>
                            <td><span class="badge">Waiting</span></td>
                            <td><?php echo date('h:i A', strtotime($row['created_at'])); ?></td>
                            <td>
                                <button class="btn-serve" onclick="location.href='process_next.php?window=<?php echo $window_id; ?>&category=<?php echo urlencode($current_category); ?>'">
                                    Call Next
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center; padding: 50px; color: #999;">No students currently in the queue for this window.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Simple search functionality
        document.getElementById('searchQueue').addEventListener('keyup', function() {
            let filter = this.value.toUpperCase();
            let rows = document.querySelector("#queueTable").rows;
            for (let i = 0; i < rows.length; i++) {
                let firstCol = rows[i].cells[0].textContent.toUpperCase();
                rows[i].style.display = firstCol.indexOf(filter) > -1 ? "" : "none";
            }
        });
    </script>
</body>
</html>