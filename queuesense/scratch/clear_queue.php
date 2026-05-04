<?php
require_once 'config.php';
require_once 'includes/functions.php';

$db = db_connect();
$db->query("DELETE FROM queue_entries WHERE DATE(joined_at) = CURDATE()");
echo "Queue successfully cleared for today. Everything is fresh!";
