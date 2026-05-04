<?php
require_once 'config.php';
require_once 'includes/functions.php';

$db = db_connect();

// Add the user's specific ID for testing
$id = 's230102815';
$name = 'Ronald Llamo';
$role = 'student';

// Check if exists
$check = $db->query("SELECT id FROM users WHERE student_id = '$id'");
if ($check->num_rows == 0) {
    $sql = "INSERT INTO users (student_id, full_name, role, department, is_active) 
            VALUES ('$id', '$name', '$role', 'BSCS', 1)";
    if ($db->query($sql)) {
        echo "<h2>Success!</h2>";
        echo "<p>Student Account <b>$id</b> (Ronald Llamo) has been created.</p>";
        echo "<p>Next Step: <a href='sync_passwords.php'>Click here to Sync Passwords</a></p>";
    } else {
        echo "Error: " . $db->error;
    }
} else {
    echo "<h2>User already exists!</h2>";
    echo "<p>Next Step: <a href='sync_passwords.php'>Click here to Sync Passwords</a></p>";
}

echo "<br><a href='modules/auth/login.php'>Go to Login</a>";
?>
