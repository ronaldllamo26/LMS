<?php
/**
 * QueueSense — QR Code Login Handler
 *
 * Two modes:
 *  1. GET ?token=xxx  — Validates the QR token and logs the student in.
 *  2. No token       — Shows the QR scanner simulation page.
 *
 * QR token URL format:
 *   http://localhost/queuesense/modules/auth/qr_login.php?token={qr_token}
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';

// ─── Token-based login (when QR is "scanned") ─────────────────────────────────
if (!empty($_GET['token'])) {
    $token = trim($_GET['token']);

    $db   = db_connect();
    $sql  = "SELECT id, student_id, full_name, role, department, is_active
             FROM users
             WHERE qr_token = ? AND role = 'student'
             LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        redirect(BASE_URL . '/modules/auth/login.php?error=invalid_qr');
    }

    if (!$user['is_active']) {
        redirect(BASE_URL . '/modules/auth/login.php?error=inactive');
    }

    // Log in the student via QR
    session_regenerate_id(true);
    $_SESSION['user']          = $user;
    $_SESSION['last_activity'] = time();
    log_action('QR_LOGIN', "QR login: {$user['student_id']}");

    redirect(BASE_URL . '/modules/queue/status.php');
}

// ─── QR Simulation Page (shows a demo QR scanner UI) ─────────────────────────
// Already logged in? Redirect
if (is_logged_in()) {
    redirect(BASE_URL . '/modules/queue/status.php');
}

$page_title = 'QR Code Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Login — <?= SYSTEM_NAME ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">

    <style>
        .qs-qr-scanner {
            position: relative;
            width: 240px;
            height: 240px;
            margin: 0 auto 24px;
            border-radius: 12px;
            overflow: hidden;
        }

        .qs-qr-frame {
            width: 100%;
            height: 100%;
            background: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qs-scanner-line {
            position: absolute;
            left: 10px;
            right: 10px;
            height: 2px;
            background: linear-gradient(90deg, transparent, #0ea5e9, transparent);
            box-shadow: 0 0 8px #0ea5e9;
            animation: scan 2s ease-in-out infinite;
        }

        @keyframes scan {
            0%   { top: 10px; }
            50%  { top: 220px; }
            100% { top: 10px; }
        }

        .qs-qr-corner {
            position: absolute;
            width: 20px;
            height: 20px;
            border-color: #0ea5e9;
            border-style: solid;
            border-width: 0;
        }

        .qs-qr-corner.tl { top: 8px; left: 8px;  border-top-width: 3px; border-left-width: 3px; }
        .qs-qr-corner.tr { top: 8px; right: 8px; border-top-width: 3px; border-right-width: 3px; }
        .qs-qr-corner.bl { bottom: 8px; left: 8px; border-bottom-width: 3px; border-left-width: 3px; }
        .qs-qr-corner.br { bottom: 8px; right: 8px; border-bottom-width: 3px; border-right-width: 3px; }

        .qs-demo-students { max-height: 280px; overflow-y: auto; }

        .qs-demo-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: var(--qs-radius-sm);
            cursor: pointer;
            transition: var(--qs-transition);
            text-decoration: none;
            color: var(--qs-text);
        }

        .qs-demo-item:hover { background: var(--qs-bg); color: var(--qs-primary); }

        .qs-demo-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--qs-primary), var(--qs-accent));
            color: white;
            font-size: 0.85rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
    </style>
</head>
<body>

<div class="qs-login-page">
    <div class="qs-login-card" style="max-width: 460px;">

        <!-- Back button -->
        <a href="<?= BASE_URL ?>/modules/auth/login.php"
           class="d-inline-flex align-items-center gap-1 mb-4 text-muted"
           style="font-size:0.85rem; text-decoration:none;">
            <i class="bi bi-arrow-left"></i> Back to Login
        </a>

        <div class="text-center mb-4">
            <div class="qs-login-logo" style="background: linear-gradient(135deg,#059669,#0ea5e9);">
                <i class="bi bi-qr-code-scan"></i>
            </div>
            <h2 style="font-size:1.4rem; font-weight:800; color:var(--qs-text); margin-bottom:4px;">
                QR Code Login
            </h2>
            <p class="text-muted" style="font-size:0.85rem;">
                Scan your student QR card to enter the queue system
            </p>
        </div>

        <!-- Simulated Scanner View -->
        <div class="qs-qr-scanner mb-2">
            <div class="qs-qr-frame">
                <i class="bi bi-qr-code text-white opacity-25" style="font-size: 5rem;"></i>
            </div>
            <div class="qs-scanner-line"></div>
            <div class="qs-qr-corner tl"></div>
            <div class="qs-qr-corner tr"></div>
            <div class="qs-qr-corner bl"></div>
            <div class="qs-qr-corner br"></div>
        </div>

        <p class="text-center text-muted mb-4" style="font-size:0.8rem;">
            <span class="qs-live-dot"></span>Scanner active — point your QR card at the camera
        </p>

        <hr style="border-color:var(--qs-border)">

        <!-- Demo: Select a student to simulate QR scan -->
        <div class="mb-2">
            <div style="font-size:0.78rem; font-weight:700; text-transform:uppercase;
                        letter-spacing:0.6px; color:var(--qs-text-muted); margin-bottom:10px;">
                <i class="bi bi-lightning-fill text-warning me-1"></i>
                Demo Mode — Select a student to simulate scan
            </div>

            <div class="qs-demo-students">
                <?php
                $db   = db_connect();
                $stmt = $db->prepare("SELECT student_id, full_name, department, qr_token
                                      FROM users WHERE role = 'student' AND qr_token IS NOT NULL
                                      ORDER BY full_name LIMIT 15");
                $stmt->execute();
                $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();

                foreach ($students as $s):
                    $qr_url = BASE_URL . '/modules/auth/qr_login.php?token=' . urlencode($s['qr_token']);
                ?>
                <a href="<?= $qr_url ?>" class="qs-demo-item">
                    <div class="qs-demo-avatar">
                        <?= strtoupper(substr($s['full_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <div style="font-size:0.875rem; font-weight:600;">
                            <?= htmlspecialchars($s['full_name']) ?>
                        </div>
                        <div class="text-muted" style="font-size:0.75rem;">
                            <?= htmlspecialchars($s['student_id']) ?> ·
                            <?= htmlspecialchars($s['department']) ?>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right ms-auto text-muted small"></i>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
