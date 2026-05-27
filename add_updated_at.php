<?php
require_once 'include/config.php';

$sql = "ALTER TABLE workmen ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

if (mysqli_query($conn, $sql)) {
    echo "Column updated_at added to workmen table successfully.\n";
} else {
    echo "Error adding column: " . mysqli_error($conn) . "\n";
}
?>

