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
    require_csrf();
    $student_id = strict_input($_POST['student_id'] ?? '', 'student_id');
    $password   = $_POST['password'] ?? '';
    $login_type = $_POST['login_type'] ?? 'student';

    if (empty($student_id)) {
        $error = 'Username or password is invalid.';
    } else {
        $db   = db_connect();
        $sql  = "SELECT id, student_id, full_name, role, department, password_hash, is_active
                 FROM users WHERE LOWER(student_id) = LOWER(?) LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $student_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user || !$user['is_active']) {
            $error = 'Username or password is invalid.';
        } else {
            $is_student = ($user['role'] === 'student');
            $requested_student = ($login_type === 'student');

            if ($is_student !== $requested_student) {
                $error = 'Username or password is invalid.';
            } elseif (empty($password) || !password_verify($password, $user['password_hash'] ?? '')) {
                $error = 'Username or password is invalid.';
            } else {
                // session_regenerate_id(true);
                $_SESSION['user']          = $user;
                $_SESSION['last_activity'] = time();
                $_SESSION['csrf_token']    = bin2hex(random_bytes(32));
                log_action('LOGIN', ucfirst($user['role']) . " login: {$user['student_id']}");
                
                $redirect = BASE_URL . '/modules/queue/status.php';
                $_SESSION['show_bcp_loading'] = true;
                
                if ($user['role'] === 'admin') $redirect = BASE_URL . '/admin/index.php';
                if ($user['role'] === 'staff') $redirect = BASE_URL . '/staff/index.php';

                if (isset($_POST['ajax'])) {
                    echo json_encode(['success' => true, 'redirect' => $redirect]);
                    exit;
                }
                redirect($redirect);
            }
        }
    }
    
    if (isset($_POST['ajax'])) {
        echo json_encode(['success' => false, 'error' => $error]);
        exit;
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

        /* Remove default browser eye icon for passwords */
        input::-ms-reveal,
        input::-ms-clear {
            display: none !important;
        }
        
        input::-webkit-contacts-auto-fill-button, 
        input::-webkit-credentials-auto-fill-button {
            visibility: hidden;
            display: none !important;
            pointer-events: none;
        }

        /* ── Split Layout ───────────────────── */
        .bcp-login-wrap {
            display: flex;
            height: 100vh;
        }

        /* ── LEFT: Form Panel ───────────────── */
        .bcp-left {
            flex: 0 0 50%;
            max-width: 50%;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 48px 60px;
        }

        .bcp-form-inner {
            width: 100%;
            max-width: 360px;
        }

        .bcp-logo {
            display: block;
            width: 144px;
            height: 144px;
            object-fit: contain;
            margin: 0 0 28px 0;
        }

        .bcp-sign-in-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 28px;
            text-align: left;
            letter-spacing: -0.5px;
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
            padding: 12px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.9rem;
            font-family: 'Inter', sans-serif;
            color: #1a1a2e;
            background: white;
            outline: none;
            transition: all 0.2s;
        }

        .bcp-input:focus {
            border-color: #1a237e;
            box-shadow: 0 0 0 1px #1a237e;
        }

        .bcp-pw-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: #f1f5f9;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .bcp-pw-toggle:hover {
            background: #e2e8f0;
            color: #64748b;
        }

        /* Submit button */
        .bcp-btn {
            width: 100%;
            padding: 14px;
            background: #4f46e5;
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 20px;
        }

        .bcp-btn:hover {
            background: #4338ca;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
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
            flex: 0 0 50%;
            max-width: 50%;
            background: linear-gradient(145deg, #1a237e 0%, #283593 50%, #1565c0 100%);
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding: 48px 60px;
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
            text-align: left;
            color: white;
            max-width: 600px;
        }

        .bcp-right-title {
            font-size: 4.2rem;
            font-weight: 700;
            line-height: 1.05;
            margin-bottom: 24px;
            letter-spacing: -1.5px;
        }

        .bcp-right-sub {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 500;
            margin-bottom: 0;
        }

        .bcp-right-link {
            display: inline-block;
            color: white;
            font-size: 1.1rem;
            font-weight: 500;
            text-decoration: none;
            margin-top: 10px;
            transition: opacity 0.2s;
        }

        .bcp-right-link:hover {
            opacity: 0.8;
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


        /* Shake Animation */
        .shake {
            animation: shake 0.4s cubic-bezier(.36,.07,.19,.97) both;
            transform: translate3d(0, 0, 0);
        }
        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
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
            <div id="bcp-logo-fallback" style="display:none; width:100px; height:100px;
                 background:linear-gradient(135deg,#1a237e,#3949ab); border-radius:12px;
                 align-items:center; justify-content:center; margin:0 0 24px 0;">
                <i class="bi bi-person-lines-fill text-white" style="font-size:2.5rem;"></i>
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
            <div class="bcp-error shake" id="loginError">
                <i class="bi bi-exclamation-circle-fill flex-shrink-0 mt-1"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" action="" id="loginForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
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
                           placeholder=""
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
                                   placeholder=""
                                   autocomplete="current-password"
                                   required
                                   style="padding-right:42px;">
                            <button type="button" class="bcp-pw-toggle" id="pwToggle"
                                    onclick="togglePassword()">
                                <i class="bi bi-eye-slash" id="pwIcon"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Student hint -->


                <button type="submit" class="bcp-btn" id="submitBtn">
                    <span id="submitLabel">Sign in</span>
                    <div id="submitSpinner" class="spinner-border spinner-border-sm d-none" role="status"></div>
                </button>
            </form>





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
                QueueSense<br>Smart Queue Sy<span onclick="switchTab('student')" style="cursor: default; user-select: none;">s</span>te<span onclick="switchTab('staff')" style="cursor: default; user-select: none;">m</span>
            </div>
            <a href="https://bcp.edu.ph" target="_blank" class="bcp-right-link">
                Student admission click here
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

    document.getElementById('idLabel').textContent = isStaff ? 'Employee ID' : 'Student ID';
    document.getElementById('student_id').placeholder = "";
}

function togglePassword() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('pwIcon');
    const show  = input.type === 'password';
    input.type = show ? 'text' : 'password';
    icon.className = show ? 'bi bi-eye' : 'bi bi-eye-slash';
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

// Splash Logic
const splash = document.getElementById('splash');
const loginForm = document.getElementById('loginForm');
const loginError = document.getElementById('loginError') || document.createElement('div');
const submitBtn = document.getElementById('submitBtn');
const submitLabel = document.getElementById('submitLabel');
const submitSpinner = document.getElementById('submitSpinner');

// Initial Load Splash (Fades out)
window.addEventListener('load', () => {
    setTimeout(() => {
        splash.classList.add('hidden');
    }, 1500);
});

// Ensure error container exists
if (!document.getElementById('loginError')) {
    loginError.id = 'loginError';
    loginError.className = 'bcp-error d-none';
    loginForm.parentNode.insertBefore(loginError, loginForm);
}

loginForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Reset state
    loginError.classList.add('d-none');
    loginError.classList.remove('shake');
    
    // Show button spinner
    submitBtn.disabled = true;
    submitLabel.classList.add('d-none');
    submitSpinner.classList.remove('d-none');
    
    const formData = new FormData(this);
    formData.append('ajax', '1');

    try {
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            // Redirect immediately
            window.location.href = data.redirect;
        } else {
            // Error handling
            loginError.innerHTML = `<span>${data.error}</span>`;
            loginError.classList.remove('d-none');
            loginError.classList.add('shake');
            
            // Re-enable button
            submitBtn.disabled = false;
            submitLabel.classList.remove('d-none');
            submitSpinner.classList.add('d-none');
        }
    } catch (err) {
        console.error('Login error:', err);
        submitBtn.disabled = false;
        submitLabel.classList.remove('d-none');
        submitSpinner.classList.add('d-none');
    }
});
</script>

</body>
</html>
