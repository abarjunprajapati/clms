<?php
include __DIR__ . '/../include/config.php';

function columnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

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

addColumn($conn, 'contractors', 'epf_account_no', 'VARCHAR(100) NULL');
addColumn($conn, 'annexure2a', 'epf_account_no', 'VARCHAR(100) NULL');
?>
