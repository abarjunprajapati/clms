<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/NotificationEngine.php';
require_once __DIR__ . '/../../api/WorkflowEngine.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$workman_id = (int)($input['workman_id'] ?? 0);
$user_id = (int)($_SESSION['user_id'] ?? 0);

if (!$workman_id) {
    echo json_encode(['success' => false, 'error' => 'Workman ID is required.']);
    exit;
}

try {
    $conn->begin_transaction();

    // 1. Fetch Workman
    $workman = db_single(
        $conn, 
        "SELECT id, application_no, status, contractor_id FROM workmen WHERE id = ? FOR UPDATE", 
        'i', 
        [$workman_id]
    );
    
    if (!$workman) {
        throw new Exception('Workman not found');
    }
    
    if ($workman['status'] !== 'temporary_issued') {
        throw new Exception("ACC can only be generated for workers with status 'temporary_issued'. Current status: {$workman['status']}");
    }

    // 2. Generate ACC Number (SAP integration mock)
    // Format: ACC-[Year]-[Random 6 digits]
    $acc_number = 'ACC-' . date('Y') . '-' . str_pad(random_int(1, 999999), 6, '0', STR_PAD_LEFT);

    // 3. Update workmen table
    db_execute(
        $conn,
        "UPDATE workmen SET status = 'acc_generated', acc_card_number = ?, updated_at = NOW() WHERE id = ?",
        'si',
        [$acc_number, $workman_id]
    );

    // 4. Update gate_passes table to permanent pass
    db_execute(
        $conn,
        "UPDATE gate_passes SET pass_type = 'permanent', acc_card_number = ? WHERE workman_id = ? AND status = 'approved'",
        'si',
        [$acc_number, $workman_id]
    );

    // 5. Audit Log
    db_execute(
        $conn,
        "INSERT INTO audit_logs (user_id, action, module, details, ip_address) VALUES (?, 'acc_generated', 'admin', ?, ?)",
        'iss',
        [$user_id, "ACC $acc_number generated for workman $workman_id", $_SERVER['REMOTE_ADDR'] ?? '']
    );

    // 6. Notify contractor
    NotificationEngine::sendRoleNotification(
        $conn, 
        'contractor', 
        "Permanent Pass and ACC ($acc_number) generated for your worker (ID: $workman_id).", 
        'acc_generation'
    );
    
    // Advance workflow if applicable
    WorkflowEngine::performAction(
        $conn,
        $workman['application_no'],
        'generate_acc',
        $_SESSION['role'] ?? 'admin',
        $user_id,
        "ACC $acc_number generated for workman $workman_id"
    );

    $conn->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'ACC generated successfully. Permanent pass issued.',
        'acc_number' => $acc_number
    ]);

} catch (Throwable $e) {
    if (isset($conn) && method_exists($conn, 'rollback')) {
        @$conn->rollback();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

