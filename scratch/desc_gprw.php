<?php
include 'include/config.php';
$r = $conn->query("DESCRIBE gate_pass_request_workers");
echo "Field | Type | Null | Key | Default\n";
echo "-----------------------------------\n";
while($row = $r->fetch_assoc()) {
    echo "{$row['Field']} | {$row['Type']} | {$row['Null']} | {$row['Key']} | {$row['Default']}\n";
}

