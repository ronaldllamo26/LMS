<?php
/**
 * QueueSense — Sidebar (Full BCP SMS Edition)
 */
$user = current_user();
$current_user_id = $user['id'] ?? 0;
$db = $db ?? db_connect();

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
            
            <?php
            // Fetch recent notifications
            $stmt_notif = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
            $stmt_notif->bind_param('i', $current_user_id);
            $stmt_notif->execute();
            $notifications = $stmt_notif->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_notif->close();
            
            $unread_count = 0;
            foreach($notifications as $n) if(!$n['is_read']) $unread_count++;
            ?>

            <!-- Notif Dropdown -->
            <div class="dropdown">
                <button class="btn btn-link p-0 text-white opacity-75 position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell fs-5"></i>
                    <?php if ($unread_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle" style="margin-top: 5px; margin-left: -5px;"></span>
                    <?php endif; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow border-0 p-0 overflow-hidden" style="font-size: 0.75rem; margin-top: 10px; min-width: 240px; border-radius: 12px;">
                    <li><div class="dropdown-header text-white fw-bold p-3 border-bottom border-secondary border-opacity-25">Notifications</div></li>
                    <div style="max-height: 250px; overflow-y: auto;">
                        <?php if (empty($notifications)): ?>
                            <li><div class="p-4 text-center text-white-50">No new notifications</div></li>
                        <?php else: ?>
                            <?php foreach($notifications as $notif): ?>
                                <li>
                                    <a class="dropdown-item p-3 border-bottom border-secondary border-opacity-10 d-flex gap-2" href="#">
                                        <div class="bg-<?= $notif['type'] ?> bg-opacity-25 p-2 rounded-circle text-<?= $notif['type'] ?> h-fit" style="height: fit-content;">
                                            <i class="bi bi-info-circle"></i>
                                        </div>
                                        <div class="text-wrap">
                                            <div class="fw-bold mb-1"><?= htmlspecialchars($notif['message']) ?></div>
                                            <div class="text-white-50" style="font-size: 0.65rem;"><?= date('M d, h:i A', strtotime($notif['created_at'])) ?></div>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <li><a class="dropdown-item text-center p-2 small text-white-50 border-top border-secondary border-opacity-25" href="#">View all alerts</a></li>
                </ul>
            </div>
            
            <!-- Account Dropdown -->
            <div class="dropdown">
                <button class="btn btn-link p-0 text-white opacity-75" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                    <i class="bi bi-person-circle fs-5"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow border-0" style="font-size: 0.8rem; margin-top: 10px; min-width: 180px;">
                    <li><div class="dropdown-header text-white-50">Signed in as</div></li>
                    <li><div class="px-3 py-1 fw-bold"><?= htmlspecialchars($user['student_id'] ?? $user['username']) ?></div></li>
                    <li><hr class="dropdown-divider opacity-25"></li>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/auth/profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                    <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/modules/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign out</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- User Profile Section -->
    <div class="qs-sidebar-profile text-center py-4">
        <div class="qs-profile-avatar mb-3 overflow-hidden">
            <?php if (!empty($user['avatar'])): ?>
                <img src="<?= BASE_URL ?>/<?= $user['avatar'] ?>" style="width: 100%; height: 100%; object-fit: cover;">
            <?php else: ?>
                <?= substr($initials, 0, 2) ?>
            <?php endif; ?>
        </div>
        <div class="qs-profile-info px-3">
            <div class="fw-bold text-white mb-1" style="font-size: 1.05rem;"><?= htmlspecialchars($user['full_name']) ?></div>
            <div class="text-white-50" style="font-size: 0.8rem; letter-spacing: 0.3px;"><?= htmlspecialchars($user['student_id'] ?? $user['username'] ?? 'ADMIN') ?>@bcp.edu.ph</div>
        </div>
    </div>

    <nav class="qs-sidebar-nav px-2 pb-5">
        <?php if (has_role('admin')): ?>
            <div class="qs-nav-label">OVERVIEW</div>
            <a href="<?= BASE_URL ?>/admin/index.php" class="qs-nav-item <?= $active_page === 'dashboard' ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pie-chart"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></svg>
                Dashboard
            </a>
            <a href="<?= BASE_URL ?>/admin/reports.php" class="qs-nav-item <?= $active_page === 'reports' ? 'active' : '' ?>">
                <i class="bi bi-bar-chart-line"></i> Analytics Reports
            </a>
            <a href="<?= BASE_URL ?>/display/index.php" target="_blank" class="qs-nav-item">
                <i class="bi bi-tv"></i> Public Monitor
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
            <a href="<?= BASE_URL ?>/display/index.php" target="_blank" class="qs-nav-item">
                <i class="bi bi-tv"></i> Public Monitor
            </a>
        <?php else: ?>
            <div class="qs-nav-label">STUDENT DASHBOARD</div>
            <a href="<?= BASE_URL ?>/modules/queue/status.php" class="qs-nav-item <?= $active_page === 'dashboard' ? 'active' : '' ?>">
                <i class="bi bi-ticket-perforated"></i> My Queue Ticket
            </a>
            <a href="<?= BASE_URL ?>/modules/auth/profile.php" class="qs-nav-item <?= $active_page === 'profile' ? 'active' : '' ?>">
                <i class="bi bi-person"></i> SMS Profile
            </a>
        <?php endif; ?>
    </nav>



</aside>
