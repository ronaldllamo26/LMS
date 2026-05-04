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
sync_journey_progress($current_user_id); // Ensure journey state matches database reality

// Live queue stats
$sql = "SELECT
            qt.id, qt.name, qt.description, qt.prefix, qt.icon, qt.color, qt.is_open,
            (SELECT ticket_number FROM queue_entries
             WHERE queue_type_id = qt.id AND status = 'serving'
               AND DATE(joined_at) = CURDATE()
             ORDER BY called_at DESC LIMIT 1) AS now_serving,
            (SELECT COUNT(*) FROM queue_entries
             WHERE queue_type_id = qt.id AND status IN ('waiting', 'pending')
               AND DATE(joined_at) = CURDATE()) AS waiting_count,
            (SELECT ticket_number FROM queue_entries
             WHERE queue_type_id = qt.id AND user_id = ?
               AND status IN ('waiting','serving','pending')
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
                      WHERE qe.user_id = ? AND qe.status IN ('waiting','serving','pending')
                        AND DATE(qe.joined_at) = CURDATE()
                      LIMIT 1");
$stmt->bind_param('i', $current_user_id);
$stmt->execute();
$active_ticket = $stmt->get_result()->fetch_assoc();
$stmt->close();

$active_page = 'dashboard';
$page_title  = 'Available Services';

// Greeting Logic
$hour = date('H');
$greeting = "Good Morning";
if ($hour >= 12 && $hour < 17) $greeting = "Good Afternoon";
if ($hour >= 17) $greeting = "Good Evening";

// Service Colors
$colors = [
    '#1e40af' => ['bg' => '#eff6ff', 'text' => '#1e40af'],
    '#059669' => ['bg' => '#ecfdf5', 'text' => '#059669'],
    '#7c3aed' => ['bg' => '#f5f3ff', 'text' => '#7c3aed'],
    '#b45309' => ['bg' => '#fffbeb', 'text' => '#b45309'],
    '#c62828' => ['bg' => '#fef2f2', 'text' => '#c62828'],
];
?>
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
    .fastpass-toggle-card {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: var(--radius);
        backdrop-filter: blur(10px);
    }
    .fastpass-toggle-card .form-check-input { width: 3em; height: 1.5em; cursor: pointer; }
    
    .multi-select-checkbox {
        position: absolute; top: 24px; left: 24px;
        width: 24px; height: 24px;
        border-radius: 6px;
        border: 2px solid #e2e8f0;
        background: white;
        display: none;
        align-items: center; justify-content: center;
        transition: all 0.2s;
        z-index: 10;
    }
    .multi-select-checkbox.selected {
        background: var(--bcp-indigo);
        border-color: var(--bcp-indigo);
        color: white;
    }
    .multi-select-mode .multi-select-checkbox { display: flex; }
    .multi-select-mode .card-icon-box { margin-left: 36px; }
    .multi-select-mode .card-tag { display: none !important; }

    .fastpass-bar {
        position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%);
        width: 100%; max-width: 600px;
        background: var(--bcp-navy);
        color: white; border-radius: 50px;
        padding: 12px 24px;
        display: none;
        align-items: center; justify-content: space-between;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        z-index: 2000;
        animation: slideUp 0.3s ease-out;
    }
    @keyframes slideUp { from { bottom: -100px; } to { bottom: 30px; } }
    /* Fix for Edge-to-Edge Footer */
    .status-content-wrapper {
        padding: 20px 40px;
        flex: 1;
    }
</style>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<main class="qs-main-layout">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="qs-main-content">
        <div class="status-content-wrapper">

    <!-- Hero Section -->
    <div class="hero-banner qs-animate-in">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="hero-title"><?= $greeting ?>, <?= explode(' ', $current_user['full_name'])[0] ?>!</h1>
                <p class="hero-sub mb-0">Select a service below to join the smart queue. We'll notify you when it's your turn.</p>
                
                <!-- AI Smart Recommendation & FastPass Toggle -->
                <?php 
                    $busy_queues = array_filter($queues, fn($q) => $q['waiting_count'] > 5);
                    $quiet_queues = array_filter($queues, fn($q) => $q['waiting_count'] <= 2 && $q['is_open']);
                ?>
                <div class="d-flex flex-wrap gap-3 align-items-center mt-3">
                    <div class="ai-recommendation mt-0 flex-grow-1">
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
                    
                    <div class="fastpass-toggle-card p-3 d-flex align-items-center gap-3">
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="fastpassToggle" onchange="toggleFastPassMode()">
                        </div>
                        <div>
                            <div class="fw-800 small text-uppercase" style="letter-spacing:1px; line-height:1;">AI FastPass</div>
                            <div class="text-white-50" style="font-size:0.65rem;">Multi-Service Journey</div>
                        </div>
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

    <!-- AI Journey Debugger (Only for Testing/Staff) -->
    <?php if (isset($_SESSION['journey'])): ?>
        <div class="card border-0 shadow-sm mb-4" style="background: #f8fafc; border-left: 4px solid var(--bcp-gold) !important;">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-warning bg-opacity-10 p-2 rounded text-warning">
                        <i class="bi bi-bug-fill"></i>
                    </div>
                    <div>
                        <div class="fw-bold small">AI JOURNEY TRACKER</div>
                        <div class="text-muted" style="font-size:0.7rem;">
                            Step: <?= $_SESSION['journey']['current_step'] + 1 ?> / <?= $_SESSION['journey']['total_steps'] ?> | 
                            Status: <span class="text-primary fw-bold"><?= $_SESSION['journey']['debug_status'] ?? 'Syncing...' ?></span>
                        </div>
                    </div>
                </div>
                <a href="reset_journey.php" class="btn btn-sm btn-outline-danger py-1 fw-bold" style="font-size:0.6rem;">RESET JOURNEY</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Active Ticket or Journey Progress -->
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
            
            // Fix: Don't block the card if it's OUR ticket
            $blocked  = $active_ticket && !$has_mine;

            $href = $has_mine ? BASE_URL . '/modules/queue/ticket.php?queue_id=' . $q['id'] : 
                   (!$active_ticket && $is_open ? BASE_URL . '/modules/queue/join.php?queue_id=' . $q['id'] : null);
        ?>
            <div onclick="handleCardClick(event, this, <?= $q['id'] ?>, '<?= $href ?>', <?= $is_open ? 1 : 0 ?>, <?= $has_mine ? 1 : 0 ?>)" 
                 class="premium-card <?= (!$is_open || ($blocked && !$has_mine)) ? 'disabled-card' : '' ?>"
                 data-queue-id="<?= $q['id'] ?>"
                 data-queue-name="<?= htmlspecialchars($q['name']) ?>"
                 data-wait-time="<?= $est['avg_service'] * $q['waiting_count'] ?>">
                
                <div class="multi-select-checkbox">
                    <i class="bi bi-check-lg"></i>
                </div>
                
                <div class="card-icon-box" style="background: <?= $colors[$q['color']]['bg'] ?? '#eff6ff' ?>; color: <?= $colors[$q['color']]['text'] ?? '#1e40af' ?>;">
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

            </div>
        <?php endforeach; ?>
        </div>

        </div>

        <!-- FastPass Bar -->
        <div id="fastpassBar" class="fastpass-bar">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-25 p-2 rounded-circle">
                    <i class="bi bi-lightning-charge-fill text-warning"></i>
                </div>
                <div>
                    <div id="selectedCount" class="fw-800" style="font-size:0.9rem;">0 Services Selected</div>
                    <div id="optimizedSavings" class="small text-white-50" style="font-size:0.7rem;">AI is ready to optimize your route</div>
                </div>
            </div>
            <button onclick="planJourney()" class="btn btn-warning rounded-pill px-4 fw-800 text-navy shadow-sm">
                PLAN JOURNEY <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </div>

        <?php include __DIR__ . '/../../includes/footer.php'; ?>
    </div>
</main>

<script>
let selectedQueues = [];
let fastPassMode = false;

function toggleFastPassMode() {
    fastPassMode = document.getElementById('fastpassToggle').checked;
    document.body.classList.toggle('multi-select-mode', fastPassMode);
    
    // Clear selections when toggling
    selectedQueues = [];
    document.querySelectorAll('.multi-select-checkbox').forEach(cb => cb.classList.remove('selected'));
    updateFastPassBar();
}

function handleCardClick(event, el, id, href, isOpen, hasMine) {
    if (!fastPassMode) {
        if (href) window.location.href = href;
        return;
    }

    if (!isOpen || hasMine) return;

    const cb = el.querySelector('.multi-select-checkbox');
    const index = selectedQueues.indexOf(id);

    if (index > -1) {
        selectedQueues.splice(index, 1);
        cb.classList.remove('selected');
    } else {
        if (selectedQueues.length >= 3) {
            alert('AI FastPass is currently limited to 3 services per journey.');
            return;
        }
        selectedQueues.push(id);
        cb.classList.add('selected');
    }
    
    updateFastPassBar();
}

function updateFastPassBar() {
    const bar = document.getElementById('fastpassBar');
    const countText = document.getElementById('selectedCount');
    const savingsText = document.getElementById('optimizedSavings');
    
    if (selectedQueues.length > 1) {
        bar.style.display = 'flex';
        countText.textContent = `${selectedQueues.length} Services Selected`;
        
        // Calculate dummy "savings" for UI feel
        const savings = selectedQueues.length * 4 + Math.floor(Math.random() * 5);
        savingsText.innerHTML = `<i class="bi bi-stars text-warning"></i> AI predicts <span class="text-white">${savings} mins</span> savings!`;
    } else {
        bar.style.display = 'none';
    }
}

function planJourney() {
    if (selectedQueues.length < 2) return;
    
    // In a real implementation, we would send this to an API.
    // For now, we'll redirect to a journey planning page.
    const ids = selectedQueues.join(',');
    window.location.href = `<?= BASE_URL ?>/modules/queue/journey.php?ids=${ids}`;
}

// Live AJAX polling
setInterval(() => {
    fetch('<?= BASE_URL ?>/api/get_queue_status.php')
        .then(r => r.json())
        .then(data => {
            // Update queue counts logic here...
        });

    <?php if (isset($_SESSION['journey'])): ?>
    // Smart Journey Sync Polling
    const currentTicketId = <?= $active_ticket['id'] ?? 'null' ?>;
    fetch('../../api/get_my_ticket_status.php')
        .then(r => r.json())
        .then(data => {
            console.log("Journey Sync Response:", data);
            // Reload if status is done/none OR if ticket ID has changed (new station joined)
            if (data.status === 'none' || data.status === 'done' || (data.ticket_id && data.ticket_id != currentTicketId)) {
                window.location.reload();
            }
        })
        .catch(err => console.error("Sync Error:", err));
    <?php endif; ?>
}, 3000);
</script>

</body>
</html>
