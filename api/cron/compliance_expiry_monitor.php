<?php
/**
 * compliance_expiry_monitor.php
 * Tracks Labour License, Insurance, ESI, and EPF expiries.
 */
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/NotificationEngine.php';

$alertDate = date('Y-m-d', strtotime('+30 days'));

// 1. Monitor Labour Licenses (Contractor Registration)
$res = clms_db_query($conn, "SELECT id, contractor_name, labour_validity, contractor_id FROM annexure2a WHERE labour_validity <= '$alertDate'");
while ($c = clms_db_fetch_assoc($res)) {
    $msg = "Labour License for " . $c['contractor_name'] . " expires on " . $c['labour_validity'] . ". Renew immediately.";
    NotificationEngine::trigger($conn, $c['contractor_id'], "Compliance Alert", $msg, 'danger');
}

// 2. Monitor Insurance (Annexure 3A)
$res = clms_db_query($conn, "SELECT id, insurance_validity_to, contractor_id FROM annexure3a WHERE insurance_validity_to <= '$alertDate'");
while ($c = clms_db_fetch_assoc($res)) {
    $msg = "Worker Insurance Policy expires on " . $c['insurance_validity_to'] . ". Update compliance documents.";
    NotificationEngine::trigger($conn, $c['contractor_id'], "Insurance Alert", $msg, 'danger');
}

echo "Compliance expiry monitor complete.\n";
?>

