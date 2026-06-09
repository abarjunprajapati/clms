<?php
include __DIR__ . '/../include/config.php';

$sql = "CREATE TABLE IF NOT EXISTS contractor_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contractor_id INT NOT NULL,
    status VARCHAR(20) NOT NULL,
    reason TEXT,
    pdf_path VARCHAR(255),
    action_by INT,
    action_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_contractor_id (contractor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql)) {
    echo "contractor_status_history table created successfully.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}
?>
