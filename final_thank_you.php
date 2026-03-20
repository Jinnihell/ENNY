<?php 
include 'auth_check.php'; // Proteksyon para sa login
include 'db_config.php'; 

// Proteksyon para sa ROLE (Para hindi ma-access ng basta-bastang user ang admin page)
// Changed: Allow students only - this page is shown after getting a ticket
if ($_SESSION['role'] !== 'student') {
    header("Location: login.php?error=unauthorized");
    exit();
}

// Get the ticket number from session or URL
$ticket_number = isset($_GET['ticket']) ? $_GET['ticket'] : (isset($_SESSION['last_ticket']) ? $_SESSION['last_ticket'] : '');
$_SESSION['last_ticket'] = $ticket_number;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Queue Status - ESCR DQMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="responsive.css">
    <style>
        * { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
        }
        
        html, body {
            height: 100%;
        }
        
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #b7ffd8 0%, #e1f5fe 50%, #90caf9 100%);
            min-height: 100vh;
            padding: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Main Container */
        .status-container {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            max-height: 95vh;
        }

        /* Header */
        .status-header {
            background: linear-gradient(135deg, #1a2a4d 0%, #2d4a8d 100%);
            color: white;
            padding: 20px;
            text-align: center;
            flex-shrink: 0;
        }

        .status-header h1 {
            font-size: clamp(20px, 5vw, 26px);
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .status-header p {
            opacity: 0.9;
            font-size: clamp(11px, 2.5vw, 13px);
        }

        /* Notification Alert */
        .notification-alert {
            display: none;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 15px;
            text-align: center;
            font-size: clamp(14px, 3vw, 18px);
            font-weight: bold;
            animation: pulse 1s infinite;
            flex-shrink: 0;
        }

        .notification-alert.show {
            display: block;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        /* Scrollable Content */
        .content-scroll {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        /* Queue Number Display */
        .queue-display {
            text-align: center;
            padding: 20px 10px;
            background: linear-gradient(145deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            margin-bottom: 15px;
        }

        .queue-number {
            font-size: clamp(50px, 18vw, 90px);
            font-weight: 900;
            color: #1a2a4d;
            line-height: 1.1;
        }

        .queue-label {
            font-size: clamp(11px, 2.5vw, 13px);
            color: #666;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 5px;
        }

        .transaction-type {
            display: inline-block;
            background: #ff8c00;
            color: white;
            padding: 6px 18px;
            border-radius: 18px;
            font-weight: bold;
            font-size: clamp(11px, 2.2vw, 13px);
            margin-top: 10px;
        }

        /* Status Grid */
        .status-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }

        .status-box {
            background: linear-gradient(145deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            padding: 15px 10px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .status-box.waiting {
            border-left: 3px solid #ffc107;
        }

        .status-box.next {
            border-left: 3px solid #ff8c00;
        }

        .status-box.serving {
            border-left: 3px solid #28a745;
        }

        .status-box.window {
            border-left: 3px solid #1a2a4d;
        }

        .status-box-label {
            font-size: clamp(9px, 2vw, 11px);
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 3px;
        }

        .status-box-value {
            font-size: clamp(20px, 5vw, 28px);
            font-weight: bold;
            color: #1a2a4d;
        }

        .status-box.waiting .status-box-value { color: #ffc107; }
        .status-box.next .status-box-value { color: #ff8c00; }
        .status-box.serving .status-box-value { color: #28a745; }

        /* Buttons */
        .button-container {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
        }

        .btn {
            flex: 1;
            padding: 12px 10px;
            border: none;
            border-radius: 10px;
            font-size: clamp(12px, 2.5vw, 14px);
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1a2a4d 0%, #2d4a8d 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 42, 77, 0.3);
        }

        .btn-logout {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
        }

        .btn-logout:hover {
            background: linear-gradient(135deg, #5a6268 0%, #495057 100%);
            transform: translateY(-2px);
        }

        /* Footer */
        .footer-note {
            text-align: center;
            padding: 15px 10px;
            color: #888;
            font-size: clamp(10px, 2vw, 12px);
            flex-shrink: 0;
        }

        .footer-note p {
            margin: 3px 0;
        }

        /* Responsive - Tablet */
        @media screen and (max-width: 400px) {
            body {
                padding: 10px;
            }
            
            .status-container {
                border-radius: 15px;
            }
            
            .status-grid {
                grid-template-columns: 1fr;
            }
            
            .button-container {
                flex-direction: column;
            }
        }

        /* Very small screens */
        @media screen and (max-width: 350px) {
            .status-header h1 {
                font-size: 18px;
            }
            
            .queue-number {
                font-size: 45px;
            }
            
            .status-box-value {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>

    <div class="status-container">
        <!-- Notification Alert -->
        <div class="notification-alert" id="notificationAlert">
            <i class="fa fa-bell"></i> YOUR TURN! Go to Window <span id="alertWindow">--</span>
        </div>

        <div class="status-header">
            <h1><i class="fa fa-ticket-alt"></i> Queue Status</h1>
            <p>East Systems Colleges of Rizal</p>
        </div>

        <div class="content-scroll">
            <div class="queue-display">
                <div class="queue-label">Your Queue Number</div>
                <div class="queue-number" id="queueNumber"><?php echo $ticket_number ?: '---'; ?></div>
                <div class="transaction-type" id="transactionType"><?php echo isset($_GET['category']) ? htmlspecialchars($_GET['category']) : '---'; ?></div>
            </div>

            <div class="status-grid">
                <div class="status-box waiting">
                    <div class="status-box-label">Position</div>
                    <div class="status-box-value" id="positionValue">#--</div>
                </div>
                <div class="status-box window">
                    <div class="status-box-label">Window</div>
                    <div class="status-box-value" id="windowValue">--</div>
                </div>
                <div class="status-box waiting" id="statusBox">
                    <div class="status-box-label">Status</div>
                    <div class="status-box-value" id="statusValue">Waiting</div>
                </div>
                <div class="status-box next">
                    <div class="status-box-label">Now Serving</div>
                    <div class="status-box-value" id="servingValue">---</div>
                </div>
            </div>
        </div>

        <div class="button-container">
            <button class="btn btn-primary" onclick="keepPageOpen()">
                <i class="fa fa-refresh"></i> Keep Open
            </button>
            <button class="btn btn-logout" onclick="logout()">
                <i class="fa fa-sign-out-alt"></i> Exit
            </button>
        </div>

        <div class="footer-note">
            <p><i class="fa fa-info-circle"></i> Auto-updates every 5 seconds</p>
            <p>Stay on this page for notifications!</p>
        </div>
    </div>

    <script>
        let currentQueueNumber = '<?php echo $ticket_number; ?>';
        let notificationPermission = Notification.permission;
        let previousStatus = null;

        // Check queue status on load
        function checkQueueStatus() {
            if (!currentQueueNumber) return;

            fetch('check_queue_status.php?queue_number=' + currentQueueNumber)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        updateDisplay(data);
                        
                        // Check for status change
                        if (previousStatus && previousStatus !== data.queue_status) {
                            if (data.queue_status === 'Serving') {
                                showNotification('Your turn!', 'Window ' + data.window + ' is now serving your number ' + data.queue_number);
                            } else if (data.is_next && !previousStatus === 'Serving') {
                                showNotification('Almost your turn!', 'You are next in line! Please proceed to Window ' + data.window);
                            }
                        }
                        previousStatus = data.queue_status;
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Update display
        function updateDisplay(data) {
            document.getElementById('queueNumber').textContent = data.queue_number;
            document.getElementById('positionValue').textContent = '#' + data.position;
            document.getElementById('windowValue').textContent = data.window;
            document.getElementById('servingValue').textContent = data.serving_number || '---';
            document.getElementById('transactionType').textContent = data.document_type;

            const statusBox = document.getElementById('statusBox');
            const statusValue = document.getElementById('statusValue');
            const notificationAlert = document.getElementById('notificationAlert');
            const alertWindow = document.getElementById('alertWindow');

            if (data.is_serving) {
                statusValue.textContent = 'BEING SERVED!';
                statusValue.style.color = '#28a745';
                statusBox.className = 'status-box serving';
                notificationAlert.classList.add('show');
                alertWindow.textContent = data.window;
            } else if (data.is_next) {
                statusValue.textContent = 'NEXT!';
                statusValue.style.color = '#ff8c00';
                statusBox.className = 'status-box next';
                notificationAlert.classList.remove('show');
            } else {
                statusValue.textContent = 'WAITING';
                statusValue.style.color = '#ffc107';
                statusBox.className = 'status-box waiting';
                notificationAlert.classList.remove('show');
            }
        }

        // Request notification permission
        function requestNotificationPermission() {
            if (!('Notification' in window)) return;
            
            Notification.requestPermission().then(permission => {
                notificationPermission = permission;
            });
        }

        // Show browser notification
        function showNotification(title, body) {
            if (notificationPermission === 'granted') {
                new Notification(title, {
                    body: body,
                    icon: 'escr-logo.png',
                    tag: 'queue-notification'
                });
            }
        }

        // Keep page open - request notifications
        function keepPageOpen() {
            requestNotificationPermission();
            alert('Notifications enabled! You will be notified when it\'s your turn.');
        }

        // Logout
        function logout() {
            if (confirm('Are you sure you want to logout? You will lose your queue position.')) {
                window.location.href = 'logout.php';
            }
        }

        // Check status every 5 seconds
        setInterval(checkQueueStatus, 5000);
        
        // Initial check
        checkQueueStatus();
        
        // Request notification permission on page load
        requestNotificationPermission();
    </script>

</body>
</html>
