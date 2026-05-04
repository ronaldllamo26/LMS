<?php
/**
 * QueueSense — My Ticket (Premium QR Version)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';

$required_role = 'student';
require_once __DIR__ . '/../../includes/auth_check.php';

$db = db_connect();

$sql = "SELECT qe.*, qt.name AS queue_name, qt.prefix, qt.color, qt.icon,
               sw.window_label
        FROM queue_entries qe
        JOIN queue_types qt ON qt.id = qe.queue_type_id
        LEFT JOIN service_windows sw ON sw.id = qe.window_id
        WHERE qe.user_id = ? AND qe.status IN ('waiting','serving')
          AND DATE(qe.joined_at) = CURDATE()
        ORDER BY qe.joined_at DESC LIMIT 1";

$stmt = $db->prepare($sql);
$stmt->bind_param('i', $current_user_id);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ticket) redirect(BASE_URL . '/modules/queue/status.php');

$est = predict_wait_time($ticket['queue_type_id'], $ticket['position']);

$stmt = $db->prepare("SELECT ticket_number FROM queue_entries
                      WHERE queue_type_id = ? AND status = 'serving'
                        AND DATE(joined_at) = CURDATE()
                      ORDER BY called_at DESC LIMIT 1");
$stmt->bind_param('i', $ticket['queue_type_id']);
$stmt->execute();
$now_serving = $stmt->get_result()->fetch_assoc();
$stmt->close();

$is_serving = $ticket['status'] === 'serving';
$page_title = 'My Digital Ticket';
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
        body { background: #f1f5f9; font-family: 'Outfit', sans-serif; }
        
        .ticket-wrapper { max-width: 400px; margin: 40px auto; padding: 0 20px; }
        
        .ticket-main {
            background: white; border-radius: 30px; overflow: hidden;
            box-shadow: 0 20px 50px rgba(0,0,0,0.1); position: relative;
        }

        /* Perforated Edge Effect */
        .ticket-main::before, .ticket-main::after {
            content: ''; position: absolute; left: -15px; top: 70%;
            width: 30px; height: 30px; background: #f1f5f9; border-radius: 50%; z-index: 10;
        }
        .ticket-main::after { left: auto; right: -15px; }

        .ticket-top { background: var(--bcp-navy); padding: 30px; text-align: center; color: white; }
        .ticket-body { padding: 40px 30px; text-align: center; }
        
        .ticket-id { font-size: 5rem; font-weight: 900; color: var(--bcp-navy); line-height: 1; letter-spacing: -2px; }
        .ticket-label { text-transform: uppercase; font-weight: 800; letter-spacing: 2px; color: #94a3b8; font-size: 0.75rem; }
        
        .qr-wrap { 
            background: #f8fafc; padding: 20px; border-radius: 20px; 
            display: inline-block; margin: 25px 0; border: 2px dashed #e2e8f0;
        }
        
        .status-pill {
            display: inline-block; padding: 8px 20px; border-radius: 100px;
            font-weight: 700; font-size: 0.85rem; margin-top: 10px;
        }
        .status-waiting { background: #fef3c7; color: #92400e; }
        .status-serving { background: #dcfce7; color: #166534; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.6; } 100% { opacity: 1; } }

        .btn-download {
            width: 100%; padding: 15px; border-radius: 15px; border: none;
            background: var(--bcp-navy); color: white; font-weight: 700;
            margin-top: 20px; transition: 0.3s;
        }
        .btn-download:hover { background: var(--bcp-blue); transform: translateY(-2px); }

        .dashed-line { border-top: 2px dashed #e2e8f0; margin: 30px 0; }
    </style>
</head>
<body>

<div class="ticket-wrapper">
    <div class="text-center mb-4">
        <img src="<?= BASE_URL ?>/assets/images/bcp_logo.png" height="60" class="mb-2">
        <h5 class="fw-800 text-navy">QueueSense Student</h5>
    </div>

    <div id="printableTicket" class="ticket-main">
        <div class="ticket-top">
            <div class="ticket-label" style="color:rgba(255,255,255,0.6)">Service Category</div>
            <h3 class="fw-800 m-0"><?= htmlspecialchars($ticket['queue_name']) ?></h3>
            <div class="small mt-1 opacity-75"><?= date('F d, Y') ?></div>
        </div>

        <div class="ticket-body">
            <div class="ticket-label">Your Ticket Number</div>
            <div class="ticket-id"><?= $ticket['ticket_number'] ?></div>
            
            <div class="qr-wrap" id="qrcode"></div>
            
            <div class="d-flex justify-content-between text-start mb-2">
                <div>
                    <div class="ticket-label">Status</div>
                    <div class="status-pill <?= $is_serving ? 'status-serving' : 'status-waiting' ?>">
                        <?= $is_serving ? 'NOW SERVING' : 'WAITING IN LINE' ?>
                    </div>
                </div>
                <div class="text-end">
                    <div class="ticket-label">Position</div>
                    <div class="fw-900 fs-4">#<?= $ticket['position'] ?></div>
                </div>
            </div>

            <div class="dashed-line"></div>

            <div class="row text-start g-3">
                <div class="col-6">
                    <div class="ticket-label">Time Joined</div>
                    <div class="fw-700"><?= date('g:i A', strtotime($ticket['joined_at'])) ?></div>
                </div>
                <div class="col-6 text-end">
                    <div class="ticket-label">Est. Wait</div>
                    <div class="fw-700 text-primary">~<?= $est['label'] ?></div>
                </div>
            </div>
        </div>
    </div>

    <button class="btn-download" onclick="downloadTicket()">
        <i class="bi bi-download me-2"></i> Save Ticket to Gallery
    </button>
    
    <div class="text-center mt-4">
        <a href="<?= BASE_URL ?>/modules/queue/status.php" class="text-decoration-none text-muted small fw-600">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
    // Generate QR Code
    const qrData = "QS-TICKET-<?= $ticket['ticket_number'] ?>-<?= $ticket['id'] ?>";
    new QRCode(document.getElementById("qrcode"), {
        text: qrData,
        width: 150,
        height: 150,
        colorDark : "#1e2a5e",
        colorLight : "#f8fafc",
        correctLevel : QRCode.CorrectLevel.H
    });

    // Download Function
    function downloadTicket() {
        const ticket = document.getElementById('printableTicket');
        html2canvas(ticket, { scale: 3 }).then(canvas => {
            const link = document.createElement('a');
            link.download = 'BCP-Ticket-<?= $ticket['ticket_number'] ?>.png';
            link.href = canvas.toDataURL();
            link.click();
        });
    }

    // Auto Refresh Check
    setInterval(() => {
        fetch('<?= BASE_URL ?>/api/get_queue_status.php')
            .then(r => r.json())
            .then(data => {
                const q = data.queues.find(x => x.id == <?= $ticket['queue_type_id'] ?>);
                if (q && q.now_serving === '<?= $ticket['ticket_number'] ?>') {
                    location.reload();
                }
            });
    }, 5000);
</script>

</body>
</html>
