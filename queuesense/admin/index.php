<?php
/**
 * QueueSense — Admin Command Center
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

$required_role = 'admin';
require_once __DIR__ . '/../includes/auth_check.php';

$db = db_connect();

// 1. Executive Stats (Real-time)
$stmt = $db->prepare("SELECT COUNT(*) FROM queue_entries WHERE status = 'done' AND DATE(served_at) = CURDATE()");
$stmt->execute();
$total_served = $stmt->get_result()->fetch_row()[0];

$stmt = $db->prepare("SELECT COUNT(*) FROM queue_entries WHERE status = 'waiting' AND DATE(joined_at) = CURDATE()");
$stmt->execute();
$total_waiting = $stmt->get_result()->fetch_row()[0];

$stmt = $db->prepare("SELECT COUNT(*) FROM service_windows WHERE status = 'open'");
$stmt->execute();
$active_windows = $stmt->get_result()->fetch_row()[0];

$stmt = $db->prepare("SELECT AVG(avg_wait_time) FROM analytics_log WHERE DATE(log_date) = CURDATE()");
$stmt->execute();
$avg_time = round($stmt->get_result()->fetch_row()[0] ?? 0, 1);

// 2. Window Status Grid
$windows_sql = "SELECT sw.*, u.full_name AS staff_name, qt.name AS queue_name,
                (SELECT ticket_number FROM queue_entries 
                 WHERE window_id = sw.id AND status = 'serving' 
                 ORDER BY called_at DESC LIMIT 1) AS ticket_number
                FROM service_windows sw
                LEFT JOIN users u ON u.id = sw.staff_id
                LEFT JOIN queue_types qt ON qt.id = sw.queue_type_id
                ORDER BY sw.window_label ASC";
$windows = $db->query($windows_sql)->fetch_all(MYSQLI_ASSOC);

// 3. Hourly Traffic Data
$hourly_sql = "SELECT HOUR(joined_at) as hr, COUNT(*) as qty 
               FROM queue_entries 
               WHERE DATE(joined_at) = CURDATE() 
               GROUP BY hr ORDER BY hr ASC";
$hourly_res = $db->query($hourly_sql)->fetch_all(MYSQLI_ASSOC);
$chart_labels = [];
$chart_values = [];
foreach($hourly_res as $h) {
    $chart_labels[] = date("gA", strtotime($h['hr'] . ":00"));
    $chart_values[] = $h['qty'];
}

$active_page = 'admin_dashboard';
$page_title = 'Admin Command Center';

include __DIR__ . '/../includes/header.php';
?>

<main class="qs-main-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="qs-main-content" style="padding: calc(var(--navbar-h) + 20px) 0 0 0 !important; display: flex; flex-direction: column; min-height: 100vh;">
        <div class="p-4 flex-grow-1">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-900 text-navy m-0">Admin Command Center</h2>
                    <p class="text-muted small m-0"><?= date('l, F j, Y') ?> — Real-time Campus Monitoring</p>
                </div>
                <button onclick="location.reload()" class="btn btn-navy btn-sm rounded-pill px-4 py-2">
                    <i class="bi bi-arrow-clockwise me-2"></i> Refresh Data
                </button>
            </div>

            <!-- Quick Stats Widgets -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="qs-stat-card card-thick-simple">
                        <div class="qs-stat-label">Total Served Today</div>
                        <div class="qs-stat-value"><?= $total_served ?></div>
                        <i class="bi bi-people qs-stat-icon"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="qs-stat-card card-thick-simple">
                        <div class="qs-stat-label">Students Waiting</div>
                        <div class="qs-stat-value"><?= $total_waiting ?></div>
                        <i class="bi bi-hourglass-split qs-stat-icon"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="qs-stat-card card-thick-simple">
                        <div class="qs-stat-label">Active Windows</div>
                        <div class="qs-stat-value"><?= $active_windows ?></div>
                        <i class="bi bi-door-open qs-stat-icon"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="qs-stat-card card-thick-simple">
                        <div class="qs-stat-label">Avg Service Time</div>
                        <div class="qs-stat-value"><?= $avg_time ?> <small class="fs-6">min</small></div>
                        <i class="bi bi-stopwatch qs-stat-icon"></i>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Window Monitor -->
                <div class="col-md-8">
                    <div class="qs-card mb-4">
                        <h5 class="fw-800 mb-4"><i class="bi bi-grid-3x3-gap me-2"></i>Service Window Monitor</h5>
                        <div class="row g-3">
                            <?php foreach($windows as $w): ?>
                            <div class="col-md-6">
                                <div class="p-3 border rounded-4 d-flex align-items-center gap-3 bg-light bg-opacity-50">
                                    <div class="live-dot <?= $w['status'] === 'open' ? '' : 'opacity-25' ?>" style="background:<?= $w['status'] === 'open' ? '#10b981' : '#94a3b8' ?>"></div>
                                    <div class="flex-grow-1">
                                        <div class="fw-800 small"><?= htmlspecialchars($w['window_label']) ?></div>
                                        <div class="text-muted" style="font-size:0.7rem;"><?= htmlspecialchars($w['queue_name'] ?? 'Unassigned') ?></div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-900 text-navy"><?= $w['ticket_number'] ?: 'IDLE' ?></div>
                                        <div class="text-muted extra-small" style="font-size:0.6rem;"><?= htmlspecialchars($w['staff_name'] ?? 'Unassigned') ?></div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Traffic Chart -->
                    <div class="qs-card">
                        <h5 class="fw-800 mb-4"><i class="bi bi-graph-up me-2"></i>Hourly Traffic Volume</h5>
                        <div style="height: 250px;">
                            <canvas id="trafficChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- System Feed -->
                <div class="col-md-4">
                    <div class="qs-card h-100">
                        <h5 class="fw-800 mb-4"><i class="bi bi-activity me-2"></i>Live System Feed</h5>
                        <div id="activityFeed">
                            <!-- Feed items generated by JS -->
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- End of flex-grow-1 -->
        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Traffic Chart
    const ctx = document.getElementById('trafficChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Joins',
                data: <?= json_encode($chart_values) ?>,
                borderColor: '#1e2a5e',
                backgroundColor: 'rgba(30, 42, 94, 0.05)',
                fill: true,
                tension: 0.4,
                borderWidth: 3,
                pointRadius: 4,
                pointBackgroundColor: '#fbbf24'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { display: false } },
                x: { grid: { display: false } }
            }
        }
    });

    // Mock Feed
    function updateFeed() {
        const feed = document.getElementById('activityFeed');
        const activities = [
            'Ticket R-012 called at Window 1',
            'Staff Maria marked R-011 as Done',
            'New student s230102815 joined Cashier queue',
            'Window 3 status changed to OPEN',
            'Student R-009 marked as No-Show'
        ];
        let html = '';
        activities.forEach(a => {
            html += `<div class="d-flex gap-3 mb-3 pb-3 border-bottom border-light">
                        <div class="text-navy"><i class="bi bi-dot fs-4"></i></div>
                        <div>
                            <div class="fw-bold small">${a}</div>
                            <div class="text-muted extra-small" style="font-size:0.7rem;">Just now</div>
                        </div>
                    </div>`;
        });
        feed.innerHTML = html;
    }
    updateFeed();
</script>

<?php // End of file ?>
