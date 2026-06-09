<?php
require_once 'include/config.php';
$res = $conn->query("DESC workmen");
while($row = $res->fetch_assoc()) print_r($row);
?>
