<?php
require_once 'include/config.php';

$cols = [
    'po_number' => "VARCHAR(50)",
    'pwo_number' => "VARCHAR(50)",
    'sales_order_number' => "VARCHAR(50)"
];

foreach ($cols as $col => $type) {
    $res = $conn->query("SHOW COLUMNS FROM contractors LIKE '$col'");
    if ($res && $res->num_rows == 0) {
        $conn->query("ALTER TABLE contractors ADD COLUMN $col $type");
        echo "Added $col to contractors.\n";
    }
}
?>
