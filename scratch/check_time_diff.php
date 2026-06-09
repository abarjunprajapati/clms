<?php
$d = "2026-05-02 22:02:13";
echo "String: $d\n";
echo "strtotime: " . strtotime($d) . "\n";
echo "time(): " . time() . "\n";
echo "Diff: " . (strtotime($d) - time()) . "\n";
?>

