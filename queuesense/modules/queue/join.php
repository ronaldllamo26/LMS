<?php
/**
 * QueueSense — Join Queue
 * Handles student joining a specific queue.
 * Validates: already in queue, daily limit, queue is open.
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';

$required_role = 'student';
require_once __DIR__ . '/../../includes/auth_check.php';

$queue_id = (int)($_GET['queue_id'] ?? 0);
if (!$queue_id) redirect(BASE_URL . '/modules/queue/status.php');

$db = db_connect();

// ─── Fetch the queue type ─────────────────────────────────────────────────
$stmt = $db->prepare("SELECT * FROM queue_types WHERE id = ? AND is_open = 1 LIMIT 1");
$stmt->bind_param('i', $queue_id);
$stmt->execute();
$queue = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$queue) {
    redirect(BASE_URL . '/modules/queue/status.php?error=queue_not_found');
}

// ─── Check if already in ANY queue today ──────────────────────────────────
$stmt = $db->prepare("SELECT id FROM queue_entries
                      WHERE user_id = ? AND status IN ('waiting','serving')
                        AND DATE(joined_at) = CURDATE() LIMIT 1");
$stmt->bind_param('i', $current_user_id);
$stmt->execute();
$already_in = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($already_in) {
    redirect(BASE_URL . '/modules/queue/ticket.php?error=already_in_queue');
}

// ─── Check daily limit ─────────────────────────────────────────────────────
$stmt = $db->prepare("SELECT COUNT(*) as total FROM queue_entries
                      WHERE queue_type_id = ? AND DATE(joined_at) = CURDATE()");
$stmt->bind_param('i', $queue_id);
$stmt->execute();
$total_today = (int)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

if ($total_today >= $queue['daily_limit']) {
    redirect(BASE_URL . '/modules/queue/status.php?error=queue_full');
}

// ─── Process join (POST confirmation) ─────────────────────────────────────
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_join'])) {

    // Get next position number
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM queue_entries
                          WHERE queue_type_id = ? AND status IN ('waiting','serving')
                            AND DATE(joined_at) = CURDATE()");
    $stmt->bind_param('i', $queue_id);
    $stmt->execute();
    $waiting_now = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    $position      = $waiting_now + 1;
    $ticket_number = generate_ticket_number($queue_id, $queue['prefix']);
    $priority      = isset($_POST['priority']) ? 1 : 0;

    $stmt = $db->prepare("INSERT INTO queue_entries
                          (user_id, queue_type_id, ticket_number, position, priority)
                          VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('iisii', $current_user_id, $queue_id, $ticket_number, $position, $priority);

    if ($stmt->execute()) {
        $entry_id = $db->insert_id;
        $stmt->close();

        // Create notification
        create_notification($current_user_id,
            "You joined the {$queue['name']} queue. Your ticket: {$ticket_number}",
            'success');

        log_action('TICKET_GENERATED',
            "Student {$current_user['student_id']} joined {$queue['name']}: {$ticket_number}");

        redirect(BASE_URL . '/modules/queue/ticket.php?new=1');
    } else {
        $error = 'Something went wrong. Please try again.';
        $stmt->close();
    }
}

// ─── Fetch live stats to show on confirmation page ────────────────────────
$stmt = $db->prepare("SELECT COUNT(*) as waiting FROM queue_entries
                      WHERE queue_type_id = ? AND status = 'waiting'
                        AND DATE(joined_at) = CURDATE()");
$stmt->bind_param('i', $queue_id);
$stmt->execute();
$waiting_count = (int)$stmt->get_result()->fetch_assoc()['waiting'];
$stmt->close();

$est = predict_wait_time($queue_id, $waiting_count + 1);
$best_times = get_best_visit_times($queue_id);
$page_title = 'Join Queue — ' . $queue['name'];
$no_sidebar = true; // Force full width layout
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> | QueueSense</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        body { background:#f0f4f8; }
        #sidebarToggle { display: none !important; }

        .back-home-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #1a237e;
            font-weight: 800;
            text-decoration: none;
            margin-bottom: 20px;
            transition: 0.3s;
        }
        .back-home-link:hover { transform: translateX(-5px); }

        .confirm-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            max-width: 480px;
            margin: 0 auto;
        }

        .confirm-header {
            background: linear-gradient(135deg, #1a237e, #3949ab);
            padding: 28px 32px;
            color: white;
        }

        .confirm-body { padding: 28px 32px; }

        .stat-pill {
            background: #f0f4f8;
            border-radius: 12px;
            padding: 14px 18px;
            text-align: center;
            flex: 1;
        }

        .stat-pill-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: #1a237e;
            line-height: 1;
            margin-bottom: 4px;
        }

        .stat-pill-label {
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
        }

        .confirm-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1a237e, #3949ab);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.2s;
        }

        .confirm-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26,35,126,0.35);
        }

        .ai-card {
            background: linear-gradient(135deg, #eff6ff, #e0f2fe);
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            padding: 16px 18px;
            margin-top: 16px;
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<main class="qs-main-layout">
    <!-- No sidebar for student standalone pages, but we keep the structure for footer alignment -->
    <div class="qs-main-content" style="padding: calc(var(--navbar-h) + 20px) 0 0 0 !important; display: flex; flex-direction: column; min-height: 100vh;">
        
        <div class="p-4 flex-grow-1">
            <div class="container" style="max-width:560px;">

                <!-- Back link (BCP SMS Precise Style) -->
                <a href="<?= BASE_URL ?>/modules/queue/status.php" 
                   class="d-inline-flex align-items-center gap-1 mb-4 text-muted fw-600 text-decoration-none" 
                   style="font-size: 0.85rem; letter-spacing: 0.3px; transition: 0.2s;">
                    <i class="bi bi-arrow-left"></i> BACK TO HOME
                </a>

                <?php if ($error): ?>
                <div class="alert alert-danger mb-3"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="confirm-card qs-animate-in">

                    <!-- Header -->
                    <div class="confirm-header">
                        <div style="font-size:0.75rem; font-weight:600; text-transform:uppercase;
                                    letter-spacing:0.8px; opacity:0.75; margin-bottom:8px;">
                            Confirm Queue Entry
                        </div>
                        <div style="font-size:1.6rem; font-weight:900; letter-spacing:-0.5px;">
                            <?= htmlspecialchars($queue['name']) ?>
                        </div>
                        <div style="font-size:0.83rem; opacity:0.75; margin-top:4px;">
                            <?= htmlspecialchars($queue['description']) ?>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="confirm-body">

                        <!-- Live stats pills -->
                        <div class="d-flex gap-3 mb-24" style="margin-bottom:20px;">
                            <div class="stat-pill">
                                <div class="stat-pill-value"><?= $waiting_count ?></div>
                                <div class="stat-pill-label">In Queue</div>
                            </div>
                            <div class="stat-pill">
                                <div class="stat-pill-value"><?= $est['avg_service'] ?>m</div>
                                <div class="stat-pill-label">Avg Service</div>
                            </div>
                            <div class="stat-pill">
                                <div class="stat-pill-value" style="font-size:1.1rem;">
                                    ~<?= $est['label'] ?>
                                </div>
                                <div class="stat-pill-label">Est. Wait</div>
                            </div>
                        </div>

                        <!-- AI Best Time Card -->
                        <?php if (!empty($best_times)): ?>
                        <div class="ai-card mb-4">
                            <div style="font-size:0.72rem; font-weight:700; text-transform:uppercase;
                                        letter-spacing:0.6px; color:#1a237e; margin-bottom:8px;">
                                <i class="bi bi-lightning-charge-fill me-1 text-warning"></i>
                                AI Recommendation — Best Times Today
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <?php foreach ($best_times as $bt): ?>
                                <span style="background:#1a237e; color:white; border-radius:20px;
                                             padding:4px 12px; font-size:0.78rem; font-weight:600;">
                                    <?= htmlspecialchars($bt['label']) ?>
                                    <span style="opacity:0.7; font-size:0.68rem;">(~<?= $bt['avg_wait'] ?>m wait)</span>
                                </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Join form -->
                        <form method="POST" action="">
                            <!-- Priority checkbox -->
                            <div class="d-flex align-items-center gap-2 mb-4 p-3"
                                 style="background:#fafbfc; border-radius:10px; border:1px solid #e8edf3;">
                                <input type="checkbox" class="form-check-input m-0" name="priority" id="priorityCheck"
                                       style="width:18px;height:18px;">
                                <label for="priorityCheck" style="font-size:0.85rem; font-weight:600; cursor:pointer; margin:0;">
                                    <i class="bi bi-shield-check text-primary me-1"></i>
                                    I am a PWD / Senior Citizen (Priority Lane)
                                </label>
                            </div>

                            <!-- Confirm info -->
                            <div class="mb-4" style="font-size:0.82rem; color:#64748b; line-height:1.7;">
                                <i class="bi bi-info-circle text-primary me-1"></i>
                                By joining this queue, you agree to be present when your ticket is called.
                                Missed calls will be marked as <strong>No Show</strong>.
                            </div>

                            <button type="submit" name="confirm_join" class="confirm-btn">
                                <i class="bi bi-ticket-perforated me-2"></i>
                                Get My Ticket — <?= htmlspecialchars($queue['name']) ?>
                            </button>
                        </form>

                    </div>
                </div>
            </div>
        </div>
        <?php include __DIR__ . '/../../includes/footer.php'; ?>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>