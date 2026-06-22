<?php
require_once __DIR__ . '/../include/config.php';
header('Content-Type: text/plain; charset=utf-8');

$res = mysqli_query($conn, "SELECT id, vendor_code, work_awarding_department, status FROM contractors WHERE vendor_code = '1100921'");
while ($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
