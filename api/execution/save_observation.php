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
$work_order_id = (int)($input['work_order_id'] ?? 0);
$observation_type = $input['observation_type'] ?? '';
$remarks = $input['remarks'] ?? '';
$severity = $input['severity'] ?? 'low';
$action_required = (int)($input['action_required'] ?? 0);

if (!$contractor_id || !$observation_type || !$remarks) {
    echo json_encode(['status' => false, 'message' => 'Missing required fields']);
    exit;
}

$sql = "INSERT INTO execution_observations (execution_officer_id, contractor_id, workman_id, work_order_id, observation_type, remarks, severity, action_required, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$success = db_execute($conn, $sql, 'iiiisssi', [
    $officerId, $contractor_id, $workman_id ?: null, $work_order_id ?: null, 
    $observation_type, $remarks, $severity, $action_required
]);

if ($success) {
    $obsId = $conn->insert_id;
    // Audit Log
    db_execute($conn, "INSERT INTO execution_audit_logs (execution_officer_id, action, entity_type, entity_id, new_value, created_at) VALUES (?, ?, ?, ?, ?, NOW())", 'issis', [
        $officerId, 'ADD_OBSERVATION', 'observation', $obsId, json_encode($input)
    ]);

    // If high severity or action required, create a notification
    if ($severity === 'high' || $action_required) {
        db_execute($conn, "INSERT INTO execution_notifications (execution_officer_id, recipient_role, title, message, created_at) VALUES (?, ?, ?, ?, NOW())", 'isss', [
            $officerId, 'welfare_admin', 'Urgent Observation: ' . $observation_type, "Observation #$obsId: $remarks"
        ]);
        db_execute($conn, "INSERT INTO execution_notifications (execution_officer_id, recipient_role, title, message, created_at) VALUES (?, ?, ?, ?, NOW())", 'isss', [
            $officerId, 'safety_user', 'Safety Observation: ' . $observation_type, "Observation #$obsId: $remarks"
        ]);
    }

    echo json_encode(['status' => true, 'message' => 'Observation saved', 'id' => $obsId]);
} else {
    echo json_encode(['status' => false, 'message' => 'Database error']);
}
?>


