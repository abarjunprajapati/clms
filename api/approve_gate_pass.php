<?php
require_once 'api_helper.php';
require_once '../include/config.php';
require_once 'workflow_action.php';
require_once '../include/auth_middleware.php';

require_role(['pass_officer', 'welfare', 'acc', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError('Only POST requests are allowed', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    apiError('Invalid JSON input');
}

$requestId = (int)($input['request_id'] ?? 0);
$decision = trim($input['status'] ?? $input['decision'] ?? '');
$remarks = trim($input['remarks'] ?? '');

if ($requestId <= 0 || !in_array($decision, ['approved', 'rejected'], true)) {
    apiError('request_id and status approved/rejected are required');
}

try {
    $request = db_single(
        $conn,
        'SELECT * FROM gate_pass_requests WHERE id = ? LIMIT 1',
        'i',
        [$requestId]
    );
    if (!$request) {
        apiError('Gate pass request not found', 404);
    }

    $application = workflowGetApplication($request['application_id']);
    if (!$application) {
        apiError('Application not found: ' . $request['application_id'], 404);
    }
    workflowRequireAtLeast($application, 'pass_requested');

$conn->begin_transaction();
    
    // ========== ANNEXURE 5/A FINAL APPROVAL VALIDATION ==========
    if ($decision === 'approved') {
        require_once __DIR__ . '/../include/pass_limit_validator.php';
        
        $workers = db_fetch_all($conn, "SELECT DISTINCT worker_id FROM gate_pass_request_workers WHERE request_id = ?", 'i', [$requestId]);
        $workerCount = count($workers);
        
        if ($workerCount > 0) {
            $appContractor = db_single($conn, "SELECT contractor_id FROM annexure2a WHERE application_id = ?", 's', [$request['application_id']]);
            $cid = (int)($appContractor['contractor_id'] ?? 0);
            
            if ($cid) {
                try {
                    validatePassLimit($conn, $cid, 'Workman', $workerCount, false); // No override at approval
                } catch (Exception $limitEx) {
                    $conn->rollback();
                    apiError("Annexure 5/A Final Check: " . $limitEx->getMessage(), 400);
                }
            }
        }
    }
    // ============================================================
    
    db_execute(
        $conn,
        'UPDATE gate_pass_requests SET status = ?, remarks = COALESCE(NULLIF(?, \'\'), remarks), updated_at = NOW() WHERE id = ?',
        'ssi',
        [$decision, $remarks, $requestId]
    );

    $targetStatus = $decision === 'approved' ? 'pass_issued' : 'rejected';
    $workflow = workflowSetStatus($request['application_id'], $targetStatus, $remarks);

    try {
        db_execute(
            $conn,
            'INSERT INTO approvals (application_id, approver_role, status, comments, approved_at)
             VALUES (?, ?, ?, ?, NOW())',
            'isss',
            [(int)$application['id'], $_SESSION['role'] ?? 'pass_officer', $decision, $remarks]
        );
    } catch (Throwable $e) {
        error_log('[approve_gate_pass] approval log failed: ' . $e->getMessage());
    }

    $conn->commit();
    apiSuccess([
        'request_id' => $requestId,
        'request_no' => $request['request_no'],
        'status' => $decision,
        'workflow' => $workflow,
    ], 'Gate pass request ' . $decision . ' successfully');
} catch (Throwable $e) {
    if (isset($conn) && method_exists($conn, 'rollback')) {
        @$conn->rollback();
    }
    apiError($e->getMessage(), 500);
}

