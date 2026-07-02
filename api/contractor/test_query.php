<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../../include/config.php';

echo "<h2>noc_requests Table Schema:</h2>";
try {
    $res = $conn->query("DESCRIBE noc_requests");
    if ($res) {
        echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th></tr>";
        while ($row = $res->fetch_assoc()) {
            echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "Failed to describe table: " . $conn->error;
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage();
}
