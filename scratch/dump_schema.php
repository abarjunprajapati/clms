<?php
require_once __DIR__ . '/../include/config.php';

try {
    $tables = [];
    $result = mysqli_query($conn, "SHOW TABLES");
    while ($row = mysqli_fetch_array($result)) {
        $tables[] = $row[0];
    }
    
    $schema = [];
    foreach ($tables as $table) {
        $schema[$table] = [];
        $res = mysqli_query($conn, "DESCRIBE $table");
        while ($row = mysqli_fetch_assoc($res)) {
            $schema[$table][] = $row;
        }
    }
    file_put_contents(__DIR__ . '/schema.json', json_encode($schema, JSON_PRETTY_PRINT));
    echo "Schema dumped successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

