<?php
/**
 * QueueSense — Global Configuration
 * Database connection, system constants, and environment settings.
 */

// ─── Timezone ─────────────────────────────────────────────────────────────────
date_default_timezone_set('Asia/Manila');

// ─── Database Credentials ─────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'queuesense_db');
define('DB_PORT', 3306);

// ─── System Info ──────────────────────────────────────────────────────────────
define('SYSTEM_NAME',    'QueueSense');
define('SYSTEM_TAGLINE', 'Smart Queue & Crowd Flow Management');
define('SYSTEM_VERSION', '1.0.0');
define('INSTITUTION',    'Bestlink College of the Philippines');
define('BASE_URL',       'http://localhost/lms/queuesense');

// ─── Queue Settings ───────────────────────────────────────────────────────────
define('QUEUE_RESET_HOUR',     8);    // Hour queues open (24h format)
define('PEAK_THRESHOLD',       10);   // Tickets/hour before flagged as "peak"
define('POLL_INTERVAL_MS',     5000); // AJAX polling interval (milliseconds)
define('MAX_DAILY_TICKETS',    150);  // Max tickets per queue type per day
define('SESSION_TIMEOUT_MINS', 30);   // Auto-logout after inactivity

// ─── QR Settings ──────────────────────────────────────────────────────────────
define('QR_API_URL', 'https://api.qrserver.com/v1/create-qr-code/');
define('QR_SIZE',    '200x200');

// ─── Database Connection ──────────────────────────────────────────────────────
function db_connect(): mysqli {
    static $conn = null;

    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

        if ($conn->connect_error) {
            // Show friendly error — never expose raw DB errors in production
            die(render_db_error($conn->connect_error));
        }

        $conn->set_charset('utf8mb4');
    }

    return $conn;
}

/**
 * Renders a clean error page when DB connection fails.
 */
function render_db_error(string $error): string {
    return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Connection Error — ' . SYSTEM_NAME . '</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
    <div class="text-center p-5">
        <div class="mb-3 text-danger" style="font-size:3rem;">⚠️</div>
        <h4 class="fw-bold text-danger">Database Connection Failed</h4>
        <p class="text-muted">Please make sure XAMPP MySQL is running and the database is configured correctly.</p>
        <code class="d-block bg-light border rounded p-2 text-danger small mt-2">' . htmlspecialchars($error) . '</code>
    </div>
</body>
</html>';
}

// ─── Start Session ────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_TIMEOUT_MINS * 60,
        'path'     => '/',
        'secure'   => false, // Set to true on HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
