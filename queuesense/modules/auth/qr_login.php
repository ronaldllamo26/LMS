<?php
/**
 * QueueSense — QR Code Login Handler
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

    if ($user && $user['is_active']) {
        session_regenerate_id(true);
        $_SESSION['user'] = $user;
        $_SESSION['last_activity'] = time();
        log_action('QR_LOGIN', "QR login: {$user['student_id']}");
        redirect(BASE_URL . '/modules/queue/status.php');
    } else {
        redirect(BASE_URL . '/modules/auth/login.php?error=invalid_qr');
    }
}

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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        body { background: #f4f7fa; font-family: 'Outfit', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .qr-kiosk-container { width: 100%; max-width: 520px; padding: 20px; }
        .qr-card { background: white; border-radius: 24px; box-shadow: 0 20px 60px rgba(30, 42, 94, 0.1); overflow: hidden; border: 1px solid rgba(0,0,0,0.05); }
        .qr-header { background: var(--bcp-navy); padding: 40px 30px; color: white; text-align: center; position: relative; }
        .qr-header::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 4px; background: rgba(255,255,255,0.1); }
        
        .scanner-container { position: relative; width: 220px; height: 220px; margin: -110px auto 30px; background: #0a0a0a; border-radius: 20px; border: 4px solid white; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
        .scanner-line { position: absolute; left: 10%; right: 10%; height: 2px; background: #3b82f6; box-shadow: 0 0 15px #3b82f6; animation: scan 2.5s ease-in-out infinite; z-index: 2; }
        @keyframes scan { 0%, 100% { top: 15%; } 50% { top: 85%; } }
        
        .demo-section { padding: 30px; background: #fcfdfe; border-top: 1px solid #f1f5f9; }
        .student-list { max-height: 320px; overflow-y: auto; padding-right: 5px; }
        .student-list::-webkit-scrollbar { width: 5px; }
        .student-list::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        
        .student-item { display: flex; align-items: center; gap: 15px; padding: 15px; border-radius: 14px; text-decoration: none; color: inherit; transition: 0.2s; border: 1px solid transparent; margin-bottom: 8px; }
        .student-item:hover { background: white; border-color: #e2e8f0; transform: translateX(5px); box-shadow: 0 4px 12px rgba(0,0,0,0.03); color: var(--bcp-blue); }
        .student-avatar { width: 42px; height: 42px; border-radius: 12px; background: #eff6ff; color: var(--bcp-blue); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.1rem; }
    </style>
</head>
<body>

<div class="qr-kiosk-container">
    
    <!-- Back to Login -->
    <div class="mb-4 text-center">
        <a href="login.php" class="text-decoration-none text-muted fw-600 small">
            <i class="bi bi-arrow-left me-1"></i> BACK TO LOGIN
        </a>
    </div>

    <div class="qr-card">
        <div class="qr-header">
            <h2 class="fw-900 m-0" style="letter-spacing:-0.5px;">QR Code Login</h2>
            <p class="small opacity-75 mt-2 mb-4">Scan your institutional ID to log in</p>
            <div style="height: 60px;"></div> <!-- Spacer for scanner overlap -->
        </div>

        <div class="scanner-container">
            <div class="scanner-line"></div>
            <div class="d-flex align-items-center justify-content-center h-100">
                <i class="bi bi-qr-code text-white opacity-25" style="font-size: 5rem;"></i>
            </div>
        </div>

        <div class="text-center px-4 pb-4">
            <div class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2 fw-bold small">
                <span class="live-dot me-2"></span> SCANNER ACTIVE
            </div>
        </div>

        <div class="demo-section">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="fw-800 text-muted small text-uppercase" style="letter-spacing:1px;">Demo Simulation</span>
                <span class="badge bg-light text-dark border fw-bold">15 Students</span>
            </div>

            <div class="student-list">
                <?php
                $db = db_connect();
                $students = $db->query("SELECT student_id, full_name, department, qr_token FROM users WHERE role = 'student' AND qr_token IS NOT NULL ORDER BY full_name LIMIT 15")->fetch_all(MYSQLI_ASSOC);
                foreach ($students as $s):
                    $qr_url = "qr_login.php?token=" . urlencode($s['qr_token']);
                ?>
                <a href="<?= $qr_url ?>" class="student-item">
                    <div class="student-avatar"><?= substr($s['full_name'], 0, 1) ?></div>
                    <div class="flex-grow-1">
                        <div class="fw-800" style="font-size:0.95rem; line-height:1.2;"><?= htmlspecialchars($s['full_name']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($s['student_id']) ?> · <?= htmlspecialchars($s['department']) ?></div>
                    </div>
                    <i class="bi bi-chevron-right text-muted opacity-50"></i>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="text-center mt-4 text-muted small opacity-50 fw-bold">
        &copy; 2026 BESTLINK COLLEGE OF THE PHILIPPINES
    </div>
</div>

</body>
</html>
