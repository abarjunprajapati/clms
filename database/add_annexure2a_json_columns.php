<?php
require_once __DIR__ . '/../include/config.php';

function addColumnIfNotExists($conn, $table, $column, $definition) {
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($res && $res->num_rows == 0) {
        $q = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        if ($conn->query($q)) {
            echo "Added column '$column' to table '$table'.\n";
        } else {
            echo "Error adding '$column' to '$table': " . $conn->error . "\n";
        }
    } else {
        echo "Column '$column' already exists in table '$table'.\n";
    }
}

echo "Starting Annexure 2A Database Migration...\n";

// Add to contractors
addColumnIfNotExists($conn, 'contractors', 'ecp_covered', "VARCHAR(10) DEFAULT NULL");
addColumnIfNotExists($conn, 'contractors', 'ecp_exemption_reason', "TEXT DEFAULT NULL");
addColumnIfNotExists($conn, 'contractors', 'ecp_details_json', "TEXT DEFAULT NULL");
addColumnIfNotExists($conn, 'contractors', 'license_details_json', "TEXT DEFAULT NULL");

// Add to annexure2a
addColumnIfNotExists($conn, 'annexure2a', 'ecp_covered', "VARCHAR(10) DEFAULT NULL");
addColumnIfNotExists($conn, 'annexure2a', 'ecp_exemption_reason', "TEXT DEFAULT NULL");
addColumnIfNotExists($conn, 'annexure2a', 'ecp_details_json', "TEXT DEFAULT NULL");
addColumnIfNotExists($conn, 'annexure2a', 'license_details_json', "TEXT DEFAULT NULL");

echo "Migration finished.\n";
?>
