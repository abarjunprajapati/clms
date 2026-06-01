<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/execution_context.php';
require_once __DIR__ . '/../auth_middleware.php';

header('Content-Type: application/json');
enforceRole(['execution_officer', 'execution', 'super_admin']);

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['status' => false, 'message' => 'No data provided']);
    exit;
}

// Get or create execution officer context for this login
$officerId = clms_execution_get_officer_id($conn, $userId);

if (!$officerId) {
    echo json_encode(['status' => false, 'message' => 'Officer record not found']);
    exit;
}

try {
    $sql = "INSERT INTO execution_recommendations (execution_officer_id, workman_id, reason, status, created_at) 
            VALUES (?, ?, ?, 'pending', NOW())";
    
    $params = [
        $officerId,
        $data['workman_id'],
        $data['reason']
    ];

    if (db_execute($conn, $sql, 'iis', $params)) {
        echo json_encode(['status' => true, 'message' => 'Recommendation logged successfully']);
    } else {
        echo json_encode(['status' => false, 'message' => 'Database error']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}
?>


