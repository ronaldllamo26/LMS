<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'includes/functions.php';

echo "<h1>QueueSense System Diagnostic (Student Security Check)</h1>";

// 1. Check DB Connection
echo "<h3>1. Database Connection</h3>";
try {
    $db = db_connect();
    echo "<span style='color:green'>✔ Connected to database: " . DB_NAME . "</span><br>";
} catch (Exception $e) {
    echo "<span style='color:red'>✘ Connection Failed: " . $e->getMessage() . "</span><br>";
    exit;
}

// 2. Check Specific Student ID (s230102815)
echo "<h3>2. Student Account Check (s230102815)</h3>";
$test_id = 's230102815';
$sql = "SELECT id, student_id, full_name, password_hash, role FROM users WHERE student_id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param('s', $test_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $user = $res->fetch_assoc();
    echo "<span style='color:green'>✔ User $test_id FOUND.</span><br>";
    echo "Full Name: " . $user['full_name'] . "<br>";
    echo "Role: " . $user['role'] . "<br>";
    echo "Hash: " . substr($user['password_hash'], 0, 15) . "...<br>";
    
    // Test the specific password pattern #Ll8080
    // We assume the full name I put earlier was "Alejandro (Test)"? 
    // Wait, if the sync script used "Alejandro (Test)", the last name is "(Test)".
    // So the password would be #(T8080. 
    
    $expected_pass = "#Ll8080";
    if (password_verify($expected_pass, $user['password_hash'])) {
        echo "<span style='color:green'>✔ Password '$expected_pass' VERIFIED correctly!</span><br>";
    } else {
        echo "<span style='color:red'>✘ Password '$expected_pass' DOES NOT match hash!</span><br>";
        echo "Note: The sync script uses the LAST WORD of your Full Name to generate the password.<br>";
        echo "Try checking what password was generated in sync_passwords.php.<br>";
    }
} else {
    echo "<span style='color:red'>✘ User $test_id NOT FOUND in database!</span><br>";
    echo "Tip: Make sure you ran 'add_test_student.php' first.<br>";
}

// 3. List all Students for verification
echo "<h3>3. Current Student List</h3>";
$list = $db->query("SELECT student_id, full_name FROM users WHERE role = 'student' LIMIT 5");
while($row = $list->fetch_assoc()){
    echo "• <code>" . $row['student_id'] . "</code> - " . $row['full_name'] . "<br>";
}

echo "<br><hr>";
echo "<p><a href='sync_passwords.php'>Run Sync Passwords Again</a> | <a href='modules/auth/login.php'>Back to Login</a></p>";
?>
