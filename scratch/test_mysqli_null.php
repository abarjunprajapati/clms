<?php
include __DIR__ . '/../include/config.php';

// Prepare a test query using one of the date fields in contractors
$stmt = $conn->prepare("UPDATE contractors SET issued_date = ? WHERE id = 1");
$date = null;
$stmt->bind_param("s", $date);
if ($stmt->execute()) {
    echo "Success! NULL was bound and executed correctly.\n";
} else {
    echo "Error: " . $stmt->error . "\n";
}
