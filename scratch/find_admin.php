<?php
include 'include/config.php';
$res = $conn->query("SELECT name, email, role FROM users WHERE role IN ('super_admin', 'admin') LIMIT 1");
echo json_encode($res->fetch_assoc());
?>

