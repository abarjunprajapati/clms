<?php
require_once 'include/config.php';

echo "--- ROLES TABLE ---\n";
$res = mysqli_query($conn, "SELECT * FROM roles");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}

echo "\n--- USERS TABLE (first 10) ---\n";
$res = mysqli_query($conn, "SELECT id, contractor_id, role_id, role, name, email, mobile, status FROM users LIMIT 10");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>

