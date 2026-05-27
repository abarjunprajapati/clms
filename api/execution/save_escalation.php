<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../auth_middleware.php';

header('Content-Type: application/json');
enforceRole(['execution_officer', 'super_admin']);

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['status' => false, 'message' => 'No data provided']);
    exit;
}

// Get Officer ID
$officerRes = db_single($conn, "SELECT id FROM execution_officers WHERE employee_code = (SELECT contractor_id FROM users WHERE id = ?)", 'i', [$userId]);
$officerId = $officerRes['id'] ?? 0;

if (!$officerId) {
    echo json_encode(['status' => false, 'message' => 'Officer record not found']);
    exit;
}

try {
    $sql = "INSERT INTO execution_escalations (execution_officer_id, escalated_to, escalation_type, contractor_id, workman_id, severity, remarks, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'open')";
    
    $params = [
        $officerId,
        $data['escalated_to'],
        $data['escalation_type'],
        $data['contractor_id'],
        !empty($data['workman_id']) ? $data['workman_id'] : null,
        $data['severity'],
        $data['remarks']
    ];

    if (db_execute($conn, $sql, 'isssiss', $params)) {
        echo json_encode(['status' => true, 'message' => 'Escalation raised successfully']);
    } else {
        echo json_encode(['status' => false, 'message' => 'Database error']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}
?>
