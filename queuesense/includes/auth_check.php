<?php
/**
 * QueueSense — Auth Guard
 * Include this at the top of any protected page.
 * Usage: require_once __DIR__ . '/../includes/auth_check.php';
 *
 * Optional: Pass $required_role before including to enforce role-based access.
 * Example:
 *   $required_role = 'admin';
 *   require_once __DIR__ . '/../includes/auth_check.php';
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';

// ─── Check if logged in ───────────────────────────────────────────────────────
if (!is_logged_in()) {
    redirect(BASE_URL . '/modules/auth/login.php?error=session_expired');
}

// ─── Check role if specified ──────────────────────────────────────────────────
if (isset($required_role) && !has_role($required_role)) {
    // If wrong role, redirect them to their correct dashboard
    $user_role = current_user()['role'];
    if ($user_role === 'admin')   redirect(BASE_URL . '/admin/index.php');
    if ($user_role === 'staff')   redirect(BASE_URL . '/staff/index.php');
    if ($user_role === 'student') redirect(BASE_URL . '/modules/queue/status.php');
    redirect(BASE_URL . '/modules/auth/login.php');
}

// ─── Session timeout check ────────────────────────────────────────────────────
$timeout_seconds = SESSION_TIMEOUT_MINS * 60;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_seconds) {
    session_unset();
    session_destroy();
    redirect(BASE_URL . '/modules/auth/login.php?error=timeout');
}
$_SESSION['last_activity'] = time();

// ─── Convenience variables available after including this file ─────────────────
$current_user      = current_user();
$current_user_id   = $current_user['id'];
$current_user_name = $current_user['full_name'];
$current_user_role = $current_user['role'];
$unread_notifs     = unread_notification_count($current_user_id);
