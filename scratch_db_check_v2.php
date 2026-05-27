<?php
require_once __DIR__ . '/include/config.php';

$tables = ['contractors', 'annexure2a', 'annexure3a', 'contractor_annexure3a'];

foreach ($tables as $table) {
    echo "\nTable: $table\n";
    $res = mysqli_query($conn, "DESCRIBE $table");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "  [ERROR] Table not found.\n";
    }
}
