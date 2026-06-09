<?php
$s = json_decode(file_get_contents('c:/xampp/htdocs/clms/scratch/schema.json'), true);
foreach($s['training_schedule'] as $c) {
    echo $c['Field'] . "\n";
}

