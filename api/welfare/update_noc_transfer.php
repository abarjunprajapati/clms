<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
header('Content-Type: application/json');

$data           = json_decode(file_get_contents('php://input'), true);
$workman_id     = (int)($data['workman_id']     ?? 0);
$to_contractor  = (int)($data['to_contractor']  ?? 0);
$action         = trim($data['action']          ?? '');   // 'approve_transfer' or 'reject_transfer'
$reason         = trim($data['reason']          ?? '');
$user_id        = (int)($_SESSION['user_id']    ?? 0);

if (!$workman_id || !in_array($action, ['approve_transfer', 'reject_transfer'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters.']);
    exit;
}

if ($action === 'approve_transfer') {
    if (!$to_contractor) {
        echo json_encode(['success' => false, 'error' => 'Target contractor required for transfer.']);
        exit;
    }
    // Move worker to new contractor
    $ok = db_execute($conn, "UPDATE workmen SET contractor_id=? WHERE id=?", 'ii', [$to_contractor, $workman_id]);
    $detail = "Worker $workman_id transferred to contractor $to_contractor.";
} else {
    // Rejection — just log, no workmen update needed
    $ok = true;
    $detail = "Transfer for worker $workman_id rejected. Reason: $reason";
}

if ($ok) {
    db_execute($conn,
        "INSERT INTO audit_logs (user_id, action, module, details, ip_address) VALUES (?,?,?,?,?)",
        'issss', [$user_id, $action, 'workmen', $detail, $_SERVER['REMOTE_ADDR'] ?? '']
    );

    // Notify original contractor
    $worker = db_single($conn, "SELECT contractor_id, name FROM workmen WHERE id=?", 'i', [$workman_id]);
    if ($worker) {
        $old_c = db_single($conn, "SELECT user_id FROM contractors WHERE id=?", 'i', [$worker['contractor_id']]);
        if ($old_c) {
            $msg = $action === 'approve_transfer'
                ? "Worker '{$worker['name']}' NOC transfer has been approved."
                : "Worker '{$worker['name']}' transfer request was rejected. Reason: $reason";
            db_execute($conn,
                "INSERT INTO notifications (user_id, message, type, is_read) VALUES (?,?,?,0)",
                'iss', [$old_c['user_id'], $msg, $action]
            );
        }
    }

    echo json_encode(['success' => true, 'message' => $action === 'approve_transfer' ? 'Transfer approved.' : 'Transfer rejected.']);
} else {
    echo json_encode(['success' => false, 'error' => 'Operation failed: ' . clms_db_error($conn)]);
}

