<?php
$db = new mysqli('localhost', 'root', '', 'queuesense_db');

$updates = [
    'staff1' => 'Registrar Staff',
    'staff2' => 'Cashier Staff',
    'staff3' => 'Bookstore Staff',
    'staff4' => 'Guidance Staff'
];

foreach ($updates as $sid => $new_name) {
    $stmt = $db->prepare("UPDATE users SET full_name = ? WHERE student_id = ?");
    $stmt->bind_param('ss', $new_name, $sid);
    $stmt->execute();
    echo "Updated $sid to $new_name\n";
}
?>
