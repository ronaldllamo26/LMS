<?php
/**
 * QueueSense — Staff QR Scanner (Professional Edition)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

$required_role = 'staff';
require_once __DIR__ . '/../includes/auth_check.php';

$active_page = 'scanner';
$page_title = 'Verification Kiosk';
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
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        :root { --bcp-navy: #1e2a5e; --bcp-gold: #fbbf24; }
        body { background: #f1f5f9; font-family: 'Outfit', sans-serif; }
        
        .kiosk-wrapper { max-width: 550px; margin: 40px auto; }
        
        .scanner-box {
            background: #000; border-radius: 40px; position: relative;
            overflow: hidden; box-shadow: 0 25px 50px rgba(0,0,0,0.2);
            border: 5px solid white; aspect-ratio: 1/1;
        }

        /* Scan Line Animation */
        .scan-line {
            position: absolute; top: 0; left: 0; width: 100%; height: 5px;
            background: var(--bcp-gold); box-shadow: 0 0 20px var(--bcp-gold);
            z-index: 10; animation: scanLine 3s infinite ease-in-out;
        }
        @keyframes scanLine { 0%, 100% { top: 5%; } 50% { top: 95%; } }

        .scan-target {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            width: 200px; height: 200px; border: 2px solid rgba(255,255,255,0.3);
            border-radius: 30px; z-index: 5;
        }

        .manual-section {
            background: white; border-radius: 25px; padding: 30px;
            margin-top: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        /* Result Modal Styles */
        .res-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(30, 42, 94, 0.9); z-index: 9999; 
            align-items: center; justify-content: center; backdrop-filter: blur(10px);
        }
        .res-overlay.active { display: flex; }
        
        .res-card {
            background: white; width: 90%; max-width: 400px; border-radius: 35px;
            padding: 40px 30px; text-align: center; position: relative;
            animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        @keyframes popIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }

        .res-ticket { font-size: 6rem; font-weight: 900; color: var(--bcp-navy); line-height: 1; margin: 15px 0; }
        .res-status { display: inline-block; padding: 10px 25px; border-radius: 100px; font-weight: 800; font-size: 0.9rem; }
    </style>
</head>
<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <main class="flex-grow-1 p-4">
        <div class="kiosk-wrapper">
            <div class="text-center mb-4">
                <h2 class="fw-900 text-navy mb-1">VERIFICATION KIOSK</h2>
                <span class="badge bg-success px-3 py-2 rounded-pill">SCANNER ACTIVE</span>
            </div>

            <!-- Professional Scanner Box -->
            <div class="scanner-box">
                <div id="reader"></div>
                <div class="scan-line"></div>
                <div class="scan-target"></div>
            </div>

            <!-- Professional Manual Entry -->
            <div class="manual-section">
                <label class="fw-800 text-muted small mb-2 text-uppercase" style="letter-spacing:1px">Manual Ticket Entry</label>
                <div class="input-group">
                    <input type="text" id="manualId" class="form-control form-control-lg rounded-start-pill border-2" 
                           placeholder="Enter Ticket (e.g. C-011)" style="font-weight:700">
                    <button class="btn btn-warning px-4 rounded-end-pill fw-900" onclick="verifyManual()">VERIFY</button>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Professional Verification Result Overlay -->
<div id="resultOverlay" class="res-overlay">
    <div class="res-card">
        <div class="text-muted fw-800 text-uppercase small" style="letter-spacing:2px">Verification Result</div>
        <div id="resTicket" class="res-ticket">---</div>
        <div id="resName" class="fs-3 fw-800 mb-1 text-navy">---</div>
        <div id="resQueue" class="text-muted fw-600 mb-4">Registrar Queue</div>
        
        <div id="resStatus" class="res-status bg-success text-white mb-4">VALID TICKET</div>
        
        <button class="btn btn-navy w-100 py-3 rounded-4 fw-900 shadow-sm" onclick="closeResult()">
            DONE & NEXT SCAN
        </button>
    </div>
</div>

<audio id="scanSound" src="https://assets.mixkit.co/active_storage/sfx/2568/2568-preview.mp3"></audio>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    const html5QrCode = new Html5Qrcode("reader");
    const sound = document.getElementById('scanSound');
    const qrConfig = { fps: 15, qrbox: { width: 250, height: 250 } };

    function verifyManual() {
        const id = document.getElementById('manualId').value;
        if (!id) return;
        
        fetch(`../api/verify_ticket.php?id=${id}`)
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }
                showResult(data);
            });
    }

    const onScanSuccess = (decodedText) => {
        if (!decodedText.startsWith('QS-TICKET')) return;
        sound.play();
        html5QrCode.stop();
        const parts = decodedText.split('-');
        const ticketId = parts[4];
        fetch(`../api/verify_ticket.php?id=${ticketId}`)
            .then(r => r.json())
            .then(data => {
                if (data.error) { alert(data.error); resetScanner(); return; }
                showResult(data);
            });
    };

    function showResult(data) {
        document.getElementById('resTicket').textContent = data.ticket_number;
        document.getElementById('resName').textContent = data.full_name;
        document.getElementById('resQueue').textContent = data.queue_name;
        
        const statusBadge = document.getElementById('resStatus');
        statusBadge.textContent = data.status.toUpperCase();
        statusBadge.className = 'res-status ' + (data.status === 'serving' ? 'bg-primary' : 'bg-success');

        document.getElementById('resultOverlay').classList.add('active');
    }

    function closeResult() {
        document.getElementById('resultOverlay').classList.remove('active');
        document.getElementById('manualId').value = '';
        startScanner();
    }

    function startScanner() {
        html5QrCode.start({ facingMode: "environment" }, qrConfig, onScanSuccess)
        .catch(err => { console.log("No camera."); });
    }

    startScanner();
</script>

</body>
</html>
