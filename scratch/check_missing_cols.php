<?php
require_once 'include/config.php';

$tables = ['gate_pass_requests', 'attendance'];

foreach ($tables as $table) {
    echo "--- TABLE: $table ---\n";
    $res = mysqli_query($conn, "SHOW COLUMNS FROM $table");
    if (!$res) {
        echo "Error: " . mysqli_error($conn) . "\n\n";
        continue;
    }
    while ($row = mysqli_fetch_assoc($res)) {
        echo "{$row['Field']} - {$row['Type']}\n";
    }
    echo "\n";
}
?>

