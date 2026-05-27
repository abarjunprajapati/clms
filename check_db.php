<?php
require_once 'include/config.php';
$tables = ['annexure2a', 'contractors', 'projects'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    echo "$table: " . ($result->num_rows > 0 ? "EXISTS" : "MISSING") . "\n";
    if ($result->num_rows > 0) {
        $columns = $conn->query("DESCRIBE $table");
        while ($row = $columns->fetch_assoc()) {
            echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    }
}

