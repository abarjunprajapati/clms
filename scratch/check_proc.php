<?php
require_once 'include/config.php';
$result = $conn->query("SHOW PROCEDURE STATUS WHERE Name = 'sp_authenticate_user'");
if ($result && $result->num_rows > 0) {
    echo "Procedure exists\n";
} else {
    echo "Procedure NOT found\n";
    echo "Error: " . $conn->error . "\n";
}
