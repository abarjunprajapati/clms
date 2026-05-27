<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['pass_user', 'super_admin', 'welfare_user', 'welfare_admin']);
include __DIR__ . '/../../include/config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
file_put_contents(__DIR__ . '/../../logs/biometric_debug.log', date('Y-m-d H:i:s') . ' - Capture Input: ' . json_encode($data) . "\n", FILE_APPEND);
$workerId = $data['worker_id'] ?? $data['id'] ?? 0;

if (!$workerId) {
    echo json_encode(['success' => false, 'message' => 'Worker ID required (received: ' . json_encode($data) . ')']);
    exit;
}

// PDF Point 10: 1 Fingerprint = 1 ACC Rule
// Here we mock the uniqueness check and capture
$stmt = $conn->prepare("UPDATE workmen SET biometric_status = 'completed', status = 'biometric_completed' WHERE id = ?");
if ($stmt) {
    $stmt->bind_param('i', $workerId);
    if ($stmt->execute()) {
        include_once __DIR__ . '/../../include/AuditLogger.php';
        AuditLogger::log($conn, 'BIOMETRIC_CAPTURED', 'workmen', 'pending', 'completed', "Worker ID: $workerId");
        echo json_encode(['success' => true, 'message' => 'Biometric captured successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'DB error']);
    }
}
?>

