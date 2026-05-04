<?php
require_once 'config.php';
require_once 'includes/functions.php';

$db = db_connect();

echo "Current Date in PHP: " . date('Y-m-d') . "\n";
echo "Current Date in MySQL: ";
$res = $db->query("SELECT CURDATE()");
echo $res->fetch_row()[0] . "\n\n";

$res = $db->query("SELECT qe.ticket_number, qe.status, qt.name, qe.joined_at 
                   FROM queue_entries qe 
                   JOIN queue_types qt ON qe.queue_type_id = qt.id 
                   WHERE DATE(qe.joined_at) = CURDATE()");

if ($res->num_rows == 0) {
    echo "No entries found for today.\n";
} else {
    while($row = $res->fetch_assoc()) {
        echo "{$row['ticket_number']} ({$row['status']}) - {$row['name']} [{$row['joined_at']}]\n";
    }
}

echo "\nQueue Types:\n";
$res = $db->query("SELECT id, name FROM queue_types");
while($row = $res->fetch_assoc()) {
    echo "{$row['id']}: {$row['name']}\n";
}
