<?php
/**
 * QueueSense — API: Staff Actions
 * Handles calling next student, marking as done, etc.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in() || (!has_role('staff') && !has_role('admin'))) {
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

$db      = db_connect();
$user_id = current_user()['id'];

// State-changing actions MUST use POST and have CSRF token
$is_post = ($_SERVER['REQUEST_METHOD'] === 'POST');
$action  = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action !== 'get_status' && !$is_post) {
    echo json_encode(['error' => 'State-changing actions must use POST.']);
    exit;
}

if ($is_post) {
    // Note: We don't use require_csrf() here because we want to return JSON error
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $log = "\n--- CSRF FAILURE [" . date('Y-m-d H:i:s') . "] ---\n";
        $log .= "POST Token: " . ($_POST['csrf_token'] ?? 'MISSING') . "\n";
        $log .= "SESSION Token: " . ($_SESSION['csrf_token'] ?? 'MISSING') . "\n";
        $log .= "Session ID: " . session_id() . "\n";
        file_put_contents(__DIR__ . '/../csrf_debug.log', $log, FILE_APPEND);
        
        echo json_encode(['error' => 'CSRF token validation failed.']);
        exit;
    }
}

// 1. Find the window assigned to this staff
$sql_window = "SELECT sw.*, qt.name as queue_name, qt.prefix 
               FROM service_windows sw
               JOIN queue_types qt ON sw.queue_type_id = qt.id
               WHERE sw.staff_id = ? LIMIT 1";
$stmt = $db->prepare($sql_window);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$window = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$window) {
    echo json_encode(['error' => 'No service window assigned to your account.']);
    exit;
}

$window_id     = $window['id'];
$queue_type_id = $window['queue_type_id'];

switch ($action) {
    case 'get_status':
        // Get current serving (if any)
        $sql_serving = "SELECT qe.*, u.full_name, u.student_id as sid
                        FROM queue_entries qe
                        JOIN users u ON qe.user_id = u.id
                        WHERE qe.window_id = ? AND qe.status = 'serving'
                        AND DATE(qe.joined_at) = CURDATE()
                        ORDER BY qe.called_at DESC LIMIT 1";
        $stmt = $db->prepare($sql_serving);
        $stmt->bind_param('i', $window_id);
        $stmt->execute();
        $serving = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Get waiting count
        $sql_waiting = "SELECT COUNT(*) as count 
                        FROM queue_entries 
                        WHERE queue_type_id = ? AND status = 'waiting'
                        AND DATE(joined_at) = CURDATE()";
        $stmt = $db->prepare($sql_waiting);
        $stmt->bind_param('i', $queue_type_id);
        $stmt->execute();
        $waiting_count = (int)($stmt->get_result()->fetch_assoc()['count'] ?? 0);
        $stmt->close();

        // Get waiting list (limit 10)
        $sql_list = "SELECT qe.id, qe.ticket_number, u.full_name, qe.priority
                     FROM queue_entries qe
                     JOIN users u ON qe.user_id = u.id
                     WHERE qe.queue_type_id = ? AND qe.status = 'waiting'
                     AND DATE(qe.joined_at) = CURDATE()
                     ORDER BY qe.priority DESC, qe.joined_at ASC LIMIT 10";
        $stmt = $db->prepare($sql_list);
        $stmt->bind_param('i', $queue_type_id);
        $stmt->execute();
        $waiting_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Predict wait time for the next person in line
        $prediction = predict_wait_time($queue_type_id, ($waiting_count + 1));

        echo json_encode([
            'success'        => true,
            'window'         => $window,
            'serving'        => $serving,
            'waiting_count'  => $waiting_count,
            'waiting_list'   => $waiting_list,
            'estimated_wait' => $prediction['label']
        ]);
        break;

    case 'call_next':
        // Check if already serving someone
        $sql_check = "SELECT id FROM queue_entries WHERE window_id = ? AND status = 'serving' AND DATE(joined_at) = CURDATE()";
        $stmt = $db->prepare($sql_check);
        $stmt->bind_param('i', $window_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(['error' => 'Please complete the current transaction first.']);
            exit;
        }
        $stmt->close();

        // Find next in line
        $sql_next = "SELECT id, user_id FROM queue_entries 
                     WHERE queue_type_id = ? AND status = 'waiting'
                     AND DATE(joined_at) = CURDATE()
                     ORDER BY priority DESC, joined_at ASC LIMIT 1";
        $stmt = $db->prepare($sql_next);
        $stmt->bind_param('i', $queue_type_id);
        $stmt->execute();
        $next = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$next) {
            echo json_encode(['error' => 'No students waiting in queue.']);
            exit;
        }

        // Update status to serving and increment call_count
        $sql_update = "UPDATE queue_entries 
                       SET status = 'serving', window_id = ?, called_at = NOW(), call_count = call_count + 1 
                       WHERE id = ?";
        $stmt = $db->prepare($sql_update);
        $stmt->bind_param('ii', $window_id, $next['id']);
        $stmt->execute();
        $stmt->close();

        create_notification($next['user_id'], "Please proceed to {$window['window_label']}.", 'call');
        log_action("Called next student", "Ticket ID: {$next['id']} to Window {$window_id}");

        echo json_encode(['success' => true]);
        break;

    case 'recall':
        $ticket_id = (int)($_POST['id'] ?? 0);
        if (!$ticket_id) { echo json_encode(['error' => 'Invalid ticket ID.']); exit; }

        $stmt = $db->prepare("UPDATE queue_entries SET called_at = NOW(), call_count = call_count + 1 WHERE id = ? AND window_id = ?");
        $stmt->bind_param('ii', $ticket_id, $window_id);
        $stmt->execute();
        $stmt->close();

        log_action("Recall student", "Ticket ID: {$ticket_id}");
        echo json_encode(['success' => true]);
        break;

    case 'mark_done':
        $entry_id = (int)($_POST['id'] ?? 0);
        if (!$entry_id) { echo json_encode(['error' => 'Invalid entry ID.']); exit; }

        $sql_get = "SELECT user_id, joined_at, called_at FROM queue_entries WHERE id = ? AND window_id = ?";
        $stmt = $db->prepare($sql_get);
        $stmt->bind_param('ii', $entry_id, $window_id);
        $stmt->execute();
        $entry = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$entry) { echo json_encode(['error' => 'Entry not found.']); exit; }

        $target_user_id = $entry['user_id'];
        $start = new DateTime($entry['joined_at']);
        $end   = new DateTime();
        $diff  = $start->diff($end);
        $wait_minutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;

        $sql_update = "UPDATE queue_entries 
                       SET status = 'done', served_at = NOW(), wait_minutes = ? 
                       WHERE id = ?";
        $stmt = $db->prepare($sql_update);
        $stmt->bind_param('ii', $wait_minutes, $entry_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            // CRITICAL: Trigger journey sync immediately
            sync_journey_progress($target_user_id);
            
            update_analytics_log($queue_type_id, $wait_minutes);
            log_action("Transaction completed", "Ticket ID: {$entry_id} served in {$wait_minutes} mins");
            echo json_encode(['success' => true]);
        } else {
            $stmt->close();
            echo json_encode(['error' => 'DB update failed.']);
        }
        break;

    case 'no_show':
        $entry_id = (int)($_POST['id'] ?? 0);
        $sql_update = "UPDATE queue_entries SET status = 'no_show', served_at = NOW() WHERE id = ? AND window_id = ?";
        $stmt = $db->prepare($sql_update);
        $stmt->bind_param('ii', $entry_id, $window_id);
        $stmt->execute();
        $stmt->close();
        log_action("Marked as no-show", "Ticket ID: {$entry_id}");
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action.']);
        break;
}
