<?php
require 'c:/xampp/htdocs/CLMS1/include/config.php';
$conn->query("CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT
)");
echo "Table created.\n";
$stmt = $conn->prepare("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES ('global_popup_message', '')");
$stmt->execute();
echo "Inserted row.";
?>
