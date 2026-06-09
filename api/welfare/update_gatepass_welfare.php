<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin', 'welfare_user']);
include __DIR__ . '/../../include/config.php';
header('Content-Type: application/json');

$data    = json_decode(file_get_contents('php://input'), true);
$id      = (int)($data['id']     ?? 0);
$action  = trim($data['action']  ?? '');   // 'approve_pass' or 'reject_pass'
$reason  = trim($data['reason']  ?? '');
$user_id = (int)($_SESSION['user_id'] ?? 0);

if (!$id || !in_array($action, ['approve_pass', 'reject_pass'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters.']);
    exit;
}

if ($action === 'reject_pass' && !$reason) {
    echo json_encode(['success' => false, 'error' => 'Rejection reason is required.']);
    exit;
}

if ($action === 'approve_pass') {
    $ok = db_execute($conn,
        "UPDATE gate_passes SET status='approved', documents_verified=1, approved_date=CURDATE() WHERE id=?",
        'i', [$id]
    );
    $log_action = 'gatepass_approved';
    $detail     = "Gate pass ID $id approved by welfare admin.";
} else {
    $ok = db_execute($conn,
        "UPDATE gate_passes SET status='rejected' WHERE id=?",
        'i', [$id]
    );
    $log_action = 'gatepass_rejected';
    $detail     = "Gate pass ID $id rejected. Reason: $reason";
}

if ($ok) {
    db_execute($conn,
        "INSERT INTO audit_logs (user_id, action, module, details, ip_address) VALUES (?,?,?,?,?)",
        'issss', [$user_id, $log_action, 'gate_passes', $detail, $_SERVER['REMOTE_ADDR'] ?? '']
    );

    // Notify contractor
    $gp = db_single($conn,
        "SELECT c.user_id, w.name as worker_name FROM gate_passes gp JOIN workmen w ON gp.workman_id=w.id JOIN contractors c ON w.contractor_id=c.id WHERE gp.id=?",
        'i', [$id]
    );
    if ($gp) {
        $msg = $action === 'approve_pass'
            ? "Gate pass for worker '{$gp['worker_name']}' has been approved by Welfare Admin. Proceed to pass issuance."
            : "Gate pass for worker '{$gp['worker_name']}' has been rejected. Reason: $reason";
        db_execute($conn,
            "INSERT INTO notifications (user_id, message, type, is_read) VALUES (?,?,?,0)",
            'iss', [$gp['user_id'], $msg, $log_action]
        );
    }

    echo json_encode(['success' => true, 'message' => $action === 'approve_pass' ? 'Gate pass approved.' : 'Gate pass rejected.']);
} else {
    echo json_encode(['success' => false, 'error' => 'Database operation failed: ' . clms_db_error($conn)]);
}

