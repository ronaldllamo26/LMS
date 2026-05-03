<?php
/**
 * QueueSense — Entry Point
 * Redirects users to the appropriate page based on their session role.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    $role = current_user()['role'];
    if ($role === 'admin') redirect(BASE_URL . '/admin/index.php');
    if ($role === 'staff') redirect(BASE_URL . '/staff/index.php');
    redirect(BASE_URL . '/modules/queue/status.php');
}

redirect(BASE_URL . '/modules/auth/login.php');
