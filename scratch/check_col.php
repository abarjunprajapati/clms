<?php
require_once __DIR__ . '/../include/config.php';
header('Content-Type: text/plain; charset=utf-8');

$res = mysqli_query($conn, "SHOW COLUMNS FROM contractors LIKE 'work_awarding_department'");
if (mysqli_num_rows($res) > 0) {
    echo "work_awarding_department exists in contractors!\n";
    $row = mysqli_fetch_assoc($res);
    print_r($row);
} else {
    echo "work_awarding_department DOES NOT exist in contractors!\n";
}
