<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$syncId = $data['id'] ?? 0;
$system = $data['system'] ?? ''; // 'SAP' or 'Attendance'

if (!$syncId || !$system) {
    echo json_encode(['success' => false, 'message' => 'ID and System required']);
    exit;
}

if ($system === 'SAP') {
    $stmt = $conn->prepare("UPDATE sap_sync_queue SET status = 'pending', retry_count = retry_count + 1 WHERE id = ?");
} else {
    $stmt = $conn->prepare("UPDATE attendance_sync_queue SET status = 'pending', retry_count = retry_count + 1 WHERE id = ?");
}

if ($stmt) {
    $stmt->bind_param('i', $syncId);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Sync queued for retry']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Retry update failed']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Prepare failed']);
}
?>
