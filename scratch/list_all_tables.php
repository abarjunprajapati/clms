<?php
$conn = mysqli_connect("localhost", "root", "", "new_clms");
if (!$conn) die("Connection failed: " . mysqli_connect_error());

$result = mysqli_query($conn, "SHOW TABLES");
$tables = [];
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
}
echo json_encode($tables, JSON_PRETTY_PRINT);
?>
