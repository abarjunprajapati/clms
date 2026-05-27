<?php
require_once 'include/config.php';

$query = "SELECT w.id, w.name, w.status, w.contractor_id, c.contractor_name 
          FROM workmen w 
          JOIN contractors c ON w.contractor_id = c.id 
          WHERE w.status IN ('temporary_issued', 'acc_generated', 'permanent_active', 'verified')";
$res = $conn->query($query);
if (!$res) die($conn->error);
while($row = $res->fetch_assoc()) print_r($row);
?>
