<?php
require_once '../../include/auth.php';
// Allow multiple roles who can advance the workflow
checkAuth(['welfare_admin', 'welfare_user', 'pass_user', 'safety_user', 'super_admin']);
require_once '../../include/config.php';
require_once '../../include/RuleEngine.php';

$data = json_decode(file_get_contents("php://input"), true);
$appId = $data['application_id'] ?? 0;
$status = $data['status'] ?? '';

if (!$appId || !$status) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

// 1. Validate if this transition is allowed (Optional: could add more strict checks here)
// 2. Perform any rule-based validation before status update
$validation = RuleEngine::validateAction($appId, 'next_step', $conn);
if (!$validation['success']) {
    echo json_encode($validation);
    exit;
}

// 3. Update the status
$success = RuleEngine::updateStatus($appId, $status, $conn);

if ($success) {
    // Log the transition in logs table
    $user_id = $_SESSION['user_id'];
    $action = "Workflow transition to $status";
    $logSql = "INSERT INTO logs (user_id, action, module, module_id) VALUES (?, ?, 'workflow', ?)";
    $logStmt = clms_db_prepare($conn, $logSql);
    clms_db_stmt_bind_param($logStmt, "isi", $user_id, $action, $appId);
    clms_db_stmt_execute($logStmt);
}

header('Content-Type: application/json');
echo json_encode(['success' => $success]);

