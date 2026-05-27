<?php
require 'include/config.php';
$desc = $conn->query("DESCRIBE contractors");
while($r = $desc->fetch_assoc()) {
    echo $r['Field'] . " - " . $r['Type'] . "\n";
}

