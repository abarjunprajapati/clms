<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'CLMS1');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$tables = ['gate_passes', 'workmen', 'training_requests', 'users'];
$out = "";
foreach ($tables as $table) {
    $out .= "Table: $table\n";
    $res = $conn->query("DESCRIBE `$table`");
    if ($res) {
        while ($col = $res->fetch_assoc()) {
            $out .= "  " . $col['Field'] . " - " . $col['Type'] . "\n";
        }
    } else {
        $out .= "  Not found.\n";
    }
    $out .= "\n";
}
file_put_contents('schema_out.txt', $out);
echo "Done";
?>
