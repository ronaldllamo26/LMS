<?php
/**
 * QueueSense — My Ticket (Clean BCP SMS style)
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_ticket'])) {
    $stmt = $db->prepare("UPDATE queue_entries SET status='cancelled' WHERE id=? AND user_id=?");
    $stmt->bind_param('ii', $ticket['id'], $current_user_id);
    $stmt->execute();
    $stmt->close();
    log_action('TICKET_CANCELLED', "Ticket {$ticket['ticket_number']} cancelled");
    redirect(BASE_URL . '/modules/queue/status.php');
}

$is_serving = $ticket['status'] === 'serving';
$is_next    = $est['people_ahead'] <= 1 && !$is_serving;
$is_new     = isset($_GET['new']);
$page_title = 'My Ticket';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> — QueueSense</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        body { background: var(--bg-page); }

        .ticket-container { max-width: 440px; margin: 0 auto; }

        .ticket-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .ticket-header {
            background: var(--bcp-navy);
            padding: 20px 24px;
            color: white;
        }

        .ticket-header-label {
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            opacity: 0.6;
            margin-bottom: 4px;
        }

        .ticket-header-name {
            font-size: 1.05rem;
            font-weight: 700;
        }

        .ticket-header-date {
            font-size: 0.72rem;
            opacity: 0.55;
            margin-top: 2px;
        }

        .ticket-dashed { border: none; border-top: 2px dashed var(--border); margin: 0; }

        .ticket-num-section {
            padding: 28px 24px 20px;
            text-align: center;
        }

        .ticket-number {
            font-size: 5rem;
            font-weight: 900;
            color: var(--bcp-navy);
            letter-spacing: -4px;
            line-height: 1;
            display: block;
        }

        .ticket-num-label {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-light);
            margin-top: 6px;
        }

        .now-serving-row {
            margin: 0 24px 20px;
            background: var(--bg-page);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .now-serving-label {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            display: block;
            margin-bottom: 2px;
        }

        .now-serving-val {
            font-size: 1.4rem;
            font-weight: 900;
            color: var(--bcp-navy);
            letter-spacing: -1px;
            line-height: 1;
        }

        .people-ahead-wrap { text-align: right; }
        .people-ahead-num  { font-size: 1.4rem; font-weight: 900; color: var(--text-dark); }
        .people-ahead-lbl  { font-size: 0.68rem; color: var(--text-muted); font-weight: 600; }

        .ticket-progress-wrap { padding: 0 24px 20px; }

        .ticket-info-table { padding: 0 24px 20px; }

        .t-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 9px 0;
            border-bottom: 1px solid var(--border);
            font-size: 0.83rem;
        }

        .t-row:last-child { border-bottom: none; }
        .t-label { color: var(--text-muted); }
        .t-value { font-weight: 600; color: var(--text-dark); }

        .ticket-actions { padding: 0 24px 24px; }

        .alert-called {
            background: #dcfce7;
            border: 1px solid #86efac;
            border-radius: var(--radius-sm);
            padding: 12px 16px;
            font-size: 0.83rem;
            font-weight: 600;
            color: #166534;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }

        .alert-next {
            background: #fef9c3;
            border: 1px solid #fde047;
            border-radius: var(--radius-sm);
            padding: 10px 16px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #854d0e;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }

        .btn-cancel {
            width: 100%;
            padding: 10px;
            background: white;
            border: 1px solid #fca5a5;
            border-radius: var(--radius-sm);
            color: var(--bcp-red);
            font-size: 0.82rem;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-cancel:hover { background: #fef2f2; }
    </style>
</head>
<body>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="d-flex" style="min-height:calc(100vh - 56px);">
<div class="flex-grow-1 p-4">
<div class="ticket-container qs-animate-in">

    <!-- Back -->
    <a href="<?= BASE_URL ?>/modules/queue/status.php"
       class="d-inline-flex align-items-center gap-1 mb-3 text-muted"
       style="font-size:0.82rem;">
        <i class="bi bi-arrow-left"></i> Back to Services
    </a>

    <!-- Success alert -->
    <?php if ($is_new): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3 py-2 px-3"
         style="border-radius:var(--radius-sm); font-size:0.82rem;" id="newAlert">
        <i class="bi bi-check-circle-fill"></i>
        Ticket <strong><?= htmlspecialchars($ticket['ticket_number']) ?></strong> generated successfully.
    </div>
    <?php endif; ?>

    <!-- Status alerts -->
    <?php if ($is_serving): ?>
    <div class="alert-called">
        <i class="bi bi-megaphone-fill"></i>
        <div>
            Your ticket is now being served.
            <?= $ticket['window_label'] ? ' Please go to <strong>' . htmlspecialchars($ticket['window_label']) . '</strong>.' : '' ?>
        </div>
    </div>
    <?php elseif ($is_next): ?>
    <div class="alert-next">
        <i class="bi bi-bell-fill"></i>
        You are next in line. Please be ready.
    </div>
    <?php endif; ?>

    <div class="ticket-card">

        <!-- Header -->
        <div class="ticket-header">
            <div class="ticket-header-label">Queue Ticket</div>
            <div class="ticket-header-name"><?= htmlspecialchars($ticket['queue_name']) ?></div>
            <div class="ticket-header-date">
                Joined: <?= date('M d, Y g:i A', strtotime($ticket['joined_at'])) ?>
            </div>
        </div>

        <hr class="ticket-dashed">

        <!-- Big ticket number -->
        <div class="ticket-num-section">
            <span class="ticket-number"><?= htmlspecialchars($ticket['ticket_number']) ?></span>
            <div class="ticket-num-label">Your Ticket Number</div>
        </div>

        <!-- Now Serving -->
        <div class="now-serving-row">
            <div>
                <span class="now-serving-label">
                    <span class="live-dot" style="width:5px;height:5px;"></span>&nbsp;Now Serving
                </span>
                <div class="now-serving-val" id="nowServing">
                    <?= $now_serving ? htmlspecialchars($now_serving['ticket_number']) : '—' ?>
                </div>
            </div>
            <div class="people-ahead-wrap">
                <div class="people-ahead-num" id="peopleAhead"><?= $est['people_ahead'] ?></div>
                <div class="people-ahead-lbl">ahead</div>
            </div>
        </div>

        <!-- Progress -->
        <div class="ticket-progress-wrap">
            <div class="qs-progress">
                <div class="qs-progress-bar" id="progressBar"
                     style="width:<?= min(95, max(5, $est['people_ahead'] === 0 ? 95 : 20)) ?>%"></div>
            </div>
            <div class="d-flex justify-content-between mt-1" style="font-size:0.7rem; color:var(--text-light);">
                <span>Queue progress</span>
                <span id="estWait">Est. ~<?= $est['label'] ?></span>
            </div>
        </div>

        <hr class="ticket-dashed">

        <!-- Info rows -->
        <div class="ticket-info-table">
            <div class="t-row">
                <span class="t-label"><i class="bi bi-123 me-1"></i>Position</span>
                <span class="t-value">#<?= (int)$ticket['position'] ?></span>
            </div>
            <div class="t-row">
                <span class="t-label"><i class="bi bi-circle-fill me-1"
                    style="font-size:0.45rem;color:<?= $is_serving ? '#059669' : '#f59e0b' ?>"></i>Status</span>
                <span class="t-value" style="color:<?= $is_serving ? '#059669' : '#f59e0b' ?>;">
                    <?= ucfirst($ticket['status']) ?>
                </span>
            </div>
            <?php if ($ticket['priority']): ?>
            <div class="t-row">
                <span class="t-label"><i class="bi bi-shield-check me-1"></i>Lane</span>
                <span class="t-value" style="color:var(--bcp-blue);">Priority (PWD / Senior)</span>
            </div>
            <?php endif; ?>
            <?php if ($ticket['window_label'] && $is_serving): ?>
            <div class="t-row">
                <span class="t-label"><i class="bi bi-grid-1x2 me-1"></i>Window</span>
                <span class="t-value"><?= htmlspecialchars($ticket['window_label']) ?></span>
            </div>
            <?php endif; ?>
            <div class="t-row">
                <span class="t-label"><i class="bi bi-clock me-1"></i>Joined</span>
                <span class="t-value"><?= date('g:i A', strtotime($ticket['joined_at'])) ?></span>
            </div>
        </div>

        <!-- Actions -->
        <div class="ticket-actions">
            <?php if ($ticket['status'] === 'waiting'): ?>
            <form method="POST" onsubmit="return confirm('Cancel your ticket?');">
                <button type="submit" name="cancel_ticket" class="btn-cancel">
                    <i class="bi bi-x-circle me-1"></i> Cancel My Ticket
                </button>
            </form>
            <?php endif; ?>
        </div>

    </div><!-- /.ticket-card -->

    <div class="text-center mt-3 text-muted" style="font-size:0.72rem;">
        <i class="bi bi-arrow-repeat me-1"></i>Auto-refreshing every 5 seconds
    </div>

</div>
</div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<script>
const MY_QUEUE = <?= (int)$ticket['queue_type_id'] ?>;
const MY_TICKET = '<?= htmlspecialchars($ticket['ticket_number']) ?>';

setInterval(() => {
    fetch('<?= BASE_URL ?>/api/get_queue_status.php')
        .then(r => r.json())
        .then(data => {
            if (!data.queues) return;
            const q = data.queues.find(x => x.id === MY_QUEUE);
            if (!q) return;
            document.getElementById('nowServing').textContent  = q.now_serving || '—';
            document.getElementById('estWait').textContent     = 'Est. ~' + q.est_wait_label;
            if (q.now_serving === MY_TICKET) location.reload();
        })
        .catch(() => {});
}, <?= POLL_INTERVAL_MS ?>);

// Dismiss new ticket alert
const newAlert = document.getElementById('newAlert');
if (newAlert) setTimeout(() => {
    newAlert.style.transition = 'opacity .5s';
    newAlert.style.opacity = '0';
    setTimeout(() => newAlert.remove(), 500);
}, 4000);
</script>
</body>
</html>
