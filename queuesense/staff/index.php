<?php
/**
 * QueueSense — Staff Serving Dashboard
 * Allows staff to call next student, mark as done, etc.
 */

$required_role = 'staff';
$active_page   = 'dashboard';
$page_title    = 'Serving Dashboard';

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// Initial fetch of window data to prevent empty flashes
$db = db_connect();
$user_id = $current_user_id;
$sql_window = "SELECT sw.*, qt.name as queue_name, qt.prefix 
               FROM service_windows sw
               JOIN queue_types qt ON sw.queue_type_id = qt.id
               WHERE sw.staff_id = ? LIMIT 1";
$stmt = $db->prepare($sql_window);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$window_data = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<main class="qs-main-layout">
    <div class="qs-main-content">

        <!-- Page Header -->
        <div class="qs-page-header d-flex align-items-center justify-content-between">
            <div>
                <h1 class="qs-page-title">Serving Dashboard</h1>
                <p class="qs-page-sub">
                    <span class="live-dot me-1"></span> Live Monitoring • 
                    <?= $window_data ? htmlspecialchars($window_data['window_label']) : 'No Window Assigned' ?>
                </p>
            </div>
            <div class="text-end">
                <div id="live-clock" class="fw-bold text-dark fs-5">00:00:00</div>
                <div class="text-muted small"><?= date('l, F j, Y') ?></div>
            </div>
        </div>

        <?php if (!$window_data): ?>
            <div class="qs-card text-center py-5">
                <i class="bi bi-exclamation-octagon text-warning fs-1 mb-3"></i>
                <h4 class="fw-bold">Window Not Assigned</h4>
                <p class="text-muted mb-0">Please contact the Administrator to assign you to a service window.</p>
            </div>
        <?php else: ?>

            <div class="row g-4">
                
                <!-- LEFT: Active Transaction -->
                <div class="col-lg-7">
                    <div class="qs-card h-100 shadow-sm" id="serving-card">
                        <div class="qs-card-header">
                            <h5 class="qs-card-title">Now Serving</h5>
                            <span class="qs-badge qs-badge-serving" id="status-badge">Active</span>
                        </div>

                        <div id="serving-placeholder" class="text-center py-5 d-none">
                            <div class="mb-3 opacity-25">
                                <i class="bi bi-person-slash" style="font-size:4rem;"></i>
                            </div>
                            <h5 class="fw-bold text-muted">No Active Transaction</h5>
                            <p class="text-muted small mb-4">Click "Next Student" to call the next person in line.</p>
                            <button onclick="callNext()" class="qs-btn-primary btn-lg px-4 py-2">
                                <i class="bi bi-megaphone-fill"></i> Next Student
                            </button>
                        </div>

                        <div id="serving-content" class="qs-animate-in">
                            <div class="d-flex align-items-center gap-4 mb-4">
                                <div class="ticket-display">
                                    <div class="ticket-label">Ticket No.</div>
                                    <div class="ticket-val" id="serving-ticket">---</div>
                                </div>
                                <div class="flex-grow-1">
                                    <h3 class="fw-800 mb-0" id="serving-name">Loading...</h3>
                                    <div class="text-muted" id="serving-sid">---</div>
                                </div>
                            </div>

                            <div class="qs-ai-card mb-4">
                                <div class="qs-ai-title"><i class="bi bi-lightning-charge-fill me-1"></i> Transaction Details</div>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="text-muted small mb-1">Time Called</div>
                                        <div class="fw-semibold" id="serving-called-at">--:-- --</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted small mb-1">Queue Category</div>
                                        <div class="fw-semibold"><?= htmlspecialchars($window_data['queue_name']) ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-auto">
                                <button onclick="markDone()" class="qs-btn-primary flex-grow-1 py-3 justify-content-center">
                                    <i class="bi bi-check-lg fs-5"></i> Mark as Done
                                </button>
                                <button onclick="recall()" class="btn btn-outline-primary border-2 px-3" title="Recall Student">
                                    <i class="bi bi-megaphone"></i>
                                </button>
                                <button onclick="noShow()" class="btn btn-outline-danger border-2 px-3" title="No Show">
                                    <i class="bi bi-person-x"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Queue List & Quick Stats -->
                <div class="col-lg-5">
                    
                    <!-- Quick Stats -->
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="qs-stat-card border-start border-4">
                                <div class="qs-stat-label">Waiting</div>
                                <div class="qs-stat-value" id="stat-waiting">0</div>
                                <i class="bi bi-people qs-stat-icon"></i>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="qs-stat-card border-start border-4 border-success">
                                <div class="qs-stat-label">Estimated Wait</div>
                                <div class="qs-stat-value" id="stat-avg" style="font-size:1.4rem; padding-top:6px;">-- min</div>
                                <i class="bi bi-clock-history qs-stat-icon"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Waiting List -->
                    <div class="qs-card shadow-sm p-0 overflow-hidden">
                        <div class="qs-card-header border-0 p-3 pb-0">
                            <h5 class="qs-card-title">Next in Line</h5>
                        </div>
                        <div class="list-group list-group-flush" id="waiting-list" style="max-height: 400px; overflow-y: auto;">
                            <!-- Items injected here -->
                            <div class="text-center py-4 text-muted small">Loading queue...</div>
                        </div>
                    </div>

                </div>

            </div>

        <?php endif; ?>

    </div>
</main>

<style>
.ticket-display {
    background: var(--bcp-navy);
    color: white;
    padding: 12px 18px;
    border-radius: var(--radius);
    text-align: center;
    min-width: 120px;
}
.ticket-label { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; opacity: 0.7; letter-spacing: 1px; }
.ticket-val { font-size: 1.8rem; font-weight: 800; line-height: 1.2; }
.fw-800 { font-weight: 800; }

.list-group-item {
    padding: 12px 16px;
    border-left: 0; border-right: 0;
    transition: var(--transition);
}
.list-group-item:hover { background: #f8fafc; }
.priority-tag { font-size: 0.6rem; background: #fee2e2; color: #b91c1c; padding: 1px 6px; border-radius: 4px; font-weight: 700; text-transform: uppercase; }
</style>

<?php
$extra_scripts = "
<script>
let currentServingId = null;

// Live Clock
setInterval(() => {
    document.getElementById('live-clock').textContent = new Date().toLocaleTimeString('en-US', { hour12: false });
}, 1000);

/**
 * Fetch live dashboard data
 */
async function updateDashboard() {
    try {
        const response = await fetch('../api/staff_actions.php?action=get_status');
        const data = await response.json();

        if (data.error) {
            console.error(data.error);
            return;
        }

        // 1. Update Serving Section
        if (data.serving) {
            currentServingId = data.serving.id;
            document.getElementById('serving-placeholder').classList.add('d-none');
            document.getElementById('serving-content').classList.remove('d-none');
            
            document.getElementById('serving-ticket').textContent = data.serving.ticket_number;
            document.getElementById('serving-name').textContent   = data.serving.full_name;
            document.getElementById('serving-sid').textContent    = data.serving.sid;
            
            const callTime = new Date(data.serving.called_at);
            document.getElementById('serving-called-at').textContent = callTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            document.getElementById('status-badge').textContent = 'Currently Serving';
            document.getElementById('status-badge').className = 'qs-badge qs-badge-serving';
        } else {
            currentServingId = null;
            document.getElementById('serving-placeholder').classList.remove('d-none');
            document.getElementById('serving-content').classList.add('d-none');
            document.getElementById('status-badge').textContent = 'Idle';
            document.getElementById('status-badge').className = 'qs-badge qs-badge-closed';
        }

        // 2. Update Stats
        document.getElementById('stat-waiting').textContent = data.waiting_count;
        // Simple estimate based on 5 mins per person
        document.getElementById('stat-avg').textContent = (data.waiting_count * 5) + ' min';

        // 3. Update Waiting List
        const listContainer = document.getElementById('waiting-list');
        if (data.waiting_list.length === 0) {
            listContainer.innerHTML = '<div class=\"text-center py-5 text-muted\">No one is waiting in queue.</div>';
        } else {
            listContainer.innerHTML = '';
            data.waiting_list.forEach(item => {
                const li = document.createElement('div');
                li.className = 'list-group-item d-flex align-items-center justify-content-between';
                li.innerHTML = `
                    <div>
                        <div class=\"fw-bold mb-0\">\${item.ticket_number}</div>
                        <div class=\"text-muted small\">\${item.full_name}</div>
                    </div>
                    \${item.priority == 1 ? '<span class=\"priority-tag\">Priority</span>' : ''}
                `;
                listContainer.appendChild(li);
            });
        }

    } catch (err) {
        console.error('Fetch error:', err);
    }
}

/**
 * Action: Call Next
 */
async function callNext() {
    try {
        const res = await fetch('../api/staff_actions.php?action=call_next', { method: 'POST' });
        const data = await res.json();
        if (data.error) {
            window.QueueSense.showToast(data.error, 'danger');
        } else {
            window.QueueSense.showToast('Next student called successfully!', 'success');
            updateDashboard();
        }
    } catch (e) { console.error(e); }
}

/**
 * Action: Mark as Done
 */
async function markDone() {
    if (!currentServingId) return;
    
    const formData = new FormData();
    formData.append('id', currentServingId);
    formData.append('action', 'mark_done');

    try {
        const res = await fetch('../api/staff_actions.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.error) {
            window.QueueSense.showToast(data.error, 'danger');
        } else {
            window.QueueSense.showToast('Transaction marked as complete.', 'success');
            updateDashboard();
        }
    } catch (e) { console.error(e); }
}

/**
 * Action: No Show
 */
function noShow() {
    if (!currentServingId) return;
    
    window.QueueSense.qsConfirm('Mark this student as No-Show?', async () => {
        const formData = new FormData();
        formData.append('id', currentServingId);
        formData.append('action', 'no_show');

        try {
            const res = await fetch('../api/staff_actions.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                window.QueueSense.showToast('Marked as No-Show.', 'info');
                updateDashboard();
            }
        } catch (e) { console.error(e); }
    });
}

/**
 * Action: Recall (Re-notify student)
 */
async function recall() {
    if (!currentServingId) return;
    
    const formData = new FormData();
    formData.append('id', currentServingId);
    formData.append('action', 'recall');

    try {
        const res = await fetch('../api/staff_actions.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            window.QueueSense.showToast('Recalling student... Notification sent.', 'info');
        } else {
            window.QueueSense.showToast(data.error || 'Recall failed.', 'danger');
        }
    } catch (e) { console.error(e); }
}

// Polling
updateDashboard();
setInterval(updateDashboard, 5000);

</script>
";

require_once __DIR__ . '/../includes/footer.php';
