<?php
include 'include/config.php';
$res = $conn->query('DESCRIBE training_results');
$cols = [];
while($row = $res->fetch_assoc()) $cols[] = $row['Field'];
echo json_encode($cols);
?>

