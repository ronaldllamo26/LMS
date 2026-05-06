<?php
/**
 * QueueSense — API: Get My Ticket Status
 * Returns the current status of the logged-in student's active ticket.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['error' => 'unauthenticated']);
    exit;
}

$db = db_connect();
$user_id = $_SESSION['user_id'];

// 1. Sync AI Journey progress (Auto-re-queue if needed)
sync_journey_progress($user_id);

// 2. Check for active OR JUST FINISHED ticket (to trigger handover)
$stmt = $db->prepare("SELECT id, status FROM queue_entries 
                      WHERE user_id = ? AND status IN ('waiting', 'serving', 'done', 'no_show') 
                      ORDER BY joined_at DESC LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();
$stmt->close();


if ($ticket) {
    $entry_id = $ticket['id'];
    $status   = $ticket['status'];
    $ahead    = 0;

    if ($status === 'waiting') {
        // Fetch queue details to calculate position
        $stmt = $db->prepare("SELECT queue_type_id, position FROM queue_entries WHERE id = ?");
        $stmt->bind_param('i', $entry_id);
        $stmt->execute();
        $details = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($details) {
            $pred = predict_wait_time($details['queue_type_id'], $details['position']);
            $ahead = $pred['people_ahead'];
        }
    }

    echo json_encode([
        'ticket_id'    => $entry_id,
        'status'       => $status,
        'people_ahead' => $ahead
    ]);
} else {
    echo json_encode([
        'ticket_id'    => null,
        'status'       => 'none',
        'people_ahead' => 0
    ]);
}
