<?php
include __DIR__ . '/../include/config.php';

$sql = "ALTER TABLE contractors 
        ADD COLUMN IF NOT EXISTS approval_reason TEXT AFTER status,
        ADD COLUMN IF NOT EXISTS approval_pdf VARCHAR(255) AFTER approval_reason,
        ADD COLUMN IF NOT EXISTS last_action_by INT AFTER approval_pdf,
        ADD COLUMN IF NOT EXISTS last_action_at TIMESTAMP NULL DEFAULT NULL AFTER last_action_by";

if ($conn->query($sql)) {
    echo "Contractors table updated successfully.\n";
} else {
    echo "Error updating table: " . $conn->error . "\n";
}
?>
