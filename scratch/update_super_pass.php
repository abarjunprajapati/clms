<?php
$conn = mysqli_connect('localhost', 'root', '', 'new_clms');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
$hash = password_hash('password123', PASSWORD_DEFAULT);
$res = mysqli_query($conn, "UPDATE users SET password = '$hash' WHERE contractor_id = 'super_admin'");
if ($res) {
    echo "Password updated successfully for super_admin to 'password123'\n";
} else {
    echo "Update failed\n";
}
?>
