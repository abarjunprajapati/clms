<?php
require 'include/config.php';

$tables = ['contractors', 'workmen', 'gate_passes'];

foreach ($tables as $t) {
    echo "Checking table: $t\n";
    $res = $conn->query("SHOW COLUMNS FROM $t LIKE 'application_id'");
    if ($res->num_rows > 0) {
        echo " - application_id exists\n";
    } else {
        echo " - application_id missing. Adding it...\n";
        $alter = "ALTER TABLE $t ADD COLUMN application_id VARCHAR(50) NULL AFTER id";
        if ($conn->query($alter)) {
            echo "   -> Added application_id to $t successfully.\n";
        } else {
            echo "   -> Error adding application_id to $t: " . $conn->error . "\n";
        }
    }
}

