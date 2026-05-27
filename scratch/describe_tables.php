<?php
include 'include/config.php';
$tables = ['permanent_gate_passes', 'permanent_passes'];
foreach($tables as $t) {
    echo "--- $t ---\n";
    $r = $conn->query("DESCRIBE $t");
    if ($r) {
        while($row = $r->fetch_assoc()) {
            echo $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "DESCRIBE failed for $t: " . $conn->error . "\n";
    }
}

