<?php
require_once 'config.php';
require_once 'includes/functions.php';
$db = db_connect();

$new_assignments = [
    'staff1' => 'Window R-1 (Enrollment)',
    'staff2' => 'Window C-1 (Payments)',
    'staff3' => 'Library Desk',
    'staff4' => 'Counseling Room 1'
];

foreach ($new_assignments as $sid => $label) {
    $db->query("UPDATE service_windows SET staff_id = (SELECT id FROM users WHERE student_id = '$sid') WHERE window_label = '$label'");
    echo "Assigned $sid to $label\n";
}

// Open all windows
$db->query("UPDATE service_windows SET status = 'open' WHERE staff_id IS NOT NULL");

echo "All assignments updated!";
?>
