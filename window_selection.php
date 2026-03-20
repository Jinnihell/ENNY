<?php 
include 'auth_check.php'; // Proteksyon para sa login
include 'db_config.php'; 

// Proteksyon para sa ROLE (Para hindi ma-access ng basta-bastang user ang admin page)
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') {
    header("Location: login.php?error=unauthorized");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Window - ESCR DQMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="responsive.css">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; padding: 0; background: linear-gradient(135deg, #b7ffd8 0%, #e1f5fe 50%, #90caf9 100%); min-height: 100vh; position: relative; }
        
        .header { display: flex; align-items: center; justify-content: space-between; padding: clamp(15px, 3vw, 20px) clamp(20px, 5vw, 40px); border-bottom: 2px solid #e0e0e0; background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.05); flex-wrap: wrap; gap: 15px; }
        .logo-box { display: flex; align-items: center; gap: 15px; }
        .logo-box img { width: clamp(35px, 8vw, 50px); }
        .logo-box span { font-weight: bold; font-size: clamp(14px, 2.5vw, 16px); color: #1a2a4d; }

        .container { text-align: center; margin-top: clamp(20px, 5vw, 60px); padding: 20px; box-sizing: border-box; min-height: calc(100vh - 100px); display: flex; flex-direction: column; }
        h2 { font-weight: 800; font-size: clamp(22px, 5vw, 28px); margin-bottom: 10px; color: #1a2a4d; }
        p { font-size: clamp(14px, 2.5vw, 16px); margin-bottom: clamp(30px, 5vw, 50px); color: #666; }

        .window-grid { 
            display: flex; 
            justify-content: center;
            flex-wrap: wrap;
            gap: clamp(15px, 3vw, 20px); 
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 10px;
        }

        .window-btn {
            background: linear-gradient(135deg, #1a2a4d 0%, #2d4a8d 100%);
            color: white;
            padding: clamp(30px, 5vw, 45px) clamp(40px, 8vw, 70px);
            border-radius: 20px;
            text-decoration: none;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(26,42,77,0.3);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            min-width: 240px;
            flex: 1;
            max-width: 320px;
        }

        .window-btn:hover { transform: translateY(-5px); box-shadow: 0 12px 35px rgba(26,42,77,0.4); }
        .window-btn:active { transform: translateY(0); }
        .window-btn i { font-size: clamp(24px, 5vw, 30px); opacity: 0.8; margin-bottom: 5px; }
        
        .win-title { font-size: clamp(16px, 3vw, 20px); font-weight: bold; letter-spacing: 1px; }
        .win-assignment { font-size: clamp(11px, 2vw, 14px); margin-top: 5px; color: #ff8c00; font-weight: 600; }

        .help-icon { width: clamp(35px, 6vw, 40px); height: clamp(35px, 6vw, 40px); border: 2px solid #1a2a4d; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #1a2a4d; cursor: pointer; transition: all 0.3s; }
        .help-icon:hover { background: #1a2a4d; color: white; }
        
        .back-link { display: inline-block; margin-top: auto; margin-bottom: 20px; color: #1a2a4d; text-decoration: none; font-weight: 600; font-size: clamp(13px, 2vw, 15px); transition: color 0.3s; align-self: flex-start; position: absolute; bottom: 30px; left: 30px; }
        .back-link:hover { color: #ff8c00; }
        
        /* Modal styling */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.4); backdrop-filter: blur(5px); justify-content: center; align-items: center; padding: 20px; }
        .modal.active { display: flex; }
        .modal-content { background-color: #fff; padding: clamp(25px, 5vw, 40px); border-radius: clamp(20px, 4vw, 25px); width: 100%; max-width: 450px; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.2); animation: popIn 0.3s ease-out; border-top: 5px solid #1a2a4d; box-sizing: border-box; }
        .modal-content h2 { color: #1a2a4d; font-size: clamp(20px, 4vw, 24px); }
        .modal-content p { color: #555; line-height: 1.8; font-size: clamp(13px, 2vw, 15px); text-align: left; }
        @keyframes popIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .close-modal-btn { background: linear-gradient(135deg, #1a2a4d, #2d4a8d); color: white; padding: 12px 35px; border: none; border-radius: 12px; margin-top: 25px; cursor: pointer; font-weight: bold; font-size: clamp(13px, 2vw, 15px); transition: all 0.3s; box-shadow: 0 4px 15px rgba(26,42,77,0.3); }
        .close-modal-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(26,42,77,0.4); }
        
        /* Responsive */
        @media screen and (max-width: 768px) {
            body { height: auto; min-height: 100vh; }
            .header { flex-direction: column; gap: 15px; padding: 15px; }
            .container { margin-top: 30px; }
            h2 { font-size: 22px; }
            p { font-size: 14px; margin-bottom: 30px; }
            .window-grid { flex-direction: row; gap: 15px; }
            .window-btn { min-width: 140px; max-width: none; flex: 1; padding: 20px 15px; }
            .win-title { font-size: 16px; }
            .win-assignment { font-size: 12px; }
            .window-btn i { font-size: 24px; }
        }

        @media screen and (max-width: 480px) {
            h2 { font-size: 18px; }
            .window-grid { flex-direction: column; align-items: center; }
            .window-btn { width: 100%; max-width: 300px; }
            .back-link { position: static; margin-top: 30px; margin-bottom: 0; align-self: center; }
            .help-icon { width: 35px; height: 35px; font-size: 16px; }
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="logo-box">
            <img src="escr-logo.png" alt="Logo">
            <span>East Systems Colleges of Rizal</span>
        </div>
        <div class="help-icon" onclick="toggleModal('helpModal')">?</div>
    </div>

    <div class="container">
        <h2>Welcome to ESCR Queueing System</h2>
        <p>Please select your serving - window.....</p>

        <form action="staff_dashboard.php" method="GET" class="window-grid">
            <button type="submit" name="window" value="1" class="window-btn">
                <i class="fa fa-clipboard-check"></i>
                <span class="win-title">Window 1</span>
                <span class="win-assignment">Assessments</span>
            </button>

            <button type="submit" name="window" value="2" class="window-btn">
                <i class="fa fa-user-plus"></i>
                <span class="win-title">Window 2</span>
                <span class="win-assignment">Enrollment</span>
            </button>

            <button type="submit" name="window" value="3" class="window-btn">
                <i class="fa fa-credit-card"></i>
                <span class="win-title">Window 3</span>
                <span class="win-assignment">Payments</span>
            </button>

            <button type="submit" name="window" value="4" class="window-btn">
                <i class="fa fa-concierge-bell"></i>
                <span class="win-title">Window 4</span>
                <span class="win-assignment">Other Concerns / Docs</span>
            </button>
        </form>
        <br> 
         <br>
          <br>
           <br>
        <a href="admin_selection.php" class="back-link"><i class="fa fa-arrow-left"></i> Back to Selection</a>
    </div>

    <div id="helpModal" class="modal">
        <div class="modal-content">
            <h2 style="color: #1a237e;">Window Selection Help</h2>
            <p>1. Select the window number you are assigned to serve.<br>
            2. Window 1 is for <b>Assessments</b>.<br>
            3. Window 2 is for <b>Enrollment</b>.<br>
            4. Window 3 is for <b>Payments</b>.<br>
            5. Window 4 is for <b>Other Concerns / Documents</b>.</p>
            <button class="close-modal-btn" onclick="toggleModal('helpModal')">I See</button>
        </div>
    </div>

    <script>
        function toggleModal(id) {
            const modal = document.getElementById(id);
            if (modal.classList.contains('active')) {
                modal.classList.remove('active');
            } else {
                modal.classList.add('active');
            }
        }
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.classList.remove('active');
                });
            }
        });
    </script>

</body>
</html>
