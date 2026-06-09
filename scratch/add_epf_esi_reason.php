<?php
include __DIR__ . '/../include/config.php';

echo "Checking epf_esi_exemption_reason in contractors table...\n";
$check = $conn->query("SHOW COLUMNS FROM contractors LIKE 'epf_esi_exemption_reason'");
if ($check && $check->num_rows > 0) {
    echo "Column 'epf_esi_exemption_reason' already exists.\n";
} else {
    $sql = "ALTER TABLE contractors ADD COLUMN `epf_esi_exemption_reason` TEXT DEFAULT NULL AFTER `esi_code`";
    if ($conn->query($sql)) {
        echo "Successfully added column 'epf_esi_exemption_reason' to contractors table.\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }
}
echo "Done.\n";
