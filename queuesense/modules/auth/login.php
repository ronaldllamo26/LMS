<?php
/**
 * QueueSense — Login Page
 * Themed for Bestlink College of the Philippines (BCP)
 * Students: authenticate via Student ID only (no password).
 * Staff/Admin: authenticate via Student ID + password.
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';

if (is_logged_in()) {
    $role = current_user()['role'];
    if ($role === 'admin') redirect(BASE_URL . '/admin/index.php');
    if ($role === 'staff') redirect(BASE_URL . '/staff/index.php');
    redirect(BASE_URL . '/modules/queue/status.php');
}

$error = '';

$url_error = $_GET['error'] ?? '';
if ($url_error === 'session_expired') $error = 'Your session has expired. Please sign in again.';
if ($url_error === 'timeout')         $error = 'You were signed out due to inactivity.';
if ($url_error === 'unauthorized')    $error = 'You do not have permission to access that page.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = strtolower(trim($_POST['student_id'] ?? ''));
    $password   = $_POST['password'] ?? '';
    $login_type = $_POST['login_type'] ?? 'student';

    if (empty($student_id)) {
        $error = 'Please enter your Student ID.';
    } else {
        $db   = db_connect();
        $sql  = "SELECT id, student_id, full_name, role, department, password_hash, is_active
                 FROM users WHERE LOWER(student_id) = LOWER(?) LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $student_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $error = 'Student ID not found. Please check your ID and try again.';
        } elseif (!$user['is_active']) {
            $error = 'Your account has been deactivated. Contact the administrator.';
        } elseif ($user['role'] === 'student') {
            if ($login_type === 'staff') {
                $error = 'This is a Student ID. Please use the Student tab.';
            } else {
                if (empty($password)) {
                    $error = 'Please enter your password.';
                } elseif (!password_verify($password, $user['password_hash'] ?? '')) {
                    $error = 'Incorrect password. Please try again.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user']          = $user;
                    $_SESSION['last_activity'] = time();
                    log_action('LOGIN', "Student login: {$user['student_id']}");
                    redirect(BASE_URL . '/modules/queue/status.php');
                }
            }
        } else {
            if ($login_type === 'student') {
                $error = 'This is a Staff/Admin ID. Please use the Staff / Admin tab.';
            } else {
            if (empty($password)) {
                $error = 'Please enter your password.';
            } elseif (!password_verify($password, $user['password_hash'] ?? '')) {
                $error = 'Incorrect password. Please try again.';
                log_action('LOGIN_FAILED', "Failed login: {$student_id}");
            } else {
                session_regenerate_id(true);
                $_SESSION['user']          = $user;
                $_SESSION['last_activity'] = time();
                log_action('LOGIN', ucfirst($user['role']) . " login: {$user['student_id']}");
                if ($user['role'] === 'admin') redirect(BASE_URL . '/admin/index.php');
                redirect(BASE_URL . '/staff/index.php');
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="QueueSense — Smart Queue Management System for Bestlink College of the Philippines">
    <title>Sign In — QueueSense | BCP</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            height: 100vh;
            overflow: hidden;
        }

        /* ── Split Layout ───────────────────── */
        .bcp-login-wrap {
            display: flex;
            height: 100vh;
        }

        /* ── LEFT: Form Panel ───────────────── */
        .bcp-left {
            flex: 0 0 42%;
            max-width: 42%;
            background: #f4f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 52px;
        }

        .bcp-form-inner {
            width: 100%;
            max-width: 360px;
        }

        .bcp-logo {
            display: block;
            width: 90px;
            height: 90px;
            object-fit: contain;
            margin: 0 auto 18px;
        }

        .bcp-sign-in-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: #1a1a2e;
            margin-bottom: 22px;
        }

        /* Tab switcher */
        .bcp-tabs {
            display: flex;
            background: #e8eaf0;
            border-radius: 8px;
            padding: 3px;
            margin-bottom: 22px;
            gap: 3px;
        }

        .bcp-tab {
            flex: 1;
            padding: 8px 10px;
            border: none;
            border-radius: 6px;
            background: transparent;
            font-size: 0.82rem;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s;
        }

        .bcp-tab.active {
            background: white;
            color: #1a237e;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
        }

        /* Fields */
        .bcp-label {
            font-size: 0.82rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
            display: block;
        }

        .bcp-label span { color: #c62828; }

        .bcp-field {
            position: relative;
            margin-bottom: 16px;
        }

        .bcp-input {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.9rem;
            font-family: 'Inter', sans-serif;
            color: #1a1a2e;
            background: white;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .bcp-input:focus {
            border-color: #1a237e;
            box-shadow: 0 0 0 3px rgba(26,35,126,0.1);
        }

        .bcp-pw-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            font-size: 1rem;
        }

        /* Submit button */
        .bcp-btn {
            width: 100%;
            padding: 12px;
            background: #5c6bc0;
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 0.95rem;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 6px;
            letter-spacing: 0.2px;
        }

        .bcp-btn:hover {
            background: #3949ab;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(92,107,192,0.35);
        }

        .bcp-btn:active { transform: translateY(0); }

        /* Error alert */
        .bcp-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.83rem;
            color: #b91c1c;
            margin-bottom: 18px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        /* Hint */
        .bcp-hint {
            font-size: 0.78rem;
            color: #9ca3af;
            margin-bottom: 18px;
            display: flex;
            align-items: flex-start;
            gap: 6px;
            line-height: 1.5;
        }

        /* QR divider */
        .bcp-divider {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 18px 0;
            color: #9ca3af;
            font-size: 0.78rem;
        }

        .bcp-divider hr { flex: 1; border: none; border-top: 1px solid #e5e7eb; }

        .bcp-qr-btn {
            width: 100%;
            padding: 10px 14px;
            background: white;
            border: 1.5px dashed #d1d5db;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #374151;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: 'Inter', sans-serif;
            text-decoration: none;
        }

        .bcp-qr-btn:hover {
            border-color: #1a237e;
            color: #1a237e;
            background: #f0f1ff;
        }

        /* ── RIGHT: BCP Blue Hero Panel ────── */
        .bcp-right {
            flex: 1;
            background: linear-gradient(145deg, #1a237e 0%, #283593 50%, #1565c0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px;
            position: relative;
            overflow: hidden;
        }

        /* Dot grid top-right */
        .bcp-dots {
            position: absolute;
            top: 24px;
            right: 24px;
            display: grid;
            grid-template-columns: repeat(8, 8px);
            gap: 5px;
            opacity: 0.15;
        }

        .bcp-dots span {
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: white;
            display: block;
        }

        /* Decorative circles */
        .bcp-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
        }

        .bcp-circle-1 { width: 450px; height: 450px; bottom: -160px; left: -100px; }
        .bcp-circle-2 { width: 300px; height: 300px; top: -80px; right: -60px; }
        .bcp-circle-3 {
            width: 200px; height: 200px;
            top: 50%; left: 60%;
            transform: translate(-50%,-50%);
            background: rgba(255,255,255,0.03);
        }

        /* Curved wave bottom */
        .bcp-wave {
            position: absolute;
            bottom: 0;
            left: -10%;
            width: 120%;
            height: 160px;
            background: rgba(255,255,255,0.04);
            border-radius: 100% 100% 0 0;
        }

        .bcp-right-content {
            position: relative;
            z-index: 2;
            text-align: center;
            color: white;
            max-width: 420px;
        }

        .bcp-right-title {
            font-size: 2.4rem;
            font-weight: 900;
            line-height: 1.15;
            margin-bottom: 16px;
            letter-spacing: -0.5px;
        }

        .bcp-right-sub {
            font-size: 0.95rem;
            opacity: 0.75;
            font-weight: 500;
            margin-bottom: 32px;
        }

        .bcp-right-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: rgba(255,255,255,0.7);
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 50px;
            padding: 8px 20px;
            transition: all 0.2s;
        }

        .bcp-right-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        /* Responsive: stack on mobile */
        @media (max-width: 768px) {
            .bcp-login-wrap { flex-direction: column; height: auto; overflow: auto; }
            .bcp-left { flex: none; max-width: 100%; padding: 32px 24px; }
            .bcp-right { flex: none; padding: 40px 24px; min-height: 220px; }
            .bcp-right-title { font-size: 1.6rem; }
            body { overflow: auto; }
        }
    </style>
</head>
<body>

<div class="bcp-login-wrap">

    <!-- ════════════════════ LEFT PANEL — Form ════════════════════ -->
    <div class="bcp-left">
        <div class="bcp-form-inner">

            <!-- BCP Logo -->
            <img src="<?= BASE_URL ?>/assets/images/bcp_logo.png"
                 alt="Bestlink College of the Philippines"
                 class="bcp-logo"
                 onerror="this.style.display='none'; document.getElementById('bcp-logo-fallback').style.display='flex';">

            <!-- Fallback icon if image fails -->
            <div id="bcp-logo-fallback" style="display:none; width:80px; height:80px;
                 background:linear-gradient(135deg,#1a237e,#3949ab); border-radius:12px;
                 align-items:center; justify-content:center; margin:0 auto 18px;">
                <i class="bi bi-person-lines-fill text-white" style="font-size:2rem;"></i>
            </div>

            <h1 class="bcp-sign-in-title">Sign in</h1>

            <!-- Tab switcher: Hidden for Staff/Admin (Secret Portal) -->
            <div class="bcp-tabs d-none" role="group">
                <button type="button" class="bcp-tab active" id="tab-student"
                        onclick="switchTab('student')">
                    <i class="bi bi-mortarboard me-1"></i> Student
                </button>
                <button type="button" class="bcp-tab" id="tab-staff"
                        onclick="switchTab('staff')">
                    <i class="bi bi-person-badge me-1"></i> Staff / Admin
                </button>
            </div>

            <!-- Error -->
            <?php if ($error): ?>
            <div class="bcp-error" id="loginError">
                <i class="bi bi-exclamation-circle-fill flex-shrink-0 mt-1"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" action="" id="loginForm" novalidate>
                <input type="hidden" name="login_type" id="login_type" value="<?= htmlspecialchars($login_type ?? 'student') ?>">

                <!-- Student ID -->
                <div class="bcp-field">
                    <label class="bcp-label" for="student_id">
                        <span id="idLabel">Student ID</span> <span>*</span>
                    </label>
                    <input type="text"
                           class="bcp-input"
                           id="student_id"
                           name="student_id"
                           placeholder="e.g. s230102815"
                           value="<?= clean($_POST['student_id'] ?? '') ?>"
                           autocomplete="username"
                           required>
                </div>

                <!-- Password -->
                <div id="passwordField">
                    <div class="bcp-field">
                        <label class="bcp-label" for="password">
                            Password <span>*</span>
                        </label>
                        <div style="position:relative;">
                            <input type="password"
                                   class="bcp-input"
                                   id="password"
                                   name="password"
                                   placeholder="Enter your password"
                                   autocomplete="current-password"
                                   required
                                   style="padding-right:42px;">
                            <button type="button" class="bcp-pw-toggle" id="pwToggle"
                                    onclick="togglePassword()">
                                <i class="bi bi-eye" id="pwIcon"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Student hint -->
                <p class="bcp-hint" id="studentHint">
                    <i class="bi bi-info-circle flex-shrink-0 mt-1 text-primary"></i>
                    Format: # + First 2 letters of Last Name + 8080 (e.g. #Ll8080)
                </p>

                <button type="submit" class="bcp-btn" id="submitBtn">
                    <span id="submitLabel">Sign in</span>
                </button>
            </form>

            <!-- QR Login -->
            <div id="qrSection">
                <div class="bcp-divider">
                    <hr> <span>or use QR code</span> <hr>
                </div>
                <a href="<?= BASE_URL ?>/modules/auth/qr_login.php" class="bcp-qr-btn">
                    <i class="bi bi-qr-code text-primary"></i>
                    Scan Student QR Card
                </a>
            </div>

            <!-- Institution footer -->
            <div class="text-center mt-4" style="font-size:0.72rem; color:#9ca3af;">
                <i class="bi bi-building me-1"></i>
                Bestlink College of the Philippines
            </div>

        </div>
    </div><!-- /.bcp-left -->

    <!-- ════════════════════ RIGHT PANEL — BCP Blue ════════════════════ -->
    <div class="bcp-right">

        <!-- Dot grid -->
        <div class="bcp-dots">
            <?php for ($i = 0; $i < 48; $i++): ?>
                <span></span>
            <?php endfor; ?>
        </div>

        <!-- Decorative circles -->
        <div class="bcp-circle bcp-circle-1"></div>
        <div class="bcp-circle bcp-circle-2"></div>
        <div class="bcp-circle bcp-circle-3"></div>
        <div class="bcp-wave"></div>

        <!-- Main content -->
        <div class="bcp-right-content">
            <div class="bcp-right-title">
                QueueSense<br>Smart Queue <span onclick="switchTab('student')" style="cursor: default; user-select: none;">S</span>yste<span onclick="switchTab('staff')" style="cursor: default; user-select: none;">m</span>
            </div>
            <p class="bcp-right-sub">
                AI-Assisted Queue & Crowd Flow Management<br>
                for Bestlink College of the Philippines
            </p>
            <a href="https://bcp.edu.ph" target="_blank" class="bcp-right-link">
                <i class="bi bi-globe2"></i>
                Student Admission — Click here
            </a>
        </div>

    </div><!-- /.bcp-right -->

</div><!-- /.bcp-login-wrap -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
'use strict';

function switchTab(type) {
    const isStaff = type === 'staff';
    document.getElementById('login_type').value = type;

    document.getElementById('tab-student').classList.toggle('active', !isStaff);
    document.getElementById('tab-staff').classList.toggle('active', isStaff);

    // document.getElementById('passwordField').style.display = isStaff ? 'block' : 'none';
    document.getElementById('password').required = true;
    document.getElementById('studentHint').style.display = isStaff ? 'none' : 'flex';
    document.getElementById('qrSection').style.display = isStaff ? 'none' : 'block';

    document.getElementById('idLabel').textContent = isStaff ? 'Employee ID' : 'Student ID';
    document.getElementById('student_id').placeholder =
        isStaff ? 'e.g. STAFF-001 or ADMIN-001' : 'e.g. s230102815';
}

function togglePassword() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('pwIcon');
    const show  = input.type === 'password';
    input.type = show ? 'text' : 'password';
    icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
}

// Restore tab state after POST error
<?php if (($login_type ?? 'student') === 'staff'): ?>
switchTab('staff');
<?php endif; ?>

// Auto-dismiss error after 5s
const errEl = document.getElementById('loginError');
if (errEl) setTimeout(() => {
    errEl.style.transition = 'opacity 0.5s';
    errEl.style.opacity = '0';
    setTimeout(() => errEl.remove(), 500);
}, 5000);
</script>

</body>
</html>
