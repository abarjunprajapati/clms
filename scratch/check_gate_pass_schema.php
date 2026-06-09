<?php
$json = file_get_contents('c:/xampp/htdocs/clms/scratch/schema.json');
$d = json_decode($json, true);
$tables = ['documents', 'workman_documents', 'gate_pass_requests', 'gate_passes', 'approvals'];
foreach($tables as $t) {
    echo "TABLE: $t\n";
    if (isset($d[$t])) {
        foreach($d[$t] as $col) {
            echo "  " . $col['Field'] . " - " . $col['Type'] . "\n";
        }
    } else {
        echo "  Not found\n";
    }
}

