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

<div class="d-flex">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <main class="flex-grow-1 qs-main-content">
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

                <div class="qs-card border-start border-4 border-warning">
                    <h5 class="fw-800 mb-3 text-warning">Security Audit</h5>
                    <p class="smaller text-muted mb-3">Last security scan was performed 2 days ago. No vulnerabilities found.</p>
                    <button class="btn btn-sm btn-light w-100 rounded-pill fw-bold">Run Scan Now</button>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
