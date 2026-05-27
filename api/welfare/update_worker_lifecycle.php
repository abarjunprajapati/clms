<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin', 'welfare_user']);
include __DIR__ . '/../../include/config.php';
header('Content-Type: application/json');

$data       = json_decode(file_get_contents('php://input'), true);
$workman_id = (int)($data['workman_id'] ?? 0);
$action     = trim($data['action'] ?? '');   // 'block' or 'unblock'
$reason     = trim($data['reason'] ?? '');
$blocked_by = (int)($_SESSION['user_id'] ?? 0);

if (!$workman_id || !in_array($action, ['block', 'unblock'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters.']);
    exit;
}

if ($action === 'block' && !$reason) {
    echo json_encode(['success' => false, 'error' => 'Reason is required for blocking.']);
    exit;
}

if ($action === 'block') {
    // Insert block record
    $ok1 = db_execute($conn,
        "INSERT INTO worker_blocks (workman_id, blocked_by, reason, block_type, status, blocked_at) VALUES (?,?,?,'permanent','active',NOW())",
        'iis', [$workman_id, $blocked_by, $reason]
    );
    // Update workman status
    $ok2 = db_execute($conn, "UPDATE workmen SET status='blocked' WHERE id=?", 'i', [$workman_id]);
    $ok = $ok1 && $ok2;
    $detail = "Worker $workman_id blocked. Reason: $reason";

} else {
    // Release all active blocks for this worker
    $ok1 = db_execute($conn,
        "UPDATE worker_blocks SET status='released' WHERE workman_id=? AND status='active'",
        'i', [$workman_id]
    );
    // Restore workman to active
    $ok2 = db_execute($conn, "UPDATE workmen SET status='active' WHERE id=?", 'i', [$workman_id]);
    $ok = $ok1 && $ok2;
    $detail = "Worker $workman_id unblocked.";
}

if ($ok) {
    db_execute($conn,
        "INSERT INTO audit_logs (user_id, action, module, details, ip_address) VALUES (?,?,?,?,?)",
        'issss', [$blocked_by, "worker_$action", 'worker_blocks', $detail, $_SERVER['REMOTE_ADDR'] ?? '']
    );

    // Notify contractor
    $worker = db_single($conn, "SELECT contractor_id, name FROM workmen WHERE id=?", 'i', [$workman_id]);
    if ($worker) {
        $contractor_user = db_single($conn, "SELECT user_id FROM contractors WHERE id=?", 'i', [$worker['contractor_id']]);
        if ($contractor_user) {
            $msg = $action === 'block'
                ? "Worker '{$worker['name']}' has been blocked. Reason: $reason"
                : "Worker '{$worker['name']}' has been unblocked and is now active.";
            db_execute($conn,
                "INSERT INTO notifications (user_id, message, type, is_read) VALUES (?,?,?,0)",
                'iss', [$contractor_user['user_id'], $msg, "worker_$action"]
            );
        }
    }

    echo json_encode(['success' => true, 'message' => "Worker $action" . "ed successfully."]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database operation failed: ' . mysqli_error($conn)]);
}

