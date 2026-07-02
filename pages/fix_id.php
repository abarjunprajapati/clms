<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../include/config.php';

echo "<h1>Final Database Fix</h1>";

try {
    // Make 'id' column auto-increment in noc_requests
    $sql = "ALTER TABLE noc_requests MODIFY COLUMN id INT(11) NOT NULL AUTO_INCREMENT";
    
    if ($conn->query($sql)) {
        echo "<h2 style='color:green;'>SUCCESS: The 'id' column is now AUTO_INCREMENT!</h2>";
        echo "<p>You can now go back to NOC Management and submit your request. It will work perfectly!</p>";
    } else {
        echo "<h2 style='color:red;'>FAILED: " . $conn->error . "</h2>";
    }

} catch (\Throwable $e) {
    echo "<h2 style='color:red;'>Exception:</h2>";
    echo $e->getMessage();
}
