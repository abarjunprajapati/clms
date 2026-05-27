<?php
include 'include/config.php';
$r = $conn->query('DESCRIBE safety_training');
while($row = $r->fetch_assoc()) echo $row['Field'] . ' (' . $row['Type'] . ")\n";
echo "--- DATA ---\n";
$r = $conn->query('SELECT COUNT(*) as count FROM safety_training');
$row = $r->fetch_assoc();
echo "Count: " . $row['count'] . "\n";

