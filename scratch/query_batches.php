<?php
include 'include/config.php';

function desc($conn, $table) {
    echo "=== DESCRIBE $table ===\n";
    $res = mysqli_query($conn, "DESCRIBE `$table`");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            printf("%s - %s - %s\n", $row['Field'], $row['Type'], $row['Null']);
        }
    }
}

desc($conn, 'training_class_batches');
desc($conn, 'training_batch_workers');
desc($conn, 'training_requests');
