<?php
$conn = mysqli_connect('localhost', 'root', '', 'new_clms');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

function desc($conn, $table) {
    echo "--- Table: $table ---\n";
    $res = mysqli_query($conn, "DESCRIBE $table");
    while($row = mysqli_fetch_assoc($res)) {
        echo "{$row['Field']} - {$row['Type']} - Null: {$row['Null']} - Key: {$row['Key']}\n";
    }
}

desc($conn, 'work_orders');
desc($conn, 'sap_customer_master');
desc($conn, 'sap_vendor_master');
?>
