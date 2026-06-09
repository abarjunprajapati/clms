<?php
$json = file_get_contents('c:/xampp/htdocs/clms/scratch/schema.json');
$d = json_decode($json, true);
$t = 'gate_pass_request_workers';
echo "TABLE: $t\n";
if (isset($d[$t])) {
    foreach($d[$t] as $col) {
        echo "  " . $col['Field'] . " - " . $col['Type'] . "\n";
    }
} else {
    echo "  Not found\n";
}

