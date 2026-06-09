<?php
$conn = mysqli_connect('localhost', 'root', '', 'new_clms');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
$res = mysqli_query($conn, "SELECT id, name, email, role, contractor_id, status FROM users WHERE role IN ('super_admin', 'welfare_admin', 'welfare_user')");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        echo "User: ID: {$row['id']} | Name: {$row['name']} | Role: {$row['role']} | Email/User: " . ($row['contractor_id'] ?: $row['email']) . " | Status: {$row['status']}\n";
    }
}
?>
