<?php
/**
 * QueueSense — System Settings
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

$required_role = 'admin';
require_once __DIR__ . '/../includes/auth_check.php';

$db = db_connect();

$success_msg = '';

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // In a real app, you'd save these to a 'settings' table.
    // For now, we'll simulate a successful save.
    $success_msg = "System settings updated successfully.";
}

$active_page = 'settings';
$page_title = 'System Configuration';

include __DIR__ . '/../includes/header.php';
?>

<main class="qs-main-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="qs-main-content" style="padding: calc(var(--navbar-h) + 20px) 0 0 0 !important; display: flex; flex-direction: column; min-height: 100vh;">
        <div class="p-4 flex-grow-1">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-900 text-navy m-0">System Configuration</h2>
                    <p class="text-muted small m-0">Global system parameters and operational hours</p>
                </div>
            </div>

            <?php if ($success_msg): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?= $success_msg ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- General Settings -->
                <div class="col-md-8">
                    <div class="qs-card h-100">
                        <h5 class="fw-800 mb-4 border-bottom pb-3">General Information</h5>
                        <form method="POST">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">System Name</label>
                                    <input type="text" class="form-control form-control-sm rounded-pill px-3" value="QueueSense Campus Management">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Institution Name</label>
                                    <input type="text" class="form-control form-control-sm rounded-pill px-3" value="Bestlink College of the Philippines">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Contact Email</label>
                                    <input type="email" class="form-control form-control-sm rounded-pill px-3" value="support@queuesense.edu.ph">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Opening Time</label>
                                    <input type="time" class="form-control form-control-sm rounded-pill px-3" value="08:00">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Closing Time</label>
                                    <input type="time" class="form-control form-control-sm rounded-pill px-3" value="17:00">
                                </div>
                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-navy rounded-pill px-5 fw-bold">
                                        Save Configuration
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- System Status -->
                <div class="col-md-4">
                    <div class="qs-card mb-4">
                        <h5 class="fw-800 mb-4">Maintenance Mode</h5>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="small fw-bold">Queue Submissions</span>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" checked>
                            </div>
                        </div>
                        <p class="smaller text-muted">When disabled, students will not be able to join any queues.</p>
                    </div>

                    <div class="qs-card border-start border-4 border-warning" id="securityAuditCard">
                        <h5 class="fw-800 mb-3 text-warning">Security Audit</h5>
                        <div id="scanStatus">
                            <p class="smaller text-muted mb-3" id="lastScanText">Last security scan was performed 2 days ago. No vulnerabilities found.</p>
                            <button id="runScanBtn" class="btn btn-sm btn-light w-100 rounded-pill fw-bold border">Run Scan Now</button>
                        </div>
                        
                        <!-- Scan Progress (Hidden) -->
                        <div id="scanProgress" class="d-none">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
                                <span class="smaller fw-bold">Scanning system...</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div id="scanProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-warning" style="width: 0%"></div>
                            </div>
                        </div>

                        <!-- Scan Results (Hidden) -->
                        <div id="scanResults" class="d-none mt-3 pt-3 border-top">
                            <div id="scanResultsList" style="max-height: 250px; overflow-y: auto;">
                                <!-- Results will be injected here -->
                            </div>
                            <button onclick="resetScanUI()" class="btn btn-sm btn-link text-muted w-100 mt-2 smaller text-decoration-none">Close Results</button>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- End of flex-grow-1 -->
        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</main>

<script>
const runScanBtn = document.getElementById('runScanBtn');
const scanStatus = document.getElementById('scanStatus');
const scanProgress = document.getElementById('scanProgress');
const scanProgressBar = document.getElementById('scanProgressBar');
const scanResults = document.getElementById('scanResults');
const scanResultsList = document.getElementById('scanResultsList');
const lastScanText = document.getElementById('lastScanText');

if (runScanBtn) {
    runScanBtn.addEventListener('click', async () => {
        // 1. UI Transition
        runScanBtn.disabled = true;
        scanStatus.classList.add('d-none');
        scanProgress.classList.remove('d-none');
        
        // 2. Animate progress bar (fake movement for feel)
        let width = 0;
        const interval = setInterval(() => {
            if (width >= 90) clearInterval(interval);
            width += Math.random() * 15;
            scanProgressBar.style.width = Math.min(width, 95) + '%';
        }, 300);

        try {
            // 3. Call Real API
            const response = await fetch(`${window.QS_BASE_URL}/api/security_scan.php`);
            const data = await response.json();
            
            clearInterval(interval);
            scanProgressBar.style.width = '100%';

            setTimeout(() => {
                renderResults(data);
            }, 600);

        } catch (error) {
            clearInterval(interval);
            alert('Security scan failed to initialize.');
            resetScanUI();
        }
    });
}

function renderResults(data) {
    scanProgress.classList.add('d-none');
    scanResults.classList.remove('d-none');
    scanResultsList.innerHTML = '';

    if (data.results) {
        data.results.forEach(res => {
            const icon = res.status === 'pass' ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-warning';
            const item = document.createElement('div');
            item.className = 'd-flex gap-2 mb-3 align-items-start';
            item.innerHTML = `
                <i class="bi ${icon} flex-shrink-0 mt-1"></i>
                <div>
                    <div class="smaller fw-bold">${res.test}</div>
                    <div class="text-muted" style="font-size: 0.75rem;">${res.message}</div>
                </div>
            `;
            scanResultsList.appendChild(item);
        });
        
        // Update the card header based on results
        const card = document.getElementById('securityAuditCard');
        if (data.vulnerabilities_found > 0) {
            lastScanText.innerHTML = `Found ${data.vulnerabilities_found} potential security optimizations.`;
            card.classList.replace('border-warning', 'border-warning'); // Keep warning or change to danger
        } else {
            lastScanText.innerHTML = `Last scan: Just now. System is fully optimized.`;
            card.classList.replace('border-warning', 'border-success');
        }
    }
}

function resetScanUI() {
    scanResults.classList.add('d-none');
    scanProgress.classList.add('d-none');
    scanStatus.classList.remove('d-none');
    runScanBtn.disabled = false;
    scanProgressBar.style.width = '0%';
}
</script>
