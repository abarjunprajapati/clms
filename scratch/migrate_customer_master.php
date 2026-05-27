<?php
require_once __DIR__ . '/../include/config.php';

$sql = "
ALTER TABLE sap_customer_master 
ADD COLUMN is_password_created TINYINT(1) DEFAULT 0,
ADD COLUMN last_login DATETIME DEFAULT NULL,
ADD COLUMN login_attempts INT DEFAULT 0,
ADD COLUMN last_otp_sent_at DATETIME DEFAULT NULL,
ADD COLUMN password_updated_at DATETIME DEFAULT NULL;
";

if (mysqli_multi_query($conn, $sql)) {
    echo "SUCCESS: sap_customer_master updated with new columns.\n";
} else {
    echo "ERROR: " . mysqli_error($conn) . "\n";
}
?>
