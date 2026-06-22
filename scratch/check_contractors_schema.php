<?php
require_once __DIR__ . '/../include/config.php';
header('Content-Type: text/plain; charset=utf-8');
$res = mysqli_query($conn, "DESCRIBE contractors");
while ($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
