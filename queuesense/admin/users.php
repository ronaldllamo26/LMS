<?php
/**
 * QueueSense — User Management
 * Handle Staff and Admin accounts
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

$required_role = 'admin';
require_once __DIR__ . '/../includes/auth_check.php';

$db = db_connect();

// Handle Form Submissions (Add/Edit/Toggle)
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $sid = $_POST['student_id'] ?? '';
        $name = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $role = $_POST['role'] ?? 'staff';
        $dept = $_POST['department'] ?? '';
        $pass = $_POST['password'] ?? 'Staff@1234';
        
        $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
        
        $stmt = $db->prepare("INSERT INTO users (student_id, full_name, email, password_hash, role, department) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $sid, $name, $email, $hash, $role, $dept);
        
        if ($stmt->execute()) $success_msg = "User created successfully.";
        else $error_msg = "Error creating user.";
    }

    if ($action === 'update') {
        $id = (int)$_POST['id'];
        $sid = $_POST['student_id'];
        $name = $_POST['full_name'];
        $email = $_POST['email'];
        $role = $_POST['role'];
        $dept = $_POST['department'];
        
        $stmt = $db->prepare("UPDATE users SET student_id=?, full_name=?, email=?, role=?, department=? WHERE id=?");
        $stmt->bind_param("sssssi", $sid, $name, $email, $role, $dept, $id);
        
        if ($stmt->execute()) $success_msg = "User updated successfully.";
        else $error_msg = "Error updating user.";
    }

    if ($action === 'toggle') {
        $id = (int)$_POST['id'];
        $status = (int)$_POST['status'];
        $stmt = $db->prepare("UPDATE users SET is_active=? WHERE id=?");
        $stmt->bind_param("ii", $status, $id);
        $stmt->execute();
        echo json_encode(['success' => true]);
        exit;
    }
}

// Fetch Stats
$total_users = $db->query("SELECT COUNT(*) FROM users WHERE role IN ('admin', 'staff')")->fetch_row()[0];
$active_staff = $db->query("SELECT COUNT(*) FROM users WHERE role = 'staff' AND is_active = 1")->fetch_row()[0];
$total_admins = $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetch_row()[0];

// Fetch Users
$users = $db->query("SELECT * FROM users WHERE role IN ('admin', 'staff') ORDER BY role ASC, full_name ASC")->fetch_all(MYSQLI_ASSOC);

$active_page = 'users';
$page_title = 'User Management';

include __DIR__ . '/../includes/header.php';
?>

<main class="qs-main-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="qs-main-content" style="padding: calc(var(--navbar-h) + 20px) 0 0 0 !important; display: flex; flex-direction: column; min-height: 100vh;">
        <div class="p-4 flex-grow-1">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-900 text-navy m-0">User Management</h2>
                    <p class="text-muted small m-0">Manage staff and administrative access</p>
                </div>
                <button class="btn btn-navy btn-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#userModal" onclick="prepAdd()">
                    <i class="bi bi-person-plus me-2"></i> Add New User
                </button>
            </div>

            <?php if ($success_msg): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $success_msg ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Mini Stats -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="qs-stat-card card-thick-simple">
                        <div class="qs-stat-label">Total Staff</div>
                        <div class="qs-stat-value"><?= $total_users ?></div>
                        <i class="bi bi-people qs-stat-icon"></i>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="qs-stat-card card-thick-simple">
                        <div class="qs-stat-label">Active Staff</div>
                        <div class="qs-stat-value text-success"><?= $active_staff ?></div>
                        <i class="bi bi-person-check qs-stat-icon"></i>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="qs-stat-card card-thick-simple">
                        <div class="qs-stat-label">System Admins</div>
                        <div class="qs-stat-value text-navy"><?= $total_admins ?></div>
                        <i class="bi bi-shield-lock qs-stat-icon"></i>
                    </div>
                </div>
            </div>

            <!-- User Table -->
            <div class="qs-card card-thick-simple">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr class="small text-uppercase fw-bold text-muted">
                                <th>Employee/ID</th>
                                <th>Full Name</th>
                                <th>Department</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php foreach($users as $u): ?>
                            <tr>
                                <td class="fw-bold text-navy"><?= htmlspecialchars($u['student_id']) ?></td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($u['full_name']) ?></div>
                                    <div class="text-muted smaller"><?= htmlspecialchars($u['email']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($u['department'] ?: 'System') ?></td>
                                <td>
                                    <?php 
                                        $role_class = $u['role'] === 'admin' ? 'badge-soft-danger' : 'badge-soft-primary';
                                    ?>
                                    <span class="qs-badge <?= $role_class ?>">
                                        <?= $u['role'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" 
                                               onchange="toggleUser(<?= $u['id'] ?>, this.checked)"
                                               <?= $u['is_active'] ? 'checked' : '' ?>>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-light btn-sm rounded-pill px-3" onclick='prepEdit(<?= json_encode($u) ?>)'>Edit</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div> <!-- End of flex-grow-1 -->
        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</main>

<!-- Add/Edit User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-800" id="modalTitle">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" id="modalAction" value="add">
                <input type="hidden" name="id" id="user_id">
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Employee ID / Student ID</label>
                        <input type="text" name="student_id" id="u_sid" class="form-control form-control-sm rounded-pill px-3" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Full Name</label>
                        <input type="text" name="full_name" id="u_name" class="form-control form-control-sm rounded-pill px-3" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email Address</label>
                        <input type="email" name="email" id="u_email" class="form-control form-control-sm rounded-pill px-3" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Role</label>
                            <select name="role" id="u_role" class="form-select form-select-sm rounded-pill px-3">
                                <option value="staff">Staff</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Department</label>
                            <input type="text" name="department" id="u_dept" class="form-control form-control-sm rounded-pill px-3" placeholder="e.g. Registrar">
                        </div>
                    </div>
                    <div id="passField" class="mt-3">
                        <label class="form-label small fw-bold">Default Password</label>
                        <input type="text" name="password" class="form-control form-control-sm rounded-pill px-3" value="Staff@1234">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-navy rounded-pill px-4" id="modalBtn">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleUser(id, checked) {
    const status = checked ? 1 : 0;
    const formData = new FormData();
    formData.append('action', 'toggle');
    formData.append('id', id);
    formData.append('status', status);
    formData.append('csrf_token', '<?= csrf_token() ?>');

    fetch('users.php', { method: 'POST', body: formData })
    .then(res => res.json()).then(data => {
        if(!data.success) alert('Error updating status');
    });
}

function prepAdd() {
    document.getElementById('modalAction').value = 'add';
    document.getElementById('modalTitle').innerText = 'Add New User';
    document.getElementById('modalBtn').innerText = 'Create Account';
    document.getElementById('passField').style.display = 'block';
    document.getElementById('user_id').value = '';
    document.getElementById('u_sid').value = '';
    document.getElementById('u_name').value = '';
    document.getElementById('u_email').value = '';
    document.getElementById('u_role').value = 'staff';
    document.getElementById('u_dept').value = '';
}

function prepEdit(u) {
    document.getElementById('modalAction').value = 'update';
    document.getElementById('modalTitle').innerText = 'Edit User Account';
    document.getElementById('modalBtn').innerText = 'Save Changes';
    document.getElementById('passField').style.display = 'none';
    
    document.getElementById('user_id').value = u.id;
    document.getElementById('u_sid').value = u.student_id;
    document.getElementById('u_name').value = u.full_name;
    document.getElementById('u_email').value = u.email;
    document.getElementById('u_role').value = u.role;
    document.getElementById('u_dept').value = u.department;
    
    new bootstrap.Modal(document.getElementById('userModal')).show();
}
</script>

<?php // End of file ?>
