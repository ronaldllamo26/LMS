<?php
/**
 * QueueSense — Security Audit API
 * Performs real-time checks on the system environment and configuration.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

// Only admins can run scans
if (!is_logged_in() || !has_role('admin')) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Simulate a slight delay for "processing" feel (better UX)
usleep(1500000); // 1.5 seconds

$results = [];
$vulnerabilities_found = 0;

// 1. Check PHP Version
$php_version = PHP_VERSION;
$is_modern_php = PHP_VERSION_ID >= 80100; // 8.1+
$results[] = [
    'test' => 'PHP Environment',
    'status' => $is_modern_php ? 'pass' : 'warning',
    'message' => "Running PHP $php_version. " . ($is_modern_php ? "Current and secure." : "Consider upgrading to 8.1+ for better security.")
];
if (!$is_modern_php) $vulnerabilities_found++;

// 2. Check Database Connection
try {
    $db = db_connect();
    if ($db->ping()) {
        $results[] = [
            'test' => 'Database Integrity',
            'status' => 'pass',
            'message' => 'Database connection is stable and responsive.'
        ];
    } else {
        throw new Exception("Database ping failed.");
    }
} catch (Exception $e) {
    $results[] = [
        'test' => 'Database Integrity',
        'status' => 'fail',
        'message' => 'Database connection issue detected.'
    ];
    $vulnerabilities_found++;
}

// 3. Check CSRF Protection
if (isset($_SESSION['csrf_token']) && strlen($_SESSION['csrf_token']) > 10) {
    $results[] = [
        'test' => 'CSRF Defense',
        'status' => 'pass',
        'message' => 'Cross-Site Request Forgery tokens are active and valid.'
    ];
} else {
    $results[] = [
        'test' => 'CSRF Defense',
        'status' => 'warning',
        'message' => 'CSRF protection session not initialized.'
    ];
    $vulnerabilities_found++;
}

// 4. Check for Sensitive Directory Exposure (.git)
$git_path = __DIR__ . '/../.git';
if (is_dir($git_path)) {
    $results[] = [
        'test' => 'Directory Privacy',
        'status' => 'warning',
        'message' => 'Repository metadata (.git) detected. Ensure this is not accessible via URL.'
    ];
    $vulnerabilities_found++;
} else {
    $results[] = [
        'test' => 'Directory Privacy',
        'status' => 'pass',
        'message' => 'No sensitive repository metadata exposed in root.'
    ];
}

// 5. Check HTTPS
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
if ($is_https) {
    $results[] = [
        'test' => 'Encryption (SSL)',
        'status' => 'pass',
        'message' => 'Connection is encrypted via HTTPS.'
    ];
} else {
    $results[] = [
        'test' => 'Encryption (SSL)',
        'status' => 'warning',
        'message' => 'System is running on unencrypted HTTP (Common for Localhost).'
    ];
}

// 6. Check for Error Display (Should be off in production)
$display_errors = ini_get('display_errors');
if ($display_errors == '1' || strtolower($display_errors) === 'on') {
    $results[] = [
        'test' => 'Debug Exposure',
        'status' => 'warning',
        'message' => 'Detailed error reporting is ON. Safe for dev, should be OFF for production.'
    ];
} else {
    $results[] = [
        'test' => 'Debug Exposure',
        'status' => 'pass',
        'message' => 'Error reporting is suppressed. (Production Ready)'
    ];
}

echo json_encode([
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'vulnerabilities_found' => $vulnerabilities_found,
    'results' => $results
]);
