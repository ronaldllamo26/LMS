<?php
require_once 'config.php';
require_once 'includes/functions.php';

$db = db_connect();

// Get some existing students to use as waiting
$res = $db->query("SELECT id FROM users WHERE role = 'student' LIMIT 10");
$student_ids = [];
while($row = $res->fetch_assoc()) $student_ids[] = $row['id'];

// Departments
// 1: Registrar (R), 2: Cashier (C)
$depts = [
    ['id' => 1, 'prefix' => 'R'],
    ['id' => 2, 'prefix' => 'C']
];

$count = 1;
foreach ($depts as $d) {
    for ($i = 11; $i <= 15; $i++) {
        $u_id = $student_ids[array_rand($student_ids)];
        $ticket = $d['prefix'] . "-" . str_pad($i, 3, '0', STR_PAD_LEFT);
        
        $sql = "INSERT INTO queue_entries (user_id, queue_type_id, ticket_number, position, status, joined_at) 
                VALUES ($u_id, {$d['id']}, '$ticket', $i, 'waiting', NOW())";
        $db->query($sql);
    }
}

echo "Successfully added 10 waiting students to the queue.";
