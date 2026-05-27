<?php
include __DIR__ . '/include/config.php';
$result = $conn->query("DESCRIBE annexure2a");
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
