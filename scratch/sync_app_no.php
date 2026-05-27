<?php
require_once 'include/config.php';

$tables_to_fix = [
    'contractors',
    'workmen',
    'gate_passes',
    'workflow_status',
    'documents',
    'safety_training',
    'training_results'
];

foreach ($tables_to_fix as $table) {
    echo "Checking table: $table\n";
    $res = mysqli_query($conn, "SHOW COLUMNS FROM $table");
    if (!$res) continue;
    
    $columns = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $columns[] = $row['Field'];
    }
    
    if (in_array('application_id', $columns) && !in_array('application_no', $columns)) {
        $sql = "ALTER TABLE $table CHANGE COLUMN application_id application_no VARCHAR(50)";
        echo "Executing: $sql\n";
        if (!mysqli_query($conn, $sql)) {
            echo "Error: " . mysqli_error($conn) . "\n";
        }
    }
}

echo "All tables synchronized to application_no.\n";
?>

