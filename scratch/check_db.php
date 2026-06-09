<?php
include __DIR__ . '/../include/config.php';

echo "=== contractors table ===\n";
$res = $conn->query("DESCRIBE contractors");
while ($row = $res->fetch_assoc()) {
    echo "{$row['Field']} - {$row['Type']} - Null: {$row['Null']} - Default: {$row['Default']}\n";
}

echo "\n=== annexure2a table ===\n";
$res = $conn->query("DESCRIBE annexure2a");
while ($row = $res->fetch_assoc()) {
    echo "{$row['Field']} - {$row['Type']} - Null: {$row['Null']} - Default: {$row['Default']}\n";
}
