<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../auth_middleware.php';

header('Content-Type: application/json');
enforceRole(['super_admin']);

$officerId = (int)($_GET['id'] ?? 0);
if (!$officerId) {
    echo json_encode(['status' => false, 'message' => 'Officer ID required']);
    exit;
}

try {
    // Departments
    $depts = db_fetch_all($conn, "SELECT department_id FROM execution_officer_departments WHERE execution_officer_id = ?", 'i', [$officerId]);
    $deptIds = array_column($depts, 'department_id');

    // Contractors
    $contractors = db_fetch_all($conn, "SELECT contractor_id FROM execution_officer_contractors WHERE execution_officer_id = ?", 'i', [$officerId]);
    $contractorIds = array_column($contractors, 'contractor_id');

    echo json_encode([
        'status' => true,
        'depts' => $deptIds,
        'contractors' => $contractorIds
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}
?>
