<?php
$json = file_get_contents('c:/xampp/htdocs/clms/scratch/schema.json');
$data = json_decode($json, true);
echo "WORKERS TABLE:\n";
print_r($data['workers'] ?? 'Not found');
echo "\nWORKMEN TABLE:\n";
print_r($data['workmen'] ?? 'Not found');

