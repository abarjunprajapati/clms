<?php
require 'include/config.php';
$res = $conn->query('SELECT role_name, description FROM roles');
if (!$res) {
    echo "Error: " . $conn->error;
} else {
    while($row = $res->fetch_assoc()) {
        echo $row['role_name'] . ": " . $row['description'] . "\n";
    }
}
?>
