<?php
require_once 'include/config.php';
$result = mysqli_query($conn, "DESCRIBE roles");
echo "ROLES TABLE:\n";
while($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

$result = mysqli_query($conn, "SELECT * FROM roles");
echo "\nROLES DATA:\n";
while($row = mysqli_fetch_assoc($result)) {
    print_r($row);
}

