<?php
include 'include/config.php';
$res = $conn->query("SHOW FULL TABLES LIKE 'gate_passes'");
while($row = $res->fetch_row()) echo $row[1];
?>

