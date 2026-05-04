<?php
require_once 'config.php';
require_once 'includes/functions.php';

$db = db_connect();

echo "<h1>Queue Spawner (Test Data)</h1>";

$students = [
    ['s2024-001', 'Juan Dela Cruz'],
    ['s2024-002', 'Maria Clara'],
    ['s2024-003', 'Jose Rizal'],
    ['s2024-004', 'Andres Bonifacio'],
    ['s2024-005', 'Emilio Aguinaldo'],
    ['s2024-006', 'Apolinario Mabini'],
    ['s2024-007', 'Melchora Aquino'],
    ['s2024-008', 'Gabriela Silang'],
    ['s2024-009', 'Juan Luna'],
    ['s2024-010', 'Marcelo H. Del Pilar']
];

$inserted = 0;
foreach ($students as $s) {
    // 1. Ensure user exists
    $sid = $s[0];
    $name = $s[1];
    $db->query("INSERT IGNORE INTO users (student_id, full_name, role, department) VALUES ('$sid', '$name', 'student', 'BSIS')");
    $uid = $db->insert_id ?: $db->query("SELECT id FROM users WHERE student_id = '$sid'")->fetch_assoc()['id'];

    // 2. Add to Queue (Alternate between Registrar[1] and Cashier[2])
    $qid = ($inserted % 2 == 0) ? 1 : 2;
    $prefix = ($qid == 1) ? 'R-' : 'C-';
    
    // Get last ticket number
    $last = $db->query("SELECT ticket_number FROM queue_entries WHERE queue_type_id = $qid ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $num = $last ? (int)substr($last['ticket_number'], 2) + 1 : 1;
    $ticket = $prefix . str_pad($num, 3, '0', STR_PAD_LEFT);

    $db->query("INSERT INTO queue_entries (user_id, queue_type_id, ticket_number, position, status) 
                VALUES ($uid, $qid, '$ticket', $num, 'waiting')");
    $inserted++;
}

echo "<h3>Successfully added $inserted students to the queue!</h3>";
echo "<p><a href='staff/index.php'>Go to Staff Dashboard to Call Them</a></p>";
?>
