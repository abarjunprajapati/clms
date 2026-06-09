<?php
include __DIR__ . '/../include/config.php';
$result = $conn->query("DESCRIBE training_requests");
$cols = [];
while($row = $result->fetch_assoc()) {
    $cols[] = $row;
}
echo json_encode($cols, JSON_PRETTY_PRINT);
?>
