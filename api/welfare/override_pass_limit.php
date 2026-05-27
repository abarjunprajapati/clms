<?php
/**
 * WELFARE OVERRIDE - Pass Limit Override API
 * 
 * Allows welfare admin to override pass limit rules
 * with audit logging
 * 
 * Usage: POST /api/welfare/override_pass_limit.php
 */

require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../api_helper.php';
require_once __DIR__ . '/../json_error_handler.php';
require_once __DIR__ . '/../../include/pass_limit_validator.php';

session_start();
header('Content-Type: application/json; charset=utf-8');

try {
    // ========== AUTHORIZATION CHECK ==========
    require_role(['welfare', 'welfare_admin', 'welfare_user', 'admin']);
    
    $data = getApiInput();
    
    // ========== VALIDATE INPUT ==========
    $contractor_id = (int)($data['contractor_id'] ?? 0);
    $pass_type = trim($data['pass_type'] ?? '');
    $requested_count = (int)($data['requested_count'] ?? 1);
    $reason = trim($data['reason'] ?? 'Administrative override');
    
    if (!$contractor_id || !$pass_type) {
        apiError('contractor_id and pass_type are required', 400);
    }
    
    if (!in_array($pass_type, ['Contractor', 'Representative', 'Supervisor', 'Workman'])) {
        apiError('Invalid pass_type', 400);
    }
    
    // ========== ATTEMPT VALIDATION WITH OVERRIDE ==========
    try {
        $validation = validatePassLimit($conn, $contractor_id, $pass_type, $requested_count, true);
    } catch (Exception $e) {
        // Check if override is allowed for this rule
        $limit = getPassLimit($conn, $contractor_id, $pass_type);
        if (!$limit || !$limit['override_allowed']) {
            apiError('Cannot override: ' . $e->getMessage(), 403);
        }
        $validation = [
            'valid' => true,
            'override' => true,
            'message' => 'Allowed with admin override'
        ];
    }
    
    // ========== LOG OVERRIDE TO AUDIT TABLE ==========
    
    $admin_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0;
    $admin_name = $_SESSION['name'] ?? $_SESSION['username'] ?? 'Unknown Admin';
    $details = "Override approved for contractor_id=$contractor_id, pass_type=$pass_type, requested_count=$requested_count. Reason: $reason by $admin_name";
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    
    $log_sql = "INSERT INTO audit_logs 
               (user_id, action, module, details, ip_address, created_at) 
               VALUES (?, ?, ?, ?, ?, NOW())";
    
    $stmt = mysqli_prepare($conn, $log_sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'issss', 
            $admin_id,
            $action = 'pass_limit_override',
            $module = 'pass_limits',
            $details,
            $ip
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    // ========== GET CURRENT STATS FOR RESPONSE ==========
    $current = getCurrentPassCount($conn, $contractor_id, $pass_type);
    $calc = calculateAllowed($conn, $contractor_id, $pass_type);
    
    apiSuccess([
        'contractor_id' => $contractor_id,
        'pass_type' => $pass_type,
        'override' => true,
        'current_count' => $current,
        'allowed' => $calc['allowed'],
        'requested' => $requested_count,
        'new_total' => $current + $requested_count,
        'rule' => $calc['rule'],
        'reason' => $reason,
        'admin_id' => $admin_id,
        'message' => '✅ Pass limit override approved and logged',
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Throwable $e) {
    apiError($e->getMessage(), 500);
}
?>

