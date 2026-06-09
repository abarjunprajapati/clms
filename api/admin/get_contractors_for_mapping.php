<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../auth_middleware.php';

header('Content-Type: application/json');
enforceRole(['super_admin']);

$officerId = (int)($_GET['officer_id'] ?? 0);

try {
    $sql = "SELECT c.id, c.contractor_name, 
            (SELECT 1 FROM execution_officer_contractors WHERE execution_officer_id = ? AND contractor_id = c.id) as assigned 
            FROM contractors c 
            WHERE c.status = 'approved' 
            ORDER BY c.contractor_name ASC";
    
    $contractors = db_fetch_all($conn, $sql, 'i', [$officerId]);
    
    echo json_encode([
        'status' => true,
        'contractors' => $contractors
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}
?>
