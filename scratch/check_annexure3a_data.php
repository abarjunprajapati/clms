<?php
require_once __DIR__ . '/../include/config.php';
$r = $conn->query("SELECT application_id, representative_name, supervisor_name FROM annexure3a");
while($row = $r->fetch_assoc()) {
    print_r($row);
}

