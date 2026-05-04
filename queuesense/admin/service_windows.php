<?php
/**
 * QueueSense — Service Windows Management
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

$required_role = 'admin';
require_once __DIR__ . '/../includes/auth_check.php';

$db = db_connect();

$success_msg = '';
$error_msg = '';

// Handle POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $label = $_POST['window_label'];
        $dept_id = (int)$_POST['queue_type_id'];
        
        $stmt = $db->prepare("INSERT INTO service_windows (queue_type_id, window_label, status) VALUES (?, ?, 'closed')");
        $stmt->bind_param("is", $dept_id, $label);
        
        if ($stmt->execute()) $success_msg = "Window registered successfully.";
        else $error_msg = "Error registering window.";
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $db->query("DELETE FROM service_windows WHERE id = $id");
        $success_msg = "Window removed.";
    }
}

// Fetch Windows with Queue Type and Staff Names
$windows_sql = "SELECT sw.*, qt.name as dept_name, u.full_name as staff_name 
                FROM service_windows sw
                LEFT JOIN queue_types qt ON qt.id = sw.queue_type_id
                LEFT JOIN users u ON u.id = sw.staff_id
                ORDER BY qt.id ASC, sw.window_label ASC";
$windows = $db->query($windows_sql)->fetch_all(MYSQLI_ASSOC);

// Fetch Queue Types for Modal
$q_types = $db->query("SELECT id, name FROM queue_types")->fetch_all(MYSQLI_ASSOC);

// Fetch Staff for Assignment Dropdown
$staff_list = $db->query("SELECT id, full_name FROM users WHERE role = 'staff' AND is_active = 1 ORDER BY full_name ASC")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
    $id = (int)$_POST['id'];
    $label = $_POST['window_label'];
    $dept_id = (int)$_POST['queue_type_id'];
    $staff_id = $_POST['staff_id'] ? (int)$_POST['staff_id'] : null;
    
    $stmt = $db->prepare("UPDATE service_windows SET window_label=?, queue_type_id=?, staff_id=? WHERE id=?");
    $stmt->bind_param("siii", $label, $dept_id, $staff_id, $id);
    
    if ($stmt->execute()) $success_msg = "Window updated successfully.";
    else $error_msg = "Error updating window.";
}

$active_page = 'service_windows';
$page_title = 'Window Management';

include __DIR__ . '/../includes/header.php';
?>

<main class="qs-main-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="qs-main-content" style="padding: calc(var(--navbar-h) + 20px) 0 0 0 !important; display: flex; flex-direction: column; min-height: 100vh;">
        <div class="p-4 flex-grow-1">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-900 text-navy m-0">Service Windows</h2>
                    <p class="text-muted small m-0">Manage physical service points across campus</p>
                </div>
                <button class="btn btn-navy btn-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addWindowModal">
                    <i class="bi bi-door-open me-2"></i> Register New Window
                </button>
            </div>

            <?php if ($success_msg): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $success_msg ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <?php foreach($windows as $w): ?>
                <div class="col-md-4">
                    <div class="qs-card card-thick-simple p-4 h-100 shadow-sm border-0 position-relative overflow-hidden">
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div class="rounded-3 bg-light p-3 text-navy">
                                <i class="bi bi-display fs-4"></i>
                            </div>
                            <?php 
                                $status_class = 'badge-soft-danger';
                                if($w['status'] == 'open') $status_class = 'badge-soft-success';
                                if($w['status'] == 'break') $status_class = 'badge-soft-warning';
                            ?>
                            <span class="qs-badge <?= $status_class ?>"><?= $w['status'] ?></span>
                        </div>

                        <h5 class="fw-800 mb-1"><?= htmlspecialchars($w['window_label']) ?></h5>
                        <div class="text-muted small mb-4"><?= htmlspecialchars($w['dept_name']) ?></div>

                        <div class="border-top pt-3 mt-auto">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                    <i class="bi bi-person small text-muted"></i>
                                </div>
                                <div class="small">
                                    <div class="text-muted smaller">Assigned Staff</div>
                                    <div class="fw-bold"><?= $w['staff_name'] ?: 'None (Idle)' ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-2 d-flex gap-2">
                            <button class="btn btn-sm btn-navy rounded-pill flex-grow-1 fw-bold small" 
                                    onclick='prepEditWindow(<?= json_encode($w) ?>)'>
                                <i class="bi bi-pencil-square me-1"></i> Edit / Assign
                            </button>
                            <form method="POST" onsubmit="return confirm('Remove this window?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $w['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-light rounded-pill px-3 text-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div> <!-- End of flex-grow-1 -->
        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</main>

<!-- Register Window Modal -->
<div class="modal fade" id="addWindowModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-800">Register New Window</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Window Label</label>
                        <input type="text" name="window_label" class="form-control form-control-sm rounded-pill px-3" placeholder="e.g. Window 1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Assign to Department</label>
                        <select name="queue_type_id" class="form-select form-select-sm rounded-pill px-3">
                            <?php foreach($q_types as $qt): ?>
                            <option value="<?= $qt['id'] ?>"><?= htmlspecialchars($qt['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy rounded-pill px-4">Register Window</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Window Modal -->
<div class="modal fade" id="editWindowModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-800">Edit Window & Assignment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_window_id">
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Window Label</label>
                        <input type="text" name="window_label" id="edit_window_label" class="form-control form-control-sm rounded-pill px-3" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Department</label>
                        <select name="queue_type_id" id="edit_queue_type_id" class="form-select form-select-sm rounded-pill px-3">
                            <?php foreach($q_types as $qt): ?>
                            <option value="<?= $qt['id'] ?>"><?= htmlspecialchars($qt['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Assign Staff Member</label>
                        <select name="staff_id" id="edit_staff_id" class="form-select form-select-sm rounded-pill px-3">
                            <option value="">-- No Staff Assigned (Idle) --</option>
                            <?php foreach($staff_list as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
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
function prepEditWindow(w) {
    document.getElementById('edit_window_id').value = w.id;
    document.getElementById('edit_window_label').value = w.window_label;
    document.getElementById('edit_queue_type_id').value = w.queue_type_id;
    document.getElementById('edit_staff_id').value = w.staff_id || '';
    
    new bootstrap.Modal(document.getElementById('editWindowModal')).show();
}
</script>

<?php // End of file ?>
