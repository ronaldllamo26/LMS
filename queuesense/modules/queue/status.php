<?php
/**
 * QueueSense — Queue Selection Page (Student)
 * BCP SMS style: clean, minimalist, no emojis.
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';

$required_role = 'student';
require_once __DIR__ . '/../../includes/auth_check.php';

$db = db_connect();

// Live queue stats
$sql = "SELECT
            qt.id, qt.name, qt.description, qt.prefix, qt.icon, qt.color, qt.is_open,
            (SELECT ticket_number FROM queue_entries
             WHERE queue_type_id = qt.id AND status = 'serving'
               AND DATE(joined_at) = CURDATE()
             ORDER BY called_at DESC LIMIT 1) AS now_serving,
            (SELECT COUNT(*) FROM queue_entries
             WHERE queue_type_id = qt.id AND status = 'waiting'
               AND DATE(joined_at) = CURDATE()) AS waiting_count,
            (SELECT ticket_number FROM queue_entries
             WHERE queue_type_id = qt.id AND user_id = ?
               AND status IN ('waiting','serving')
               AND DATE(joined_at) = CURDATE()
             LIMIT 1) AS my_ticket,
            (SELECT COUNT(*) FROM service_windows
             WHERE queue_type_id = qt.id AND status = 'open') AS open_windows
        FROM queue_types qt
        ORDER BY qt.sort_order ASC, qt.id ASC";

$stmt = $db->prepare($sql);
$stmt->bind_param('i', $current_user_id);
$stmt->execute();
$queues = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Active ticket anywhere
$stmt = $db->prepare("SELECT qe.*, qt.name AS queue_name, qt.color
                      FROM queue_entries qe
                      JOIN queue_types qt ON qt.id = qe.queue_type_id
                      WHERE qe.user_id = ? AND qe.status IN ('waiting','serving')
                        AND DATE(qe.joined_at) = CURDATE()
                      LIMIT 1");
$stmt->bind_param('i', $current_user_id);
$stmt->execute();
$active_ticket = $stmt->get_result()->fetch_assoc();
$stmt->close();

$active_page = 'queue';
$page_title  = 'Queue Services';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> — QueueSense</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .queue-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
            gap: 16px;
        }

        .queue-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 0;
            transition: var(--transition);
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .queue-card:hover {
            border-color: var(--bcp-indigo);
            box-shadow: var(--shadow);
            color: inherit;
        }

        .queue-card-top {
            height: 4px;
        }

        .queue-card-body {
            padding: 18px 20px;
            flex: 1;
        }

        .queue-card-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 3px;
        }

        .queue-card-desc {
            font-size: 0.76rem;
            color: var(--text-muted);
            margin-bottom: 16px;
            line-height: 1.5;
        }

        .queue-card-footer {
            background: #f8fafc;
            border-top: 1px solid var(--border);
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .q-stat { font-size: 0.76rem; color: var(--text-muted); }
        .q-stat strong { color: var(--text-dark); font-weight: 700; }

        .queue-icon {
            width: 40px; height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            margin-bottom: 14px;
            flex-shrink: 0;
        }

        .active-ticket-bar {
            background: var(--bcp-navy);
            border-radius: var(--radius);
            padding: 16px 20px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
        }

        .active-ticket-num {
            font-size: 1.8rem;
            font-weight: 900;
            letter-spacing: -2px;
            line-height: 1;
        }

        .queue-card.disabled-card {
            opacity: 0.5;
            pointer-events: none;
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="d-flex" style="min-height:calc(100vh - 56px);">

    <!-- No sidebar for students — clean layout -->
    <div class="flex-grow-1 p-4" style="max-width:1100px; margin:0 auto; width:100%;">

        <!-- Page Header -->
        <div class="qs-page-header d-flex align-items-center justify-content-between">
            <div>
                <h1 class="qs-page-title">Available Services</h1>
                <p class="qs-page-sub">
                    <?= date('l, F j, Y') ?> &mdash;
                    <?= htmlspecialchars($current_user['department'] ?? 'BCP Student') ?>
                </p>
            </div>
            <div style="font-size:0.78rem; color:var(--text-muted);">
                <span class="live-dot"></span>&nbsp;Live updates every 5s
            </div>
        </div>

        <!-- Active Ticket Bar -->
        <?php if ($active_ticket): ?>
        <div class="active-ticket-bar qs-animate-in">
            <div class="d-flex align-items-center gap-4">
                <div>
                    <div style="font-size:0.65rem; font-weight:700; letter-spacing:0.6px;
                                text-transform:uppercase; opacity:0.6; margin-bottom:3px;">
                        Active Ticket
                    </div>
                    <div class="active-ticket-num"><?= htmlspecialchars($active_ticket['ticket_number']) ?></div>
                </div>
                <div style="width:1px; background:rgba(255,255,255,0.15); height:40px;"></div>
                <div>
                    <div style="font-size:0.8rem; font-weight:600;"><?= htmlspecialchars($active_ticket['queue_name']) ?></div>
                    <div style="font-size:0.72rem; opacity:0.65;">Status: <?= ucfirst($active_ticket['status']) ?></div>
                </div>
            </div>
            <a href="<?= BASE_URL ?>/modules/queue/ticket.php"
               style="background:rgba(255,255,255,0.12); color:white; border:1px solid rgba(255,255,255,0.2);
                      border-radius:var(--radius-sm); padding:7px 14px; font-size:0.78rem; font-weight:600;
                      white-space:nowrap; text-decoration:none;">
                <i class="bi bi-ticket-perforated me-1"></i> View Ticket
            </a>
        </div>
        <?php endif; ?>

        <!-- Queue Grid -->
        <div class="queue-grid">
            <?php foreach ($queues as $q):
                $is_open  = (bool)$q['is_open'];
                $has_mine = !empty($q['my_ticket']);
                $est      = predict_wait_time($q['id'], (int)$q['waiting_count'] + 1);
                $blocked  = $active_ticket && !$has_mine;

                // Icon bg tints
                $tints = [
                    '#1a237e' => ['bg'=>'#e8eaf6', 'color'=>'#1a237e'],
                    '#c62828' => ['bg'=>'#fce4e4', 'color'=>'#c62828'],
                    '#059669' => ['bg'=>'#d1fae5', 'color'=>'#065f46'],
                    '#7c3aed' => ['bg'=>'#ede9fe', 'color'=>'#5b21b6'],
                    '#b45309' => ['bg'=>'#fef3c7', 'color'=>'#92400e'],
                ];
                $tint = $tints[$q['color']] ?? $tints['#1a237e'];

                $href = $has_mine
                    ? BASE_URL . '/modules/queue/ticket.php'
                    : (!$active_ticket && $is_open ? BASE_URL . '/modules/queue/join.php?queue_id=' . $q['id'] : null);
            ?>
            <<?= $href ? 'a href="' . $href . '"' : 'div' ?>
                class="queue-card <?= (!$is_open || $blocked) && !$has_mine ? 'disabled-card' : '' ?>">

                <div class="queue-card-top" style="background:<?= $q['color'] ?>;"></div>

                <div class="queue-card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="queue-icon"
                             style="background:<?= $tint['bg'] ?>; color:<?= $tint['color'] ?>;">
                            <i class="bi <?= htmlspecialchars($q['icon']) ?>"></i>
                        </div>
                        <?php if ($has_mine): ?>
                            <span class="qs-badge" style="background:#dbeafe; color:#1e40af;">
                                <i class="bi bi-ticket-perforated"></i> <?= htmlspecialchars($q['my_ticket']) ?>
                            </span>
                        <?php elseif ($is_open): ?>
                            <span class="qs-badge qs-badge-open">
                                <span class="live-dot" style="width:5px;height:5px;"></span> Open
                            </span>
                        <?php else: ?>
                            <span class="qs-badge qs-badge-closed">Closed</span>
                        <?php endif; ?>
                    </div>

                    <div class="queue-card-title"><?= htmlspecialchars($q['name']) ?></div>
                    <div class="queue-card-desc"><?= htmlspecialchars($q['description']) ?></div>

                    <div class="d-flex gap-3">
                        <div class="q-stat">
                            <i class="bi bi-person-check me-1"></i>
                            Serving: <strong><?= $q['now_serving'] ? htmlspecialchars($q['now_serving']) : '—' ?></strong>
                        </div>
                        <div class="q-stat">
                            <i class="bi bi-hourglass-split me-1"></i>
                            Wait: <strong><?= (int)$q['waiting_count'] > 0 ? '~' . $est['label'] : 'No wait' ?></strong>
                        </div>
                    </div>
                </div>

                <div class="queue-card-footer">
                    <span class="q-stat">
                        <i class="bi bi-people me-1"></i>
                        <strong><?= (int)$q['waiting_count'] ?></strong> in queue
                    </span>
                    <?php if ($href && !$has_mine): ?>
                    <span style="font-size:0.76rem; font-weight:600; color:var(--bcp-indigo);">
                        Join <i class="bi bi-arrow-right ms-1"></i>
                    </span>
                    <?php elseif ($has_mine): ?>
                    <span style="font-size:0.76rem; font-weight:600; color:var(--bcp-blue);">
                        View Ticket <i class="bi bi-arrow-right ms-1"></i>
                    </span>
                    <?php endif; ?>
                </div>

            </<?= $href ? 'a' : 'div' ?>>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<script>
setInterval(() => {
    fetch('<?= BASE_URL ?>/api/get_queue_status.php')
        .then(r => r.json())
        .then(data => {
            if (!data.queues) return;
            // Silent live update handled server-side on next poll refresh
        })
        .catch(() => {});
}, <?= POLL_INTERVAL_MS ?>);
</script>
</body>
</html>
