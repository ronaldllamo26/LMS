<?php
require_once 'config.php';
require_once 'includes/functions.php';

$db = db_connect();

echo "<h1>All Users in " . DB_NAME . "</h1>";

$result = $db->query("SELECT id, student_id, full_name, role FROM users");

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse; width:100%;'>";
    echo "<tr style='background:#f4f4f4;'><th>ID</th><th>Student ID</th><th>Full Name</th><th>Role</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $color = ($row['student_id'] == 's230102815') ? 'background:#fff3cd; font-weight:bold;' : '';
        echo "<tr style='$color'>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td><code>" . $row['student_id'] . "</code></td>";
        echo "<td>" . $row['full_name'] . "</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>The users table is empty!</p>";
}

echo "<br><hr>";
echo "<h3>Debug Info:</h3>";
echo "Current Database: <b>" . DB_NAME . "</b><br>";
echo "Search for 's230102815': ";
$check = $db->query("SELECT id FROM users WHERE student_id = 's230102815'");
echo ($check->num_rows > 0) ? "<span style='color:green'>FOUND!</span>" : "<span style='color:red'>NOT FOUND!</span>";

echo "<br><br><a href='add_test_student.php'>Run Add Student Again</a>";
?>
