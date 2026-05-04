<?php
/**
 * QueueSense — AI Analytics & Insights
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

$required_role = 'admin';
require_once __DIR__ . '/../includes/auth_check.php';

$active_page = 'ai_analytics';
$page_title = 'Intelligence Dashboard';

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <main class="flex-grow-1 qs-main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-900 text-navy m-0"><i class="bi bi-cpu me-2"></i> AI Intelligence</h2>
                <p class="text-muted small m-0">Predictive insights and system optimization</p>
            </div>
            <div class="badge badge-soft-primary qs-badge px-3 py-2">
                <i class="bi bi-stars me-2"></i> AI Engine Active
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Prediction Card -->
            <div class="col-md-8">
                <div class="qs-card bg-navy text-white h-100" style="background: linear-gradient(135deg, #1e2a5e 0%, #2a3a7c 100%);">
                    <div class="d-flex justify-content-between mb-4">
                        <h5 class="fw-800 m-0">Peak Traffic Prediction</h5>
                        <span class="small opacity-75">Next 24 Hours</span>
                    </div>
                    <div style="height: 250px; position: relative;">
                        <canvas id="predictionChart"></canvas>
                    </div>
                    <div class="mt-4 p-3 rounded-3 bg-white bg-opacity-10 border border-white border-opacity-10">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-lightbulb-fill text-warning fs-4 me-3"></i>
                            <p class="small m-0">
                                <span class="fw-bold">AI Suggestion:</span> Based on historical data, expect a <span class="text-warning fw-bold">15% increase</span> in Registrar traffic tomorrow between 9:00 AM and 10:30 AM.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Efficiency Card -->
            <div class="col-md-4">
                <div class="qs-card h-100">
                    <h5 class="fw-800 mb-4">System Efficiency</h5>
                    <div class="text-center py-4">
                        <div class="position-relative d-inline-block">
                            <svg width="150" height="150" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="45" fill="none" stroke="#f1f5f9" stroke-width="10" />
                                <circle cx="50" cy="50" r="45" fill="none" stroke="#1e2a5e" stroke-width="10" 
                                        stroke-dasharray="282.7" stroke-dashoffset="42.4" stroke-linecap="round" 
                                        style="transition: stroke-dashoffset 1s ease-out;" />
                            </svg>
                            <div class="position-absolute top-50 start-50 translate-middle text-center">
                                <div class="h2 fw-900 m-0 text-navy">85%</div>
                                <div class="smaller text-muted">Optimal</div>
                            </div>
                        </div>
                    </div>
                    <div class="border-top pt-4">
                        <div class="d-flex justify-content-between small mb-2">
                            <span>Service Speed</span>
                            <span class="text-success fw-bold">Excellent</span>
                        </div>
                        <div class="d-flex justify-content-between small mb-2">
                            <span>Queue Stability</span>
                            <span class="text-success fw-bold">High</span>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span>Staff Performance</span>
                            <span class="text-warning fw-bold">Average</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Smart Alerts -->
            <div class="col-md-12">
                <div class="qs-card">
                    <h5 class="fw-800 mb-4">Optimization Insights</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 rounded-4 border-start border-4 border-success bg-light">
                                <div class="fw-bold small mb-1 text-success">Queue Re-routing</div>
                                <p class="smaller text-muted m-0">Library window is currently idle. AI recommends temporary re-routing for Document verification.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 rounded-4 border-start border-4 border-warning bg-light">
                                <div class="fw-bold small mb-1 text-warning">Wait-Time Warning</div>
                                <p class="smaller text-muted m-0">Registrar wait times are exceeding 20 minutes. Consider opening Window R-2.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 rounded-4 border-start border-4 border-primary bg-light">
                                <div class="fw-bold small mb-1 text-primary">Peak Performance</div>
                                <p class="smaller text-muted m-0">System handled 45 tickets per hour during lunch break with zero cancelled tokens.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('predictionChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['8 AM', '10 AM', '12 PM', '2 PM', '4 PM', '6 PM'],
            datasets: [{
                label: 'Predicted Traffic',
                data: [12, 45, 18, 55, 30, 5],
                borderColor: '#fbbf24',
                backgroundColor: 'rgba(251, 191, 36, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 4,
                pointRadius: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { display: false },
                x: { 
                    grid: { display: false },
                    ticks: { color: 'rgba(255,255,255,0.7)', font: { size: 10 } }
                }
            }
        }
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
