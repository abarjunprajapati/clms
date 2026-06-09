<?php
require_once 'include/config.php';
$res = mysqli_query($conn, "DESCRIBE training_results");
if($res) {
    while($row = mysqli_fetch_assoc($res)) {
        echo $row['Field'] . " | " . $row['Type'] . "\n";
    }
} else {
    echo "Table training_results not found\n";
}
?>

