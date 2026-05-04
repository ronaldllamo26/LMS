<?php
require_once 'config.php';
require_once 'includes/functions.php';
$db = db_connect();

$assignments = [
    'staff1' => 'R-', // Registrar
    'staff2' => 'C-', // Cashier
    'staff3' => 'B-', // Bookstore
    'staff4' => 'G-'  // Guidance
];

foreach ($assignments as $sid => $prefix) {
    $db->query("UPDATE service_windows SET staff_id = (SELECT id FROM users WHERE student_id = '$sid') WHERE window_label LIKE '$prefix%'");
    echo "Assigned $sid to window $prefix\n";
}
echo "All windows assigned!";
?>
