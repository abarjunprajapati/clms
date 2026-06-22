<?php
require_once __DIR__ . '/../include/config.php';
header('Content-Type: text/plain; charset=utf-8');

$res = mysqli_query($conn, "SELECT id, vendor_code, contractor_name, work_awarding_department, status FROM contractors WHERE vendor_code = '1100921'");
if (mysqli_num_rows($res) == 0) {
    echo "No contractor found with code 1100921.\n";
} else {
    $row = mysqli_fetch_assoc($res);
    print_r($row);
}
