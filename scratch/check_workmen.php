<?php
include 'include/config.php';
echo "\n--- workmen ---\n";
$res = $conn->query("DESCRIBE workmen");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . ' (' . $row['Type'] . ")\n";
}

