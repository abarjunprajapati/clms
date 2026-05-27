<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../auth_middleware.php';

header('Content-Type: application/json');
enforceRole(['execution_officer', 'super_admin']);

$userId = $_SESSION['user_id'];
$type = $_GET['type'] ?? '';

// Get Officer ID
$officerRes = db_single($conn, "SELECT id FROM execution_officers WHERE employee_code = (SELECT contractor_id FROM users WHERE id = ?)", 'i', [$userId]);
$officerId = $officerRes['id'] ?? 0;

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
