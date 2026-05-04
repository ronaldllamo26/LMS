<?php
/**
 * QueueSense — Sidebar (Full BCP SMS Edition)
 */
$user = current_user();
$initials = '';
if ($user) {
    $names = explode(' ', $user['full_name']);
    foreach($names as $n) $initials .= strtoupper(substr($n, 0, 1));
}
?>
<aside class="qs-sidebar">
    
    <!-- Sidebar Header: Logo + Notif + User Icon -->
    <div class="qs-sidebar-header d-flex align-items-center justify-content-between px-3">
        <div class="d-flex align-items-center gap-2">
            <img src="<?= BASE_URL ?>/assets/images/bcp_logo.png" height="28" alt="Logo">
        </div>
        <div class="d-flex align-items-center gap-2" style="position: relative;">
            <!-- Notif -->
            <button class="btn btn-link p-0 text-white opacity-75"><i class="bi bi-bell fs-5"></i></button>
            
            <!-- Account Dropdown -->
            <div class="dropdown">
                <button class="btn btn-link p-0 text-white opacity-75" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                    <i class="bi bi-person-circle fs-5"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow border-0" style="font-size: 0.8rem; margin-top: 10px; min-width: 180px;">
                    <li><div class="dropdown-header text-white-50">Signed in as</div></li>
                    <li><div class="px-3 py-1 fw-bold"><?= htmlspecialchars($user['student_id'] ?? $user['username']) ?></div></li>
                    <li><hr class="dropdown-divider opacity-25"></li>
                    <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profile</a></li>
                    <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/modules/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign out</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- User Profile Section -->
    <div class="qs-sidebar-profile text-center py-4">
        <div class="qs-profile-avatar mb-3"><?= substr($initials, 0, 2) ?></div>
        <div class="qs-profile-info px-3">
            <div class="fw-bold text-white small"><?= htmlspecialchars($user['full_name']) ?></div>
            <div class="text-white-50 small" style="font-size: 0.65rem;"><?= htmlspecialchars($user['student_id'] ?? $user['username'] ?? 'ADMIN') ?>@bcp.edu.ph</div>
        </div>
    </div>

    <nav class="qs-sidebar-nav px-2 pb-5">
        <?php if (has_role('admin')): ?>
            <div class="qs-nav-label">OVERVIEW</div>
            <a href="<?= BASE_URL ?>/admin/index.php" class="qs-nav-item <?= $active_page === 'dashboard' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="<?= BASE_URL ?>/admin/reports.php" class="qs-nav-item <?= $active_page === 'reports' ? 'active' : '' ?>">
                <i class="bi bi-bar-chart-line"></i> Analytics Reports
            </a>
            <a href="<?= BASE_URL ?>/admin/settings.php" class="qs-nav-item <?= $active_page === 'settings' ? 'active' : '' ?>">
                <i class="bi bi-gear-fill"></i> Management Settings
            </a>

            <div class="qs-nav-label">QUEUE MANAGEMENT</div>
            <a href="<?= BASE_URL ?>/admin/queue_types.php" class="qs-nav-item <?= $active_page === 'queue_types' ? 'active' : '' ?>"><i class="bi bi-list-task"></i> Queue Types</a>
            <a href="<?= BASE_URL ?>/admin/service_windows.php" class="qs-nav-item <?= $active_page === 'service_windows' ? 'active' : '' ?>"><i class="bi bi-window-sidebar"></i> Service Windows</a>
            <a href="<?= BASE_URL ?>/admin/users.php" class="qs-nav-item <?= $active_page === 'users' ? 'active' : '' ?>"><i class="bi bi-people"></i> Users</a>

            <div class="qs-nav-label">INTELLIGENCE</div>
            <a href="<?= BASE_URL ?>/admin/ai_analytics.php" class="qs-nav-item <?= $active_page === 'ai_analytics' ? 'active' : '' ?>"><i class="bi bi-cpu"></i> AI Analytics</a>
            
        <?php elseif (has_role('staff')): ?>
            <div class="qs-nav-label">MY WINDOW</div>
            <a href="<?= BASE_URL ?>/staff/index.php" class="qs-nav-item <?= $active_page === 'dashboard' ? 'active' : '' ?>">
                <i class="bi bi-display"></i> Serving Dashboard
            </a>
            <a href="<?= BASE_URL ?>/staff/scanner.php" class="qs-nav-item <?= $active_page === 'scanner' ? 'active' : '' ?>">
                <i class="bi bi-qr-code-scan"></i> QR Scanner
            </a>
        <?php else: ?>
            <div class="qs-nav-label">STUDENT DASHBOARD</div>
            <a href="<?= BASE_URL ?>/modules/queue/status.php" class="qs-nav-item <?= $active_page === 'dashboard' ? 'active' : '' ?>">
                <i class="bi bi-ticket-perforated"></i> My Queue Ticket
            </a>
        <?php endif; ?>
    </nav>

    <div class="qs-sidebar-footer mt-auto p-3">
        <a href="<?= BASE_URL ?>/modules/auth/logout.php" class="text-white-50 text-decoration-none small d-flex align-items-center gap-2">
            <i class="bi bi-box-arrow-left"></i> Sign Out
        </a>
    </div>

</aside>
