<?php
include 'include/config.php';
$tables = ['contractors', 'workmen', 'contractor_block_history', 'supervisors'];
foreach ($tables as $table) {
    echo "--- $table ---\n";
    $res = mysqli_query($conn, "DESCRIBE $table");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']} - {$row['Default']}\n";
        }
    } else {
        echo "Table $table not found\n";
    }
    echo "\n";
}
?>
