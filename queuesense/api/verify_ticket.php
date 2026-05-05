<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

// Only staff and admins should be able to verify tickets
require_auth();
if (!has_role('staff') && !has_role('admin')) {
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

$db = db_connect();

$id = $_GET['id'] ?? '';

if (!$id) {
    echo json_encode(['error' => 'Invalid Ticket ID.']);
    exit;
}

// Support both Internal ID (numeric) and Ticket Number (e.g. R-001)
$sql = "SELECT qe.*, u.full_name, qt.name AS queue_name
        FROM queue_entries qe
        JOIN users u ON qe.user_id = u.id
        JOIN queue_types qt ON qt.id = qe.queue_type_id
        WHERE (qe.id = ? OR qe.ticket_number = ?) AND DATE(qe.joined_at) = CURDATE()";

$stmt = $db->prepare($sql);
$stmt->bind_param('ss', $id, $id);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ticket) {
    echo json_encode(['error' => 'Ticket not found or expired.']);
    exit;
}

echo json_encode($ticket);
?>
