<?php
require_once 'include/config.php';
$res = $conn->query("SHOW CREATE PROCEDURE sp_authenticate_user");
$row = $res->fetch_assoc();
print_r($row);
?>
