<?php
require_once 'include/config.php';

header('Content-Type: text/plain');

echo "CLMS LIVE DB Fix: Adding AUTO_INCREMENT to work_orders.id\n";
echo "=========================================================\n\n";

try {
    // 1. Check if column has auto_increment already
    $res = db_fetch_all($conn, "DESCRIBE work_orders");
    $idCol = null;
    foreach ($res as $col) {
        if ($col['Field'] === 'id') {
            $idCol = $col;
            break;
        }
    }

    if ($idCol) {
        echo "Current work_orders.id config:\n";
        echo "Type: " . $idCol['Type'] . "\n";
        echo "Null: " . $idCol['Null'] . "\n";
        echo "Key: " . $idCol['Key'] . "\n";
        echo "Default: " . ($idCol['Default'] ?? 'NULL') . "\n";
        echo "Extra: " . ($idCol['Extra'] ?? '(empty)') . "\n\n";

        if (strpos($idCol['Extra'], 'auto_increment') !== false) {
            echo "AUTO_INCREMENT is already enabled. No action needed.\n";
            exit;
        }
    }

    // 2. Perform the Alteration
    echo "Running query: ALTER TABLE work_orders MODIFY id INT(11) NOT NULL AUTO_INCREMENT...\n";
    $ok = db_execute($conn, "ALTER TABLE work_orders MODIFY id INT(11) NOT NULL AUTO_INCREMENT");
    if ($ok) {
        echo "SUCCESS! Added AUTO_INCREMENT to work_orders.id column successfully.\n";
    } else {
        echo "FAILED! Error: " . mysqli_error($conn) . "\n";
    }

    // 3. Confirm config
    $resAfter = db_fetch_all($conn, "DESCRIBE work_orders");
    foreach ($resAfter as $col) {
        if ($col['Field'] === 'id') {
            echo "\nNew work_orders.id config:\n";
            echo "Extra: " . ($col['Extra'] ?? '(empty)') . "\n";
            break;
        }
    }
} catch (Throwable $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
}
