<?php
include 'include/config.php';
$r = $conn->query("SELECT workflow_status, COUNT(*) as c FROM annexure2a GROUP BY workflow_status");
if ($r) {
    while($row = $r->fetch_assoc()) {
        echo ($row['workflow_status'] ?: 'NULL') . ": " . $row['c'] . "\n";
    }
} else {
    echo "Query failed: " . $conn->error . "\n";
}
echo "--- Workmen Status ---\n";
$r = $conn->query("SELECT training_status, COUNT(*) as c FROM workmen GROUP BY training_status");
if ($r) {
    while($row = $r->fetch_assoc()) {
        echo ($row['training_status'] ?: 'NULL') . ": " . $row['c'] . "\n";
    }
}

