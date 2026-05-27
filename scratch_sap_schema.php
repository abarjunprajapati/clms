<?php
require_once 'include/config.php';
$res = $conn->query("DESCRIBE sap_workers");
while($row = $res->fetch_assoc()) print_r($row);
?>
