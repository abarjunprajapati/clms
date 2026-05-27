<?php
require_once 'include/config.php';

echo "--- Table: users ---\n";
$res = mysqli_query($conn, "DESCRIBE users");
if ($res) {
    while($row = mysqli_fetch_assoc($res)) {
        echo str_pad($row['Field'], 20) . " | " . str_pad($row['Type'], 20) . " | " . $row['Null'] . " | " . $row['Default'] . "\n";
    }
}

// Update users table roles to include all needed roles
$alter_sql = "ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'contractor', 'welfare_admin', 'welfare_user', 'pass_issuer', 'safety', 'frontline', 'super_admin') NOT NULL";
if (mysqli_query($conn, $alter_sql)) {
    echo "\nSuccess: Updated users table roles.\n";
} else {
    echo "\nError: " . mysqli_error($conn) . "\n";
}
?>

