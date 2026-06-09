<?php
/**
 * document_expiry_monitor.php
 * Cron job to handle worker document expiry logic.
 * Enforces warnings at 30 days, marks expired documents, and suspends passes for safety-critical document expiries.
 */

require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/NotificationEngine.php';

$today = date('Y-m-d');
$warningDate = date('Y-m-d', strtotime('+30 days'));

echo "Starting document expiry monitor...\n";

// 1. Send warning for documents expiring within 30 days
$warningQuery = "
    SELECT wd.*, wm.aadhaar_no, wm.contractor_id, wm.mobile_no, c.contractor_name as contractor_name, c.user_id as contractor_user_id,
           (SELECT name FROM workmen WHERE id = wd.worker_id) as worker_name
    FROM worker_documents wd
    JOIN worker_master wm ON wd.worker_id = wm.worker_id
    LEFT JOIN contractors c ON wm.contractor_id = c.id
    WHERE wd.expiry_date = '$warningDate' 
      AND wd.verification_status = 'Verified'
      AND wm.worker_status != 'Deleted'
";

$res = clms_db_query($conn, $warningQuery);
$warningCount = 0;
if ($res) {
    while ($doc = clms_db_fetch_assoc($res)) {
        $workerName = $doc['worker_name'] ?: "Aadhaar: " . $doc['aadhaar_no'];
        $msg = "Document '{$doc['document_type']}' for worker '{$workerName}' will expire on {$doc['expiry_date']} (in 30 days). Please upload a renewed document.";
        
        if ($doc['contractor_user_id']) {
            NotificationEngine::trigger($conn, (int)$doc['contractor_user_id'], "Document Expiry Warning", $msg, 'warning');
            $warningCount++;
        }
    }
}
echo "Sent $warningCount document expiry warnings.\n";

// 2. Mark documents as 'Expired' if expiry date has passed and they were verified
$expiryQuery = "
    UPDATE worker_documents 
    SET verification_status = 'Expired' 
    WHERE expiry_date <= '$today' 
      AND verification_status = 'Verified'
";
clms_db_query($conn, $expiryQuery);
$expiredDocsCount = clms_db_affected_rows($conn);
echo "Marked $expiredDocsCount documents as Expired.\n";

// 3. Suspend passes/workers if safety-critical documents have expired
// Safety critical documents: Medical Fitness Certificate, Police Clearance Certificate, Insurance (ESI/WC)
$criticalExpiredQuery = "
    SELECT DISTINCT wd.worker_id, wm.contractor_id, c.user_id as contractor_user_id,
                    (SELECT name FROM workmen WHERE id = wd.worker_id) as worker_name
    FROM worker_documents wd
    JOIN worker_master wm ON wd.worker_id = wm.worker_id
    LEFT JOIN contractors c ON wm.contractor_id = c.id
    WHERE wd.verification_status = 'Expired'
      AND wd.document_type IN ('Medical Fitness Certificate', 'Police Clearance Certificate', 'Insurance (ESI/WC)')
      AND wm.worker_status IN ('Active', 'Pass Pending')
";

$resCritical = clms_db_query($conn, $criticalExpiredQuery);
$suspendCount = 0;
if ($resCritical) {
    while ($row = clms_db_fetch_assoc($resCritical)) {
        $worker_id = (int)$row['worker_id'];
        
        // Begin Transaction for each suspension to be safe
        clms_db_begin_transaction($conn);
        try {
            // Update worker master status to Expired
            $updateMaster = "
                UPDATE worker_master 
                SET worker_status = 'Expired',
                    updated_at = NOW()
                WHERE worker_id = $worker_id
            ";
            clms_db_query($conn, $updateMaster);

            // Suspend worker passes
            $updatePasses = "
                UPDATE worker_passes 
                SET pass_status = 'Expired' 
                WHERE worker_id = $worker_id AND pass_status IN ('Approved', 'Issued')
            ";
            clms_db_query($conn, $updatePasses);

            // Update mirrored workmen status to expired
            $updateWorkmen = "
                UPDATE workmen 
                SET status = 'expired' 
                WHERE id = $worker_id
            ";
            clms_db_query($conn, $updateWorkmen);

            // Log Audit action
            $ip = '127.0.0.1';
            $browser = 'System Cron';
            $logQuery = "
                INSERT INTO worker_audit_logs (worker_id, module_name, action_type, old_values, new_values, ip_address, browser_info, remarks, created_by) 
                VALUES ($worker_id, 'Document Expiry Cron', 'Suspend Worker', '{\"status\":\"Active\"}', '{\"status\":\"Expired\"}', '$ip', '$browser', 'Suspended due to critical document expiry', 0)
            ";
            clms_db_query($conn, $logQuery);

            clms_db_commit($conn);

            // Notify contractor
            if ($row['contractor_user_id']) {
                $workerName = $row['worker_name'] ?: "ID: " . $worker_id;
                $msg = "CRITICAL: Worker '{$workerName}' has been suspended (status set to Expired) because a critical safety document (Medical/Police/Insurance) has expired. Their gate pass has been deactivated.";
                NotificationEngine::trigger($conn, (int)$row['contractor_user_id'], "Critical Suspension Alert", $msg, 'danger');
                $suspendCount++;
            }
        } catch (Exception $ex) {
            clms_db_rollback($conn);
            echo "Error suspending worker ID $worker_id: " . $ex->getMessage() . "\n";
        }
    }
}
echo "Suspended $suspendCount workers due to critical document expiry.\n";
echo "Document expiry monitor complete.\n";
?>
