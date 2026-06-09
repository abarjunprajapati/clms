<?php
include __DIR__ . '/include/config.php';
$result = $conn->query("DESCRIBE contractors");
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
