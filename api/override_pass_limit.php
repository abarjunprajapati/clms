<?php
/**
 * Welfare Admin Override - Annexure 5/A
 * Allows override if rule permits
 */
require_once 'api_helper.php';
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/pass_limit_validator.php';
require_once __DIR__ . '/../include/AuditLogger.php'; // If exists, else manual log

try {
    $input = getApiInput();
    $contractor_id = (int)$input['contractor_id'];
    $pass_type = $input['pass_type'];
    $adding = (int)($input['adding'] ?? 1);
    $reason = trim($input['reason'] ?? '');
    
    if (!$reason) apiError('Override reason required', 400);
    
    // Validate with override
    $result = validatePassLimit($conn, $contractor_id, $pass_type, $adding, true);
    
    // Log override (manual if AuditLogger not ready)
    $admin_id = $_SESSION['user_id'] ?? 1;
    $admin_name = $_SESSION['user_name'] ?? 'Welfare Admin';
    $details = "Override approved for contractor_id=$contractor_id, pass_type=$pass_type, requested_count=$adding. Reason: $reason by $admin_name";
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    
    $log_sql = "INSERT INTO audit_logs (user_id, action, module, details, ip_address, created_at) VALUES (?, 'pass_limit_override', 'pass_limits', ?, ?, NOW())";
    db_execute($conn, $log_sql, 'iss', [
        $admin_id,
        $details,
        $ip
    ]);
    
    apiSuccess($result + ['overridden' => true, 'reason' => $reason]);
    
} catch (Exception $e) {
    apiError($e->getMessage(), 400);
}
?>

