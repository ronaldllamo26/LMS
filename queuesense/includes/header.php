<?php
/**
 * QueueSense — Global Header
 * BCP SMS style: clean white topbar + navy sidebar.
 */
$page_title = $page_title ?? SYSTEM_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= SYSTEM_NAME ?> — <?= SYSTEM_TAGLINE ?>">
    <title><?= htmlspecialchars($page_title) ?> — <?= SYSTEM_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- ── TOPBAR ──────────────────────────────────────────────────── -->
<nav class="qs-navbar navbar px-0">
    <div class="container-fluid px-3 gap-2">

        <!-- Hamburger (mobile) -->
        <button class="btn btn-sm d-lg-none border-0 text-muted me-1"
                id="sidebarToggle" onclick="openSidebar()">
            <i class="bi bi-list fs-5"></i>
        </button>

        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center gap-2 me-auto" href="<?= BASE_URL ?>">
            <div class="qs-brand-icon">
                <i class="bi bi-person-lines-fill"></i>
            </div>
            <div class="d-none d-md-block">
                <span class="qs-brand-name"><?= SYSTEM_NAME ?></span>
                <span class="qs-brand-sub d-block"><?= INSTITUTION ?></span>
            </div>
        </a>

        <!-- Right actions -->
        <div class="d-flex align-items-center gap-2">

            <?php if (is_logged_in()): ?>
                <?php $user = current_user(); ?>

                <!-- Notification Bell -->
                <div class="dropdown">
                    <button class="qs-nav-icon position-relative border-0 bg-transparent"
                            id="notifDropdown" data-bs-toggle="dropdown">
                        <i class="bi bi-bell"></i>
                        <?php if (($unread_notifs ?? 0) > 0): ?>
                        <span class="qs-notif-badge" id="notif-count"><?= $unread_notifs ?></span>
                        <?php endif; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end qs-notif-dropdown" id="notif-list">
                        <li><h6 class="dropdown-header" style="font-size:0.72rem;">Notifications</h6></li>
                        <li>
                            <div class="dropdown-item text-muted small text-center py-3" id="notif-loading">
                                Loading...
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- User Menu -->
                <div class="dropdown">
                    <button class="qs-user-menu d-flex align-items-center gap-2 border-0 bg-transparent"
                            id="userDropdown" data-bs-toggle="dropdown">
                        <div class="qs-avatar">
                            <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                        </div>
                        <div class="d-none d-md-block text-start">
                            <div class="qs-username"><?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?></div>
                            <div class="qs-userrole"><?= ucfirst($user['role']) ?></div>
                        </div>
                        <i class="bi bi-chevron-down text-muted" style="font-size:0.65rem;"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end qs-dropdown">
                        <li>
                            <div class="px-3 py-2 border-bottom" style="font-size:0.8rem;">
                                <div class="fw-semibold"><?= htmlspecialchars($user['full_name']) ?></div>
                                <div class="text-muted" style="font-size:0.72rem;"><?= htmlspecialchars($user['student_id']) ?></div>
                            </div>
                        </li>
                        <?php if ($user['role'] === 'student'): ?>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/queue/status.php">
                            <i class="bi bi-ticket-perforated me-2 text-muted"></i>My Queue
                        </a></li>
                        <?php elseif ($user['role'] === 'admin'): ?>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/index.php">
                            <i class="bi bi-speedometer2 me-2 text-muted"></i>Dashboard
                        </a></li>
                        <?php elseif ($user['role'] === 'staff'): ?>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/staff/index.php">
                            <i class="bi bi-display me-2 text-muted"></i>My Window
                        </a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/modules/auth/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Sign Out
                        </a></li>
                    </ul>
                </div>

            <?php else: ?>
                <a href="<?= BASE_URL ?>/modules/auth/login.php" class="qs-btn-primary">
                    <i class="bi bi-box-arrow-in-right"></i> Sign In
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<!-- ── END TOPBAR ──────────────────────────────────────────────── -->
