<?php
include 'd:/Xampp/htdocs/clms/include/config.php';

echo "GATE_PASSES SCHEMA:\n";
$res = $conn->query("DESCRIBE gate_passes");
while($row = $res->fetch_assoc()) print_r($row);

echo "\n\nWORKER_TRANSFER_LOGS SCHEMA:\n";
$res = $conn->query("DESCRIBE worker_transfer_logs");
while($row = $res->fetch_assoc()) print_r($row);
?>
