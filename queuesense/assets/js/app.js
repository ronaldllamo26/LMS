/**
 * QueueSense — Global App JS
 * Handles: sidebar toggle, notification polling, toast alerts, utilities.
 */

'use strict';

/**
 * Escapes HTML characters in a string to prevent XSS.
 */
function escapeHTML(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// ─── Sidebar Toggle (Mobile) ──────────────────────────────────────────────────
const sidebar        = document.getElementById('qsSidebar');
const sidebarOverlay = document.getElementById('sidebarOverlay');
const sidebarClose   = document.getElementById('sidebarClose');

function openSidebar() {
    sidebar?.classList.add('open');
    sidebarOverlay?.style && (sidebarOverlay.style.display = 'block');
}

function closeSidebar() {
    sidebar?.classList.remove('open');
    sidebarOverlay?.style && (sidebarOverlay.style.display = 'none');
}

document.querySelector('[data-bs-toggle="offcanvas"]')?.addEventListener('click', openSidebar);
sidebarClose?.addEventListener('click', closeSidebar);
sidebarOverlay?.addEventListener('click', closeSidebar);

// ─── Notifications Dropdown ───────────────────────────────────────────────────
const notifList   = document.getElementById('notif-list');
const notifCount  = document.getElementById('notif-count');

/**
 * Fetches unread notifications from the API and renders them in the dropdown.
 */
function loadNotifications() {
    if (!notifList) return;

    fetch(`${window.QS_BASE_URL}/api/get_notifications.php`)
        .then(res => res.json())
        .then(data => {
            const loading = document.getElementById('notif-loading');
            if (loading) loading.remove();

            if (!data.notifications || data.notifications.length === 0) {
                notifList.innerHTML += `
                    <li><div class="dropdown-item text-muted small text-center py-3">
                        <i class="bi bi-bell-slash d-block mb-1 fs-5"></i>
                        No new notifications
                    </div></li>`;
                return;
            }

            data.notifications.forEach(n => {
                const iconMap = {
                    call:    'bi-megaphone-fill text-primary',
                    success: 'bi-check-circle-fill text-success',
                    warning: 'bi-exclamation-triangle-fill text-warning',
                    info:    'bi-info-circle-fill text-info',
                };
                const icon = iconMap[n.type] || iconMap.info;
                const item = document.createElement('li');
                item.innerHTML = `
                    <a class="dropdown-item py-2 px-3" href="#"
                       onclick="markNotifRead(${n.id}, this)">
                        <div class="d-flex gap-2">
                            <i class="bi ${icon} mt-1 flex-shrink-0"></i>
                            <div>
                                <div class="small fw-semibold">${escapeHTML(n.message)}</div>
                                <div class="text-muted" style="font-size:0.7rem">${escapeHTML(n.time_ago)}</div>
                            </div>
                        </div>
                    </a>`;
                notifList.appendChild(item);
            });

            // Update badge count
            if (notifCount) notifCount.textContent = data.unread_count;
            if (data.unread_count === 0 && notifCount) notifCount.remove();
        })
        .catch(() => {
            if (notifList) {
                const loading = document.getElementById('notif-loading');
                if (loading) loading.textContent = 'Failed to load.';
            }
        });
}

function markNotifRead(id, el) {
    fetch(`${window.QS_BASE_URL}/api/get_notifications.php?mark_read=${id}`, { method: 'POST' })
        .then(() => el.closest('li')?.remove());
}

// Load notifications when dropdown opens
document.getElementById('notifDropdown')?.addEventListener('show.bs.dropdown', loadNotifications);

// ─── Toast Notification System ────────────────────────────────────────────────
/**
 * Shows a Bootstrap-style toast.
 * @param {string} message
 * @param {string} type — 'success' | 'danger' | 'warning' | 'info'
 */
function showToast(message, type = 'info') {
    let container = document.getElementById('qs-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'qs-toast-container';
        container.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:8px;';
        document.body.appendChild(container);
    }

    const iconMap = {
        success: 'bi-check-circle-fill',
        danger:  'bi-x-circle-fill',
        warning: 'bi-exclamation-triangle-fill',
        info:    'bi-info-circle-fill',
    };

    const toast = document.createElement('div');
    toast.style.cssText = `
        background: white;
        border: 1px solid #e2e8f0;
        border-left: 4px solid var(--bs-${type === 'danger' ? 'danger' : type});
        border-radius: 10px;
        padding: 12px 16px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        min-width: 280px;
        max-width: 360px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        animation: qs-fadein 0.3s ease forwards;
        font-size: 0.875rem;
        font-family: 'Inter', sans-serif;
    `;

    toast.innerHTML = `
        <i class="bi ${iconMap[type] || iconMap.info} text-${type} flex-shrink-0 mt-1"></i>
        <div style="flex:1">${escapeHTML(message)}</div>
        <button onclick="this.closest('div').remove()" 
                style="background:none;border:none;color:#94a3b8;cursor:pointer;padding:0;line-height:1">
            <i class="bi bi-x"></i>
        </button>
    `;

    container.appendChild(toast);
    setTimeout(() => toast.style.opacity === '' && toast.remove(), 5000);
}

// ─── Confirm Dialog Helper ────────────────────────────────────────────────────
function qsConfirm(message, onConfirm) {
    if (window.confirm(message)) onConfirm();
}

// ─── Format Countdown ─────────────────────────────────────────────────────────
/**
 * Given minutes, formats as a live countdown display string.
 */
function formatMinutes(mins) {
    if (mins <= 0) return 'Any moment now';
    if (mins < 60) return `~${mins} min${mins > 1 ? 's' : ''}`;
    const h = Math.floor(mins / 60);
    const m = mins % 60;
    return `~${h}hr ${m > 0 ? m + 'min' : ''}`;
}

// ─── Auto-hide alerts ─────────────────────────────────────────────────────────
document.querySelectorAll('.alert.auto-hide').forEach(el => {
    setTimeout(() => {
        el.style.transition = 'opacity 0.5s';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 500);
    }, 4000);
});

// ─── Institutional Splash Hider (Global) ──────────────────────────────────────
window.addEventListener('load', () => {
    const splash = document.getElementById('splash');
    // Only auto-hide if the page doesn't have custom chaining logic
    if (splash && !window.HAS_CUSTOM_LOADER) {
        setTimeout(() => {
            splash.classList.add('hidden');
        }, 1200);
    }
});

// ─── Expose globals ───────────────────────────────────────────────────────────
window.QueueSense = { showToast, qsConfirm, formatMinutes };
