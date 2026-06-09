<?php
$s = json_decode(file_get_contents('c:/xampp/htdocs/clms/scratch/schema.json'), true);
if (isset($s['notifications'])) {
    foreach($s['notifications'] as $col) {
        echo $col['Field'] . "\n";
    }
} else {
    echo "Table not found";
}

