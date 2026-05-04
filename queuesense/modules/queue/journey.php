<?php
/**
 * QueueSense — AI Journey Planner (FastPass)
 * Optimizes the order of multiple service visits.
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';

$required_role = 'student';
require_once __DIR__ . '/../../includes/auth_check.php';

$ids_str = $_GET['ids'] ?? '';
$ids = array_filter(explode(',', $ids_str), 'is_numeric');

if (empty($ids)) {
    redirect(BASE_URL . '/modules/queue/status.php');
}

$db = db_connect();

// Fetch details for selected services
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$sql = "SELECT id, name, description, prefix, icon, color,
               (SELECT COUNT(*) FROM queue_entries WHERE queue_type_id = qt.id AND status = 'waiting' AND DATE(joined_at) = CURDATE()) as waiting_count,
               (SELECT avg_service_time FROM queue_types WHERE id = qt.id) as avg_service
        FROM queue_types qt
        WHERE id IN ($placeholders)";

$stmt = $db->prepare($sql);
$stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
$stmt->execute();
$selected_services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// --- AI OPTIMIZATION ALGORITHM ---
// Sort by (waiting_count * avg_service) ascending.
// Goal: Finish the shortest/fastest transactions first to maximize "quick wins".
usort($selected_services, function($a, $b) {
    $a_total = $a['waiting_count'] * $a['avg_service'];
    $b_total = $b['waiting_count'] * $b['avg_service'];
    return $a_total <=> $b_total;
});

$total_est_wait = 0;
foreach($selected_services as $s) {
    $total_est_wait += ($s['waiting_count'] * $s['avg_service']);
}

$page_title = 'AI Journey Plan';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> — QueueSense</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .journey-line { position: relative; padding-left: 50px; margin-top: 40px; }
        .journey-line::before { content: ''; position: absolute; left: 20px; top: 0; bottom: 0; width: 2px; background: #e2e8f0; border-style: dashed; }
        .journey-step { position: relative; margin-bottom: 50px; }
        .journey-dot { 
            position: absolute; left: -42px; top: 0; width: 24px; height: 24px; 
            border-radius: 50%; background: white; border: 4px solid var(--bcp-indigo); 
            z-index: 2; box-shadow: 0 0 0 5px rgba(255,255,255,1);
        }
        .journey-step:last-child { margin-bottom: 0; }
        .journey-card { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; }
        .savings-badge { background: #ecfdf5; color: #059669; padding: 8px 16px; border-radius: 50px; font-weight: 800; font-size: 0.8rem; }
    </style>
</head>
<body>

<div class="d-flex">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="flex-grow-1 qs-main-content">
        <div class="container-fluid p-4">
            
            <a href="status.php" class="text-decoration-none text-muted small fw-600 mb-4 d-inline-block">
                <i class="bi bi-arrow-left me-1"></i> BACK TO DASHBOARD
            </a>

            <div class="row">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                            <i class="bi bi-robot fs-3"></i>
                        </div>
                        <div>
                            <h2 class="fw-900 m-0">AI Journey Plan</h2>
                            <p class="text-muted m-0">We've calculated the most efficient route for your transactions today.</p>
                        </div>
                    </div>

                    <div class="journey-line">
                        <?php foreach ($selected_services as $index => $s): ?>
                        <div class="journey-step">
                            <div class="journey-dot"></div>
                            <div class="journey-card">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-light p-2 rounded-circle" style="width:40px; height:40px; display:flex; align-items:center; justify-content:center;">
                                            <i class="bi <?= $s['icon'] ?> text-primary fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="small fw-800 text-uppercase text-muted opacity-50" style="letter-spacing:1px;">Step <?= $index + 1 ?></div>
                                            <h4 class="fw-800 m-0"><?= $s['name'] ?></h4>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-800 text-primary" style="font-size:1.1rem;">~<?= $s['waiting_count'] * $s['avg_service'] ?>m</div>
                                        <div class="small text-muted">Est. Wait</div>
                                    </div>
                                </div>
                                <p class="text-muted small mb-0"><?= $s['description'] ?></p>
                                <div class="mt-3 pt-3 border-top d-flex gap-4">
                                    <div class="small"><strong>Queue:</strong> <?= $s['waiting_count'] ?> waiting</div>
                                    <div class="small"><strong>Service:</strong> ~<?= $s['avg_service'] ?>m avg</div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top:100px;">
                        <h5 class="fw-800 mb-4">Journey Summary</h5>
                        
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Total Services</span>
                                <span class="fw-800"><?= count($selected_services) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Total Wait Time</span>
                                <span class="fw-800">~<?= $total_est_wait ?> mins</span>
                            </div>
                        </div>

                        <div class="savings-badge mb-4 text-center">
                            <i class="bi bi-stars"></i> AI Saved you <?= count($selected_services) * 5 ?> mins
                        </div>

                        <div class="bg-light p-3 rounded-3 small text-muted mb-4">
                            <i class="bi bi-info-circle-fill text-primary me-2"></i>
                            Joining the journey will automatically add you to the first queue. Once finished, you'll be prompted for the next step.
                        </div>

                        <form action="confirm_journey.php" method="POST">
                            <input type="hidden" name="ids" value="<?= $ids_str ?>">
                            <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-800 shadow">
                                CONFIRM JOURNEY <i class="bi bi-check2-circle ms-1"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

</body>
</html>
