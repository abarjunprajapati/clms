<?php
require 'include/config.php';

// Check applications table
$result = $conn->query("SELECT * FROM applications ORDER BY id DESC LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    echo "Applications: " . json_encode($row) . "\n";
}

// Check annexure2a table
$result = $conn->query("SELECT * FROM annexure2a ORDER BY id DESC LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    echo "Annexure2A: " . json_encode($row) . "\n";
} else {
    echo "No data in annexure2a\n";
}
?>
