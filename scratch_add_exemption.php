<?php
include 'include/config.php';
$cols = $conn->query("SHOW COLUMNS FROM contractors LIKE 'epf_esi_exemption_reason'");
if($cols->num_rows == 0) {
    $conn->query("ALTER TABLE contractors ADD COLUMN epf_esi_exemption_reason TEXT DEFAULT NULL AFTER esi_code");
    echo "Added to contractors.\n";
} else { echo "Already in contractors.\n"; }

$cols = $conn->query("SHOW COLUMNS FROM annexure2a LIKE 'epf_esi_exemption_reason'");
if($cols->num_rows == 0) {
    $conn->query("ALTER TABLE annexure2a ADD COLUMN epf_esi_exemption_reason TEXT DEFAULT NULL AFTER esic_code");
    echo "Added to annexure2a.\n";
} else { echo "Already in annexure2a.\n"; }
