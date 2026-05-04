<?php
require_once 'config.php';
require_once 'includes/functions.php';
$db = db_connect();

echo "<h3>Waiting Tickets:</h3><pre>";
$res = $db->query("SELECT qe.*, qt.name as queue_name 
                   FROM queue_entries qe 
                   JOIN queue_types qt ON qt.id = qe.queue_type_id 
                   WHERE qe.status = 'waiting' 
                   AND DATE(qe.joined_at) = CURDATE()");
while($row = $res->fetch_assoc()) print_r($row);
echo "</pre>";

echo "<h3>Session Journey (Simulated):</h3><pre>";
print_r($_SESSION['journey'] ?? 'No journey in session');
echo "</pre>";
?>
