<?php
include __DIR__ . '/../include/config.php';
$res = $conn->query("SELECT password FROM users WHERE email='safety1@example.com'");
$row = $res->fetch_assoc();
if (password_verify('admin@123', $row['password'])) {
    echo "Password matches!\n";
} else {
    echo "Password DOES NOT match!\n";
}
?>
