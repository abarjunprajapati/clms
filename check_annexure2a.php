<?php
require_once 'include/config.php';
$res = mysqli_query($conn, "DESCRIBE annexure2a");
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . " | " . $row['Type'] . "\n";
}
?>
