<?php
include 'include/config.php';
$r = $conn->query("DESCRIBE users");
while($row = $r->fetch_assoc()) {
    echo $row['Field'] . " | " . $row['Type'] . "\n";
}
echo "\n--- ROLES ---\n";
$r = $conn->query("SELECT DISTINCT role FROM users");
if($r) {
    while($row = $r->fetch_assoc()) {
        echo "[" . $row['role'] . "]\n";
    }
} else {
    echo "Query failed: " . $conn->error . "\n";
}

