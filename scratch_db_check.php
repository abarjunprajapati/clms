<?php
require_once __DIR__ . '/include/config.php';

$tables = [
    'sap_vendor_master',
    'sap_customer_master',
    'contractor_annexure2a',
    'contractor_annexure3a',
    'contractor_vendor_customer_map',
    'contractors'
];

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
