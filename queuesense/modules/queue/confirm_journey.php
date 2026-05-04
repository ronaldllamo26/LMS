<?php
/**
 * QueueSense — Confirm Journey Logic
 * Joins the first queue of an optimized journey.
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';

$required_role = 'student';
require_once __DIR__ . '/../../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/modules/queue/status.php');
}

$ids_str = $_POST['ids'] ?? '';
$ids = array_filter(explode(',', $ids_str), 'is_numeric');

if (empty($ids)) {
    redirect(BASE_URL . '/modules/queue/status.php');
}

$db = db_connect();

// Fetch details to re-optimize (security/consistency check)
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$sql = "SELECT id, (SELECT COUNT(*) FROM queue_entries WHERE queue_type_id = qt.id AND status = 'waiting' AND DATE(joined_at) = CURDATE()) as waiting_count,
               (SELECT avg_service_time FROM queue_types WHERE id = qt.id) as avg_service
        FROM queue_types qt
        WHERE id IN ($placeholders)";

$stmt = $db->prepare($sql);
$stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
$stmt->execute();
$services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Optimization Sort
usort($services, function($a, $b) {
    $a_total = $a['waiting_count'] * $a['avg_service'];
    $b_total = $b['waiting_count'] * $b['avg_service'];
    return $a_total <=> $b_total;
});

// Final sequence of IDs
$optimized_ids = array_column($services, 'id');

// Fetch current DB time to avoid timezone mismatch
$db_time_res = $db->query("SELECT NOW() as db_now")->fetch_assoc();
$start_time = $db_time_res['db_now'] ?? date('Y-m-d H:i:s');

// Store journey in session with DB start time
    // Save the maximum ID currently in the database as an anchor
    // This ensures we only count tickets created AFTER this journey started.
    $res = $db->query("SELECT MAX(id) as max_id FROM queue_entries");
    $max_row = $res->fetch_assoc();
    $last_id = $max_row['max_id'] ?? 0;

    $_SESSION['journey'] = [
        'steps' => $optimized_ids,
        'current_step' => 0,
        'total_steps' => count($optimized_ids),
        'start_time' => $start_time,
        'anchor_id' => $last_id
    ];

    // SAVE TO DATABASE FOR SERVER-SIDE SYNC
    $steps_json = json_encode($optimized_ids);
    $total_steps = count($optimized_ids);
    $sql_journey = "INSERT INTO user_journeys (user_id, steps, current_step, total_steps, anchor_id, status) 
                    VALUES (?, ?, 0, ?, ?, 'active') 
                    ON DUPLICATE KEY UPDATE steps = VALUES(steps), current_step = 0, total_steps = VALUES(total_steps), anchor_id = VALUES(anchor_id), status = 'active'";
    $stmt = $db->prepare($sql_journey);
    $stmt->bind_param('isii', $current_user['id'], $steps_json, $total_steps, $last_id);
    $stmt->execute();
    $stmt->close();

// SMART SYNC: Skip steps ONLY if they have an ACTIVE ticket already (to prevent double-queueing)
foreach ($optimized_ids as $index => $sid) {
    $stmt = $db->prepare("SELECT id FROM queue_entries WHERE user_id = ? AND queue_type_id = ? AND status IN ('waiting', 'serving') AND DATE(joined_at) = CURDATE() LIMIT 1");
    $stmt->bind_param('ii', $current_user_id, $sid);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $_SESSION['journey']['current_step'] = $index + 1;
    }
    $stmt->close();
}

// JOIN ALL QUEUES IMMEDIATELY TO SECURE POSITIONS
$ticket_id_map = [];

foreach ($optimized_ids as $index => $sid) {
    // Check if already has an active ticket for this queue today
    $stmt = $db->prepare("SELECT id FROM queue_entries WHERE user_id = ? AND queue_type_id = ? AND status IN ('waiting', 'serving', 'pending') AND DATE(joined_at) = CURDATE() LIMIT 1");
    $stmt->bind_param('ii', $current_user_id, $sid);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        $ticket_id_map[$sid] = $existing['id'];
    } else {
        // Fetch details for the target queue
        $stmt = $db->prepare("SELECT prefix FROM queue_types WHERE id = ?");
        $stmt->bind_param('i', $sid);
        $stmt->execute();
        $q_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $prefix = $q_data['prefix'] ?? 'T';
        $ticket_number = generate_ticket_number($sid, $prefix);

        // First step is 'waiting', others are 'pending' (Smart Reservation)
        $initial_status = ($index === 0) ? 'waiting' : 'pending';

        // Insert Ticket
        $sql = "INSERT INTO queue_entries (queue_type_id, user_id, ticket_number, status, joined_at, position) 
                VALUES (?, ?, ?, ?, NOW(), (SELECT IFNULL(MAX(position), 0) + 1 FROM queue_entries q2 WHERE q2.queue_type_id = ? AND DATE(q2.joined_at) = CURDATE()))";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('iissi', $sid, $current_user_id, $ticket_number, $initial_status, $sid);
        $stmt->execute();
        
        $new_id = $db->insert_id;
        $ticket_id_map[$sid] = $new_id;
        
        // If it's the FIRST step, set it as the active ticket for the immediate redirect
        if ($index === 0) {
            $_SESSION['active_ticket_id'] = $new_id;
        }
        
        $stmt->close();
        log_action('JOURNEY_JOIN', "Joined queue $sid ($ticket_number) as $initial_status.");
    }
}

// SAVE THE TICKET IDS TO DATABASE
$ticket_ids_json = json_encode($ticket_id_map);
$sql_upd_journey = "UPDATE user_journeys SET ticket_ids = ? WHERE user_id = ?";
$stmt = $db->prepare($sql_upd_journey);
$stmt->bind_param('si', $ticket_ids_json, $current_user_id);
$stmt->execute();
$stmt->close();

// Save to session too
$_SESSION['journey']['ticket_ids'] = $ticket_id_map;

redirect(BASE_URL . '/modules/queue/ticket.php?success=journey_started');
exit;
