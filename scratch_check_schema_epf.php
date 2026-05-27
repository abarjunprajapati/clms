<?php
include 'include/config.php';
$res = $conn->query("DESCRIBE contractors");
while($row = $res->fetch_assoc()) {
    echo json_encode($row) . "\n";
}
echo "--- annexure2a ---\n";
$res = $conn->query("DESCRIBE annexure2a");
while($row = $res->fetch_assoc()) {
    echo json_encode($row) . "\n";
}
