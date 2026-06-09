<?php
session_start();
require_once 'api_helper.php';
require_once '../include/config.php';
require_once 'WorkflowEngine.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError('Only POST requests are allowed', 405);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new Exception('Invalid JSON input');
    }

    $applicationNo = trim($input['application_id'] ?? ($_SESSION['current_application_id'] ?? ''));
    if ($applicationNo === '') {
        throw new Exception('application_id is required');
    }

    $contractor = db_single($conn, "SELECT contractor_id FROM annexure2a WHERE application_id = ? LIMIT 1", 's', [$applicationNo]);
    $contractorId = (int)($contractor['contractor_id'] ?? 0);
    if (!$contractorId) {
        $contractorId = (int)($_SESSION['contractor_id'] ?? $_SESSION['user_id'] ?? 0);
    }

    $workerIds = array_values(array_unique(array_filter(array_map('intval', $input['worker_ids'] ?? []))));
    if (!$workerIds) {
        throw new Exception('Please select at least one workman');
    }

    $passType = strtolower(trim($input['pass_type'] ?? 'temporary')) === 'permanent' ? 'permanent' : 'temporary';
    $validFrom = trim($input['from_date'] ?? $input['valid_from'] ?? date('Y-m-d'));
    $validTo = trim($input['to_date'] ?? $input['valid_to'] ?? date('Y-m-d', strtotime('+30 days')));
    if (strtotime($validTo) < strtotime($validFrom)) {
        throw new Exception('Invalid date range');
    }

    $conn->begin_transaction();
    $saved = 0;

    foreach ($workerIds as $workerId) {
        $worker = db_single(
            $conn,
            "SELECT id, training_status, safety_training_status FROM workmen
             WHERE id = ? AND application_no = ? AND contractor_id = ? LIMIT 1",
            'isi',
            [$workerId, $applicationNo, $contractorId]
        );
        if (!$worker) {
            continue;
        }

        $training = strtolower((string)$worker['training_status']);
        if (!in_array($training, ['pass', 'passed', 'training_passed', 'qualified', 'completed'], true) && (int)$worker['safety_training_status'] !== 1) {
            continue;
        }

        db_execute(
            $conn,
            "INSERT INTO gate_passes (
                application_no, workman_id, pass_type, request_date, valid_from, valid_to,
                safety_training_status, documents_verified, status, created_at
             ) VALUES (?, ?, ?, CURDATE(), ?, ?, 1, 0, 'pending', NOW())",
            'sisss',
            [$applicationNo, $workerId, $passType, $validFrom, $validTo]
        );
        $saved++;
    }

    if ($saved === 0) {
        throw new Exception('No eligible workers found');
    }

    $wf = WorkflowEngine::performAction(
        $conn,
        $applicationNo,
        'request_gatepass',
        $_SESSION['role'] ?? 'contractor',
        (int)($_SESSION['user_id'] ?? 0),
        'Annexure 5A gate pass request submitted'
    );
    if (!$wf['success'] && strpos($wf['message'], "Invalid action 'request_gatepass'") === false) {
        throw new Exception($wf['message']);
    }

    $conn->commit();

    apiSuccess([
        'application_id' => $applicationNo,
        'worker_count' => $saved,
        'request_id' => 'GP-' . date('Ymd') . '-' . random_int(1000, 9999),
    ], 'Gate pass request submitted successfully');
} catch (Throwable $e) {
    if (isset($conn) && method_exists($conn, 'rollback')) {
        @$conn->rollback();
    }
    apiError($e->getMessage(), 400);
}
?>

