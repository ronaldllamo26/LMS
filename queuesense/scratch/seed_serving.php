<?php
require_once 'config.php';
require_once 'includes/functions.php';

$db = db_connect();

// Clear existing serving for today to make it fresh (optional, but good for demo)
$db->query("UPDATE queue_entries SET status = 'done' WHERE status = 'serving' AND DATE(joined_at) = CURDATE()");

// Data for 5 serving students
$students = [
    ['user_id' => 6,  'qt_id' => 1, 'window_id' => 1, 'ticket' => 'R-101'],
    ['user_id' => 7,  'qt_id' => 2, 'window_id' => 3, 'ticket' => 'C-101'],
    ['user_id' => 8,  'qt_id' => 3, 'window_id' => 5, 'ticket' => 'G-101'],
    ['user_id' => 9,  'qt_id' => 4, 'window_id' => 6, 'ticket' => 'L-101'],
    ['user_id' => 10, 'qt_id' => 1, 'window_id' => 1, 'ticket' => 'R-102']
];

foreach ($students as $s) {
    $sql = "INSERT INTO queue_entries (user_id, queue_type_id, window_id, ticket_number, position, status, joined_at, called_at) 
            VALUES ({$s['user_id']}, {$s['qt_id']}, {$s['window_id']}, '{$s['ticket']}', 99, 'serving', NOW(), NOW())";
    $db->query($sql);
}

echo "Inserted 5 serving entries successfully.";
