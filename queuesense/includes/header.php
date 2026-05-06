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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    
    <!-- PWA Settings -->
    <link rel="manifest" href="<?= BASE_URL ?>/manifest.json">
    <meta name="theme-color" content="#1e2a5e">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>/assets/images/bcp_logo.png">

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js')
                .then(reg => console.log('SW Registered'))
                .catch(err => console.log('SW Registration Failed', err));
        }
    </script>
</head>
<body class="<?= (isset($no_sidebar) && $no_sidebar) ? 'no-sidebar' : '' ?> <?= isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true' ? 'sidebar-collapsed' : '' ?>">

<?php if (isset($_SESSION['show_bcp_loading']) && $_SESSION['show_bcp_loading']): ?>
<!-- BCP Institutional Splash (Visible by default to prevent peeking) -->
<div id="splash" class="bcp-splash">
    <img src="<?= BASE_URL ?>/assets/images/bcp_logo.png" alt="BCP" class="bcp-splash-logo">
</div>

<!-- BCP Syncing Overlay (Hidden by default) -->
<div id="syncOverlay" class="bcp-sync-overlay hidden">
    <div class="sync-spinner">
        <div class="dot"></div>
        <div class="dot"></div>
        <div class="dot"></div>
    </div>
    <div class="sync-text">Syncing...</div>
</div>
<?php 
    // Clear flag so it only shows ONCE per login
    unset($_SESSION['show_bcp_loading']); 
?>
<?php endif; ?>

<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- ── TOPBAR (BCP SMS STYLE) ────────────────────────────────── -->
<nav class="qs-navbar d-flex align-items-center justify-content-between px-3">
    
    <!-- Left: Hamburger or Back -->
    <div class="d-flex align-items-center">
        <?php if (isset($show_back_button) && $show_back_button): ?>
            <a href="<?= $back_url ?? 'javascript:history.back()' ?>" class="btn btn-link text-muted p-0 me-3 d-flex align-items-center text-decoration-none">
                <i class="bi bi-arrow-left fs-4"></i>
                <span class="ms-2 fw-800 small" style="letter-spacing:1px;">BACK</span>
            </a>
        <?php else: ?>
            <button class="btn btn-link text-muted p-0 me-3" id="sidebarToggle" onclick="toggleSidebar()">
                <i class="bi bi-list fs-4"></i>
            </button>
        <?php endif; ?>

        <!-- Brand (Mobile Only) -->
        <div class="d-lg-none ms-2">
            <span class="qs-brand-name">QueueSense</span>
            <span class="qs-brand-sub d-block">Student Queueing</span>
        </div>
    </div>

    <!-- Right: Clock, Fullscreen, Search -->
    <div class="d-flex align-items-center gap-4">
        <div id="digitalClock" class="fw-600 text-muted small desktop-only" style="letter-spacing: 0.5px;">
            00:00:00 PM
        </div>
        
        <div class="d-flex align-items-center gap-3 text-muted">
            <i class="bi bi-arrows-fullscreen cursor-pointer desktop-only" onclick="toggleFullscreen()" style="font-size: 1.1rem;" title="Fullscreen"></i>
            <i class="bi bi-search cursor-pointer" onclick="toggleSearch()" style="font-size: 1.1rem;" title="Search for a page"></i>
        </div>
    </div>

    <!-- ── GLOBAL SEARCH OVERLAY (BCP SMS STYLE) ──────────────── -->
    <div id="qsSearchOverlay" class="qs-search-overlay d-none">
        <div class="container-fluid h-100 d-flex align-items-center">
            <i class="bi bi-search search-icon-inner"></i>
            <input type="text" id="qsSearchInput" class="qs-search-input" placeholder="Search for a page" autocomplete="off">
            <button class="btn-close-search" onclick="toggleSearch()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>
</nav>

<style>
    .qs-search-overlay {
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: #ffffff;
        z-index: 1050;
        animation: slideDown 0.2s ease-out;
    }
    .search-icon-inner {
        font-size: 1.2rem;
        color: #94a3b8;
        margin-left: 20px;
    }
    .qs-search-input {
        flex: 1;
        border: none;
        background: transparent;
        padding: 0 20px;
        font-size: 1.05rem;
        color: #1e293b;
        font-weight: 500;
        outline: none;
    }
    .btn-close-search {
        background: none;
        border: none;
        color: #94a3b8;
        padding: 0 20px;
        font-size: 1.1rem;
        cursor: pointer;
        transition: color 0.2s;
    }
    .btn-close-search:hover { color: #1e293b; }
    
    @keyframes slideDown {
        from { transform: translateY(-100%); }
        to { transform: translateY(0); }
    }
    
    .cursor-pointer { cursor: pointer; }
</style>

<script>
    window.QS_BASE_URL = '<?= BASE_URL ?>';

    // Real-time Clock (Global Fix)
    function updateClock() {
        const now = new Date();
        const timeStr = now.toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit', 
            hour12: true 
        });
        
        // Update any clock element with ID 'digitalClock' or 'live-clock'
        const elements = ['digitalClock', 'live-clock'];
        elements.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = timeStr;
        });
    }
    setInterval(updateClock, 1000);
    // Use a small delay to ensure DOM is ready for the first update
    setTimeout(updateClock, 100);

    // Sidebar Toggle Logic
    function toggleSidebar() {
        if (window.innerWidth < 992) {
            document.body.classList.toggle('sidebar-open');
            document.body.classList.remove('sidebar-collapsed');
        } else {
            document.body.classList.toggle('sidebar-collapsed');
            document.body.classList.remove('sidebar-open');
            const isCollapsed = document.body.classList.contains('sidebar-collapsed');
            document.cookie = "sidebar_collapsed=" + isCollapsed + "; path=/; max-age=" + (30 * 24 * 60 * 60);
        }
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

    // Global Search Toggle
    function toggleSearch() {
        const overlay = document.getElementById('qsSearchOverlay');
        const input = document.getElementById('qsSearchInput');
        
        if (overlay.classList.contains('d-none')) {
            overlay.classList.remove('d-none');
            input.focus();
            
            // Close on ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') toggleSearch();
            }, { once: true });
        } else {
            overlay.classList.add('d-none');
            input.value = '';
        }
    }
</script>
