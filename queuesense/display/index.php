<?php
/**
 * QueueSense — Public Display Board (Multi-Window Version)
 * Shows all active windows and their current tickets.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

$db = db_connect();

// Get all active serving tickets across all windows (MATCH API COLUMNS)
$sql = "SELECT qe.ticket_number, qe.called_at, qe.call_count, sw.window_label, qt.name AS queue_name, qt.color
        FROM queue_entries qe
        JOIN service_windows sw ON sw.id = qe.window_id
        JOIN queue_types qt ON qt.id = qe.queue_type_id
        WHERE qe.status = 'serving'
          AND DATE(qe.joined_at) = CURDATE()
        ORDER BY qe.called_at DESC";
$all_serving = $db->query($sql)->fetch_all(MYSQLI_ASSOC);

$page_title = 'Public Queue Display';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> — QueueSense</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root { --bcp-navy: #1e2a5e; --bcp-blue: #2d3ab0; --bcp-gold: #fbbf24; }
        body { font-family: 'Outfit', sans-serif; background-color: #0f172a; color: white; height: 100vh; overflow: hidden; display: flex; flex-direction: column; }
        
        .display-header { background: var(--bcp-navy); padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 4px solid var(--bcp-gold); }
        
        .display-main { flex: 1; padding: 30px; overflow-y: auto; }
        
        .serving-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px; }
        
        .serving-card {
            background: rgba(255, 255, 255, 0.05); border-radius: 24px; border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 30px; text-align: center; transition: transform 0.3s; position: relative; overflow: hidden;
        }
        .serving-card.new-call { animation: pulse-card 2s infinite; border-color: var(--bcp-gold); }
        @keyframes pulse-card { 0%, 100% { box-shadow: 0 0 0 rgba(251, 191, 36, 0); } 50% { box-shadow: 0 0 30px rgba(251, 191, 36, 0.3); } }
        
        .card-ticket { font-size: 6rem; font-weight: 900; line-height: 1; margin: 15px 0; color: white; text-shadow: 0 4px 10px rgba(0,0,0,0.3); }
        .card-window { font-size: 2rem; font-weight: 800; color: var(--bcp-gold); text-transform: uppercase; }
        .card-dept { font-size: 1rem; opacity: 0.6; font-weight: 600; }

        #callOverlay { 
            display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
            background:rgba(15, 23, 42, 0.98); z-index:9999; flex-direction:column; 
            align-items:center; justify-content:center; text-align:center; backdrop-filter:blur(20px); 
        }

        .display-footer { background: var(--bcp-navy); padding: 10px 40px; font-size: 1rem; border-top: 1px solid rgba(255,255,255,0.1); }
        .ticker { display: inline-block; animation: ticker 40s linear infinite; }
        @keyframes ticker { 0% { transform: translateX(100vw); } 100% { transform: translateX(-100%); } }

        /* New Side Panel Layout */
        .display-content { display: flex; flex: 1; overflow: hidden; }
        .display-main { flex: 3.5; padding: 30px; overflow-y: auto; }
        .display-side { 
            flex: 1; background: rgba(255,255,255,0.02); 
            border-left: 1px solid rgba(255,255,255,0.08); 
            padding: 30px; display: flex; flex-direction: column;
        }
        .side-title { font-size: 1.5rem; font-weight: 800; color: var(--bcp-gold); margin-bottom: 25px; text-transform: uppercase; letter-spacing: 1px; }
        .waiting-item { 
            background: rgba(255,255,255,0.04); border-radius: 16px; padding: 15px 20px; 
            margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;
            border-left: 4px solid var(--bcp-blue);
        }
        .waiting-ticket { font-size: 1.6rem; font-weight: 800; }
        .waiting-dept { font-size: 0.8rem; opacity: 0.6; font-weight: 600; text-transform: uppercase; }
    </style>
</head>
<body>

    <header class="display-header">
        <div class="d-flex align-items-center gap-3">
            <img src="<?= BASE_URL ?>/assets/images/bcp_logo.png" height="50">
            <h3 class="m-0 fw-800">Now Serving</h3>
        </div>
        <div class="text-end">
            <h2 class="m-0 fw-900" id="liveClock">00:00:00</h2>
            <div class="small opacity-50"><?= date('l, F j, Y') ?></div>
        </div>
    </header>

    <div class="display-content">
        <main class="display-main">
            <div class="serving-grid" id="servingGrid">
                <?php foreach ($all_serving as $s): ?>
                <div class="serving-card">
                    <div class="card-dept"><?= $s['queue_name'] ?></div>
                    <div class="card-ticket"><?= $s['ticket_number'] ?></div>
                    <div class="card-window"><?= $s['window_label'] ?></div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($all_serving)): ?>
                <div class="w-100 text-center py-5 opacity-25">
                    <i class="bi bi-info-circle fs-1 d-block mb-3"></i>
                    <h3>Waiting for Next Student</h3>
                </div>
                <?php endif; ?>
            </div>
        </main>

        <aside class="display-side">
            <div class="side-title"><i class="bi bi-hourglass-split me-2"></i> Next in Line</div>
            <div id="waitingList">
                <div class="text-center opacity-25 py-5">
                    <div class="small">Scanning queue...</div>
                </div>
            </div>
        </aside>
    </div>

    <div id="callOverlay">
        <div class="text-warning fw-800 mb-4" style="font-size:3rem; letter-spacing:10px;">NOW CALLING</div>
        <div id="overlayTicket" class="fw-900 text-white" style="font-size:18rem; line-height:1;">R-000</div>
        <div id="overlayWindow" class="fw-800 text-warning" style="font-size:5rem;">WINDOW 1</div>
    </div>

    <footer class="display-footer">
        <div class="ticker">
            Welcome to Bestlink College of the Philippines. Please keep your QR code ready. | 
            Visit the Guidance Office for Counseling. | Admissions are open for Academic Year 2025-2026.
        </div>
    </footer>

    <audio id="callChime" src="https://assets.mixkit.co/active_storage/sfx/1000/1000-preview.mp3"></audio>

    <div id="interactionOverlay" onclick="this.remove()" style="position:fixed;bottom:30px;left:0;width:100%;z-index:9999;cursor:pointer;display:flex;align-items:center;justify-content:center;">
        <div style="background:var(--bcp-gold);color:var(--bcp-navy);padding:12px 30px;border-radius:50px;pointer-events:none;font-weight:800;box-shadow:0 10px 30px rgba(0,0,0,0.5);border:2px solid white;animation: bounce 2s infinite;">
            <i class="bi bi-volume-up-fill me-2"></i> CLICK ANYWHERE TO ACTIVATE SOUND & VOICE
        </div>
    </div>

    <style>
        @keyframes bounce { 0%, 20%, 50%, 80%, 100% {transform: translateY(0);} 40% {transform: translateY(-10px);} 60% {transform: translateY(-5px);} }
    </style>

    <script>
        let lastChecksum = '<?= md5(json_encode($all_serving)) ?>';

        setInterval(() => {
            document.getElementById('liveClock').textContent = new Date().toLocaleTimeString();
        }, 1000);

        let voices = [];
        function loadVoices() {
            voices = window.speechSynthesis.getVoices();
        }
        window.speechSynthesis.onvoiceschanged = loadVoices;
        loadVoices();

        let announcedTickets = new Set();
        let speechQueue = [];
        let isSpeaking = false;

        function announce(ticket, windowLabel, callCount) {
            // Unique key includes callCount so Recalls trigger a new announcement
            const key = `${ticket}-${windowLabel}-${callCount}`;
            if (announcedTickets.has(key)) return;
            announcedTickets.add(key);
            
            speechQueue.push({ ticket, windowLabel });
            processQueue();
        }

        async function processQueue() {
            if (isSpeaking || speechQueue.length === 0) return;
            isSpeaking = true;

            const { ticket, windowLabel } = speechQueue.shift();
            const chime = document.getElementById('callChime');
            const overlay = document.getElementById('callOverlay');
            
            document.getElementById('overlayTicket').textContent = ticket;
            document.getElementById('overlayWindow').textContent = windowLabel;

            // Prepare synthesis
            window.speechSynthesis.cancel(); 
            overlay.style.display = 'flex';
            
            if (chime) {
                chime.pause(); 
                chime.currentTime = 0; 
                try { await chime.play(); } catch(e) { console.warn("Chime blocked"); }
            }
            
            let speechFinished = false;
            let timerFinished = false;

            const checkFinish = () => {
                if (speechFinished && timerFinished) {
                    overlay.style.display = 'none';
                    isSpeaking = false;
                    setTimeout(processQueue, 500); // Small gap between calls
                }
            };

            // Safety timer: Minimum 5 seconds visibility
            setTimeout(() => {
                timerFinished = true;
                checkFinish();
            }, 5000);
            
            setTimeout(() => {
                const msg = new SpeechSynthesisUtterance();
                if (voices.length === 0) loadVoices();
                
                msg.voice = voices.find(v => v.lang.includes('en') && v.name.includes('Female')) || 
                            voices.find(v => v.lang.includes('en')) || 
                            voices[0];
                            
                msg.text = `Now serving, Ticket Number ${ticket.split('').join(' ')}, at ${windowLabel}`;
                msg.rate = 0.85;
                msg.volume = 1;
                
                msg.onend = () => {
                    speechFinished = true;
                    checkFinish();
                };

                msg.onerror = (err) => {
                    console.error("Speech Error:", err);
                    speechFinished = true; // Still mark as finished to allow timer to close it
                    checkFinish();
                };

                window.speechSynthesis.speak(msg);
                
                if (window.speechSynthesis.paused) {
                    window.speechSynthesis.resume();
                }
            }, 1000);
        }

        function updateServingGrid(serving) {
            const grid = document.getElementById('servingGrid');
            const newHTML = serving.length === 0 
                ? `<div class="w-100 text-center py-5 opacity-25"><i class="bi bi-info-circle fs-1 d-block mb-3"></i><h3>Waiting for Next Student</h3></div>`
                : serving.map(s => `
                    <div class="serving-card">
                        <div class="card-dept">${s.queue_name}</div>
                        <div class="card-ticket">${s.ticket_number}</div>
                        <div class="card-window">${s.window_label}</div>
                    </div>
                `).join('');
            
            if (grid.innerHTML !== newHTML) {
                grid.innerHTML = newHTML;
            }
        }

        function updateWaitingList(waiting) {
            const list = document.getElementById('waitingList');
            const newHTML = waiting.length === 0
                ? `<div class="text-center opacity-25 py-5"><div class="small">No one waiting</div></div>`
                : waiting.map(w => `
                    <div class="waiting-item">
                        <div>
                            <div class="waiting-dept">${w.queue_name}</div>
                            <div class="waiting-ticket">${w.ticket_number}</div>
                        </div>
                        <div class="small text-white-50">WAITING</div>
                    </div>
                `).join('');
            
            if (list.innerHTML !== newHTML) {
                list.innerHTML = newHTML;
            }
        }

        setInterval(() => {
            fetch('<?= BASE_URL ?>/api/display_status.php')
                .then(r => r.json())
                .then(data => {
                    if (data.checksum && data.checksum !== lastChecksum) {
                        lastChecksum = data.checksum;
                        
                        if (data.serving) {
                            updateServingGrid(data.serving);
                            data.serving.forEach(s => {
                                announce(s.ticket_number, s.window_label, s.call_count);
                            });
                        }

                        if (data.waiting) {
                            updateWaitingList(data.waiting);
                        }
                    }
                })
                .catch(err => console.error("Poll Error:", err));
        }, 2000);

        // Stuck Prevention: Reset isSpeaking if it takes too long (> 10s)
        setInterval(() => {
            if (isSpeaking && !window.speechSynthesis.speaking) {
                console.warn("Speech synthesis seems stuck. Resetting...");
                isSpeaking = false;
                document.getElementById('callOverlay').style.display = 'none';
                processQueue();
            }
        }, 10000);
    </script>
</body>
</html>
