<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

$db = db_connect();

$sql = "SELECT qe.ticket_number, qe.called_at, qe.call_count, sw.window_label, qt.name AS queue_name, qt.color
        FROM queue_entries qe
        JOIN service_windows sw ON sw.id = qe.window_id
        JOIN queue_types qt ON qt.id = qe.queue_type_id
        WHERE qe.status = 'serving'
          AND DATE(qe.joined_at) = CURDATE()
        ORDER BY qe.called_at DESC
        LIMIT 4";

$result = $db->query($sql);
$serving = $result->fetch_all(MYSQLI_ASSOC);

// Create a unique checksum based on ticket numbers and their call counts
$checksum = md5(json_encode($serving));

echo json_encode([
    'serving' => $serving,
    'checksum' => $checksum
]);
?>
