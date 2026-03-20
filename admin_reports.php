<?php 
include 'auth_check.php'; // Proteksyon para sa login
include 'db_config.php'; 

// Proteksyon para sa ROLE (Para hindi ma-access ng basta-bastang user ang admin page)
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') {
    header("Location: login.php?error=unauthorized");
    exit();
}

// 1. Handle Date Filtering
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// 2. Fetch Total Students Served - Using Prepared Statement
$totalStmt = $conn->prepare("SELECT COUNT(*) as total FROM transaction_history WHERE DATE(served_at) BETWEEN ? AND ?");
$totalStmt->bind_param("ss", $start_date, $end_date);
$totalStmt->execute();
$totalResult = $totalStmt->get_result();
$totalData = $totalResult->fetch_assoc();
$totalServed = $totalData['total'] ?? 0;
$totalStmt->close();

// 3. Fetch Top Performing Window - Using Prepared Statement
$topWindowNum = "N/A";
$topWindowCount = 0;

$windowStmt = $conn->prepare("SELECT window_number, COUNT(*) as count FROM transaction_history WHERE DATE(served_at) BETWEEN ? AND ? GROUP BY window_number ORDER BY count DESC LIMIT 1");
$windowStmt->bind_param("ss", $start_date, $end_date);
$windowStmt->execute();
$windowResult = $windowStmt->get_result();

if ($windowResult && $windowResult->num_rows > 0) {
    $topWindowData = $windowResult->fetch_assoc();
    $topWindowNum = $topWindowData['window_number'];
    $topWindowCount = $topWindowData['count'];
}
$windowStmt->close();

// 4. Fetch Category Breakdown - Using Prepared Statement
$categories = ['Enrollment', 'Assessments', 'Payments', 'Other Concerns'];
$chart_data = [];
foreach ($categories as $cat) {
    $catStmt = $conn->prepare("SELECT COUNT(*) as total FROM transaction_history WHERE transaction_type = ? AND DATE(served_at) BETWEEN ? AND ?");
    $catStmt->bind_param("sss", $cat, $start_date, $end_date);
    $catStmt->execute();
    $catResult = $catStmt->get_result();
    $row = $catResult->fetch_assoc();
    $chart_data[$cat] = $row['total'] ?? 0;
    $catStmt->close();
}

// 5. Fetch Peak Hour Data - Using Prepared Statement
$hour_data = array_fill(8, 10, 0); 
$hourStmt = $conn->prepare("SELECT HOUR(served_at) as hr, COUNT(*) as count FROM transaction_history WHERE DATE(served_at) BETWEEN ? AND ? GROUP BY hr");
$hourStmt->bind_param("ss", $start_date, $end_date);
$hourStmt->execute();
$hourResult = $hourStmt->get_result();
if ($hourResult) {
    while($row = $hourResult->fetch_assoc()) {
        if(isset($hour_data[$row['hr']])) $hour_data[$row['hr']] = $row['count'];
    }
}
$hourStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESCR Analytics Professional</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="responsive.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #b7ffd8, #90caf9); padding: clamp(15px, 4vw, 30px); margin: 0; }
        .header { display: flex; align-items: center; justify-content: space-between; background: white; padding: clamp(10px, 3vw, 15px) clamp(15px, 4vw, 30px); border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); flex-wrap: wrap; gap: 15px; }
        .logo-box { display: flex; align-items: center; gap: 15px; }
        .logo-box img { width: clamp(35px, 8vw, 50px); }
        .logo-box h2 { margin: 0; color: #1a2a4d; font-size: clamp(14px, 3vw, 20px); }
        .logo-box span { font-size: clamp(10px, 2vw, 12px); color: #666; }
        .window-badge { background: #1a2a4d; color: white; padding: 8px 20px; border-radius: 20px; font-weight: bold; font-size: clamp(12px, 2vw, 14px); }
        .back-btn { text-decoration: none; color: #1a2a4d; font-weight: bold; }
        .filter-bar { background: white; padding: clamp(10px, 3vw, 15px); border-radius: 12px; margin-bottom: 20px; display: flex; gap: 15px; align-items: center; box-shadow: 0 4px 10px rgba(0,0,0,0.05); flex-wrap: wrap; }
        .summary-row { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px; }
        .hero-card { background: white; padding: clamp(15px, 4vw, 25px); border-radius: 15px; border-left: 8px solid #1a2a4d; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .hero-card h1 { margin: 0; font-size: clamp(32px, 8vw, 48px); color: #1a2a4d; }
        .hero-card p { margin: 0; color: #666; font-weight: bold; letter-spacing: 1px; font-size: clamp(10px, 2vw, 14px); }
        .top-win-card { background: #1a2a4d; color: white; padding: clamp(15px, 4vw, 25px); border-radius: 15px; text-align: center; }
        .top-win-card p { margin: 0; opacity: 0.8; font-size: clamp(10px, 2vw, 12px); }
        .top-win-card h2 { margin: 5px 0; font-size: clamp(18px, 5vw, 28px); }
        .top-win-card small { font-size: clamp(10px, 2vw, 12px); }
        .chart-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .full-chart { background: white; padding: 20px; border-radius: 15px; margin-bottom: 20px; }
        .full-chart h4 { text-align: center; color: #1a2a4d; margin-top: 0; font-size: clamp(14px, 3vw, 18px); }
        .chart-box { background: white; padding: 20px; border-radius: 15px; min-height: 250px; box-sizing: border-box; }
        .btn-print { background: #00c853; color: white; border: none; padding: 10px 20px; border-radius: 20px; cursor: pointer; font-weight: bold; font-size: clamp(12px, 2vw, 14px); }
        @media print { .filter-bar, .btn-print, .back-btn { display: none; } }

        /* Title section */
        .title-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .title-section h2 {
            color: #1a2a4d;
            margin: 0;
            font-size: clamp(18px, 4vw, 28px);
        }

        /* Responsive adjustments */
        @media screen and (max-width: 1024px) {
            .summary-row {
                grid-template-columns: 1fr;
            }
            
            .chart-row {
                grid-template-columns: 1fr;
            }
        }

        @media screen and (max-width: 768px) {
            .header {
                justify-content: center;
                text-align: center;
            }
            
            .logo-box {
                flex-direction: column;
                gap: 10px;
            }
            
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-bar input,
            .filter-bar button {
                width: 100%;
            }
            
            .title-section {
                flex-direction: column;
                text-align: center;
            }
        }

        @media screen and (max-width: 480px) {
            body {
                padding: 15px 10px;
            }
            
            .hero-card h1 {
                font-size: 32px;
            }
            
            .top-win-card h2 {
                font-size: 24px;
            }
            
            .chart-box {
                padding: 10px;
                min-height: 200px;
            }
        }
    </style>
</head>
<body>
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
            <a href="admin_settings.php" style="text-decoration:none; color:#1a2a4d; font-weight:bold; font-size:14px;"><i class="fa fa-cog"></i> Settings</a>
            <div class="window-badge">Window <?php echo $_SESSION['active_window'] ?? '1'; ?></div>
        </div>
    </div>

    <div class="title-section">
        <h2 style="color:#1a2a4d; margin:0;"><i class="fa fa-chart-line"></i> System Performance</h2>
        <div style="display:flex; gap:10px;">
            <button class="btn-print" onclick="exportPDF()"><i class="fa fa-file-pdf"></i> Export PDF</button>
            <button class="btn-print" onclick="window.print()"><i class="fa fa-print"></i> Print</button>
        </div>
    </div>

    <form class="filter-bar" method="GET">
        <input type="date" name="start_date" value="<?php echo $start_date; ?>">
        <input type="date" name="end_date" value="<?php echo $end_date; ?>">
        <button type="submit" style="background:#1a2a4d; color:white; border:none; padding:8px 15px; border-radius:5px; cursor:pointer;">Filter</button>
    </form>

    <div class="summary-row">
        <div class="hero-card">
            <h1 style="margin:0; font-size:48px; color:#1a2a4d;"><?php echo number_format($totalServed); ?></h1>
            <p style="margin:0; color:#666; font-weight:bold; letter-spacing:1px;">TOTAL STUDENTS SERVED</p>
        </div>
        <div class="top-win-card">
            <p style="margin:0; opacity:0.8;">BEST SERVICE WINDOW</p>
            <h2 style="margin:5px 0;">WINDOW <?php echo $topWindowNum; ?></h2>
            <small><?php echo $topWindowCount; ?> Served</small>
        </div>
    </div>

    <div class="chart-row">
        <div class="chart-box"><canvas id="pieChart"></canvas></div>
        <div class="chart-box"><canvas id="barChart"></canvas></div>
    </div>

    <div class="full-chart">
        <h4 style="text-align:center; color:#1a2a4d; margin-top:0;">Hourly Student Traffic (Peak Hours)</h4>
        <canvas id="lineChart" style="max-height: 250px;"></canvas>
    </div>

    <a href="ReportsAndSettingsMenu.php" class="back-btn" style="text-decoration:none; color:#1a2a4d; font-weight:bold;">← Back</a>

    <script>
        const catLabels = <?php echo json_encode(array_keys($chart_data)); ?>;
        const catVals = <?php echo json_encode(array_values($chart_data)); ?>;
        const hourLabels = ["8AM", "9AM", "10AM", "11AM", "12PM", "1PM", "2PM", "3PM", "4PM", "5PM"];
        const hourVals = <?php echo json_encode(array_values($hour_data)); ?>;
    
        // Create charts
        const pieChart = new Chart(document.getElementById('pieChart'), {
            type: 'pie',
            data: { labels: catLabels, datasets: [{ data: catVals, backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'] }] }
        });

        const barChart = new Chart(document.getElementById('barChart'), {
            type: 'bar',
            data: { labels: catLabels, datasets: [{ label: 'Transactions', data: catVals, backgroundColor: '#1a2a4d' }] }
        });

        const lineChart = new Chart(document.getElementById('lineChart'), {
            type: 'line',
            data: { 
                labels: hourLabels, 
                datasets: [{ 
                    label: 'Students per Hour', 
                    data: hourVals, 
                    borderColor: '#1a2a4d', 
                    backgroundColor: 'rgba(26, 42, 77, 0.1)',
                    fill: true,
                    tension: 0.4 
                }] 
            },
            options: { scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });
        
        // PDF Export Function - Fixed for charts
        async function exportPDF() {
            // Show loading indicator
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Generating...';
            btn.disabled = true;
            
            try {
                // Convert canvas to images
                const canvas1 = document.getElementById('pieChart');
                const canvas2 = document.getElementById('barChart');
                const canvas3 = document.getElementById('lineChart');
                
                // Create image URLs from canvases
                const imgPie = canvas1.toDataURL('image/png');
                const imgBar = canvas2.toDataURL('image/png');
                const imgLine = canvas3.toDataURL('image/png');
                
                // Create a new element with images instead of canvas
                const container = document.createElement('div');
                container.innerHTML = `
                    <div style="padding:20px; font-family: Arial, sans-serif;">
                        <h1 style="text-align:center; color:#1a2a4d;">ESCR System Performance Report</h1>
                        <p style="text-align:center; color:#666;">Date: <?php echo date('Y-m-d'); ?></p>
                        <hr>
                        <div style="display:flex; justify-content:space-around; margin:20px 0;">
                            <div style="text-align:center;">
                                <h2 style="color:#1a2a4d;"><?php echo number_format($totalServed); ?></h2>
                                <p>Total Students Served</p>
                            </div>
                            <div style="text-align:center; background:#1a2a4d; color:white; padding:15px; border-radius:10px;">
                                <h2 style="margin:0;">WINDOW <?php echo $topWindowNum; ?></h2>
                                <p style="margin:5px 0 0;">Best Window (<?php echo $topWindowCount; ?> served)</p>
                            </div>
                        </div>
                        <h3 style="color:#1a2a4d;">Category Breakdown</h3>
                        <img src="${imgPie}" style="max-width:45%; float:left; margin:10px;">
                        <img src="${imgBar}" style="max-width:45%; float:right; margin:10px;">
                        <div style="clear:both;"></div>
                        <h3 style="color:#1a2a4d;">Hourly Traffic</h3>
                        <img src="${imgLine}" style="max-width:100%; margin:10px 0;">
                    </div>
                `;
                
                // Generate PDF
                const opt = {
                    margin: 10,
                    filename: 'ESCR_Report_<?php echo date("Y-m-d"); ?>.pdf',
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2 },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
                };
                
                await html2pdf().set(opt).from(container).save();
                
            } catch (e) {
                alert('Error generating PDF: ' + e.message);
                console.error(e);
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>