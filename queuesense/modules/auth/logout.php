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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signed Out — QueueSense</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f1f5f9;
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .logout-card {
            background: white;
            padding: 50px 40px;
            border-radius: 24px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            text-align: center;
            max-width: 420px;
            width: 90%;
            border: 1px solid rgba(0,0,0,0.03);
        }
        .bcp-logo {
            width: 100px;
            margin-bottom: 30px;
        }
        h1 {
            font-weight: 800;
            color: #1e293b;
            font-size: 1.8rem;
            margin-bottom: 12px;
        }
        p {
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 24px;
        }
        .timer-val {
            font-weight: 700;
            color: #334155;
        }
        .signin-link {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: color 0.2s;
        }
        .signin-link:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="logout-card">
        <img src="<?= BASE_URL ?>/assets/images/bcp_logo.png" alt="BCP" class="bcp-logo">
        <h1>You have signed out!</h1>
        <p>Redirecting in <span id="timer" class="timer-val">5</span> seconds</p>
        <div>
            <span class="text-muted small">Go to</span>
            <a href="login.php" class="signin-link ms-1">sign in</a>
        </div>
    </div>

    <script>
        let timeLeft = 5;
        const timerEl = document.getElementById('timer');
        
        const countdown = setInterval(() => {
            timeLeft--;
            if (timerEl) timerEl.textContent = timeLeft;
            
            if (timeLeft <= 0) {
                clearInterval(countdown);
                window.location.href = 'login.php';
            }
        }, 1000);
    </script>
</body>
</html>
