<?php
include 'include/config.php';
$tables = ['workmen', 'training_sessions', 'gate_pass_requests', 'pass_limits', 'compliance'];
foreach ($tables as $t) {
    echo "\n--- $t ---\n";
    $res = $conn->query("DESCRIBE $t");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            echo $row['Field'] . ' (' . $row['Type'] . ")\n";
        }
    } else {
        echo "Table $t not found.\n";
    }
}

