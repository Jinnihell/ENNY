<?php 
include 'auth_check.php';
include 'db_config.php'; 

// If admin or staff reaches this page, redirect them to their dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'staff') {
        header("Location: admin_selection.php");
        exit();
    }
    // For students, show the landing page with Get Started button
    // They can click "Get Started" to proceed to transaction_selection.php
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESCR Digital Queueing System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="responsive.css">
    <style>
        /* CSS Reset at Background Layout */
        body, html { 
            margin: 0; 
            padding: 0; 
            height: 100%; 
            width: 100%; 
            font-family: 'Segoe UI', Arial, sans-serif; 
            overflow: hidden; 
        }

        .hero-section {
            /* Saktong gradient mula sa screenshot */
            background: linear-gradient(135deg, #b7ffd8 0%, #e1f5fe 50%, #90caf9 100%);
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            padding: 20px;
            box-sizing: border-box;
        }

        /* Help Icon */
        .help-icon {
            position: absolute;
            top: 25px;
            right: 25px;
            font-size: 20px;
            color: #1a2a4d;
            cursor: pointer;
            background: white;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 2px solid #1a2a4d;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            font-weight: bold;
            z-index: 100;
        }
        .help-icon:hover { 
            background: #1a2a4d; 
            color: white; 
            transform: scale(1.1); 
        }

        /* Logo at Typography */
        .logo { 
            width: 160px; 
            margin-bottom: 15px; 
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));
            animation: float 3s ease-in-out infinite;
            max-width: 100%;
            height: auto;
        }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }

        h1 { 
            font-size: clamp(32px, 5vw, 68px); 
            margin: 0; 
            letter-spacing: clamp(3px, 1vw, 10px); 
            color: #1a2a4d; 
            font-weight: 900;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.05);
        }

        .sub-title { 
            font-size: clamp(14px, 2.5vw, 28px); 
            font-weight: 600; 
            margin: 5px 0 clamp(20px, 5vw, 50px); 
            color: #555; 
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        /* Button Group Layout */
        .button-group { 
            display: flex; 
            flex-direction: column; 
            gap: 20px; 
            align-items: center;
            width: 100%;
            max-width: 400px;
        }

        .btn {
            width: 100%;
            max-width: 350px;
            padding: 18px 0;
            border-radius: 15px;
            text-decoration: none;
            font-weight: bold;
            font-size: clamp(16px, 2vw, 20px);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            border: none;
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
            letter-spacing: 1px;
            box-sizing: border-box;
        }

        .btn-get-started { 
            background: linear-gradient(135deg, #1a2a4d 0%, #2d4a8d 100%); 
            color: white; 
        }
        .btn-get-started:hover { 
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(40,167,69,0.35);
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
        }

        .btn-about { 
            background: white; 
            color: #1a2a4d; 
            border: 2px solid #1a2a4d;
        }
        .btn-about:hover { 
            background: #28a745;
            color: white;
            border-color: #28a745;
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(40,167,69,0.25);
        }

        /* Modal styling para sa Help at About popups */
        .modal {
            display: none;
            position: fixed; 
            z-index: 1000; 
            left: 0; top: 0; width: 100%; height: 100%; 
            background-color: rgba(0, 0, 0, 0.4); 
            backdrop-filter: blur(5px);
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background-color: #fff;
            padding: clamp(20px, 5vw, 40px);
            border-radius: 25px;
            width: 90%;
            max-width: 450px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            animation: popIn 0.3s ease-out;
            border-top: 5px solid #1a2a4d;
            box-sizing: border-box;
        }
        .modal-content h2 { color: #1a2a4d; font-size: clamp(18px, 4vw, 24px); }
        .modal-content p { color: #555; line-height: 1.8; font-size: clamp(13px, 2vw, 15px); }

        @keyframes popIn { 
            from { transform: scale(0.8); opacity: 0; } 
            to { transform: scale(1); opacity: 1; } 
        }

        .close-modal-btn { 
            background: linear-gradient(135deg, #1a2a4d, #2d4a8d); 
            color: white; 
            padding: 12px 35px; 
            border: none; 
            border-radius: 12px; 
            margin-top: 25px; 
            cursor: pointer; 
            font-weight: bold;
            font-size: 15px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(26,42,77,0.3);
        }
        .close-modal-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(26,42,77,0.4); }

        /* Responsive adjustments */
        @media screen and (max-width: 480px) {
            .hero-section {
                padding: 15px;
            }
            
            .logo {
                width: 120px;
            }
            
            .button-group {
                gap: 15px;
            }
            
            .btn {
                max-width: 280px;
                padding: 15px;
            }
            
            .help-icon {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }
        }

        @media screen and (max-height: 600px) and (orientation: landscape) {
            .hero-section {
                min-height: 100vh;
                padding: 10px;
            }
            
            h1 {
                font-size: 28px;
                margin-bottom: 5px;
            }
            
            .sub-title {
                margin-bottom: 10px;
            }
            
            .logo {
                width: 80px;
                margin-bottom: 10px;
            }
            
            .button-group {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .btn {
                width: auto;
                min-width: 150px;
                padding: 10px 20px;
            }
        }
    </style>
</head>
<body>

    <div class="hero-section">
        <div class="help-icon" onclick="toggleModal('helpModal')">?</div>
        
        <img src="escr-logo.png" alt="ESCR Logo" class="logo">
        
        <h1>ESCR</h1>
        <div class="sub-title">Digital Queueing System</div>

        <div class="button-group">
            <?php if(isset($_SESSION['user_id']) && $_SESSION['role'] === 'student'): ?>
                <a href="transaction_selection.php" class="btn btn-get-started">
                    Get Started
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-get-started">
                    Get Started
                </a>
            <?php endif; ?>
            
            <button class="btn btn-about" onclick="toggleModal('aboutModal')">
                About
            </button>
        </div>
    </div>

    <div id="aboutModal" class="modal">
        <div class="modal-content">
            <h2 style="color: #1a237e;">About DQMS</h2>
            <p>The ESCR Digital Queueing System was developed to modernize school operations by ensuring a faster, more systematic, and highly organized flow of transactions. By transitioning from manual lines to a digital framework, 
            the system significantly reduces wait times and minimizes campus congestion, allowing students, parents, and staff to complete their tasks with greater ease. This innovation reflects the school's commitment to utilizing technology to provide a professional, fair, and seamless service experience for the entire community.</p>
            <button class="close-modal-btn" onclick="toggleModal('aboutModal')">Close</button>
        </div>
    </div>

   <div id="helpModal" class="modal">
        <div class="modal-content">
            <h2 style="color: #1a237e;">Quick Help</h2>
            <p style="text-align: left;">1. I-click ang <b>Get Started</b>.<br>
            2. Mag-login gamit ang iyong account.<br>
            3. Piliin ang iyong sadya sa Registrar o Cashier.<br>
            4. Kunin ang iyong printed ticket.</p>
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

        // Close modal when clicking outside the modal box
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });

        // Close modals with Escape key
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