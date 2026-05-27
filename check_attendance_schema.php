<?php
require_once 'include/config.php';
$res = $conn->query("DESCRIBE attendance");
if (!$res) die("Table 'attendance' not found or error: " . $conn->error);
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>

