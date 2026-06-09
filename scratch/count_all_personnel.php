<?php
require_once __DIR__ . '/../include/config.php';
$tables = ['workmen', 'supervisors', 'representatives'];
foreach($tables as $t) {
    $r = $conn->query("SELECT COUNT(*) as c FROM $t");
    $row = $r->fetch_assoc();
    echo "$t: " . $row['c'] . "\n";
}

