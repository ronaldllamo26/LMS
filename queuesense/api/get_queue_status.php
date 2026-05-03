<?php
/**
 * QueueSense — API: Get Queue Status
 * Returns live queue data as JSON for AJAX polling.
 * Called every 5 seconds from the student status page.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store');

if (!is_logged_in()) {
    echo json_encode(['error' => 'unauthenticated']);
    exit;
}

$db  = db_connect();
$sql = "SELECT
            qt.id,
            qt.name,
            qt.prefix,
            qt.color,
            qt.is_open,

            (SELECT ticket_number FROM queue_entries
             WHERE queue_type_id = qt.id AND status = 'serving'
               AND DATE(joined_at) = CURDATE()
             ORDER BY called_at DESC LIMIT 1) AS now_serving,

            (SELECT COUNT(*) FROM queue_entries
             WHERE queue_type_id = qt.id AND status = 'waiting'
               AND DATE(joined_at) = CURDATE()) AS waiting_count

        FROM queue_types qt ORDER BY qt.sort_order ASC";

$result = $db->query($sql);
$queues = [];

while ($row = $result->fetch_assoc()) {
    $est = predict_wait_time((int)$row['id'], (int)$row['waiting_count'] + 1);
    $queues[] = [
        'id'             => (int)$row['id'],
        'name'           => $row['name'],
        'now_serving'    => $row['now_serving'],
        'waiting_count'  => (int)$row['waiting_count'],
        'est_wait_label' => $est['label'],
        'is_open'        => (bool)$row['is_open'],
    ];
}

echo json_encode(['queues' => $queues, 'timestamp' => date('H:i:s')]);
