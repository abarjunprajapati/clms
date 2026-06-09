<?php
include __DIR__ . '/../include/config.php';
$r = $conn->query("DESCRIBE contractor_ecp_history");
while($row = $r->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
