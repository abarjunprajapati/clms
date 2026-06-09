<?php
require_once 'include/config.php';
$res = $conn->query("DESCRIBE acc_attendance_map");
while($row = $res->fetch_assoc()) print_r($row);
?>
