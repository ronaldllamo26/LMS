<?php
require_once 'config.php';
require_once 'includes/functions.php';

$db = db_connect();
$new_password = 'admin123';
$hash = password_hash($new_password, PASSWORD_BCRYPT);

$sql = "UPDATE users SET password_hash = ? WHERE student_id = 'STAFF-001'";
$stmt = $db->prepare($sql);
$stmt->bind_param('s', $hash);

if ($stmt->execute()) {
    echo "<h2>Password Updated Successfully!</h2>";
    echo "<p>User: <b>STAFF-001</b></p>";
    echo "<p>New Password: <b>admin123</b></p>";
    echo "<br><a href='modules/auth/login.php'>Go to Login Page</a>";
} else {
    echo "Error updating password: " . $db->error;
}
$stmt->close();
?>
