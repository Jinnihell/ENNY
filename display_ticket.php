<?php
include 'auth_check.php';
include 'db_config.php'; 

/**
 * 1. SECURITY & DATA CAPTURE
 */
// Only allow access if a ticket number is present in the URL
if (!isset($_GET['ticket'])) {
    header("Location: transaction_selection.php");
    exit();
}

$ticket     = isset($_GET['ticket']) ? preg_replace('/[^A-Z0-9]/', '', $_GET['ticket']) : '';
$name       = isset($_GET['name']) ? trim($_GET['name']) : '';
$blk_course = isset($_GET['blk_course']) ? trim($_GET['blk_course']) : '';
$category   = isset($_GET['category']) ? trim($_GET['category']) : '';

// Validate required fields
if (empty($ticket) || empty($name) || empty($blk_course) || empty($category)) {
    header("Location: transaction_selection.php");
    exit();
}

// Validate category is one of the allowed values
$valid_categories = ['Assessments', 'Enrollment', 'Payments', 'Other Concerns'];
if (!in_array($category, $valid_categories)) {
    header("Location: transaction_selection.php");
    exit();
}
$date       = date('Y-m-d');
$time       = date('H:i:s');

/**
 * 2. WINDOW MAPPING LOGIC
 */
$window_mapping = [
    'Assessments'    => 1,
    'Enrollment'     => 2,
    'Payments'       => 3,
    'Other Concerns' => 4
];
$suggested_window = isset($window_mapping[$category]) ? $window_mapping[$category] : "Counter";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Queue Ticket - ESCR DQMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="responsive.css">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            background: linear-gradient(135deg, #b7ffd8 0%, #e1f5fe 50%, #90caf9 100%);
        }

        .ticket-container {
            background: white;
            width: min(500px, 95%); max-width: 500px;
            padding: clamp(25px, 5vw, 40px);
            border-radius: clamp(20px, 4vw, 30px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            position: relative;
            text-align: center;
        }

        /* Logo and Header Styling */
        .logo-header {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            margin-bottom: 20px;
            gap: 15px;
        }

        .logo-header img {
            width: clamp(40px, 8vw, 50px);
            height: auto;
        }

        .ticket-header {
            font-style: italic;
            font-weight: bold;
            font-size: clamp(14px, 3vw, 18px);
        }

        .queue-label {
            font-size: clamp(12px, 2.5vw, 14px);
            color: #555;
            margin-bottom: 5px;
        }

        .queue-number {
            font-size: clamp(60px, 15vw, 100px);
            font-weight: 900;
            margin: 10px 0;
            line-height: 1;
            color: #000;
        }

        .meta-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
            font-weight: bold;
            border-top: 2px dashed #eee;
            border-bottom: 2px dashed #eee;
            padding: 10px 0;
            flex-wrap: wrap;
            gap: 10px;
        }

        .category-label {
            font-size: clamp(16px, 4vw, 20px);
            font-style: italic;
            font-weight: 900;
        }

        .details-section {
            text-align: left;
            margin: 20px 0;
            padding-left: clamp(10px, 3vw, 20px);
        }

        .details-section div {
            display: flex;
            margin: 10px 0;
            font-size: clamp(14px, 2.5vw, 18px);
            flex-wrap: wrap;
        }

        .details-section .label {
            width: clamp(100px, 30vw, 150px);
            font-weight: bold;
            font-style: italic;
        }

        /* Window Suggestion Box */
        .window-box {
            margin-top: 15px;
            border: 2px solid #1a2a4d;
            padding: clamp(10px, 3vw, 15px);
            border-radius: 15px;
            background-color: #f0f4f8;
        }

        .window-box p {
            margin: 0;
            font-size: clamp(12px, 2.5vw, 14px);
            font-weight: bold;
            color: #1a2a4d;
        }

        .window-box h2 {
            margin: 5px 0 0;
            color: #1a2a4d;
            font-size: clamp(22px, 5vw, 28px);
            font-weight: 900;
        }

        .footer-note {
            font-size: clamp(14px, 2.5vw, 16px);
            line-height: 1.5;
            margin-top: 25px;
            font-weight: 500;
        }

        .btn-proceed {
            position: relative;
            margin-top: 20px;
            background-color: #1a2a4d;
            color: white;
            border: none;
            padding: clamp(10px, 2.5vw, 12px) clamp(25px, 5vw, 35px);
            border-radius: 25px;
            font-weight: bold;
            font-size: clamp(14px, 3vw, 18px);
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-proceed:hover {
            background-color: #111b33;
            transform: scale(1.05);
        }
        
        /* Responsive for smaller screens */
        @media screen and (max-width: 480px) {
            .ticket-container {
                padding: 20px 15px;
            }
            
            .meta-info {
                flex-direction: column;
                text-align: center;
            }
            
            .details-section div {
                flex-direction: column;
                gap: 5px;
            }
            
            .details-section .label {
                width: 100%;
            }
        }
    </style>

        /* PRINT OPTIMIZATION */
        @media print {
            .btn-proceed { display: none; }
            body { background: white; padding: 0; }
            .ticket-container { box-shadow: none; border: none; width: 100%; padding: 0; }
        }
    </style>
</head>
<body>

    <div class="ticket-container" id="printableArea">
        <div class="logo-header">
            <img src="escr-logo.png" alt="ESCR Logo">
            <div class="ticket-header">Student Queue Ticket</div>
        </div>
        
        <p class="queue-label">Your queuing number</p>
        <h1 class="queue-number"><?php echo htmlspecialchars($ticket); ?></h1>
        
        <div class="meta-info">
            <span><?php echo $date; ?></span>
            <span class="category-label"><?php echo htmlspecialchars($category); ?></span>
            <span><?php echo $time; ?></span>
        </div>

        <div class="details-section">
            <div>
                <span class="label">Name:</span> 
                <span><?php echo htmlspecialchars($name); ?></span>
            </div>
            <div>
                <span class="label">BLK & Course:</span> 
                <span><?php echo htmlspecialchars($blk_course); ?></span>
            </div>
        </div>

        <div class="window-box">
            <p>PLEASE PROCEED TO:</p>
            <h2>WINDOW <?php echo $suggested_window; ?></h2>
        </div>

        <div class="footer-note">
            Keep quiet and be patient. <br>
            <strong>Thank You!</strong>
        </div>

        <button class="btn-proceed" onclick="proceedToFinal()">Proceed</button>
    </div>

    <script>
        // Trigger print immediately on load
        window.onload = function() {
            window.print();
        };

        function proceedToFinal() {
            window.location.href = "final_thank_you.php";
        }
    </script>
</body>
</html>