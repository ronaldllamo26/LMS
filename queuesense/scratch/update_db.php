<?php
require_once 'config.php';
$db = db_connect();

// 1. Add columns
$db->query("ALTER TABLE users 
            ADD COLUMN personal_email VARCHAR(255) AFTER email, 
            ADD COLUMN contact_number VARCHAR(20) AFTER personal_email, 
            ADD COLUMN civil_status VARCHAR(50) AFTER contact_number, 
            ADD COLUMN birthday DATE AFTER civil_status");

// 2. Update Ronald's data
$db->query("UPDATE users SET 
            email = '230102815@bcp.edu.ph', 
            personal_email = 'llamo.ronald.estiler@gmail.com', 
            contact_number = '09683255290', 
            civil_status = 'Single', 
            birthday = '2003-08-12' 
            WHERE student_id = 's230102815'");

echo "Database and Ronald's data updated successfully!";
?>
