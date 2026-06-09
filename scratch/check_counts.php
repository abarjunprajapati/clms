<?php
include 'include/config.php';
$tables = ['applications', 'annexure2a', 'annexure3a', 'workmen', 'permanent_gate_passes', 'permanent_passes'];
foreach($tables as $t) {
    $r = $conn->query("SELECT COUNT(*) as c FROM $t");
    echo "$t: " . ($r ? $r->fetch_assoc()['c'] : 'ERROR') . "\n";
}

