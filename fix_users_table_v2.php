<?php
require_once 'include/config.php';

$roles = [
    'admin', 
    'contractor', 
    'welfare_admin', 
    'welfare_user', 
    'pass_issuer', 
    'pass_user', 
    'safety', 
    'safety_user', 
    'frontline', 
    'front_line_user', 
    'super_admin'
];

$role_string = "'" . implode("','", $roles) . "'";

$alter_sql = "ALTER TABLE users MODIFY COLUMN role ENUM($role_string) DEFAULT 'contractor'";
if (mysqli_query($conn, $alter_sql)) {
    echo "Success: Updated users table roles to include: " . implode(", ", $roles) . "\n";
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}

// Also verify current users
$res = mysqli_query($conn, "SELECT username, role FROM users LIMIT 10");
while($row = mysqli_fetch_assoc($res)) {
    echo "User: " . ($row['username'] ?? 'N/A') . " | Role: " . $row['role'] . "\n";
}
?>

