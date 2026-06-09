<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/execution_context.php';
require_once __DIR__ . '/../auth_middleware.php';

header('Content-Type: application/json');
enforceRole(['execution_officer', 'execution', 'super_admin']);

$userId = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['status' => false, 'message' => 'Invalid input']);
    exit;
}

// Get or create execution officer context for this login
$officerId = clms_execution_get_officer_id($conn, $userId);

if (!$officerId) {
    echo json_encode(['status' => false, 'message' => 'Officer record not found']);
    exit;
}

$contractor_id = (int)($input['contractor_id'] ?? 0);
$workman_id = (int)($input['workman_id'] ?? 0);
$action_type = $input['action_type'] ?? '';
$action_reason = $input['action_reason'] ?? '';
$target_dept = $input['target_dept'] ?? 'admin';

if (!$contractor_id || !$action_type || !$action_reason) {
    echo json_encode(['status' => false, 'message' => 'Missing required fields']);
    exit;
}

$sql = "INSERT INTO execution_actions (execution_officer_id, workman_id, contractor_id, action_type, action_reason, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())";

$success = db_execute($conn, $sql, 'iiiss', [
    $officerId, $workman_id ?: null, $contractor_id, $action_type, $action_reason
]);

if ($success) {
    $actionId = $conn->insert_id;
    
    // Audit Log
    db_execute($conn, "INSERT INTO execution_audit_logs (execution_officer_id, action, entity_type, entity_id, new_value, created_at) VALUES (?, ?, ?, ?, ?, NOW())", 'issis', [
        $officerId, 'ADD_ACTION', 'action', $actionId, json_encode($input)
    ]);

    // Notification to target department
    $recipient_role = ($target_dept === 'safety') ? 'safety_user' : (($target_dept === 'welfare') ? 'welfare_admin' : 'super_admin');
    
    db_execute($conn, "INSERT INTO execution_notifications (execution_officer_id, recipient_role, title, message, created_at) VALUES (?, ?, ?, ?, NOW())", 'isss', [
        $officerId, $recipient_role, 'Escalation Raised: ' . strtoupper($action_type), "Escalation #$actionId: $action_reason"
    ]);

    echo json_encode(['status' => true, 'message' => 'Action saved', 'id' => $actionId]);
} else {
    echo json_encode(['status' => false, 'message' => 'Database error']);
}
?>


