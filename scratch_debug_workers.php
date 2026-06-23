<?php
include 'include/config.php';
$tables = ['sap_worker_master', 'sap_workers'];
foreach ($tables as $t) {
    echo "=== Columns of $t ===\n";
    $res = $conn->query("DESCRIBE `$t`");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            if (stripos($row['Field'], 'relig') !== false) {
                echo "  Found: " . $row['Field'] . " (" . $row['Type'] . ")\n";
            }
        }
    }
}
?>
