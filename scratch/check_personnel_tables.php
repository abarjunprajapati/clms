<?php
include 'include/config.php';
$tables = ['representatives', 'supervisors', 'annexure3a', 'workmen'];
foreach($tables as $t) {
    $r = $conn->query("SELECT COUNT(*) as c FROM $t");
    echo "$t: " . ($r ? $r->fetch_assoc()['c'] : 'ERROR: ' . $conn->error) . "\n";
}
?>

