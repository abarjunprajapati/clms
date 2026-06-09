<?php
include 'include/config.php';
$r = $conn->query("SELECT id, name, role FROM users WHERE role = '' OR role IS NULL");
while($row = $r->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Name: " . $row['name'] . " | Role: [" . $row['role'] . "]\n";
}

