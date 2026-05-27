<?php
require_once 'include/config.php';

echo "<h2>Fixing Missing 'contractor_documents' Table</h2>";

$sql = "CREATE TABLE IF NOT EXISTS contractor_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contractor_id INT NOT NULL,
    doc_type VARCHAR(100) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    remarks TEXT DEFAULT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (contractor_id) REFERENCES contractors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql)) {
    echo "<p style='color:green'>✅ Table 'contractor_documents' created successfully.</p>";
} else {
    echo "<p style='color:red'>❌ Error creating table: " . $conn->error . "</p>";
}

// Create upload directory
$upload_dir = 'uploads/contractor_docs/';
if (!is_dir($upload_dir)) {
    if (mkdir($upload_dir, 0777, true)) {
        echo "<p style='color:green'>✅ Upload directory '$upload_dir' created.</p>";
    } else {
        echo "<p style='color:red'>❌ Failed to create upload directory.</p>";
    }
} else {
    echo "<p style='color:blue'>ℹ️ Upload directory already exists.</p>";
}

echo "<p><a href='pages/contractor/documents.php'>Back to Documents Page</a></p>";
?>

