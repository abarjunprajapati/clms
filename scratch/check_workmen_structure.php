<?php
require_once __DIR__ . '/../include/config.php';
$t = 'workmen';
echo "--- $t ---\n";
$r = $conn->query("DESCRIBE $t");
if ($r) {
    while($row = $r->fetch_assoc()) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "DESCRIBE failed for $t: " . $conn->error . "\n";
}

