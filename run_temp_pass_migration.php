<?php
require_once __DIR__ . '/include/config.php';

// If DB connection fails, script will die from config.php

function addColumn($conn, $table, $column, $definition) {
    $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($check && $check->num_rows == 0) {
        if ($conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition")) {
            echo "Added $column to $table<br>";
        } else {
            echo "Error adding $column to $table: " . $conn->error . "<br>";
        }
    } else {
        echo "Column $column already exists in $table<br>";
    }
}

// Ensure gate_passes has the temporary pass columns
addColumn($conn, 'gate_passes', 'pass_type', "ENUM('permanent', 'temporary') DEFAULT 'permanent' AFTER workman_id");
addColumn($conn, 'gate_passes', 'is_temporary', "TINYINT(1) DEFAULT 0 AFTER pass_type");
addColumn($conn, 'gate_passes', 'temporary_validity_start', "DATE NULL AFTER is_temporary");
addColumn($conn, 'gate_passes', 'temporary_validity_end', "DATE NULL AFTER temporary_validity_start");
addColumn($conn, 'gate_passes', 'executing_officer_declaration_path', "VARCHAR(255) NULL AFTER temporary_validity_end");
addColumn($conn, 'gate_passes', 'gm_declaration_path', "VARCHAR(255) NULL AFTER executing_officer_declaration_path");
addColumn($conn, 'gate_passes', 'is_extended', "TINYINT(1) DEFAULT 0 AFTER gm_declaration_path");

echo "Migration completed.";
?>
