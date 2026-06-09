<?php
$s = json_decode(file_get_contents('c:/xampp/htdocs/clms/scratch/schema.json'), true);
foreach($s['gate_pass_requests'] as $c) {
    if($c['Null'] == 'NO' && $c['Default'] === NULL && $c['Extra'] != 'auto_increment') {
        echo $c['Field'] . " is mandatory\n";
    }
}

