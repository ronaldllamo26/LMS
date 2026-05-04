<?php
require_once 'config.php';
require_once 'includes/functions.php';

$db = db_connect();
$new_password = 'admin123';
$hash = password_hash($new_password, PASSWORD_BCRYPT);

$sql = "UPDATE users SET password_hash = ? WHERE student_id = 'ADMIN-001'";
$stmt = $db->prepare($sql);
$stmt->bind_param('s', $hash);

if ($stmt->execute()) {
    echo "<h2>Admin Password Reset Success!</h2>";
    echo "<p>User: <b>ADMIN-001</b></p>";
    echo "<p>Password: <b>admin123</b></p>";
    echo "<br><a href='modules/auth/login.php'>Go to Admin Login</a>";
} else {
    echo "Error: " . $db->error;
}
$stmt->close();
?>
