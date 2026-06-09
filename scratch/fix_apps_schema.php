<?php
require_once 'include/config.php';

echo "Updating applications table schema...\n";

// Check if columns already exist to avoid errors
$res = mysqli_query($conn, "SHOW COLUMNS FROM applications");
$columns = [];
while ($row = mysqli_fetch_assoc($res)) {
    $columns[] = $row['Field'];
}

$queries = [];

if (in_array('application_id', $columns) && !in_array('application_no', $columns)) {
    $queries[] = "ALTER TABLE applications CHANGE COLUMN application_id application_no VARCHAR(50)";
}

if (in_array('status', $columns) && !in_array('current_status', $columns)) {
    $queries[] = "ALTER TABLE applications CHANGE COLUMN status current_status VARCHAR(50)";
}

if (!in_array('type', $columns)) {
    $queries[] = "ALTER TABLE applications ADD COLUMN type VARCHAR(50) AFTER application_no";
}

if (!in_array('rejection_reason', $columns)) {
    $queries[] = "ALTER TABLE applications ADD COLUMN rejection_reason TEXT AFTER current_status";
}

foreach ($queries as $sql) {
    echo "Executing: $sql\n";
    if (!mysqli_query($conn, $sql)) {
        echo "Error: " . mysqli_error($conn) . "\n";
    }
}

echo "Schema update complete.\n";
?>

