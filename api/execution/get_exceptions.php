<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../auth_middleware.php';

header('Content-Type: application/json');
enforceRole(['execution_officer', 'super_admin']);

$userId = $_SESSION['user_id'];

// Get Officer ID
$officerRes = db_single($conn, "SELECT id FROM execution_officers WHERE employee_code = (SELECT contractor_id FROM users WHERE id = ?)", 'i', [$userId]);
$officerId = $officerRes['id'] ?? 0;

if (!$officerId) {
    echo json_encode(['status' => false, 'message' => 'Officer record not found']);
    exit;
}

try {
    // 1. Unauthorized Attendance (Present but no deployment)
    $unauthorizedCount = db_count($conn, "SELECT COUNT(*) FROM attendance a 
                                         WHERE DATE(a.check_in) = CURDATE() 
                                         AND a.workman_id IN (SELECT id FROM workmen WHERE contractor_id IN (SELECT contractor_id FROM execution_officer_contractors WHERE execution_officer_id = ?))
                                         AND a.workman_id NOT IN (SELECT workman_id FROM execution_worker_deployments WHERE status = 'active')", 'i', [$officerId]);

    // 2. Blocked Worker Attendance attempts
    $blockedAttempts = db_count($conn, "SELECT COUNT(*) FROM attendance a 
                                       JOIN workmen w ON a.workman_id = w.id 
                                       WHERE DATE(a.check_in) = CURDATE() 
                                       AND w.contractor_id IN (SELECT contractor_id FROM execution_officer_contractors WHERE execution_officer_id = ?)
                                       AND w.status IN ('blocked', 'perm_blocked', 'inactive')", 'i', [$officerId]);

    echo json_encode([
        'status' => true,
        'unauthorized_attendance' => $unauthorizedCount,
        'blocked_attempts' => $blockedAttempts
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}
?>
