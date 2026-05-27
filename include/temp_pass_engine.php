<?php
/**
 * Temporary Workforce Pass Engine
 * Handles auto-expiry, gate restrictions, and status sync.
 */
include_once __DIR__ . '/config.php';

function check_pass_expiry($conn) {
    $now = date('Y-m-d');
    
    // 1. Find passes that have passed valid_to date and are still 'approved' or 'pending'
    $sql = "SELECT id, workman_name FROM temporary_passes 
            WHERE valid_to < ? AND status IN ('approved', 'pending')";
    
    $expired = db_fetch_all($conn, $sql, 's', [$now]);
    
    foreach ($expired as $pass) {
        // 2. Mark as expired
        db_execute($conn, "UPDATE temporary_passes SET status = 'expired', is_active = 0 WHERE id = ?", 'i', [$pass['id']]);
        
        // 3. Log event
        db_execute($conn, "INSERT INTO audit_logs (event_type, description) VALUES ('PASS_EXPIRED', ?)", ["Pass for {$pass['workman_name']} (ID: {$pass['id']}) has expired and been deactivated."]);
        
        // 4. Trigger Business Rule: Pass Expired -> Attendance Block
        // This will be handled by the Policy Engine in Phase 7
    }
    
    return count($expired);
}

function validate_gate_entry($conn, $pass_id) {
    $pass = db_single($conn, "SELECT * FROM temporary_passes WHERE id = ?", 'i', [$pass_id]);
    
    if (!$pass) return ['allowed' => false, 'reason' => 'Invalid Pass'];
    if ($pass['status'] !== 'approved') return ['allowed' => false, 'reason' => "Pass status: {$pass['status']}"];
    if ($pass['is_active'] == 0) return ['allowed' => false, 'reason' => 'Pass is inactive'];
    
    $now = date('Y-m-d');
    if ($now < $pass['valid_from']) return ['allowed' => false, 'reason' => 'Pass validity has not started'];
    if ($now > $pass['valid_to']) return ['allowed' => false, 'reason' => 'Pass has expired'];
    
    return ['allowed' => true, 'reason' => 'Entry permitted'];
}
?>
