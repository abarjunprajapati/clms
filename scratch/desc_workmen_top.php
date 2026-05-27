<?php
include 'include/config.php';
$r = $conn->query("DESCRIBE workmen");
$i = 0;
while($row = $r->fetch_assoc()) {
    print_r($row);
    if (++$i > 20) break;
}

