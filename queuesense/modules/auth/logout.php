<?php
/**
 * QueueSense — Logout
 * Destroys the current session and redirects to login.
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';

if (is_logged_in()) {
    $user = current_user();
    log_action('LOGOUT', ucfirst($user['role']) . " signed out: {$user['student_id']}");
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();
redirect(BASE_URL . '/modules/auth/login.php');
