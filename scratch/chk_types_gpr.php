<?php
$s = json_decode(file_get_contents('c:/xampp/htdocs/clms/scratch/schema.json'), true);
foreach($s['gate_pass_requests'] as $c) {
    echo $c['Field'] . " " . $c['Type'] . "\n";
}

