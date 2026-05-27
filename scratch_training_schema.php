<?php
require_once 'include/config.php';
echo "--- training_requests ---\n";
$res = $conn->query("DESCRIBE training_requests");
while($row = $res->fetch_assoc()) print_r($row);
echo "\n--- training_results ---\n";
$res = $conn->query("DESCRIBE training_results");
while($row = $res->fetch_assoc()) print_r($row);
?>
