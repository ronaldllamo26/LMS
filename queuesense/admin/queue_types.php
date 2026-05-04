<?php
/**
 * QueueSense — Queue Types (Department Management)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

$required_role = 'admin';
require_once __DIR__ . '/../includes/auth_check.php';

$db = db_connect();

$success_msg = '';
$error_msg = '';

// 1. Handle POST Actions (Edit / Toggle / Add)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update') {
        $id = (int)$_POST['id'];
        $name = $_POST['name'];
        $prefix = $_POST['prefix'];
        $limit = (int)$_POST['daily_limit'];
        $avg_time = (int)$_POST['avg_service_time'];
        
        $stmt = $db->prepare("UPDATE queue_types SET name=?, prefix=?, daily_limit=?, avg_service_time=? WHERE id=?");
        $stmt->bind_param("ssiii", $name, $prefix, $limit, $avg_time, $id);
        
        if ($stmt->execute()) $success_msg = "Department updated successfully.";
        else $error_msg = "Error updating department.";
    }
    
    if ($action === 'toggle') {
        $id = (int)$_POST['id'];
        $status = (int)$_POST['status'];
        $stmt = $db->prepare("UPDATE queue_types SET is_open=? WHERE id=?");
        $stmt->bind_param("ii", $status, $id);
        $stmt->execute();
        echo json_encode(['success' => true]);
        exit;
    }
}

// Fetch Queue Types
$q_types = $db->query("SELECT * FROM queue_types ORDER BY sort_order ASC")->fetch_all(MYSQLI_ASSOC);

$active_page = 'queue_types';
$page_title = 'Queue Management';

include __DIR__ . '/../includes/header.php';
?>

<main class="qs-main-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="qs-main-content" style="padding: calc(var(--navbar-h) + 20px) 0 0 0 !important; display: flex; flex-direction: column; min-height: 100vh;">
        <div class="p-4 flex-grow-1">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-900 text-navy m-0">Queue Types</h2>
                    <p class="text-muted small m-0">Configure departments and ticket prefixes</p>
                </div>
                <button class="btn btn-navy btn-sm rounded-pill px-4">
                    <i class="bi bi-plus-lg me-2"></i> Create New Type
                </button>
            </div>

            <?php if ($success_msg): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $success_msg ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <?php foreach($q_types as $qt): ?>
                <div class="col-md-6">
                    <div class="qs-card card-thick-simple p-0 overflow-hidden h-100">
                        <div class="p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="bg-light rounded-circle p-3 me-3 text-navy">
                                        <i class="bi <?= $qt['icon'] ?> fs-4"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-800 m-0"><?= htmlspecialchars($qt['name']) ?></h5>
                                        <span class="badge badge-soft-primary qs-badge mt-1">Prefix: <?= $qt['prefix'] ?></span>
                                    </div>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" 
                                           onchange="toggleStatus(<?= $qt['id'] ?>, this.checked)"
                                           <?= $qt['is_open'] ? 'checked' : '' ?>>
                                </div>
                            </div>
                            <p class="small text-muted mb-4"><?= htmlspecialchars($qt['description']) ?></p>
                            
                            <div class="row g-3 border-top pt-3">
                                <div class="col-6">
                                    <div class="small text-muted mb-1">Daily Limit</div>
                                    <div class="fw-bold"><?= $qt['daily_limit'] ?> Tickets</div>
                                </div>
                                <div class="col-6 text-end">
                                    <div class="small text-muted mb-1">Avg. Time</div>
                                    <div class="fw-bold"><?= $qt['avg_service_time'] ?> mins</div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-light p-3 d-flex justify-content-between mt-auto">
                            <button class="btn btn-sm btn-white rounded-pill px-3 fw-bold shadow-sm" 
                                    onclick="editType(<?= htmlspecialchars(json_encode($qt)) ?>)">
                                <i class="bi bi-pencil-square me-2"></i> Edit Config
                            </button>
                            <a href="service_windows.php?dept=<?= $qt['id'] ?>" class="btn btn-sm btn-white rounded-pill px-3 fw-bold text-navy shadow-sm">
                                View Windows
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div> <!-- End of flex-grow-1 -->
        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</main>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-800">Edit Department Configuration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Department Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control form-control-sm rounded-pill px-3" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Ticket Prefix</label>
                            <input type="text" name="prefix" id="edit_prefix" class="form-control form-control-sm rounded-pill px-3" maxlength="5" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Daily Limit</label>
                            <input type="number" name="daily_limit" id="edit_limit" class="form-control form-control-sm rounded-pill px-3" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label small fw-bold">Avg. Service Time (Minutes)</label>
                        <input type="number" name="avg_service_time" id="edit_avg_time" class="form-control form-control-sm rounded-pill px-3" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy rounded-pill px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleStatus(id, checked) {
    const status = checked ? 1 : 0;
    const formData = new FormData();
    formData.append('action', 'toggle');
    formData.append('id', id);
    formData.append('status', status);

    fetch('queue_types.php', {
        method: 'POST',
        body: formData
    }).then(res => res.json()).then(data => {
        if(!data.success) alert('Failed to update status');
    });
}

function editType(qt) {
    document.getElementById('edit_id').value = qt.id;
    document.getElementById('edit_name').value = qt.name;
    document.getElementById('edit_prefix').value = qt.prefix;
    document.getElementById('edit_limit').value = qt.daily_limit;
    document.getElementById('edit_avg_time').value = qt.avg_service_time;
    
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
