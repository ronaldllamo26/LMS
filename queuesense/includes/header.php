<?php
/**
 * QueueSense — Global Header (BCP SMS Precise Edition)
 */
$page_title = $page_title ?? SYSTEM_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> — <?= SYSTEM_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="<?= isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true' ? 'sidebar-collapsed' : '' ?>">

<!-- ── TOPBAR (BCP SMS STYLE) ────────────────────────────────── -->
<nav class="qs-navbar d-flex align-items-center justify-content-between px-3">
    
    <!-- Left: Hamburger -->
    <div class="d-flex align-items-center">
        <button class="btn btn-link text-muted p-0 me-3" id="sidebarToggle" onclick="toggleSidebar()">
            <i class="bi bi-list fs-4"></i>
        </button>
    </div>

    <!-- Right: Clock, Fullscreen, Search -->
    <div class="d-flex align-items-center gap-4">
        <div id="digitalClock" class="fw-600 text-muted small" style="letter-spacing: 0.5px;">
            00:00:00 PM
        </div>
        
        <div class="d-flex align-items-center gap-3 text-muted">
            <i class="bi bi-arrows-fullscreen cursor-pointer" onclick="toggleFullscreen()" style="font-size: 1.1rem;"></i>
            <i class="bi bi-search cursor-pointer" style="font-size: 1.1rem;"></i>
        </div>
    </div>
</nav>

<script>
    // Real-time Clock
    function updateClock() {
        const now = new Date();
        const timeStr = now.toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit', 
            hour12: true 
        });
        document.getElementById('digitalClock').textContent = timeStr;
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Sidebar Toggle Logic
    function toggleSidebar() {
        document.body.classList.toggle('sidebar-collapsed');
        const isCollapsed = document.body.classList.contains('sidebar-collapsed');
        document.cookie = "sidebar_collapsed=" + isCollapsed + "; path=/; max-age=" + (30 * 24 * 60 * 60);
    }

    // Fullscreen Toggle
    function toggleFullscreen() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen();
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        }
    }
</script>
