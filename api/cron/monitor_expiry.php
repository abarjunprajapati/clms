<?php
/**
 * monitor_expiry.php
 * Daily cron to track temporary and permanent pass expiry.
 */
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/NotificationEngine.php';

$today = date('Y-m-d');
$alertDate = date('Y-m-d', strtotime('+30 days'));

// 1. Find passes expiring within 30 days
$res = clms_db_query($conn, "SELECT w.id, w.name, w.valid_to, w.contractor_id, c.name as contractor_name 
                            FROM workmen w 
                            JOIN contractors c ON w.contractor_id = c.id 
                            WHERE w.valid_to <= '$alertDate' AND w.status NOT IN ('blocked','relieved')");

while ($w = clms_db_fetch_assoc($res)) {
    $days = (strtotime($w['valid_to']) - strtotime($today)) / 86400;
    $msg = "Gate pass for worker " . $w['name'] . " expires in " . round($days) . " days (Date: " . $w['valid_to'] . "). Please apply for extension.";
    
    // Notify Contractor
    NotificationEngine::trigger($conn, $w['contractor_id'], "Pass Expiry Alert", $msg, 'warning');
}

echo "Pass expiry monitor complete.\n";
?>

