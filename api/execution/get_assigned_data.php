<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/execution_context.php';
require_once __DIR__ . '/../auth_middleware.php';

header('Content-Type: application/json');
enforceRole(['execution_officer', 'execution', 'super_admin']);

$userId = $_SESSION['user_id'];
$type = $_GET['type'] ?? '';

// Get or create execution officer context for this login
$officerId = clms_execution_get_officer_id($conn, $userId);

if (!$officerId) {
    echo json_encode(['status' => false, 'message' => 'Officer record not found']);
    exit;
}

try {
    if ($type === 'workers') {
        $contractorId = (int)($_GET['contractor_id'] ?? 0);
        if (!$contractorId) throw new Exception('Contractor ID required');
        
        $workers = db_fetch_all($conn, "SELECT id, name, aadhaar FROM workmen WHERE contractor_id = ?", 'i', [$contractorId]);
        echo json_encode(['status' => true, 'data' => $workers]);
    } else {
        echo json_encode(['status' => false, 'message' => 'Invalid type']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}
?>


