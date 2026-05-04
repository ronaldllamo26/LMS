<?php
require_once 'config.php';
require_once 'includes/functions.php';
$db = db_connect();

echo "<h3>Service Windows:</h3><pre>";
$res = $db->query("SELECT * FROM service_windows");
while($row = $res->fetch_assoc()) print_r($row);
echo "</pre>";

echo "<h3>Staff Users:</h3><pre>";
$res = $db->query("SELECT id, student_id, full_name FROM users WHERE role = 'staff'");
while($row = $res->fetch_assoc()) print_r($row);
echo "</pre>";
?>
