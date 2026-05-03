<?php
/**
 * QueueSense — Sidebar (BCP SMS Style)
 * Navy dark sidebar with avatar block, section labels, clean nav links.
 */
$active_page = $active_page ?? '';
?>

<aside class="qs-sidebar" id="qsSidebar">

    <!-- Logo -->
    <div class="qs-sidebar-header">
        <div class="qs-sidebar-brand">
            <img src="<?= BASE_URL ?>/assets/images/bcp_logo.png" alt="BCP">
            <span>QueueSense</span>
        </div>
        <button class="qs-sidebar-close d-lg-none" onclick="closeSidebar()">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <!-- User block (like BCP SMS) -->
    <div class="qs-sidebar-user">
        <div class="qs-sidebar-avatar">
            <?= strtoupper(substr($current_user_name ?? 'U', 0, 2)) ?>
        </div>
        <div class="qs-sidebar-uname"><?= htmlspecialchars($current_user_name ?? '') ?></div>
        <div class="qs-sidebar-uid"><?= htmlspecialchars($current_user['student_id'] ?? '') ?></div>
        <span class="qs-sidebar-role"><?= ucfirst($current_user_role ?? '') ?></span>
    </div>

    <!-- Navigation -->
    <nav class="qs-sidebar-nav">

        <?php if (($current_user_role ?? '') === 'admin'): ?>

        <div class="qs-nav-group-label">Overview</div>
        <a href="<?= BASE_URL ?>/admin/index.php"
           class="qs-nav-link <?= $active_page === 'dashboard' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i><span>Dashboard</span>
        </a>

        <div class="qs-nav-group-label">Queue Management</div>
        <a href="<?= BASE_URL ?>/admin/manage_queues.php"
           class="qs-nav-link <?= $active_page === 'queues' ? 'active' : '' ?>">
            <i class="bi bi-list-ol"></i><span>Queue Types</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/manage_windows.php"
           class="qs-nav-link <?= $active_page === 'windows' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2"></i><span>Service Windows</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/manage_users.php"
           class="qs-nav-link <?= $active_page === 'users' ? 'active' : '' ?>">
            <i class="bi bi-people"></i><span>Users</span>
        </a>

        <div class="qs-nav-group-label">Intelligence</div>
        <a href="<?= BASE_URL ?>/admin/analytics.php"
           class="qs-nav-link <?= $active_page === 'analytics' ? 'active' : '' ?>">
            <i class="bi bi-bar-chart-line"></i>
            <span>Analytics</span>
            <span class="qs-nav-badge">AI</span>
        </a>

        <div class="qs-nav-group-label">System</div>
        <a href="<?= BASE_URL ?>/admin/logs.php"
           class="qs-nav-link <?= $active_page === 'logs' ? 'active' : '' ?>">
            <i class="bi bi-journal-text"></i><span>System Logs</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/settings.php"
           class="qs-nav-link <?= $active_page === 'settings' ? 'active' : '' ?>">
            <i class="bi bi-gear"></i><span>Settings</span>
        </a>

        <?php elseif (($current_user_role ?? '') === 'staff'): ?>

        <div class="qs-nav-group-label">My Window</div>
        <a href="<?= BASE_URL ?>/staff/index.php"
           class="qs-nav-link <?= $active_page === 'dashboard' ? 'active' : '' ?>">
            <i class="bi bi-display"></i><span>Serving Dashboard</span>
        </a>

        <?php endif; ?>

    </nav>

    <!-- Footer -->
    <div class="qs-sidebar-footer">
        <a href="<?= BASE_URL ?>/modules/auth/logout.php" class="qs-sidebar-logout">
            <i class="bi bi-box-arrow-right"></i><span>Sign Out</span>
        </a>
    </div>

</aside>

<div class="qs-sidebar-overlay d-lg-none" id="sidebarOverlay" onclick="closeSidebar()"></div>
