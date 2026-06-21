<?php
require_once __DIR__ . '/../include/config.php';

$res = mysqli_query($conn, "SHOW CREATE TABLE training_class_batches");
$row = mysqli_fetch_assoc($res);
echo "=== TABLE DEFINITION ===\n";
echo $row['Create Table'] . "\n\n";

$res = mysqli_query($conn, "SHOW TRIGGERS LIKE 'training_class_batches'");
echo "=== TRIGGERS ===\n";
while ($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
echo "=== DONE ===\n";
