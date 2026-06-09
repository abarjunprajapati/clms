<?php
require_once 'include/config.php';
$res = $conn->query("SELECT id, name, status, acc_number, temp_id FROM workmen WHERE application_no = 'APP-1'");
while($row = $res->fetch_assoc()) print_r($row);
?>
