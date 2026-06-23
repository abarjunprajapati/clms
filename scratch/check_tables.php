<?php
require_once __DIR__ . '/../include/config.php';

function describeTable($conn, $table) {
    echo "=== DESCRIBE $table ===\n";
    $res = $conn->query("DESCRIBE $table");
    if ($res) {
        while($row = $res->fetch_assoc()) {
            echo "  " . $row['Field'] . " | " . $row['Type'] . "\n";
        }
    } else {
        echo "Error: " . $conn->error . "\n";
    }
    echo "\n";
}

describeTable($conn, 'contractor_status_history');
describeTable($conn, 'contractor_annexure2a_history');

// Print some rows from contractor_status_history if any
$res = $conn->query("SELECT * FROM contractor_status_history LIMIT 10");
if ($res && $res->num_rows > 0) {
    echo "=== Rows in contractor_status_history ===\n";
    while($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "No rows in contractor_status_history\n";
}
?>
