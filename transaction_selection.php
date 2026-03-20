<?php 
include 'auth_check.php';
include 'db_config.php'; 
include 'csrf_protection.php'; 

// Display error message if exists
$error_msg = '';
if (isset($_SESSION['error'])) {
    $error_msg = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Redirect Admins/Staff away from the Student Selection page
if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'staff') {
    header("Location: admin_selection.php"); 
    exit();
}

// Ensure ONLY students can stay here
if ($_SESSION['role'] !== 'student') {
    header("Location: login.php?error=unauthorized");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Transaction - ESCR DQMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="responsive.css">
    
    <style>
        /* General Layout */
        body { font-family: 'Segoe UI', sans-serif; margin: 0; padding: 0; background: linear-gradient(135deg, #b7ffd8 0%, #e1f5fe 50%, #90caf9 100%); min-height: 100vh; overflow-x: hidden; }
        
        .navbar { background: white; padding: clamp(10px, 3vw, 20px) clamp(15px, 5vw, 40px); display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #e0e0e0; position: relative; z-index: 10; box-shadow: 0 2px 10px rgba(0,0,0,0.05); flex-wrap: wrap; gap: 10px; }
        .logo-section { display: flex; align-items: center; gap: 15px; }
        .logo-section img { width: clamp(35px, 8vw, 50px); }
        .logo-section span { font-weight: bold; font-size: clamp(12px, 2vw, 16px); color: #1a2a4d; }

        .container { text-align: center; margin-top: clamp(20px, 5vw, 60px); padding: 20px; box-sizing: border-box; }
        h1 { font-weight: 900; margin-bottom: 15px; color: #1a2a4d; font-size: clamp(28px, 5vw, 42px); letter-spacing: 2px; }
        h2 { font-weight: 600; margin-bottom: clamp(20px, 5vw, 50px); color: #555; font-size: clamp(16px, 3vw, 22px); }

        /* Transaction Buttons */
        .button-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: clamp(15px, 4vw, 30px); justify-content: center; margin-bottom: 30px; max-width: 1200px; margin-left: auto; margin-right: auto; }
        .nav-btn {
            background: linear-gradient(135deg, #1a2a4d 0%, #2d4a8d 100%); color: white; padding: clamp(25px, 5vw, 45px) clamp(15px, 3vw, 30px); border-radius: 20px;
            font-size: clamp(16px, 2.5vw, 24px); font-weight: bold; border: none; cursor: pointer; transition: all 0.3s ease; width: 100%;
            box-shadow: 0 8px 25px rgba(26, 42, 77, 0.3); letter-spacing: 1px;
            display: flex; flex-direction: column; align-items: center; gap: 15px;
            box-sizing: border-box;
        }
        .nav-btn:hover { background: linear-gradient(135deg, #2d4a8d 0%, #3d5a9d 100%); transform: scale(1.05) translateY(-5px); box-shadow: 0 15px 35px rgba(26, 42, 77, 0.4); }
        .nav-btn:active { transform: scale(0.98); }
        .nav-btn i { font-size: clamp(28px, 5vw, 40px); opacity: 0.9; }
        
        /* Single button container */
        .single-button-container { display: flex; justify-content: center; margin-top: 10px; width: 100%; }
        .single-button-container .nav-btn { max-width: 300px; }
        
        /* Modals */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(5px); display: none; justify-content: center; align-items: center; z-index: 1000; padding: 15px; box-sizing: border-box; }
        .modal-overlay.active { display: flex; }
        .modal-box { background: white; padding: clamp(20px, 5vw, 45px); border-radius: 25px; width: 100%; max-width: 580px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); text-align: left; position: relative; border-top: 5px solid #1a2a4d; animation: slideUp 0.3s ease; box-sizing: border-box; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .modal-box h3 { color: #1a2a4d; font-size: clamp(18px, 3vw, 24px); margin-bottom: 5px; }
        .modal-box p { color: #666; font-size: clamp(13px, 1.5vw, 15px); }

        /* Tooltip Styles */
        .tooltip {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }
        .tooltip .tooltiptext {
            visibility: hidden;
            width: 200px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 8px 12px;
            position: absolute;
            z-index: 101;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 12px;
            font-weight: normal;
        }
        .tooltip .tooltiptext::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #333 transparent transparent transparent;
        }
        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
        
        /* Close Button */
        .close-btn {
            position: absolute;
            right: 20px;
            top: 20px;
            width: 35px;
            height: 35px;
            border: 2px solid #ddd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-weight: bold;
            background: #f8f9fa;
            transition: all 0.3s;
            font-size: 16px;
            color: #666;
        }
        .close-btn:hover {
            background: #ff4d4d;
            color: white;
            border-color: #ff4d4d;
            transform: rotate(90deg);
        }

        /* Form Elements */
        .input-group { margin-bottom: 22px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
        .input-group label { font-weight: 700; min-width: 30%; color: #1a2a4d; font-size: clamp(13px, 1.5vw, 15px); }
        .input-group input, .input-group select { padding: 14px 18px; border: 2px solid #e0e0e0; border-radius: 12px; outline: none; font-size: clamp(13px, 1.5vw, 15px); transition: border-color 0.3s; background: #fafafa; flex: 1; min-width: 200px; box-sizing: border-box; }
        .input-group input:focus, .input-group select:focus { border-color: #1a2a4d; background: white; }
        .dropdown-container { flex: 1; display: flex; gap: 10px; min-width: 200px; }
        .blk-select { flex: 0.4; }
        .course-select { flex: 0.6; }

        /* Action Buttons */
        .modal-buttons { display: flex; justify-content: center; gap: 20px; margin-top: 35px; flex-wrap: wrap; }
        .btn-back { background: linear-gradient(135deg, #ff4d4d, #e03030); color: white; border: none; padding: 14px 45px; border-radius: 12px; font-weight: bold; cursor: pointer; font-size: clamp(14px, 1.5vw, 16px); transition: all 0.3s; box-shadow: 0 4px 15px rgba(255,77,77,0.3); }
        .btn-back:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(255,77,77,0.4); }
        .btn-submit { background: linear-gradient(135deg, #00c853, #00a844); color: white; border: none; padding: 14px 45px; border-radius: 12px; font-weight: bold; cursor: pointer; font-size: clamp(14px, 1.5vw, 16px); transition: all 0.3s; box-shadow: 0 4px 15px rgba(0,200,83,0.3); }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,200,83,0.4); }
        .help-circle { width: clamp(35px, 8vw, 40px); height: clamp(35px, 8vw, 40px); border: 2px solid #1a2a4d; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; cursor: pointer; color: #1a2a4d; font-size: clamp(14px, 2vw, 18px); transition: all 0.3s; }
        .help-circle:hover { background: #1a2a4d; color: white; }

        /* Printing Logic */
        @media print {
            body * { visibility: hidden; }
            #printableTicket, #printableTicket * { visibility: visible; }
            #printableTicket { position: absolute; left: 0; top: 0; width: 100%; border: none; box-shadow: none; }
            .btn-proceed, .navbar, .container { display: none !important; }
        }

        /* Ticket Modal */
        #ticketModal .modal-box {
            max-width: 520px;
            padding: clamp(20px, 5vw, 45px);
        }
        
        #ticketModal .queue-number-display {
            font-size: clamp(60px, 15vw, 100px);
        }
        
        /* Responsive adjustments for forms */
        @media screen and (max-width: 768px) {
            .input-group {
                flex-direction: column;
                align-items: stretch;
            }
            
            .input-group label {
                width: 100%;
                margin-bottom: 5px;
            }
            
            .input-group input, 
            .input-group select {
                width: 100%;
            }
            
            .dropdown-container {
                width: 100%;
                flex-direction: column;
            }
            
            .blk-select, 
            .course-select {
                width: 100px;
            }
            
            .modal-buttons {
                flex-direction: column;
            }
            
            .btn-back, 
            .btn-submit {
                width: 100%;
            }
        }
        
        @media screen and (max-width: 480px) {
            .navbar {
                justify-content: center;
            }
            
            .logo-section {
                flex: 1;
                justify-content: center;
            }
            
            .help-circle {
                position: absolute;
                top: 10px;
                right: 10px;
            }
            
            .button-grid {
                grid-template-columns: 1fr;
                padding: 0 10px;
            }
            
            .single-button-container {
                padding: 0 10px;
            }
            
            .single-button-container .nav-btn {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

<!-- Queue Status Tracker (Hidden by default) -->
<div id="queueTracker" style="display: none; position: fixed; top: 0; left: 0; right: 0; z-index: 9999; background: linear-gradient(135deg, #1a2a4d, #2d4a8d); padding: 10px 20px; color: white; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
    <div style="display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div>
                <span style="font-size: 12px; opacity: 0.8;">Your Queue:</span>
                <span id="trackerQueueNumber" style="font-size: 24px; font-weight: bold; color: #ff8c00;">---</span>
            </div>
            <div>
                <span style="font-size: 12px; opacity: 0.8;">Status:</span>
                <span id="trackerStatus" style="font-size: 16px; font-weight: bold;">---</span>
            </div>
            <div>
                <span style="font-size: 12px; opacity: 0.8;">Position:</span>
                <span id="trackerPosition" style="font-size: 16px; font-weight: bold;">#--</span>
            </div>
            <div>
                <span style="font-size: 12px; opacity: 0.8;">Window:</span>
                <span id="trackerWindow" style="font-size: 16px; font-weight: bold;">--</span>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <button id="enableNotificationsBtn" onclick="requestNotificationPermission()" style="background: #ff8c00; border: none; padding: 8px 15px; border-radius: 20px; color: white; cursor: pointer; font-size: 12px;">
                <i class="fa fa-bell"></i> Enable Notifications
            </button>
            <button onclick="toggleTrackerDetails()" style="background: rgba(255,255,255,0.2); border: none; padding: 8px 15px; border-radius: 20px; color: white; cursor: pointer;">
                <i class="fa fa-chevron-up" id="trackerToggleIcon"></i>
            </button>
        </div>
    </div>
    <!-- Expandable Details -->
    <div id="trackerDetails" style="display: none; max-width: 1200px; margin: 15px auto 0; padding: 15px; background: rgba(255,255,255,0.1); border-radius: 10px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; text-align: center;">
            <div>
                <p style="margin: 0; font-size: 11px; opacity: 0.8;">NOW SERVING</p>
                <p id="trackerServing" style="margin: 5px 0; font-size: 20px; font-weight: bold; color: #ff8c00;">---</p>
            </div>
            <div>
                <p style="margin: 0; font-size: 11px; opacity: 0.8;">TRANSACTION</p>
                <p id="trackerType" style="margin: 5px 0; font-size: 14px; font-weight: bold;">---</p>
            </div>
            <div>
                <p style="margin: 0; font-size: 11px; opacity: 0.8;">YOUR STATUS</p>
                <p id="trackerYourStatus" style="margin: 5px 0; font-size: 14px; font-weight: bold;">---</p>
            </div>
            <div>
                <p style="margin: 0; font-size: 11px; opacity: 0.8;">TIME IN QUEUE</p>
                <p id="trackerTime" style="margin: 5px 0; font-size: 14px; font-weight: bold;">---</p>
            </div>
        </div>
    </div>
</div>

    <div class="navbar">
        <div class="logo-section">
            <img src="escr-logo.png" alt="Logo">
            <span>East Systems Colleges of Rizal</span>
        </div>
        <div class="help-circle" onclick="toggleHelpModal()">?
        </div>
    </div>

    <div class="container">
        <h1>WELCOME!</h1>
        <h2>Please select your transaction...</h2>
        <div class="button-grid">
            <button type="button" class="nav-btn" onclick="openModal('Enrollment')"><i class="fa fa-user-plus"></i> Enrollment</button>
            <button type="button" class="nav-btn" onclick="openModal('Assessments')"><i class="fa fa-clipboard-check"></i> Assessments</button>
            <button type="button" class="nav-btn" onclick="openModal('Payments')"><i class="fa fa-credit-card"></i> Payments</button>
        </div>
        <div style="display: flex; justify-content: center; margin-top: 10px;">
            <button type="button" class="nav-btn" style="width: 300px;" onclick="openModal('Other Concerns')"><i class="fa fa-concierge-bell"></i> Other Concerns</button>
        </div>
    </div>

    <div class="modal-overlay" id="detailsModal">
        <div class="modal-box">
            <button class="close-btn" onclick="closeModal()">&times;</button>
            <div style="text-align: center; margin-bottom: 25px;">
                <img src="escr-logo.png" alt="Logo" style="width: 50px; height: 50px; object-fit: contain; margin-bottom: 10px;">
                <h3 style="margin: 0;">Student Details</h3>
                <p style="text-align: center; font-weight: 500; font-size: 14px;">Please fill in your information below</p>
            </div>
            
            <form action="generate_ticket.php" method="POST" id="detailsForm">
                <?php if ($error_msg): ?>
                <div style="background: #ffebee; color: #c62828; padding: 12px; border-radius: 8px; margin-bottom: 15px; text-align: center; font-weight: 500;">
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
                <?php endif; ?>
                <input type="hidden" name="category" id="selectedCategory">
                <input type="hidden" name="blk_course" id="final_blk_course">
                <input type="hidden" name="year" id="final_year">
                <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">

                <div class="input-group">
                    <label>Name:</label>
                    <input type="text" name="student_name" placeholder="Full Name" required>
                </div>

                <div class="input-group">
                    <label>BLK & Course:</label>
                    <div class="dropdown-container">
                        <select id="blk_val" class="blk-select" required>
                            <option value="" disabled selected>BLK</option>
                            <?php foreach(range('A', 'F') as $char) echo "<option value='$char'>$char</option>"; ?>
                        </select>
                        <select id="course_val" class="course-select" required>
                            <option value="" disabled selected>Select Course</option>
                            <option value ="G11">Senior High - Grade 11</option>
                             <option value ="G12">Senior High - Grade 12</option>
                            <option value="BSBA">Bachelor of Science in Business Administration(BSBA)</option>
                            <option value="BSAIS">Bachelor of Science in Accounting Information System(BSAIS)</option>
                            <option value="BSOA">Bachelor of Science in Office Administration(BSOA)</option>
                            <option value ="BSCS">Bachelor of Science in Computer Science(BSCS)</option>
                            <option value="BSIT">Bachelor of Science in Information Technology(BSIT)</option>
                            <option value="BTVTED ELEC">Bachelor of Technical-Vocational Education and Training (Electrical)(BTVTED ELEC)</option>
                            <option value="BTVTED">Bachelor of Technical-Vocational Education and Training(BTVTED)</option>
                            <option value="BSBA-FM">Bachelor of Science in Business Administration(Food Management)(BSBA-FM)</option>
                            <option value="BSBA-HM">Bachelor of Science in Business Administration(Hospitality Management)(BSBA-HM)</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>
                </div>

                <div class="input-group">
                    <label>Year:</label>
                    <select id="year_val" name="year" required style="width: 68%; padding: 14px 18px; border: 2px solid #e0e0e0; border-radius: 12px; outline: none; font-size: 15px; background: #fafafa; transition: border-color 0.3s;">
                        <option value="" disabled selected>Select Year</option>
                        <option value="Senior High">Senior High</option>
                        <option value="1st Year">1st Year</option>
                        <option value="2nd Year">2nd Year</option>
                        <option value="3rd Year">3rd Year</option>
                        <option value="4th Year">4th Year</option>
                        
                    </select>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="btn-back" onclick="closeModal()">Back</button>
                    <button type="submit" class="btn-submit">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <?php if(isset($_GET['ticket'])): ?>
    <div class="modal-overlay active" id="ticketModal">
        <div class="modal-box" id="printableTicket" style="width: 520px; padding: 45px; text-align: left; border-top: 5px solid #ff8c00;">
            <button class="close-btn" onclick="closeTicketModal()">&times;</button>
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                <img src="escr-logo.png" alt="Logo" style="width: 45px; height: 45px; object-fit: contain;">
                <div>
                    <h4 style="margin: 0; font-weight: bold; font-size: 20px; color: #1a2a4d;">Student Queue Ticket</h4>
                    <p style="margin: 0; font-size: 12px; color: #888;">East Systems Colleges of Rizal</p>
                </div>
            </div>
            <hr style="border: 1px dashed #ddd; margin: 15px 0;">
            <div style="text-align: center; margin: 20px 0;">
                <p style="margin-bottom: 5px; font-size: 14px; color: #888; text-transform: uppercase; letter-spacing: 2px;">Your Queue Number</p>
                <h1 style="font-size: 100px; margin: 10px 0; font-weight: 900; color: #1a2a4d;"><?php echo $_GET['ticket']; ?></h1>
                <span style="background: #ff8c00; color: white; padding: 8px 25px; border-radius: 20px; font-weight: bold; font-size: 16px;"><?php echo $_GET['category']; ?></span>
                <div style="display: flex; justify-content: space-around; margin: 20px 0; font-weight: 600; color: #666; font-size: 14px;">
                    <span><i class="fa fa-calendar"></i> <?php echo date('M d, Y'); ?></span>
                    <span><i class="fa fa-clock"></i> <?php echo date('h:i A'); ?></span>
                </div>
            </div>
            <hr style="border: 1px dashed #ddd; margin: 15px 0;">
            <div style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 12px; border-left: 4px solid #1a2a4d;">
                <p style="margin: 8px 0; font-size: 15px;"><strong><i class="fa fa-user"></i> Name:</strong> <?php echo htmlspecialchars($_GET['name'] ?? ''); ?></p>
                <p style="margin: 8px 0; font-size: 15px;"><strong><i class="fa fa-book"></i> BLK & Course:</strong> <?php echo htmlspecialchars($_GET['blk_course'] ?? ''); ?></p>
                <p style="margin: 8px 0; font-size: 15px;"><strong><i class="fa fa-graduation-cap"></i> Year:</strong> <?php echo htmlspecialchars($_GET['year'] ?? ''); ?></p>
            </div>
            
            <!-- Position and Window Info -->
            <div style="display: flex; gap: 15px; margin: 20px 0;">
                <div style="flex: 1; padding: 15px; background: linear-gradient(135deg, #ff8c00, #ffa500); border-radius: 12px; text-align: center; color: white;">
                    <p style="margin: 0; font-size: 12px; opacity: 0.9;">POSITION IN QUEUE</p>
                    <p style="margin: 5px 0; font-size: 28px; font-weight: bold;">#<?php echo intval($_GET['position'] ?? 1); ?></p>
                </div>
                <div style="flex: 1; padding: 15px; background: linear-gradient(135deg, #1a2a4d, #2d4a8d); border-radius: 12px; text-align: center; color: white;">
                    <p style="margin: 0; font-size: 12px; opacity: 0.9;">WINDOW</p>
                    <p style="margin: 5px 0; font-size: 28px; font-weight: bold;"><?php echo intval($_GET['window'] ?? 1); ?></p>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 20px; font-size: 13px; color: #888;">
                <p>Please keep quiet and be patient. <br><strong style="color: #1a2a4d;">Thank you for your understanding!</strong></p>
            </div>
            <div style="display: flex; justify-content: flex-end; margin-top: 25px;">
                <button type="button" class="btn-submit btn-proceed" onclick="showFinalConfirmation()" style="background: linear-gradient(135deg, #1a2a4d, #2d4a8d); padding: 14px 40px; font-size: 16px;">Proceed <i class="fa fa-arrow-right"></i></button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="finalConfirmation">
        <div class="modal-box" style="text-align: center; width: 450px; padding: 60px 50px; border-top: 5px solid #00c853;">
            <button class="close-btn" onclick="closeFinalConfirmation()">&times;</button>
            <div style="width: 90px; height: 90px; background: linear-gradient(135deg, #00c853, #00a844); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px;">
                <i class="fa fa-check" style="color: white; font-size: 40px;"></i>
            </div>
            <h2 style="color: #1a2a4d; font-size: 28px; margin-bottom: 10px;">Thank You!</h2>
            <p style="color: #666; font-size: 16px; line-height: 1.6;">Your ticket has been generated successfully.<br>Please wait for your number on the screen.</p>
            <button type="button" class="btn-submit" onclick="window.location.href='logout.php'" style="background: linear-gradient(135deg, #1a2a4d, #2d4a8d); border-radius: 12px; margin-top: 30px; padding: 14px 50px; font-size: 16px;">Done <i class="fa fa-check"></i></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Help Modal -->
    <div class="modal-overlay" id="helpModal">
        <div class="modal-box" style="width: 450px; text-align: center;">
            <button class="close-btn" onclick="toggleHelpModal()">&times;</button>
            <h3 style="color: #1a237e;">Transaction Selection Help</h3>
            <p style="text-align: left;">1. Click on a transaction button (<b>Enrollment</b>, <b>Assessments</b>, <b>Payments</b>, or <b>Other Concerns</b>).<br>
            2. Fill in your details in the form.<br>
            3. Submit to get your queue ticket.<br>
            4. Wait for your number to be called.</p>
            <button type="button" class="btn-submit" onclick="toggleHelpModal()" style="background: linear-gradient(135deg, #1a2a4d, #2d4a8d);">I See</button>
        </div>
    </div>

    <script>
        function openModal(cat) { 
            document.getElementById('selectedCategory').value = cat;
            document.getElementById('detailsModal').classList.add('active');
        }
        function closeModal() { 
            document.getElementById('detailsModal').classList.remove('active'); 
        }
        
        function closeTicketModal() {
            document.getElementById('ticketModal').classList.remove('active');
        }
        
        function closeFinalConfirmation() {
            document.getElementById('finalConfirmation').classList.remove('active');
        }
        
        // Close modal when clicking outside the modal box - with null checks
        const detailsModal = document.getElementById('detailsModal');
        if (detailsModal) {
            detailsModal.addEventListener('click', function(e) {
                if (e.target === this) closeModal();
            });
        }
        
        const ticketModal = document.getElementById('ticketModal');
        if (ticketModal) {
            ticketModal.addEventListener('click', function(e) {
                if (e.target === this) closeTicketModal();
            });
        }
        
        const finalConfirmation = document.getElementById('finalConfirmation');
        if (finalConfirmation) {
            finalConfirmation.addEventListener('click', function(e) {
                if (e.target === this) closeFinalConfirmation();
            });
        }
        
        const detailsForm = document.getElementById('detailsForm');
        if (detailsForm) {
            detailsForm.onsubmit = function() {
                const blk = document.getElementById('blk_val').value;
                const course = document.getElementById('course_val').value;
                const year = document.getElementById('year_val').value;
                document.getElementById('final_blk_course').value = blk + " - " + course;
                document.getElementById('final_year').value = year;
                return true;
            };
        }

        function showFinalConfirmation() {
            // Get URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const ticket = urlParams.get('ticket');
            const category = urlParams.get('category');
            
            // Redirect to final_thank_you.php with ticket details
            if (ticket) {
                window.location.href = 'final_thank_you.php?ticket=' + ticket + '&category=' + category;
            } else {
                window.location.href = 'login.php';
            }
        }

        function toggleHelpModal() {
            const modal = document.getElementById('helpModal');
            if (modal.classList.contains('active')) {
                modal.classList.remove('active');
            } else {
                modal.classList.add('active');
            }
        }

        // Close help modal when clicking outside
        const helpModal = document.getElementById('helpModal');
        if (helpModal) {
            helpModal.addEventListener('click', function(e) {
                if (e.target === this) toggleHelpModal();
            });
        }

        // Auto-display ticket modal on load
        window.onload = function() {
            if (window.location.search.indexOf('ticket=') > -1) {
                const tm = document.getElementById('ticketModal');
                if (tm) {
                    tm.classList.add('active');
                    // Small delay to ensure print dialog shows after modal is visible
                    setTimeout(function() {
                        window.print();
                    }, 500);
                }
            }
        };
        
        // Close modals with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
                closeTicketModal();
                closeFinalConfirmation();
                const helpModal = document.getElementById('helpModal');
                if (helpModal && helpModal.classList.contains('active')) {
                    toggleHelpModal();
                }
            }
        });
    </script>
    
    <!-- Queue Status Tracker JavaScript -->
    <script>
    // Queue tracker variables
    let currentQueueNumber = null;
    let notificationPermission = Notification.permission;
    let trackerInterval = null;
    let previousStatus = null;
    
    // Initialize tracker if queue number exists in URL
    function initQueueTracker() {
        const urlParams = new URLSearchParams(window.location.search);
        const ticket = urlParams.get('ticket');
        
        if (ticket) {
            currentQueueNumber = ticket;
            document.getElementById('queueTracker').style.display = 'block';
            
            // Start polling
            checkQueueStatus();
            trackerInterval = setInterval(checkQueueStatus, 5000); // Check every 5 seconds
            
            // Check notification permission
            updateNotificationButton();
            
            // Request permission on page load (non-intrusive)
            if (notificationPermission === 'default') {
                // Don't auto-request, let user click button
            }
        }
    }
    
    // Check queue status via API
    function checkQueueStatus() {
        if (!currentQueueNumber) return;
        
        fetch('check_queue_status.php?queue_number=' + currentQueueNumber)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    updateTrackerDisplay(data);
                    
                    // Check for status change to trigger notification
                    if (previousStatus && previousStatus !== data.queue_status) {
                        if (data.queue_status === 'Serving') {
                            showBrowserNotification('Your turn!', 'Window ' + data.window + ' is now serving your number ' + data.queue_number);
                        } else if (data.is_next && !previousStatus === 'Serving') {
                            showBrowserNotification('Almost your turn!', 'You are next in line! Please proceed to Window ' + data.window);
                        }
                    }
                    previousStatus = data.queue_status;
                }
            })
            .catch(error => console.error('Error checking queue status:', error));
    }
    
    // Update tracker display
    function updateTrackerDisplay(data) {
        document.getElementById('trackerQueueNumber').textContent = data.queue_number;
        document.getElementById('trackerPosition').textContent = '#' + data.position;
        document.getElementById('trackerWindow').textContent = data.window;
        document.getElementById('trackerServing').textContent = data.serving_number || '---';
        document.getElementById('trackerType').textContent = data.document_type;
        
        // Status
        const statusEl = document.getElementById('trackerStatus');
        const yourStatusEl = document.getElementById('trackerYourStatus');
        
        if (data.is_serving) {
            statusEl.textContent = 'BEING SERVED';
            statusEl.style.color = '#28a745';
            yourStatusEl.textContent = 'Go to Window ' + data.window + ' now!';
            yourStatusEl.style.color = '#28a745';
        } else if (data.is_next) {
            statusEl.textContent = 'NEXT';
            statusEl.style.color = '#ff8c00';
            yourStatusEl.textContent = 'Be ready! You are next!';
            yourStatusEl.style.color = '#ff8c00';
        } else {
            statusEl.textContent = 'WAITING';
            statusEl.style.color = '#ffc107';
            yourStatusEl.textContent = 'Please wait...';
            yourStatusEl.style.color = '#ffc107';
        }
        
        // Calculate time
        if (data.created_at) {
            const created = new Date(data.created_at);
            const now = new Date();
            const diff = Math.floor((now - created) / 1000 / 60);
            document.getElementById('trackerTime').textContent = diff + ' min';
        }
    }
    
    // Request browser notification permission
    function requestNotificationPermission() {
        if (!('Notification' in window)) {
            alert('This browser does not support notifications. Please use a modern browser.');
            return;
        }
        
        Notification.requestPermission().then(permission => {
            notificationPermission = permission;
            updateNotificationButton();
            
            if (permission === 'granted') {
                showBrowserNotification('Notifications Enabled', 'You will be notified when your turn comes!');
            }
        });
    }
    
    // Update notification button based on permission
    function updateNotificationButton() {
        const btn = document.getElementById('enableNotificationsBtn');
        if (notificationPermission === 'granted') {
            btn.innerHTML = '<i class="fa fa-bell"></i> Notifications On';
            btn.style.background = '#28a745';
            btn.disabled = true;
        } else if (notificationPermission === 'denied') {
            btn.innerHTML = '<i class="fa fa-bell-slash"></i> Blocked';
            btn.style.background = '#dc3545';
            btn.disabled = true;
        }
    }
    
    // Show browser notification
    function showBrowserNotification(title, body) {
        if (notificationPermission === 'granted') {
            const notification = new Notification(title, {
                body: body,
                icon: 'escr-logo.png',
                badge: 'escr-logo.png',
                tag: 'queue-notification',
                requireInteraction: true
            });
            
            notification.onclick = function() {
                window.focus();
                this.close();
            };
        }
    }
    
    // Toggle tracker details
    function toggleTrackerDetails() {
        const details = document.getElementById('trackerDetails');
        const icon = document.getElementById('trackerToggleIcon');
        
        if (details.style.display === 'none') {
            details.style.display = 'block';
            icon.className = 'fa fa-chevron-down';
        } else {
            details.style.display = 'none';
            icon.className = 'fa fa-chevron-up';
        }
    }
    
    // Initialize on page load
    window.addEventListener('load', initQueueTracker);
    
    // Clean up on page unload
    window.addEventListener('beforeunload', function() {
        if (trackerInterval) {
            clearInterval(trackerInterval);
        }
    });
    </script>
</body>
</html>