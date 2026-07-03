<?php
require 'include/config.php';

$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

foreach ($tables as $table) {
    if (strpos($table, 'pass') !== false || strpos($table, 'workmen') !== false || strpos($table, 'welfare') !== false || strpos($table, 'contractor') !== false) {
        echo "Table: $table\n";
        $res = $conn->query("DESCRIBE `$table`");
        while ($col = $res->fetch_assoc()) {
            echo "  " . $col['Field'] . " - " . $col['Type'] . "\n";
        }
        echo "\n";
    }
}
?>
