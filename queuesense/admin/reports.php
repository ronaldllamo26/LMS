<?php
/**
 * QueueSense — Admin Analytics & Reports
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

$required_role = 'admin';
require_once __DIR__ . '/../includes/auth_check.php';

$db = db_connect();

// 1. KPI Calculations (Real-time)
// Total Traffic Today
$stmt = $db->prepare("SELECT COUNT(*) FROM queue_entries WHERE DATE(joined_at) = CURDATE()");
$stmt->execute();
$total_traffic = $stmt->get_result()->fetch_row()[0];

// Total Served Today
$stmt = $db->prepare("SELECT COUNT(*) FROM queue_entries WHERE status = 'done' AND DATE(served_at) = CURDATE()");
$stmt->execute();
$total_served = $stmt->get_result()->fetch_row()[0];

// Total No-Show Today
$stmt = $db->prepare("SELECT COUNT(*) FROM queue_entries WHERE status = 'no-show' AND DATE(joined_at) = CURDATE()");
$stmt->execute();
$total_noshow = $stmt->get_result()->fetch_row()[0];

// Avg Wait Time Today (from logs)
$stmt = $db->prepare("SELECT AVG(avg_wait_time) FROM analytics_log WHERE DATE(log_date) = CURDATE()");
$stmt->execute();
$avg_wait = round($stmt->get_result()->fetch_row()[0] ?? 0, 0);

// 2. Hourly Traffic Data (for Chart)
$hourly_sql = "SELECT HOUR(joined_at) as hr, COUNT(*) as qty FROM queue_entries WHERE DATE(joined_at) = CURDATE() GROUP BY hr ORDER BY hr ASC";
$hourly_res = $db->query($hourly_sql)->fetch_all(MYSQLI_ASSOC);
$chart_labels = []; $chart_values = [];
for($i=8; $i<=17; $i++) {
    $found = false;
    foreach($hourly_res as $h) {
        if($h['hr'] == $i) {
            $chart_labels[] = date("g A", strtotime("$i:00"));
            $chart_values[] = $h['qty'];
            $found = true; break;
        }
    }
    if(!$found) {
        $chart_labels[] = date("g A", strtotime("$i:00"));
        $chart_values[] = 0;
    }
}

// 3. Departmental Distribution
$dept_sql = "SELECT qt.name, COUNT(qe.id) as qty 
             FROM queue_types qt 
             LEFT JOIN queue_entries qe ON qe.queue_type_id = qt.id AND DATE(qe.joined_at) = CURDATE()
             GROUP BY qt.id";
$dept_stats = $db->query($dept_sql)->fetch_all(MYSQLI_ASSOC);

// 4. Recent Transactions
$transactions_sql = "SELECT qe.*, u.full_name, qt.name as dept_name, sw.window_label 
                    FROM queue_entries qe
                    LEFT JOIN users u ON u.id = qe.user_id
                    LEFT JOIN queue_types qt ON qt.id = qe.queue_type_id
                    LEFT JOIN service_windows sw ON sw.id = qe.window_id
                    WHERE DATE(qe.joined_at) = CURDATE()
                    ORDER BY qe.joined_at DESC LIMIT 10";
$transactions = $db->query($transactions_sql)->fetch_all(MYSQLI_ASSOC);

$active_page = 'reports';
$page_title = 'System Analytics';

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <main class="flex-grow-1 qs-main-content">
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <div>
                <h2 class="fw-900 text-navy m-0">System Analytics</h2>
                <p class="text-muted small m-0">Data insights for <?= date('F d, Y') ?></p>
            </div>
            <button onclick="window.print()" class="btn btn-navy btn-sm rounded-pill px-4">
                <i class="bi bi-printer me-2"></i> Print Daily Report
            </button>
        </div>

        <!-- KPI Cards (Thick Simple Style - Same as Dashboard) -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="qs-stat-card card-thick-simple">
                    <div class="qs-stat-label">Total Traffic</div>
                    <div class="qs-stat-value"><?= $total_traffic ?></div>
                    <i class="bi bi-people-fill qs-stat-icon"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="qs-stat-card card-thick-simple">
                    <div class="qs-stat-label">Served</div>
                    <div class="qs-stat-value text-success"><?= $total_served ?></div>
                    <i class="bi bi-check-circle-fill qs-stat-icon"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="qs-stat-card card-thick-simple">
                    <div class="qs-stat-label">No-Show</div>
                    <div class="qs-stat-value text-danger"><?= $total_noshow ?></div>
                    <i class="bi bi-x-octagon-fill qs-stat-icon"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="qs-stat-card card-thick-simple">
                    <div class="qs-stat-label">Avg. Wait</div>
                    <div class="qs-stat-value"><?= $avg_wait ?> <small class="fs-6">min</small></div>
                    <i class="bi bi-clock-history qs-stat-icon"></i>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4 no-print">
            <!-- Traffic Chart -->
            <div class="col-md-8">
                <div class="qs-card h-100">
                    <h5 class="fw-800 mb-4">Hourly Student Traffic</h5>
                    <div style="height: 350px; position: relative;">
                        <canvas id="analyticsChart"></canvas>
                    </div>
                </div>
            </div>
            <!-- Dept Usage -->
            <div class="col-md-4">
                <div class="qs-card h-100">
                    <h5 class="fw-800 mb-4">Department Usage</h5>
                    <?php foreach($dept_stats as $ds): 
                        $pct = $total_traffic > 0 ? ($ds['qty'] / $total_traffic) * 100 : 0;
                    ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="fw-bold"><?= htmlspecialchars($ds['name']) ?></span>
                            <span class="text-muted"><?= $ds['qty'] ?></span>
                        </div>
                        <div class="progress" style="height: 8px; border-radius: 10px;">
                            <div class="progress-bar bg-navy" style="width: <?= $pct ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Recent Transactions Table -->
        <div class="qs-card">
            <h5 class="fw-800 mb-4">Recent Transactions</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="bg-light">
                        <tr class="small text-uppercase fw-bold text-muted">
                            <th>Ticket</th>
                            <th>Student</th>
                            <th>Department</th>
                            <th>Window</th>
                            <th>Time Joined</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php foreach($transactions as $t): ?>
                        <tr>
                            <td class="fw-bold text-navy"><?= $t['ticket_number'] ?></td>
                            <td><?= htmlspecialchars($t['full_name']) ?></td>
                            <td><?= htmlspecialchars($t['dept_name']) ?></td>
                            <td><?= $t['window_label'] ?: '<span class="text-muted">—</span>' ?></td>
                            <td><?= date('g:i A', strtotime($t['joined_at'])) ?></td>
                            <td>
                                <?php 
                                    $badge_class = 'badge-soft-warning';
                                    if($t['status'] == 'done') $badge_class = 'badge-soft-success';
                                    if($t['status'] == 'no_show') $badge_class = 'badge-soft-danger';
                                    if($t['status'] == 'serving') $badge_class = 'badge-soft-primary';
                                ?>
                                <span class="qs-badge <?= $badge_class ?>">
                                    <?= str_replace('_', ' ', $t['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('analyticsChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Student Joins',
                data: <?= json_encode($chart_values) ?>,
                borderColor: '#1e2a5e',
                backgroundColor: 'rgba(30, 42, 94, 0.08)',
                fill: true,
                tension: 0.4,
                borderWidth: 3,
                pointRadius: 5,
                pointBackgroundColor: '#fbbf24',
                pointBorderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                x: { grid: { display: false } }
            }
        }
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
