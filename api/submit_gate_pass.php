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
    // Handle JSON or FormData input
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) throw new Exception('Invalid JSON input');
    } else {
        $input = $_POST;
        if (isset($input['worker_ids']) && is_string($input['worker_ids'])) {
            $input['worker_ids'] = json_decode($input['worker_ids'], true);
        }
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

    $isTempStr = $input['is_temporary'] ?? '0';
    $isTemp = ($isTempStr === '1' || $isTempStr === 'true' || $isTempStr === true);
    $passType = $isTemp ? 'temporary' : (strtolower(trim($input['pass_type'] ?? 'permanent')) === 'temporary' ? 'temporary' : 'permanent');
    $validFrom = trim($input['from_date'] ?? $input['valid_from'] ?? date('Y-m-d'));
    $validTo = trim($input['to_date'] ?? $input['valid_to'] ?? date('Y-m-d', strtotime('+30 days')));
    
    if (strtotime($validTo) < strtotime($validFrom)) {
        throw new Exception('Invalid date range');
    }
    
    if ($isTemp) {
        $diffTime = abs(strtotime($validTo) - strtotime($validFrom));
        $diffDays = floor($diffTime / (60 * 60 * 24)) + 1;
        if ($diffDays > 7) {
            throw new Exception('Temporary Pass validity cannot exceed 7 days');
        }
    }

    $uploadPath = null;
    if ($isTemp) {
        if (!isset($_FILES['executing_officer_declaration']) || $_FILES['executing_officer_declaration']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Executing Officer Declaration file is required for Temporary Pass');
        }
        $file = $_FILES['executing_officer_declaration'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
            throw new Exception('Invalid file type for declaration. Only PDF/JPG/PNG allowed.');
        }
        $uploadDir = '../uploads/gate_passes/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = 'temp_decl_' . $applicationNo . '_' . time() . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
            throw new Exception('Failed to upload Executing Officer Declaration');
        }
        $uploadPath = 'uploads/gate_passes/' . $fileName;
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

        // If temporary pass is requested, bypass strict training checks
        if (!$isTemp) {
            $training = strtolower((string)$worker['training_status']);
            if (!in_array($training, ['pass', 'passed', 'training_passed', 'qualified', 'completed'], true) && (int)$worker['safety_training_status'] !== 1) {
                continue;
            }
        }

        db_execute(
            $conn,
            "INSERT INTO gate_passes (
                application_no, workman_id, pass_type, is_temporary, temporary_validity_start, temporary_validity_end, executing_officer_declaration_path, request_date, valid_from, valid_to,
                safety_training_status, documents_verified, status, created_at
             ) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?, 1, 0, 'pending', NOW())",
            'sisissssss',
            [$applicationNo, $workerId, $passType, $isTemp ? 1 : 0, $isTemp ? $validFrom : null, $isTemp ? $validTo : null, $uploadPath, $validFrom, $validTo]
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

