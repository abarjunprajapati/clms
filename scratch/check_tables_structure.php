<?php
require_once 'include/config.php';

function check_table($conn, $table) {
    echo "--- Table: $table ---\n";
    $result = mysqli_query($conn, "SHOW COLUMNS FROM $table");
    if (!$result) {
        echo "Error: " . mysqli_error($conn) . "\n";
        return;
    }
    while ($row = mysqli_fetch_assoc($result)) {
        echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']} - {$row['Default']}\n";
    }
    echo "\n";
}

check_table($conn, 'users');
check_table($conn, 'roles');
?>

