<?php 
include 'auth_check.php'; // Proteksyon para sa login
include 'db_config.php'; 

// Proteksyon para sa ROLE (Para hindi ma-access ng basta-bastang user ang admin page)
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') {
    header("Location: login.php?error=unauthorized");
    exit();
}

// Check for reset success message
$reset_success = '';
if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $reset_success = 'Queue has been reset successfully!';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - ESCR DQMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="responsive.css">
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #b7ffd8 0%, #e1f5fe 50%, #90caf9 100%);
            --navy: #1a2a4d;
            --text-color: #333;
        }
        
        .header { display: flex; align-items: center; justify-content: space-between; background: white; padding: clamp(10px, 2vw, 15px) clamp(20px, 4vw, 30px); border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); flex-wrap: wrap; gap: 15px; }
        .logo-box { display: flex; align-items: center; gap: 15px; }
        .logo-box img { width: clamp(35px, 8vw, 50px); }
        .logo-box h2 { margin: 0; color: #1a2a4d; font-size: clamp(16px, 3vw, 20px); }
        .logo-box span { font-size: clamp(10px, 2vw, 12px); color: #666; }
        .window-badge { background: #1a2a4d; color: white; padding: 8px 20px; border-radius: 20px; font-weight: bold; font-size: clamp(12px, 2vw, 14px); }

        /* Dark Mode Class */
        body.dark-mode {
            --bg-gradient: linear-gradient(135deg, #121212 0%, #1a2a4d 100%);
            --text-color: #f4f4f4;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: var(--bg-gradient);
            color: var(--text-color);
            margin: 0;
            padding: clamp(15px, 5vw, 50px);
            transition: 0.3s;
        }

        .settings-container {
            max-width: 900px;
            margin: auto;
        }

        h1 { display: flex; align-items: center; gap: 15px; font-size: clamp(22px, 5vw, 32px); flex-wrap: wrap; }

        /* Accordion Styling */
        .setting-item {
            border-bottom: 2px solid rgba(0,0,0,0.1);
            margin-bottom: 10px;
        }

        .setting-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: clamp(15px, 4vw, 25px) 10px;
            cursor: pointer;
            transition: 0.2s;
            flex-wrap: wrap;
            gap: 10px;
        }

        .setting-header:hover { background: rgba(255,255,255,0.1); }

        .setting-header h2 { margin: 0; font-size: clamp(18px, 3vw, 22px); font-weight: 600; }

        .setting-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
        }

        .content-inner { padding: clamp(15px, 3vw, 20px); }

        /* Form Elements */
        select, button.action-btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: clamp(14px, 2vw, 16px);
        }

        .danger-zone {
            margin-top: 50px;
            padding: 20px;
            border: 2px dashed #ff4444;
            border-radius: 15px;
            background: rgba(255, 68, 68, 0.1);
        }

        .btn-reset {
            background: #ff4444;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }

        .back-btn {
            text-decoration: none;
            color: var(--navy);
            font-weight: bold;
            display: inline-block;
            margin-top: 30px;
        }
   

        /* Responsive Styles */
        @media screen and (max-width: 768px) {
            body {
                padding: 20px 15px;
            }
            
            .header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .logo-box {
                flex-direction: column;
            }
            
            .settings-container {
                padding: 0 10px;
            }
            
            h1 {
                font-size: 24px;
                flex-direction: column;
                text-align: center;
            }
            
            .setting-header {
                padding: 15px 5px;
            }
            
            .setting-header h2 {
                font-size: 16px;
            }
            
            .content-inner {
                padding: 15px;
            }
            
            select, button.action-btn {
                width: 100%;
                margin-bottom: 10px;
            }
            
            .danger-zone {
                margin-top: 30px;
                padding: 15px;
            }
            
            .btn-reset {
                width: 100%;
            }
        }

        @media screen and (max-width: 480px) {
            body {
                padding: 15px 10px;
            }
            
            h1 {
                font-size: 20px;
            }
            
            .logo-box img {
                width: 40px;
            }
            
            .logo-box h2 {
                font-size: 16px;
            }
            
            .window-badge {
                font-size: 12px;
                padding: 6px 15px;
            }
        }
    </style>
</head>
<body id="mainBody">
    <div class="header">
        <div class="logo-box">
            <img src="escr-logo.png" alt="Logo">
            <div>
                <h2>ESCR DQMS</h2>
                <span>East Systems Colleges of Rizal</span>
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:15px;">
            <a href="staff_dashboard.php" style="text-decoration:none; color:#1a2a4d; font-weight:bold; font-size:14px;"><i class="fa fa-home"></i> Dashboard</a>
            <a href="admin_reports.php" style="text-decoration:none; color:#1a2a4d; font-weight:bold; font-size:14px;"><i class="fa fa-chart-line"></i> Reports</a>
            <div class="window-badge">Window <?php echo $_SESSION['active_window'] ?? '1'; ?></div>
        </div>
    </div>

    <div class="settings-container">
        <h1><i class="fa fa-cog"></i> System Settings</h1>
        
        <?php if($reset_success): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
            <i class="fa fa-check-circle"></i> <?php echo $reset_success; ?>
        </div>
        <?php endif; ?>
        
        <hr style="border: 1px solid rgba(0,0,0,0.1); margin-bottom: 30px;">

        <div class="setting-item">
            <div class="setting-header" onclick="toggleAccordion(this)">
                <h2><i class="fa fa-language"></i> Language</h2>
                <i class="fa fa-chevron-down"></i>
            </div>
            <div class="setting-content">
                <div class="content-inner">
                    <p>Select the primary language for the Kiosk Interface:</p>
                    <select>
                        <option>English (US)</option>
                        <option>Filipino (Tagalog)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="setting-item">
            <div class="setting-header" onclick="toggleAccordion(this)">
                <h2><i class="fa fa-palette"></i> Theme / Color Display</h2>
                <i class="fa fa-chevron-down"></i>
            </div>
            <div class="setting-content">
                <div class="content-inner">
                    <p>Toggle between Light and Dark mode for the dashboard:</p>
                    <button class="action-btn" onclick="toggleDarkMode()">Toggle Dark/Light Mode</button>
                </div>
            </div>
        </div>

        <div class="setting-item">
            <div class="setting-header" onclick="toggleAccordion(this)">
                <h2><i class="fa fa-bell"></i> Feedback and Notifications Alert</h2>
                <i class="fa fa-chevron-down"></i>
            </div>
            <div class="setting-content">
                <div class="content-inner">
                    <label><input type="checkbox" checked> Enable "Ding" sound for new tickets</label><br><br>
                    <label><input type="checkbox" checked> Show desktop pop-up notifications</label>
                </div>
            </div>
        </div>
        <div class="setting-item">
    <div class="setting-header" onclick="toggleAccordion(this)">
        <h2><i class="fa fa-database"></i> Database Backup & Recovery</h2>
        <i class="fa fa-chevron-down"></i>
    </div>
    <div class="setting-content">
        <div class="content-inner">
            <p>Download a complete backup of the Queue and Transaction History tables.</p>
            <button class="action-btn" style="background-color: #27ae60; color: white; border: none;" onclick="window.location.href='export_database.php'">
                <i class="fa fa-download"></i> Download SQL Backup
            </button>
            <p style="font-size: 12px; color: #666; margin-top: 10px;">
                Note: It is recommended to back up your data every Friday afternoon.
            </p>
        </div>
    </div>
</div>

        <div class="danger-zone">
            <h3><i class="fa fa-exclamation-triangle"></i> System Maintenance</h3>
            <p>Use this to clear all active queues. Note: This will not delete history, only pending tickets.</p>
            <button class="btn-reset" onclick="confirmReset()">Clear Active Queue (New Day)</button>
        </div>

        <a href="ReportsAndSettingsMenu.php" class="back-btn"><i class="fa fa-arrow-left"></i> Back to Menu</a>
    </div>

    <script>
        // Accordion Toggle Logic
        function toggleAccordion(header) {
            const content = header.nextElementSibling;
            const icon = header.querySelector('.fa-chevron-down');
            
            if (content.style.maxHeight) {
                content.style.maxHeight = null;
                icon.style.transform = "rotate(0deg)";
            } else {
                // Close all other open sections
                document.querySelectorAll('.setting-content').forEach(c => c.style.maxHeight = null);
                document.querySelectorAll('.fa-chevron-down').forEach(i => i.style.transform = "rotate(0deg)");
                
                content.style.maxHeight = content.scrollHeight + "px";
                icon.style.transform = "rotate(180deg)";
            }
        }

        // Dark Mode Logic
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        }

        // Apply saved theme on load
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
        }

        // Confirm Reset Logic
        function confirmReset() {
            if (confirm("Are you sure you want to clear all PENDING tickets? This action cannot be undone.")) {
                window.location.href = "process_reset_queue.php";
            }
        }
    </script>
</body>
</html>