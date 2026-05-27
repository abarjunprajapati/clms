<?php
require_once 'include/config.php';
$res = mysqli_query($conn, "DESCRIBE workmen");
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . " | " . $row['Type'] . "\n";
}
?>

