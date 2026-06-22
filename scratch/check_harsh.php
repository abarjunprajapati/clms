<?php
require_once __DIR__ . '/../include/config.php';
$tables = [];
$res = $conn->query("SHOW TABLES");
while ($row = $res->fetch_array()) {
    $tables[] = $row[0];
}
echo json_encode($tables, JSON_PRETTY_PRINT);
?>
