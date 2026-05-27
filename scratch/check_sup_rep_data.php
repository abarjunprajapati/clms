<?php
require_once __DIR__ . '/../include/config.php';
echo "--- supervisors ---\n";
$r = $conn->query("SELECT * FROM supervisors");
while($row = $r->fetch_assoc()) { print_r($row); }
echo "--- representatives ---\n";
$r = $conn->query("SELECT * FROM representatives");
while($row = $r->fetch_assoc()) { print_r($row); }

