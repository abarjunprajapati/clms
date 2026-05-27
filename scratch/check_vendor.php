<?php
$conn = mysqli_connect('localhost', 'root', '', 'new_clms');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
$res = mysqli_query($conn, "SELECT id, vendor_code, vendor_name, status, user_id FROM contractors WHERE vendor_code = '1100908'");
$row = mysqli_fetch_assoc($res);
if ($row) {
    echo "Found Contractor: ID: {$row['id']} | Code: {$row['vendor_code']} | Name: {$row['vendor_name']} | Status: {$row['status']} | User ID: {$row['user_id']}\n";
} else {
    echo "No contractor profile found for 1100908\n";
}
?>
