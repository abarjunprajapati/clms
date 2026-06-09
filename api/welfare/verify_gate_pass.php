<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_user', 'super_admin', 'pass_user']);
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/NotificationEngine.php';
require_once __DIR__ . '/../../include/gate_pass_document_master.php';
require_once __DIR__ . '/../../api/WorkflowEngine.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$request_id = (int)($input['request_id'] ?? 0);
$action = trim($input['action'] ?? ''); // 'approve' or 'reject'
$reason = trim($input['reason'] ?? '');
$user_id = (int)($_SESSION['user_id'] ?? 0);

if (!$request_id || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters.']);
    exit;
}

if ($action === 'reject' && empty($reason)) {
    echo json_encode(['success' => false, 'error' => 'Rejection reason is required.']);
    exit;
}

try {
    $conn->begin_transaction();

    // 1. Get request
    $req = db_single(
        $conn, 
        "SELECT * FROM gate_pass_requests WHERE id = ? FOR UPDATE", 
        'i', 
        [$request_id]
    );
    if (!$req) {
        throw new Exception('Gate pass request not found');
    }
    if ($req['status'] !== 'pending') {
        throw new Exception("Request is already {$req['status']}");
    }

    // 2. Get linked workers
    $workers = db_fetch_all(
        $conn,
        "SELECT workman_id FROM gate_pass_request_workers WHERE request_id = ?",
        'i',
        [$request_id]
    );
    
    if (empty($workers)) {
        throw new Exception('No workers found for this request');
    }

    if ($action === 'reject') {
        // Handle Rejection
        db_execute(
            $conn,
            "UPDATE gate_pass_requests SET status = 'rejected', rejection_reason = ?, updated_at = NOW() WHERE id = ?",
            'si',
            [$reason, $request_id]
        );
        
        db_execute(
            $conn,
            "UPDATE gate_pass_request_workers SET status = 'rejected', updated_at = NOW() WHERE request_id = ?",
            'i',
            [$request_id]
        );
        
        // Notify contractor
        NotificationEngine::sendRoleNotification(
            $conn, 
            'contractor', 
            "Gate Pass Request ({$req['request_no']}) was rejected. Reason: $reason", 
            'gatepass'
        );
        
        $msg = 'Gate pass request rejected successfully.';

    } else {
        // Handle Approval
        db_execute(
            $conn,
            "UPDATE gate_pass_requests SET status = 'approved', updated_at = NOW() WHERE id = ?",
            'i',
            [$request_id]
        );
        
        db_execute(
            $conn,
            "UPDATE gate_pass_request_workers SET status = 'approved', updated_at = NOW() WHERE request_id = ?",
            'i',
            [$request_id]
        );

        foreach ($workers as $w) {
            $workman_id = (int)$w['workman_id'];
            
            // Validate required Annexure 6A documents exist.
            $requiredDocTypes = array_values(clms_get_gate_pass_document_type_map($conn, true));
            $requiredDocTypesSql = "'" . implode("','", array_map([$conn, 'real_escape_string'], $requiredDocTypes)) . "'";
            $docCount = db_count($conn, "SELECT COUNT(DISTINCT document_type) FROM documents WHERE workman_id = ? AND document_type IN ($requiredDocTypesSql)", 'i', [$workman_id]);
            if ($docCount < count($requiredDocTypes)) {
                throw new Exception("Worker ID $workman_id does not have all required Annexure 6A documents.");
            }
            
            // Update worker - Mark as pre-verified by welfare
            db_execute(
                $conn,
                "UPDATE workmen SET pass_issuer_verified = 1, updated_at = NOW() WHERE id = ?",
                'i',
                [$workman_id]
            );
            
            // Update documents status
            db_execute(
                $conn,
                "UPDATE documents SET status = 'approved' WHERE workman_id = ?",
                'i',
                [$workman_id]
            );
        }

        // Notify Pass Issuer: document verification is complete, pass is not issued yet.
        NotificationEngine::sendRoleNotification(
            $conn, 
            'pass_issuer', 
            "New Gate Pass Request ({$req['request_no']}) approved by Welfare. Ready for issuance.", 
            'gatepass'
        );

        // Advance Workflow to document verification only. Temporary pass issuance is the next desk step.
        WorkflowEngine::performAction(
            $conn,
            $req['application_id'],
            'verify_documents',
            $_SESSION['role'] ?? 'welfare',
            $user_id,
            "Annexure 6A documents approved for request {$req['request_no']}"
        );

        $msg = 'Gate pass request approved. Worker moved to temporary pass issuance queue.';
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => $msg]);

} catch (Throwable $e) {
    if (isset($conn) && method_exists($conn, 'rollback')) {
        @$conn->rollback();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

