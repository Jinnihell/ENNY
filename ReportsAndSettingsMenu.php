<?php
include 'auth_check.php';
include 'db_config.php';

// Proteksyon para sa ROLE: only admin/staff
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') {
    header("Location: login.php?error=unauthorized");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports & Settings Menu - ESCR DQMS</title>
    <style>
        body { font-family: 'Times New Roman', serif; margin: 0; background: linear-gradient(135deg, #b7ffd8 0%, #e1f5fe 50%, #90caf9 100%); display: flex; flex-direction: column; height: 100vh; }
        .header { display: flex; align-items: center; justify-content: space-between; padding: 15px 30px; background: white; border-bottom: 1px solid #ccc; }
        .logo-box { display: flex; align-items: center; gap: 10px; }
        .logo-box img { width: 45px; }
        .container { text-align: center; margin-top: 100px; flex-grow: 1; }
        .selection-box { display: flex; flex-direction: column; align-items: center; gap: 20px; margin-top: 30px; }
        
        .menu-btn {
            background-color: #1a2a4d;
            color: white;
            width: 350px;
            padding: 25px;
            border-radius: 15px;
            text-decoration: none;
            font-size: 24px;
            transition: 0.3s;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
            text-align: center;
        }

        .menu-btn:hover { background-color: #111b33; transform: scale(1.02); }
        .back-link { margin-top: 30px; display: inline-block; color: #1a2a4d; font-weight: bold; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="header">
        <div class="logo-box">
            <img src="escr-logo.png" alt="Logo">
            <span>East Systems Colleges of Rizal</span>
        </div>
        <div style="background: #1a2a4d; color: white; padding: 8px 20px; border-radius: 20px; font-weight: bold;">Window <?php echo $_SESSION['active_window'] ?? '1'; ?></div>
    </div>

    <div class="container">
        <h2>🛡️Administrative Controls</h2>
        <p>Choose which section you would like to manage:</p>

        <div class="selection-box">
            <a href="admin_reports.php" class="menu-btn">View Reports</a>
            
            <a href="admin_settings.php" class="menu-btn">System Settings</a>
            
            <a href="admin_selection.php" class="back-link">← Back to Main Menu</a>
        </div>
    </div>

</body>
</html>
