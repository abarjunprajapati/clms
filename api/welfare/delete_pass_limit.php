<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
header('Content-Type: application/json');

$data       = json_decode(file_get_contents('php://input'), true);
$id         = (int)($data['id'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID.']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? 0;

$ok = db_execute($conn, "DELETE FROM pass_limits WHERE id=?", 'i', [$id]);

if ($ok) {
    db_execute($conn,
        "INSERT INTO audit_logs (user_id, action, module, details, ip_address) VALUES (?,?,?,?,?)",
        'issss', [$user_id, 'delete_pass_limit', 'pass_limits', "Deleted pass limit ID $id", $_SERVER['REMOTE_ADDR'] ?? '']
    );
    echo json_encode(['success' => true, 'message' => 'Pass limit removed.']);
} else {
    echo json_encode(['success' => false, 'error' => 'Delete failed: ' . mysqli_error($conn)]);
}

