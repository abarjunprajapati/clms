<?php
require_once __DIR__ . '/../include/config.php';
$r = $conn->query('DESCRIBE pass_limits');
if (!$r) { echo "TABLE NOT EXISTS: " . $conn->error; exit; }
while ($row = $r->fetch_assoc()) {
    echo $row['Field'] . ' | ' . $row['Type'] . ' | ' . ($row['Default'] ?? 'NULL') . "\n";
}
echo "\n--- DATA ---\n";
$d = $conn->query('SELECT * FROM pass_limits');
if ($d) {
    while ($row = $d->fetch_assoc()) print_r($row);
} else {
    echo "No data or error\n";
}

