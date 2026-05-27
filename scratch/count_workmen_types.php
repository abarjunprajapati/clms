<?php
require_once __DIR__ . '/../include/config.php';
$r = $conn->query("SELECT type, COUNT(*) FROM workmen GROUP BY type");
while($row = $r->fetch_assoc()) {
    print_r($row);
}

