<?php
$json = file_get_contents('c:/xampp/htdocs/clms/scratch/schema.json');
$data = json_decode($json, true);
foreach(array_keys($data) as $table) {
    echo $table . "\n";
}

