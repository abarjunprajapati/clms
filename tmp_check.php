<?php
require_once 'include/config.php';
$res = mysqli_query($conn, "SELECT id, contractor_id, password FROM users WHERE contractor_id='CONT-2024-001'");
$row = mysqli_fetch_assoc($res);
var_dump($row);
mysqli_close($conn);
?>
