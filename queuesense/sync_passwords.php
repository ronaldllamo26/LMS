<?php
/**
 * QueueSense — Institutional Password Syncer
 * Updates all student passwords based on the logic:
 * # + First 2 letters of Last Name + 8080
 */

require_once 'config.php';
require_once 'includes/functions.php';

$db = db_connect();

echo "<h1>Institutional Password Sync</h1>";

$sql = "SELECT id, full_name, student_id FROM users WHERE role = 'student'";
$result = $db->query($sql);

$updated = 0;

while ($user = $result->fetch_assoc()) {
    $name_parts = explode(' ', trim($user['full_name']));
    $last_name  = end($name_parts); // Get the last word as last name
    
    // Logic: # + First 2 letters of last name + 8080
    // Example: Llamo -> Ll -> #Ll8080
    $prefix = substr($last_name, 0, 2);
    $raw_pass = "#" . $prefix . "8080";
    $hash = password_hash($raw_pass, PASSWORD_BCRYPT);
    
    $update_sql = "UPDATE users SET password_hash = ? WHERE id = ?";
    $stmt = $db->prepare($update_sql);
    $stmt->bind_param('si', $hash, $user['id']);
    
    if ($stmt->execute()) {
        echo "✔ Updated <b>{$user['full_name']}</b> ({$user['student_id']}) -> Default Pass: <code style='background:#eee;padding:2px 5px;'>$raw_pass</code><br>";
        $updated++;
    }
    $stmt->close();
}

echo "<h3>Total Updated: $updated students.</h3>";
echo "<br><a href='modules/auth/login.php'>Go to Secure Login</a>";
?>
