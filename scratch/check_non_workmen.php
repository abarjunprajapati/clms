<?php
require_once __DIR__ . '/../include/config.php';
$r = $conn->query("SELECT * FROM workmen WHERE type != 'workman'");
while($row = $r->fetch_assoc()) {
    print_r($row);
}

