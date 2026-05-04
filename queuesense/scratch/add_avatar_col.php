<?php
require_once 'config.php';
$db = db_connect();
$db->query("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) AFTER full_name");
echo "Avatar column added!";
?>
