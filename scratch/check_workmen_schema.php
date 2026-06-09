<?php
require 'include/config.php';
$desc = $conn->query("DESCRIBE workmen");
while($r = $desc->fetch_assoc()) {
    echo $r['Field'] . " - " . $r['Type'] . "\n";
}

