<?php
include 'include/config.php';
$r = $conn->query("SELECT id, username, otp, otp_expiry, otp_attempts FROM users WHERE username = 'CONT-2024-001'");
if ($r) {
    print_r($r->fetch_assoc());
}
?>

