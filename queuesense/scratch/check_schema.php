<?php
require_once 'config.php';
$db = db_connect();
$res = $db->query("DESCRIBE users");
while($row = $res->fetch_assoc()) echo $row['Field'] . "\n";
?>
