<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LIVE QUEUE MONITOR - ESCR</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="responsive.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #0b162c; color: white; margin: 0; padding: 0; overflow: hidden; }
        
        .layout-container { display: flex; height: 100vh; width: 100vw; }

        /* LEFT SIDE: QUEUE CONTENT (55%) */
        .queue-section { width: 55%; padding: clamp(10px, 2vw, 20px); display: flex; flex-direction: column; box-sizing: border-box; }
        
        .header { display: flex; align-items: center; gap: 20px; background: rgba(255,255,255,0.05); padding: clamp(8px, 2vw, 10px) clamp(15px, 4vw, 30px); border-radius: 15px; margin-bottom: clamp(10px, 3vw, 20px); flex-wrap: wrap; }
        .header img { width: clamp(40px, 8vw, 60px); height: auto; }
        .header h2 { margin: 0; font-size: clamp(16px, 3vw, 24px); }
        .header .live-clock { color: #ff8c00; font-weight: bold; font-size: clamp(12px, 2vw, 16px); }
        
        .monitor-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: clamp(10px, 3vw, 20px); flex-grow: 1; }
        
        .window-card { background: white; color: #1a2a4d; border-radius: 20px; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; border-bottom: 8px solid #ff8c00; padding: clamp(10px, 3vw, 20px); min-height: clamp(120px, 25vh, 200px); box-sizing: border-box; }
        
        .window-label { font-size: clamp(14px, 3vw, 28px); font-weight: bold; background: #1a2a4d; color: white; padding: clamp(3px, 1vw, 5px) clamp(12px, 3vw, 25px); border-radius: 8px; position: absolute; top: 15px; }
        
        .queue-number { font-size: clamp(50px, 15vw, 130px); font-weight: 900; margin-top: 20px; line-height: 1; }
        
        .category-label { font-size: clamp(14px, 3vw, 22px); color: #ff8c00; font-weight: bold; }

        /* MIDDLE SIDE: WAITING QUEUES (20%) */
        .waiting-sidebar { width: 20%; background: #ffffff; border-left: 4px solid #1a2a4d; display: flex; flex-direction: column; padding: clamp(8px, 2vw, 15px); overflow-y: auto; box-sizing: border-box; }
        
        .waiting-header { text-align: center; padding-bottom: 15px; border-bottom: 2px solid #ff8c00; margin-bottom: 15px; }
        .waiting-header h2 { margin: 0; color: #250558; font-size: clamp(18px, 4vw, 32px); font-weight: bold; }
        .waiting-header h3 { margin: 0; color: black; font-size: clamp(18px, 4vw, 32px); font-weight: bold; }
        .waiting-header p { margin: 5px 0 0; color: #0f071f; font-size: clamp(10px, 2vw, 14px); }
        
        .waiting-item { background: #1a2a4d; color: white; padding: clamp(8px, 2vw, 17px) clamp(10px, 3vw, 20px); border-radius: 8px; font-size: clamp(24px, 6vw, 46px); font-weight: bold; margin-bottom: 8px; text-align: center; }
        .waiting-item .num { color: #ff8c00; }
        .no-waiting { color: #999; font-size: clamp(10px, 2vw, 14px); text-align: center; padding: 20px; }

        /* RIGHT SIDE: VIDEO SIDEBAR (25%) */
        .video-sidebar { width: 25%; background: #000; border-left: 4px solid #ff8c00; display: flex; flex-direction: column; }
        
        .video-container { width: 100%; aspect-ratio: 16/9; background: #222; }
        .video-container iframe, .video-container #player { width: 100%; height: 100%; }
        
        .announcement-box { padding: clamp(10px, 3vw, 20px); flex-grow: 1; font-size: clamp(12px, 2.5vw, 20px); background: #1a2a4d; border-top: 4px solid #ff8c00; box-sizing: border-box; overflow-y: auto; }
        .announcement-box h3 { color: #ff8c00; font-size: clamp(14px, 3vw, 20px); margin: 0 0 10px 0; }
        .announcement-box p { margin: 8px 0; font-size: clamp(11px, 2vw, 15px); }

        .footer-ticker { height: clamp(35px, 8vw, 50px); background: #ff8c00; color: #1a2a4d; font-size: clamp(12px, 2.5vw, 22px); font-weight: bold; display: flex; align-items: center; width: 100%; position: absolute; bottom: 0; overflow: hidden; }
        .ticker-content { white-space: nowrap; animation: ticker 20s linear infinite; padding-left: 100%; }
        @keyframes ticker { 0% { transform: translateX(0); } 100% { transform: translateX(-100%); } }

        /* Responsive: Tablet and below */
        @media screen and (max-width: 1024px) {
            .layout-container {
                flex-direction: column;
                height: auto;
                min-height: 100vh;
            }
            
            .queue-section {
                width: 100%;
                min-height: 50vh;
            }
            
            .monitor-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .waiting-sidebar {
                width: 100%;
                flex-direction: row;
                flex-wrap: wrap;
                border-left: none;
                border-top: 4px solid #1a2a4d;
            }
            
            .waiting-header {
                width: 100%;
                text-align: center;
            }
            
            .waiting-item {
                flex: 0 0 auto;
                margin-right: 10px;
            }
            
            .video-sidebar {
                width: 100%;
                border-left: none;
                border-top: 4px solid #ff8c00;
            }
            
            .announcement-box {
                display: none;
            }
            
            .footer-ticker {
                position: relative;
            }
        }

        /* Responsive: Mobile */
        @media screen and (max-width: 600px) {
            .queue-section {
                padding: 10px;
            }
            
            .monitor-grid {
                grid-template-columns: 1fr;
            }
            
            .window-card {
                min-height: 120px;
            }
            
            .queue-number {
                font-size: 60px;
            }
            
            .window-label {
                font-size: 14px;
                padding: 4px 12px;
            }
            
            .category-label {
                font-size: 14px;
            }
            
            .waiting-sidebar {
                padding: 10px;
            }
            
            .waiting-item {
                font-size: 24px;
                padding: 8px 12px;
            }
            
            .video-sidebar {
                display: none;
            }
        }

        /* Responsive: Small mobile */
        @media screen and (max-width: 400px) {
            .header {
                flex-direction: column;
                text-align: center;
            }
            
            .queue-number {
                font-size: 48px;
            }
            
            .footer-ticker {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>

<div class="layout-container">
    <div class="queue-section">
        <div class="header">
            <img src="escr-logo.png" width="60">
            <div>
                <h2 style="margin:0;">ESCR QUEUE SYSTEM</h2>
                <div id="live-clock" style="color:#ff8c00; font-weight:bold;"></div>
            </div>
        </div>

        <div class="monitor-grid" id="queue-display">
            </div>
    </div>

    <!-- WAITING QUEUES SIDEBAR -->
    <div class="waiting-sidebar">
        <div class="waiting-header">
            <h3><i class="fa fa-clock"></i> Next in Line</h3>
            <p>Upcoming queue numbers</p>
        </div>
        <div id="waiting-display"></div>
    </div>

     <div class="video-sidebar">
        <div class="video-container" style="background:#000; display:flex; align-items:center; justify-content:center;position:relative;overflow:hidden;">
            <!-- Animated background display -->
            <div id="video-placeholder" style="width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;background:linear-gradient(135deg, #1a2a4d 0%, #2d4a8d 50%, #1a2a4d 100%);color:white;padding:20px;text-align:center;box-sizing:border-box;position:absolute;top:0;left:0;right:0;bottom:0;">
                <div style="position:absolute;top:0;left:0;right:0;bottom:0;opacity:0.1;">
                    <div style="position:absolute;top:10%;left:10%;width:80px;height:80px;border:2px solid white;border-radius:50%;animation:pulse 2s infinite;"></div>
                    <div style="position:absolute;bottom:20%;right:15%;width:60px;height:60px;border:2px solid white;border-radius:50%;animation:pulse 2s infinite 0.5s;"></div>
                </div>
                <i class="fa fa-graduation-cap" style="font-size:60px;margin-bottom:20px;color:#ff8c00;animation:bounce 2s infinite;"></i>
                <p style="font-size:24px;margin:0;font-weight:bold;">ESCR DQMS</p>
                <p style="font-size:14px;margin:10px 0 0;color:#aaa;">East Systems Colleges of Rizal</p>
                <p style="font-size:12px;margin:20px 0 0;color:#888;">Digital Queue Management System</p>
                <div style="margin-top:30px;display:flex;gap:20px;">
                    <div style="text-align:center;"><i class="fa fa-users" style="font-size:24px;color:#ff8c00;"></i><p style="font-size:10px;margin:5px 0 0;">Fair Queue</p></div>
                    <div style="text-align:center;"><i class="fa fa-clock" style="font-size:24px;color:#ff8c00;"></i><p style="font-size:10px;margin:5px 0 0;">Fast Service</p></div>
                    <div style="text-align:center;"><i class="fa fa-star" style="font-size:24px;color:#ff8c00;"></i><p style="font-size:10px;margin:5px 0 0;">Quality</p></div>
                </div>
            </div>
            <style>
                @keyframes pulse {
                    0%, 100% { transform: scale(1); opacity: 0.5; }
                    50% { transform: scale(1.1); opacity: 1; }
                }
                @keyframes bounce {
                    0%, 100% { transform: translateY(0); }
                    50% { transform: translateY(-10px); }
                }
            </style>
        </div>
        <div class="announcement-box">
            <h3 style="color:#ff8c00;"><i class="fa fa-bullhorn"></i> ESCR News</h3>
            
            <p>• Enrollment for Mid-Year 2026 is now open.</p>
            <p>• Please prepare your Student ID and Assessment form.</p>
            <p>• Free WiFi is available in the student lounge.</p>
            <p>• Operating hours: 8:00 AM - 5:00 PM</p>
        </div>
    </div>
</div>

<div class="footer-ticker">
    <div class="ticker-content">Welcome to East Systems Colleges of Rizal! Kindly check your queue number on the display monitor and proceed to your assigned window. Thank you!</div>
</div>

<script>
    let lastNumbers = {};
    let bellAudioContext = null;
    
    function playBellSound() {
        try {
            // Create or get audio context
            if (!bellAudioContext) {
                bellAudioContext = new (window.AudioContext || window.webkitAudioContext)();
            }
            
            // Handle browser audio policy
            if (bellAudioContext.state === 'suspended') {
                bellAudioContext.resume();
            }
            
            const ctx = bellAudioContext;
            const now = ctx.currentTime;
            
            // Play loud bell with harmonics (like a real desk bell)
            playLoudBell(ctx, now);
            
        } catch(e) {
            console.log('Bell error:', e.message);
        }
    }
    
    function playLoudBell(ctx, startTime) {
        // Main tone - loud and clear (1000Hz)
        var osc1 = ctx.createOscillator();
        var gain1 = ctx.createGain();
        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(1000, startTime); // Main bell frequency
        gain1.gain.setValueAtTime(0, startTime);
        gain1.gain.linearRampToValueAtTime(0.9, startTime + 0.01); // Loud attack
        gain1.gain.exponentialRampToValueAtTime(0.01, startTime + 1.2); // Long decay
        osc1.connect(gain1);
        gain1.connect(ctx.destination);
        osc1.start(startTime);
        osc1.stop(startTime + 1.2);
        
        // Harmonic overtone (stronger bell sound)
        var osc2 = ctx.createOscillator();
        var gain2 = ctx.createGain();
        osc2.type = 'sine';
        osc2.frequency.setValueAtTime(2000, startTime); // 2nd harmonic
        gain2.gain.setValueAtTime(0, startTime);
        gain2.gain.linearRampToValueAtTime(0.4, startTime + 0.01);
        gain2.gain.exponentialRampToValueAtTime(0.01, startTime + 0.8);
        osc2.connect(gain2);
        gain2.connect(ctx.destination);
        osc2.start(startTime);
        osc2.stop(startTime + 0.8);
        
        // Third harmonic for shimmer
        var osc3 = ctx.createOscillator();
        var gain3 = ctx.createGain();
        osc3.type = 'sine';
        osc3.frequency.setValueAtTime(3500, startTime); // 3rd harmonic
        gain3.gain.setValueAtTime(0, startTime);
        gain3.gain.linearRampToValueAtTime(0.2, startTime + 0.005);
        gain3.gain.exponentialRampToValueAtTime(0.01, startTime + 0.4);
        osc3.connect(gain3);
        gain3.connect(ctx.destination);
        osc3.start(startTime);
        osc3.stop(startTime + 0.4);
    }

    function updateClock() {
        document.getElementById('live-clock').innerHTML = new Date().toLocaleTimeString();
    }
    setInterval(updateClock, 1000);

    function announceNumber(num, win) {
        playBellSound();
        if ('speechSynthesis' in window) {
            const msg = new SpeechSynthesisUtterance(`Ticket ${num} proceed to window ${win}`);
            msg.rate = 0.85;
            window.speechSynthesis.speak(msg);
        }
    }

    function fetchQueues() {
        fetch('fetch_monitor_data.php')
            .then(res => {
                if (!res.ok) throw new Error('Network error');
                return res.json();
            })
            .then(data => {
                let html = "";
                let waitingHtml = "";
                
                data.forEach(item => {
                    let isNew = (item.number !== "---" && lastNumbers[item.window] !== item.number);
                    if (isNew) {
                        announceNumber(item.number, item.window);
                        lastNumbers[item.window] = item.number;
                    }
                    
                    // Main queue display
                    html += `
                        <div class="window-card ${isNew ? 'blink' : ''}">
                            <div class="window-label">WINDOW ${item.window}</div>
                            <div class="queue-number">${item.number}</div>
                            <div class="category-label">${item.category}</div>
                        </div>`;
                    
                    // Waiting queue sidebar - just queue numbers
                    if (item.waiting && item.waiting.length > 0) {
                        item.waiting.forEach((wq) => {
                            waitingHtml += `<div class="waiting-item"><span class="num">${wq}</span></div>`;
                        });
                    }
                });
                
                if (waitingHtml === "") {
                    waitingHtml = '<div class="no-waiting">No waiting</div>';
                }
                
                document.getElementById('queue-display').innerHTML = html;
                document.getElementById('waiting-display').innerHTML = waitingHtml;
            })
            .catch(err => {
                console.error('Error fetching queue data:', err);
            });
    }

    setInterval(fetchQueues, 3000);
    fetchQueues();
</script>

</body>
</html>