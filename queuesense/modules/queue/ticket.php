<?php
/**
 * QueueSense — My Ticket (Premium QR Version)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';

$required_role = 'student';
require_once __DIR__ . '/../../includes/auth_check.php';

$db = db_connect();
sync_journey_progress($current_user_id); // Ensure session matches DB reality

$target_queue_id = $_GET['queue_id'] ?? null;

$sql = "SELECT qe.*, qt.name AS queue_name, qt.prefix, qt.color, qt.icon,
               sw.window_label
        FROM queue_entries qe
        JOIN queue_types qt ON qt.id = qe.queue_type_id
        LEFT JOIN service_windows sw ON sw.id = qe.window_id
        WHERE qe.user_id = ? AND qe.status IN ('waiting','serving')
          AND DATE(qe.joined_at) = CURDATE() ";

if ($target_queue_id) {
    $sql .= " AND qe.queue_type_id = ? ";
}

$sql .= " ORDER BY qe.joined_at DESC LIMIT 1";

$stmt = $db->prepare($sql);
if ($target_queue_id) {
    $stmt->bind_param('ii', $current_user_id, $target_queue_id);
} else {
    $stmt->bind_param('i', $current_user_id);
}
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ticket) {
    redirect(BASE_URL . '/modules/queue/status.php');
}

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
    <title><?= h($page_title) ?> — QueueSense</title>
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
        .status-pending { background: #e2e8f0; color: #475569; }
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
        <!-- Added Logo inside printable area -->
        <div class="text-center pt-4 pb-2">
            <img src="<?= BASE_URL ?>/assets/images/bcp_logo.png" height="50">
        </div>

        <div class="ticket-top" style="padding: 20px 30px;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="text-start">
                    <div class="ticket-label" style="color:rgba(255,255,255,0.6); font-size: 0.6rem;">Service Category</div>
                    <h5 class="fw-800 m-0"><?= htmlspecialchars($ticket['queue_name']) ?></h5>
                </div>
                <?php if (isset($_SESSION['journey'])): 
                    $display_step = 1;
                    $step_idx = array_search($ticket['queue_type_id'], $_SESSION['journey']['steps']);
                    if ($step_idx !== false) {
                        $display_step = $step_idx + 1;
                    }
                ?>
                    <span class="badge rounded-pill bg-white px-3 py-2 fw-800" style="font-size:0.65rem; color: #1e2a5e !important; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                        STEP <?= h($display_step) ?> / <?= h($_SESSION['journey']['total_steps']) ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <div class="ticket-id" style="color:white; font-size: 3.5rem; letter-spacing: -1px;"><?= h($ticket['ticket_number']) ?></div>
        </div>

        <div class="ticket-body" style="padding: 25px 30px;">
            <?php if (isset($_SESSION['journey'])): ?>
                <div class="progress mb-4" style="height: 4px; background: #f1f5f9; border-radius: 10px;">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?= ($display_step / $_SESSION['journey']['total_steps']) * 100 ?>%"></div>
                </div>
            <?php endif; ?>

            <div class="qr-wrap" id="qrcode-container" style="margin: 0 0 20px 0; padding: 15px; background: #ffffff; min-height: 160px; display: flex; align-items: center; justify-content: center;">
                <!-- Fixed placeholder for the QR Image -->
                <div id="qrcode-source" style="display:none;"></div>
                <img id="finalQrImg" style="display:none; width: 130px; height: 130px; image-rendering: pixelated;">
            </div>
            
            <div class="row text-start g-3 mb-3">
                <div class="col-6">
                    <div class="ticket-label">Status</div>
                    <?php 
                        $status_class = 'status-waiting';
                        $status_label = 'WAITING';
                        if ($ticket['status'] === 'serving') {
                            $status_class = 'status-serving';
                            $status_label = 'SERVING';
                        } elseif ($ticket['status'] === 'pending') {
                            $status_class = 'status-pending';
                            $status_label = 'RESERVED';
                        }
                    ?>
                    <div class="status-pill <?= $status_class ?>" style="padding: 5px 12px; font-size: 0.75rem;">
                        <?= h($status_label) ?>
                    </div>
                </div>
                <div class="col-6 text-end">
                    <div class="ticket-label">Est. Wait</div>
                    <div class="fw-800 text-primary">~<?= h($est['label']) ?></div>
                </div>
            </div>

            <div class="dashed-line" style="margin: 20px 0;"></div>

            <!-- Next Step Preview (If Journey) -->
            <?php if (isset($_SESSION['journey']) && $_SESSION['journey']['current_step'] < $_SESSION['journey']['total_steps'] - 1): 
                $next_id = $_SESSION['journey']['steps'][$_SESSION['journey']['current_step'] + 1];
                $next_q = $db->query("SELECT name FROM queue_types WHERE id = $next_id")->fetch_assoc();
            ?>
                <div class="p-2 px-3 bg-light rounded-3 text-start d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-right-circle-fill text-primary" style="font-size: 0.9rem;"></i>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between">
                            <span class="ticket-label" style="font-size:0.55rem;">NEXT STOP</span>
                            <span class="fw-800 text-navy" style="font-size:0.75rem;"><?= h($next_q['name']) ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <button class="btn-download" id="downloadBtn" onclick="downloadTicket()">
        <i class="bi bi-download me-2"></i> Save Ticket to Gallery
    </button>
    
    <div class="text-center mt-3">
        <a href="<?= BASE_URL ?>/modules/queue/status.php" class="btn btn-outline-secondary w-100 rounded-pill fw-700 py-3 border-0" style="font-size:0.85rem;">
            <i class="bi bi-arrow-left me-1"></i> BACK TO DASHBOARD
        </a>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
    // Generate QR Code
    const qrSource = document.getElementById("qrcode-source");
    const qrFinalImg = document.getElementById("finalQrImg");
    const qrData = "QS-TICKET-<?= h($ticket['ticket_number']) ?>-<?= h($ticket['id']) ?>";
    
    const qrcode = new QRCode(qrSource, {
        text: qrData,
        width: 256,
        height: 256,
        colorDark : "#1e2a5e",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.H
    });

    // Wait for the library to render, then sync to <img> tag
    setTimeout(() => {
        const canvas = qrSource.querySelector('canvas');
        if (canvas) {
            qrFinalImg.src = canvas.toDataURL("image/png");
            qrFinalImg.style.display = "block";
        }
    }, 500);

    // Enhanced Download Function
    async function downloadTicket() {
        const btn = document.getElementById('downloadBtn');
        const ticket = document.getElementById('printableTicket');
        
        btn.innerHTML = '<span class=\"spinner-border spinner-border-sm me-2\"></span> Processing...';
        btn.disabled = true;

        // Force scroll to top for capture
        window.scrollTo(0,0);

        // Explicitly wait for image decoding
        try {
            await qrFinalImg.decode();
        } catch (e) {
            console.log("Image not decoded yet, continuing anyway...");
        }

        setTimeout(() => {
            html2canvas(ticket, { 
                scale: 2,
                useCORS: true,
                allowTaint: true, // Allow tainted canvas for local base64 images
                backgroundColor: "#ffffff",
                logging: false,
                width: ticket.offsetWidth,
                height: ticket.offsetHeight
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'BCP-Ticket-<?= h($ticket['ticket_number']) ?>.png';
                link.href = canvas.toDataURL("image/png", 1.0);
                link.click();
                
                btn.innerHTML = '<i class=\"bi bi-download me-2\"></i> Save Ticket to Gallery';
                btn.disabled = false;
            }).catch(err => {
                alert("Download failed. Please try again.");
                btn.disabled = false;
            });
        }, 1200);
    }

    // Real-Time Ticket Monitor
    setInterval(() => {
        fetch('<?= BASE_URL ?>/api/get_my_ticket_status.php')
            .then(r => r.json())
            .then(data => {
                // Refresh if status changed, OR if we have a NEW ticket ID (Journey Progressed)
                const currentId = <?= (int)$ticket['id'] ?>;
                if (data.status !== '<?= h($ticket['status']) ?>' || 
                    data.ticket_id === null || 
                    (data.ticket_id !== null && data.ticket_id != currentId)) {
                    location.reload();
                }
            });
    }, 3000); // Check every 3 seconds for snappy feel
</script>

</body>
</html>
