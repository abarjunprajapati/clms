<?php
require_once 'include/config.php';
function check_table($conn, $table) {
    echo "\n--- Table: $table ---\n";
    $res = mysqli_query($conn, "DESCRIBE $table");
    if ($res) {
        while($row = mysqli_fetch_assoc($res)) {
            echo str_pad($row['Field'], 25) . " | " . str_pad($row['Type'], 20) . " | " . $row['Null'] . " | " . $row['Default'] . "\n";
        }
    } else {
        echo "Table $table does not exist.\n";
    }
}
check_table($conn, 'workmen');
check_table($conn, 'gate_passes');
check_table($conn, 'documents');

