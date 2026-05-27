<?php
require_once 'include/config.php';
function describeTable($conn, $table) {
    echo "\nTABLE: $table\n";
    $result = mysqli_query($conn, "DESCRIBE $table");
    if (!$result) {
        echo "Error: " . mysqli_error($conn) . "\n";
        return;
    }
    while($row = mysqli_fetch_assoc($result)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
}

describeTable($conn, 'applications');
describeTable($conn, 'application_workflow');
describeTable($conn, 'contractors');
describeTable($conn, 'safety_training');
describeTable($conn, 'logs');
describeTable($conn, 'remarks_history');
describeTable($conn, 'pass_limits');

