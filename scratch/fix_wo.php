<?php
require_once '../include/config.php';

try {
    // Check current state of work_orders id column
    $res = db_fetch_all($conn, "DESCRIBE work_orders");
    echo "Before alteration:\n";
    echo json_encode($res, JSON_PRETTY_PRINT) . "\n\n";

    // Alter table
    $ok = db_execute($conn, "ALTER TABLE work_orders MODIFY id INT(11) NOT NULL AUTO_INCREMENT");
    if ($ok) {
        echo "Successfully added AUTO_INCREMENT to work_orders.id!\n";
    } else {
        echo "Failed to alter table. Error: " . mysqli_error($conn) . "\n";
    }

    $resAfter = db_fetch_all($conn, "DESCRIBE work_orders");
    echo "After alteration:\n";
    echo json_encode($resAfter, JSON_PRETTY_PRINT) . "\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
