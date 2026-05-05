<?php
/**
 * QueueSense — Global Configuration
 * Database connection, system constants, and environment settings.
 */

// ─── Timezone ─────────────────────────────────────────────────────────────────
date_default_timezone_set('Asia/Manila');

// ─── Security Headers ─────────────────────────────────────────────────────────
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com; img-src 'self' data: https://api.qrserver.com; connect-src 'self';");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

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

// ─── Rate Limiting ────────────────────────────────────────────────────────────
function check_rate_limit(): void {
    $is_post = ($_SERVER['REQUEST_METHOD'] === 'POST');
    $max_requests = $is_post ? 15 : 60; // 15 POSTs or 60 GETs per minute
    $seconds = 60;
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $key = "rate_limit_" . ($is_post ? 'post_' : 'get_') . md5($ip);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 1, 'start' => time()];
        return;
    }
    
    $data = &$_SESSION[$key];
    if (time() - $data['start'] > $seconds) {
        $data = ['count' => 1, 'start' => time()];
    } else {
        $data['count']++;
        if ($data['count'] > $max_requests) {
            http_response_code(429);
            die('Too many requests. Please slow down.');
        }
    }
}

// Apply rate limiting globally (except for some assets if needed)
if (strpos($_SERVER['REQUEST_URI'] ?? '', 'assets/') === false) {
    check_rate_limit();
}
