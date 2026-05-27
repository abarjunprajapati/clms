<?php
require_once 'include/config.php';

$sql = [
    "ALTER TABLE workmen ADD COLUMN IF NOT EXISTS valid_from DATE",
    "ALTER TABLE workmen ADD COLUMN IF NOT EXISTS valid_to DATE",
    "ALTER TABLE workmen ADD COLUMN IF NOT EXISTS safety_training_status TINYINT DEFAULT 0",
    "ALTER TABLE workmen ADD COLUMN IF NOT EXISTS acc_card_number VARCHAR(100)"
];

foreach ($sql as $query) {
    if (mysqli_query($conn, $query)) {
        echo "Success: $query\n";
    } else {
        echo "Error: " . mysqli_error($conn) . " | Query: $query\n";
    }
}
?>

