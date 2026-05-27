<?php
include 'd:/Xampp/htdocs/clms/include/config.php';

echo "--- Users Table (Contractors with status active) ---\n";
$res1 = mysqli_query($conn, "SELECT id, contractor_id, name, status FROM users WHERE role = 'contractor' AND status = 'active'");
while($row = mysqli_fetch_assoc($res1)) {
    print_r($row);
}

echo "\n--- Contractors Table (Status approved or activated) ---\n";
$res2 = mysqli_query($conn, "SELECT id, user_id, vendor_code, contractor_name, status FROM contractors WHERE status = 'approved' OR activated_at IS NOT NULL");
while($row = mysqli_fetch_assoc($res2)) {
    print_r($row);
}
