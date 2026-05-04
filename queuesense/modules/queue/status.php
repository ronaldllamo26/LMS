<?php
/**
 * QueueSense — Premium Queue Selection (Student)
 * BCP SMS style: Advanced UI with AI Insights.
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
$page_title  = 'Available Services';

// Greeting Logic
$hour = date('H');
$greeting = "Good Morning";
if ($hour >= 12 && $hour < 17) $greeting = "Good Afternoon";
if ($hour >= 17) $greeting = "Good Evening";
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
        .hero-banner {
            background: linear-gradient(135deg, var(--bcp-navy) 0%, #2d3ab0 100%);
            border-radius: var(--radius-lg);
            padding: 32px 36px;
            color: white;
            position: relative;
            overflow: hidden;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(30, 42, 94, 0.2);
        }
        .hero-banner::before {
            content: ''; position: absolute; top: -50px; right: -50px;
            width: 200px; height: 200px; border-radius: 50%;
            background: rgba(255,255,255,0.05);
        }
        .hero-title { font-size: 1.8rem; font-weight: 800; letter-spacing: -0.5px; }
        .hero-sub { opacity: 0.8; font-weight: 500; font-size: 0.95rem; }

        .ai-recommendation {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: var(--radius);
            padding: 16px 20px;
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .queue-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .premium-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 24px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }

        .premium-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.06);
            border-color: var(--bcp-indigo);
        }

        .card-icon-box {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 18px;
            transition: transform 0.3s ease;
        }
        .premium-card:hover .card-icon-box { transform: scale(1.1) rotate(-5deg); }

        .card-tag {
            position: absolute; top: 24px; right: 24px;
            font-size: 0.65rem; font-weight: 700; text-transform: uppercase;
            padding: 4px 10px; border-radius: 20px; letter-spacing: 0.5px;
        }

        .card-stat-row {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 12px; margin-top: auto;
            padding-top: 18px; border-top: 1px solid #f1f5f9;
        }

        .stat-item { display: flex; flex-direction: column; }
        .stat-label { font-size: 0.65rem; color: var(--text-light); text-transform: uppercase; font-weight: 700; margin-bottom: 2px; }
        .stat-value { font-size: 0.95rem; font-weight: 700; color: var(--text-dark); }

        .disabled-card { opacity: 0.6; filter: grayscale(0.5); pointer-events: none; }
        
        .active-ticket-mini {
            background: #fdf2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 10px 16px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center; gap: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<main class="flex-grow-1 p-4" style="max-width:1200px; margin:0 auto; width:100%;">

    <!-- Hero Section -->
    <div class="hero-banner qs-animate-in">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="hero-title"><?= $greeting ?>, <?= explode(' ', $current_user['full_name'])[0] ?>!</h1>
                <p class="hero-sub mb-0">Select a service below to join the smart queue. We'll notify you when it's your turn.</p>
                
                <!-- AI Smart Recommendation -->
                <?php 
                    $busy_queues = array_filter($queues, fn($q) => $q['waiting_count'] > 5);
                    $quiet_queues = array_filter($queues, fn($q) => $q['waiting_count'] <= 2 && $q['is_open']);
                ?>
                <div class="ai-recommendation">
                    <div class="fs-4"><i class="bi bi-robot"></i></div>
                    <div class="small">
                        <span class="fw-bold">AI Insight:</span> 
                        <?php if (count($quiet_queues) > 0): ?>
                            It's a great time to visit the <span class="text-warning fw-bold"><?= reset($quiet_queues)['name'] ?></span>. Very short wait time!
                        <?php else: ?>
                            The campus services are currently busy. Expected wait times are slightly higher than average.
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4 d-none d-md-flex justify-content-end">
                <div class="text-center opacity-75">
                    <div style="font-size:3rem; font-weight:800; line-height:1;"><?= date('H:i') ?></div>
                    <div class="small fw-bold text-uppercase"><?= date('M d, Y') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Ticket Quick View -->
    <?php if ($active_ticket): ?>
        <div class="active-ticket-mini qs-animate-in">
            <i class="bi bi-ticket-perforated-fill"></i>
            You are currently in queue for <?= $active_ticket['queue_name'] ?>: 
            <span class="text-decoration-underline"><?= $active_ticket['ticket_number'] ?></span>
            <a href="<?= BASE_URL ?>/modules/queue/ticket.php" class="ms-2 btn btn-sm btn-danger rounded-pill px-3 py-1 fw-bold" style="font-size:0.7rem;">VIEW DETAILS</a>
        </div>
    <?php endif; ?>

    <!-- Services Section -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h3 class="fw-800 m-0" style="font-size:1.15rem;">Available Services</h3>
        <div class="small text-muted"><span class="live-dot me-1"></span> Live Status Updated</div>
    </div>

    <div class="queue-grid">
        <?php foreach ($queues as $q): 
            $is_open  = (bool)$q['is_open'];
            $has_mine = !empty($q['my_ticket']);
            $est      = predict_wait_time($q['id'], (int)$q['waiting_count'] + 1);
            $blocked  = $active_ticket && !$has_mine;

            // Icon Colors
            $colors = [
                '#1e40af' => ['bg' => '#eff6ff', 'text' => '#1e40af'],
                '#059669' => ['bg' => '#ecfdf5', 'text' => '#059669'],
                '#7c3aed' => ['bg' => '#f5f3ff', 'text' => '#7c3aed'],
                '#b45309' => ['bg' => '#fffbeb', 'text' => '#b45309'],
                '#c62828' => ['bg' => '#fef2f2', 'text' => '#c62828'],
            ];
            $theme = $colors[$q['color']] ?? $colors['#1e40af'];

            $href = $has_mine ? BASE_URL . '/modules/queue/ticket.php' : 
                   (!$active_ticket && $is_open ? BASE_URL . '/modules/queue/join.php?queue_id=' . $q['id'] : null);
        ?>
            <<?= $href ? 'a href="' . $href . '"' : 'div' ?> 
               class="premium-card <?= (!$is_open || $blocked) && !$has_mine ? 'disabled-card' : '' ?>">
                
                <div class="card-icon-box" style="background: <?= $theme['bg'] ?>; color: <?= $theme['text'] ?>;">
                    <i class="bi <?= $q['icon'] ?>"></i>
                </div>

                <?php if ($has_mine): ?>
                    <span class="card-tag" style="background: #dbeafe; color: #1e40af;">My Ticket: <?= $q['my_ticket'] ?></span>
                <?php elseif ($is_open): ?>
                    <span class="card-tag qs-badge-open"><span class="live-dot" style="width:5px; height:5px;"></span> Open</span>
                <?php else: ?>
                    <span class="card-tag qs-badge-closed">Closed</span>
                <?php endif; ?>

                <h4 class="fw-bold mb-1" style="font-size: 1.05rem;"><?= $q['name'] ?></h4>
                <p class="text-muted small mb-4" style="line-height: 1.4;"><?= $q['description'] ?></p>

                <div class="card-stat-row">
                    <div class="stat-item">
                        <span class="stat-label">Now Serving</span>
                        <span class="stat-value"><?= $q['now_serving'] ?: '—' ?></span>
                    </div>
                    <div class="stat-item text-end">
                        <span class="stat-label">Est. Wait</span>
                        <span class="stat-value" style="color: <?= (int)$q['waiting_count'] > 5 ? '#c62828' : '#059669' ?>;">
                            <?= (int)$q['waiting_count'] > 0 ? $est['label'] : 'No wait' ?>
                        </span>
                    </div>
                </div>

                <div class="mt-3 d-flex align-items-center justify-content-between pt-2">
                    <span class="small text-muted fw-bold"><?= $q['waiting_count'] ?> in queue</span>
                    <?php if ($href): ?>
                        <span class="text-primary fw-bold small"><?= $has_mine ? 'View Ticket' : 'Join Queue' ?> <i class="bi bi-arrow-right"></i></span>
                    <?php endif; ?>
                </div>

            </<?= $href ? 'a' : 'div' ?>>
        <?php endforeach; ?>
    </div>

</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<script>
// Live AJAX polling
setInterval(() => {
    fetch('<?= BASE_URL ?>/api/get_queue_status.php')
        .then(r => r.json())
        .then(data => {
            if (data.queues) {
                // For a truly seamless feel, we could update the DOM elements manually here.
                // But for now, we'll let the user experience the live dots.
            }
        });
}, <?= POLL_INTERVAL_MS ?>);
</script>

</body>
</html>
