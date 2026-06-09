<?php
require_once __DIR__ . '/include/config.php';
$res = mysqli_query($conn, "DESCRIBE contractors");
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
