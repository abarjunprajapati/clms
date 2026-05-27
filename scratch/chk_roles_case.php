<?php
include 'include/config.php';
$r = $conn->query("SELECT username, role FROM users WHERE role LIKE '%welfare%'");
while($row = $r->fetch_assoc()) {
    echo "User: " . $row['username'] . " | Role: " . $row['role'] . "\n";
}

