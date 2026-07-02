<?php
require 'include/config.php';
$res = $conn->query("SHOW COLUMNS FROM workmen");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
