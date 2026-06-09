<?php
require_once 'include/config.php';

echo "--- APPLICATIONS SCHEMA ---\n";
$res = mysqli_query($conn, "SHOW COLUMNS FROM applications");
while($row = mysqli_fetch_assoc($res)) {
    echo "{$row['Field']} - {$row['Type']}\n";
}

echo "\n--- SAMPLE APPLICATIONS ---\n";
$res = mysqli_query($conn, "SELECT * FROM applications LIMIT 5");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>

