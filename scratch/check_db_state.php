<?php
require_once __DIR__ . '/../include/config.php';

$tables = ['users', 'contractors', 'pass_limits', 'safety_batches'];
foreach ($tables as $t) {
    echo "\nStructure of $t:\n";
    $res = $conn->query("DESCRIBE $t");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            echo "  " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    }
}

// Print some data from contractors
$res = $conn->query("SELECT * FROM contractors LIMIT 5");
if ($res) {
    echo "\nContractors:\n";
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
}

$res = $conn->query("SELECT * FROM users LIMIT 5");
if ($res) {
    echo "\nUsers:\n";
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
}
?>
