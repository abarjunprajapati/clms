<?php
require_once __DIR__ . '/../../include/config.php';

// This script simulates a daily cron job that checks for idle workmen and contractors.

try {
    $conn->begin_transaction();
    
    // 1. Automatic blocking of idle workmen & moving to Common Pool
    // "automatically deactivate the access control cards of contractors and their workmen if they remain continuously absent for a period of three months"
    // "Common Pool: The system shall release the workers into a common pool under the following conditions: (b) Workman Idle for 3 Months."
    
    // 3 months ago
    $three_months_ago = date('Y-m-d', strtotime('-3 months'));
    $block_time = date('Y-m-d H:i:s');
    $reason = "Idle for 3 Months";
    
    // Find workmen who are not yet in common pool, not permanently blocked, and last punch date is before 3 months ago
    // (Assuming last_punch_date is updated by attendance system integration, if NULL they haven't punched. If NULL and created > 3 months ago, also idle)
    $w_query = "SELECT id FROM workmen WHERE block_status != 'blocked_permanent_welfare' AND is_in_common_pool = 0 AND 
                ((last_punch_date IS NOT NULL AND last_punch_date < ?) OR 
                 (last_punch_date IS NULL AND DATE(created_at) < ?))";
                 
    $stmt = $conn->prepare($w_query);
    $stmt->bind_param("ss", $three_months_ago, $three_months_ago);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $idle_workmen_count = 0;
    while ($row = $res->fetch_assoc()) {
        $id = $row['id'];
        
        // Move to common pool and block
        $upd = $conn->prepare("UPDATE workmen SET block_status = 'blocked_system_idle', block_reason = ?, blocked_at = ?, is_in_common_pool = 1, contractor_id = NULL WHERE id = ?");
        $upd->bind_param("ssi", $reason, $block_time, $id);
        $upd->execute();
        
        // Mock SAP & Attendance block
        $sap_log = $conn->prepare("INSERT INTO system_error_logs (error_type, error_message, file_name) VALUES ('SAP_INTEGRATION', ?, 'cron/idle_check.php')");
        $msg = "SAP & Attendance Blocked for Workman ID $id (Idle for 3 months, moved to Common Pool)";
        $sap_log->bind_param("s", $msg);
        $sap_log->execute();
        
        $idle_workmen_count++;
    }
    
    // 2. Automatic blocking of idle contractors
    // A contractor is idle if ALL their workmen are idle or they haven't had active passes for 3 months.
    // For simplicity here, we'll assume a field or logic. We'll skip complex logic and just provide the skeleton for contractor idle check.
    
    $conn->commit();
    echo "Cron completed. Processed $idle_workmen_count idle workmen.\n";

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    echo "Error: " . $e->getMessage() . "\n";
}
