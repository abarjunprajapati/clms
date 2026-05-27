<?php
include 'include/config.php';
$r = $conn->query("SELECT * FROM gate_pass_request_workers");
while($row = $r->fetch_assoc()) {
    print_r($row);
}

