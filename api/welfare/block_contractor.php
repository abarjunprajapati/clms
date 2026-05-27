<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'welfare_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/ContractorBlockingService.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$contractorId = $data['contractor_id'] ?? 0;
$action = $data['action'] ?? 'block'; // 'block' or 'unblock'
$reason = $data['reason'] ?? 'Admin Manual Block';
$remarks = $data['remarks'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;

if (!$contractorId) {
    echo json_encode(['success' => false, 'message' => 'Contractor ID required']);
    exit;
}

if ($action === 'block') {
    $result = ContractorBlockingService::blockContractor($conn, $contractorId, $reason, $remarks, $userId);
} else {
    $result = ContractorBlockingService::unblockContractor($conn, $contractorId, $userId);
}

echo json_encode($result);
?>
