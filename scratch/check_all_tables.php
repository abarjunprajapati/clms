<?php
$conn = mysqli_connect("localhost", "root", "", "new_clms");
if (!$conn) {
    die("Connection failed\n");
}

$tables_res = $conn->query("SHOW TABLES");
if (!$tables_res) {
    die("Failed to show tables\n");
}

echo "Scanning all tables in new_clms database for missing AUTO_INCREMENT on 'id'...\n";
$missing = [];

while ($row = $tables_res->fetch_row()) {
    $table = $row[0];
    $desc = $conn->query("DESCRIBE `$table`");
    if ($desc) {
        while ($field = $desc->fetch_assoc()) {
            if ($field['Field'] === 'id') {
                if ($field['Key'] === 'PRI' && strpos($field['Extra'], 'auto_increment') === false) {
                    $missing[] = $table;
                    echo "Table: $table - Field 'id' is PRI but lacks auto_increment!\n";
                }
            }
        }
    }
}

if (empty($missing)) {
    echo "Scan complete: No tables are missing AUTO_INCREMENT on 'id' column!\n";
} else {
    echo "Scan complete: Found " . count($missing) . " tables missing AUTO_INCREMENT.\n";
}

mysqli_close($conn);
?>
