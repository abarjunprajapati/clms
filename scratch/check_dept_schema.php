<?php
require_once __DIR__ . '/../include/config.php';
header('Content-Type: text/plain; charset=utf-8');

$res = mysqli_query($conn, "DESCRIBE master_departments");
while ($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}

$res = mysqli_query($conn, "SELECT * FROM master_departments LIMIT 10");
while ($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
