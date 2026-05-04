<?php
require_once 'config.php';
require_once 'includes/functions.php';

$db = db_connect();

echo "<h1>Database Schema Check</h1>";

$result = $db->query("SHOW COLUMNS FROM queue_entries");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

echo "<h3>Columns in queue_entries:</h3>";
echo "<ul>";
foreach ($columns as $c) {
    $found = ($c === 'call_count') ? "<b style='color:green'> (FOUND!)</b>" : "";
    echo "<li>$c $found</li>";
}
echo "</ul>";

if (!in_array('call_count', $columns)) {
    echo "<p style='color:red'>✘ Column <b>call_count</b> is MISSING! Fixing it now...</p>";
    $db->query("ALTER TABLE queue_entries ADD COLUMN call_count INT DEFAULT 1");
    echo "<p style='color:green'>✔ Column added successfully. Please refresh this page to verify.</p>";
} else {
    echo "<p style='color:green'>✔ Everything looks fine in the database.</p>";
}

echo "<br><a href='staff/index.php'>Back to Staff Dashboard</a>";
?>
