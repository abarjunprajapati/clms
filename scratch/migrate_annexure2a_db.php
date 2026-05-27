<?php
include __DIR__ . '/../include/config.php';

echo "Starting migration...\n";

// Helper to check if column exists
function columnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

// Helper to add column if not exists
function addColumn($conn, $table, $column, $definition) {
    if (!columnExists($conn, $table, $column)) {
        $sql = "ALTER TABLE `$table` ADD `$column` $definition";
        if ($conn->query($sql)) {
            echo "Added column '$column' to table '$table'.\n";
        } else {
            echo "Error adding column '$column' to table '$table': " . $conn->error . "\n";
        }
    } else {
        echo "Column '$column' already exists in table '$table'.\n";
    }
}

// Helper to modify column
function modifyColumn($conn, $table, $column, $definition) {
    if (columnExists($conn, $table, $column)) {
        $sql = "ALTER TABLE `$table` MODIFY `$column` $definition";
        if ($conn->query($sql)) {
            echo "Modified column '$column' in table '$table'.\n";
        } else {
            echo "Error modifying column '$column' in table '$table': " . $conn->error . "\n";
        }
    }
}

// 1. Table: contractors
modifyColumn($conn, 'contractors', 'wage_declaration', 'TEXT NULL');
addColumn($conn, 'contractors', 'ecp_covered', "VARCHAR(10) DEFAULT 'NO'");
addColumn($conn, 'contractors', 'ecp_details_json', 'TEXT NULL');
addColumn($conn, 'contractors', 'license_details_json', 'TEXT NULL');
addColumn($conn, 'contractors', 'labour_license_appl_no', 'VARCHAR(100) NULL');

// 2. Table: annexure2a
addColumn($conn, 'annexure2a', 'wage_declaration', 'TEXT NULL');
addColumn($conn, 'annexure2a', 'ecp_covered', "VARCHAR(10) DEFAULT 'NO'");
addColumn($conn, 'annexure2a', 'ecp_details_json', 'TEXT NULL');
addColumn($conn, 'annexure2a', 'license_details_json', 'TEXT NULL');
addColumn($conn, 'annexure2a', 'labour_license_appl_no', 'VARCHAR(100) NULL');
addColumn($conn, 'annexure2a', 'vendor_mob2', 'VARCHAR(20) NULL');

echo "Migration finished.\n";
?>
