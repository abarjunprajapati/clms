<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin', 'welfare_user']);
include __DIR__ . '/../../include/config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$id     = (int)($data['id']     ?? 0);
$status = $data['status'] ?? '';
$reason = $data['reason'] ?? '';

if (!$id || !in_array($status, ['approved', 'rejected'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

if ($status === 'rejected' && !$reason) {
    echo json_encode(['success' => false, 'error' => 'Rejection reason is required']);
    exit;
}

$updated_by = $_SESSION['user_id'] ?? 0;

$sql = "UPDATE contractors SET status=? WHERE id=?";
$ok = db_execute($conn, $sql, 'si', [$status, $id]);

if ($ok) {
    // Log the action
    $action_desc = "Contractor ID $id " . ($status === 'approved' ? 'approved' : "rejected. Reason: $reason");
    db_execute($conn,
        "INSERT INTO audit_logs (user_id, action, module, details, ip_address) VALUES (?,?,?,?,?)",
        'issss',
        [$updated_by, "contractor_$status", 'contractors', $action_desc, $_SERVER['REMOTE_ADDR'] ?? '']
    );

    // Notify contractor user
    $contractor = db_single($conn, "SELECT user_id FROM contractors WHERE id=?", 'i', [$id]);
    if ($contractor && $contractor['user_id']) {
        $msg = $status === 'approved'
            ? "Your contractor registration has been approved. You may now proceed with workmen enrollment."
            : "Your contractor registration has been rejected. Reason: $reason";
        db_execute($conn,
            "INSERT INTO notifications (user_id, message, type, is_read) VALUES (?,?,?,0)",
            'iss', [$contractor['user_id'], $msg, "contractor_$status"]
        );
    }

    echo json_encode(['success' => true, 'message' => "Contractor $status successfully"]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database update failed: ' . mysqli_error($conn)]);
}

