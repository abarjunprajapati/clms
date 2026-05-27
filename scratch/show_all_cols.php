<?php
require_once __DIR__ . '/../include/config.php';
function show($t) {
    global $conn;
    echo "--- $t ---\n";
    $r = $conn->query("SHOW COLUMNS FROM $t");
    while($row = $r->fetch_assoc()) echo $row['Field'] . "\n";
}
show('workmen');
show('supervisors');
show('representatives');

