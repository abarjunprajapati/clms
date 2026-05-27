<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../auth_middleware.php';

header('Content-Type: application/json');
enforceRole(['execution_officer', 'super_admin']);

$contractorId = $_GET['id'] ?? 0;

if (!$contractorId) {
    echo json_encode(['status' => false, 'message' => 'Invalid Contractor ID']);
    exit;
}

try {
    $contractor = db_single($conn, "SELECT c.*, 
                                   (SELECT COUNT(*) FROM workmen WHERE contractor_id = c.id) as total_workmen,
                                   (SELECT COUNT(*) FROM execution_worker_deployments WHERE contractor_id = c.id AND status = 'active') as active_deployments
                                   FROM contractors c WHERE c.id = ?", 'i', [$contractorId]);

    if (!$contractor) {
        echo json_encode(['status' => false, 'message' => 'Contractor not found']);
        exit;
    }

    echo json_encode([
        'status' => true,
        'data' => $contractor
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}
?>
