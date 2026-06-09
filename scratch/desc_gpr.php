<?php
include 'include/config.php';
$r = $conn->query("DESCRIBE gate_pass_requests");
while($row = $r->fetch_assoc()) {
    print_r($row);
}

