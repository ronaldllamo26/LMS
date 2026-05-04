<?php
require_once 'config.php';
require_once 'includes/functions.php';
$db = db_connect();

// Update naming for consistency
$db->query("UPDATE queue_types SET name = 'Bookstore' WHERE id = 4");
$db->query("UPDATE service_windows SET window_label = 'Window B-1 (Bookstore)' WHERE id = 6");

echo "Names updated to Bookstore!\n";

// FORCE RESTORE THE JOURNEY FOR RONALD (Testing purpose)
// We assume Ronald wanted Cashier (2) and Bookstore (4).
// Since Cashier is done, we set it to Step 1 (index 1).
session_start();
$_SESSION['journey'] = [
    'steps' => [2, 4],
    'current_step' => 1,
    'total_steps' => 2
];
echo "Journey session restored for Ronald! Refresh your dashboard.";
?>
