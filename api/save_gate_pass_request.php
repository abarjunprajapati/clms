<?php
session_start();
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/customer_portal_context.php';
require_once __DIR__ . '/../include/gate_pass_document_master.php';
require_once __DIR__ . '/api_helper.php';
require_once __DIR__ . '/WorkflowEngine.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError('Only POST requests are allowed', 405);
}

function ensureGatePassDocumentSchema($conn) {
    @mysqli_query($conn, "ALTER TABLE documents ADD COLUMN gate_pass_request_id INT NULL");
    @mysqli_query($conn, "ALTER TABLE documents MODIFY document_type VARCHAR(255) NULL");
    @mysqli_query($conn, "ALTER TABLE documents MODIFY status VARCHAR(30) DEFAULT 'pending'");
    @mysqli_query($conn, "ALTER TABLE gate_pass_requests MODIFY status VARCHAR(30) DEFAULT 'pending'");
    @mysqli_query($conn, "ALTER TABLE gate_pass_request_workers MODIFY status VARCHAR(30) DEFAULT 'pending'");
}

function gatePassApiColumnExists($conn, $table, $column) {
    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && mysqli_num_rows($result) > 0;
}

function uploadAnnexure6ADoc($conn, $workmanId, $requestId, $docType, $key) {
    $dir = __DIR__ . '/../uploads/documents/';
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0777, true)) {
            throw new Exception("Could not create directory: $dir");
        }
        chmod($dir, 0777);
    }
    if (!is_writable($dir)) {
        throw new Exception("Directory not writable: $dir");
    }

    if (empty($_FILES[$key]) || $_FILES[$key]['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $ext = strtolower(pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
        throw new Exception("Invalid file type for $key");
    }

    $filename = $workmanId . '_' . $key . '_' . uniqid('', true) . '.' . $ext;
    if (!move_uploaded_file($_FILES[$key]['tmp_name'], $dir . $filename)) {
        throw new Exception("Could not upload $key");
    }

    $stmt = $conn->prepare("INSERT INTO documents (workman_id, gate_pass_request_id, document_type, file_path, status, uploaded_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
    if (!$stmt) {
        throw new Exception("Could not prepare document upload for $key");
    }
    $stmt->bind_param("iiss", $workmanId, $requestId, $docType, $filename);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception("Could not save uploaded document: $key");
    }
    $stmt->close();
    
    return true;
}

try {
    ensureGatePassDocumentSchema($conn);
    clms_get_portal_contractor($conn);
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $contractor = $userId ? db_single($conn, "SELECT id, application_no FROM contractors WHERE user_id = ? ORDER BY id DESC LIMIT 1", 'i', [$userId]) : null;
    if (!$contractor) {
        throw new Exception('Contractor registration not found');
    }

    $contractorId = (int)$contractor['id'];
    $app = db_single($conn, "SELECT application_id FROM annexure2a WHERE contractor_id = ? ORDER BY id DESC LIMIT 1", 'i', [$contractorId]);
    $applicationNo = $contractor['application_no'] ?: ($app['application_id'] ?? ('APP-' . $contractorId));

    $action = strtolower(trim((string)($_POST['action'] ?? 'submit')));
    $workmanId = (int)($_POST['workman_id'] ?? 0);
    $requestId = (int)($_POST['request_id'] ?? 0);
    $passType = strtolower(trim($_POST['pass_type'] ?? 'Workmen'));
    $passType = in_array(ucfirst($passType), ['Contractor', 'Supervisor', 'Workmen']) ? ucfirst($passType) : 'Workmen';
    
    $validFrom = trim($_POST['valid_from'] ?? date('Y-m-d'));
    $validTo = trim($_POST['valid_to'] ?? date('Y-m-d', strtotime('+30 days')));

    if (!$workmanId) {
        throw new Exception('Select a worker');
    }
    if (strtotime($validTo) < strtotime($validFrom)) {
        throw new Exception('Invalid validity date range');
    }

    $roleTypeExpr = gatePassApiColumnExists($conn, 'workmen', 'role_type') ? "COALESCE(role_type, '')" : "''";
    $safetyEnrollmentExpr = gatePassApiColumnExists($conn, 'workmen', 'safety_enrollment_status') ? "COALESCE(safety_enrollment_status, 'approved')" : "'approved'";
    $worker = db_single(
        $conn,
        "SELECT id, name, worker_type, $roleTypeExpr AS role_type, training_status, safety_training_status,
                $safetyEnrollmentExpr AS safety_enrollment_status, training_valid_till
         FROM workmen WHERE id = ? AND contractor_id = ? LIMIT 1",
        'ii',
        [$workmanId, $contractorId]
    );
    if (!$worker) {
        throw new Exception('Worker not found for this contractor');
    }

    $trainingStatus = strtolower((string)$worker['training_status']);
    $safetyTrainingStatus = strtolower((string)($worker['safety_training_status'] ?? ''));
    $trainingPassed = in_array($trainingStatus, ['pass', 'passed', 'training_passed', 'qualified', 'completed'], true)
        || in_array($safetyTrainingStatus, ['1', 'training_passed', 'passed', 'pass'], true);
    if (!$trainingPassed) {
        throw new Exception('Safety training is not passed. Annexure 5A gate pass request is blocked.');
    }
    if (strtolower((string)($worker['safety_enrollment_status'] ?? 'approved')) !== 'approved') {
        throw new Exception('Enrollment approval is required before Gate Pass creation.');
    }
    if (!empty($worker['training_valid_till']) && strtotime($worker['training_valid_till']) < strtotime(date('Y-m-d'))) {
        throw new Exception('Safety training validity has expired. Please request re-training before gate pass.');
    }

    if ($action === 'prepare') {
        $workerCategory = strtolower(trim((string)($worker['worker_type'] ?: ($worker['role_type'] ?? 'workmen'))));
        if (strpos($workerCategory, 'supervisor') !== false) {
            $passType = 'Supervisor';
        } elseif (strpos($workerCategory, 'contractor') !== false || strpos($workerCategory, 'representative') !== false) {
            $passType = 'Contractor';
        } else {
            $passType = 'Workmen';
        }
        $existing = db_single(
            $conn,
            "SELECT gpr.id, gpr.request_no, gpr.status
             FROM gate_pass_requests gpr
             JOIN gate_pass_request_workers gprw ON gprw.request_id = gpr.id
             WHERE gpr.contractor_id = ?
               AND gprw.workman_id = ?
               AND LOWER(COALESCE(gpr.status, 'pending')) IN ('draft','pending','submitted','reupload_required')
             ORDER BY gpr.id DESC LIMIT 1",
            'ii',
            [$contractorId, $workmanId]
        );
        if ($existing && strtolower((string)$existing['status']) !== 'draft') {
            throw new Exception('An active Gate Pass request already exists for this employee.');
        }
        if ($existing) {
            apiSuccess([
                'request_id' => (int)$existing['id'],
                'request_no' => $existing['request_no'],
                'workman_id' => $workmanId,
            ], 'Gate Pass document upload is ready.');
        }

        $requestNo = 'GPR-' . date('Ymd') . '-' . random_int(1000, 9999);
        $conn->begin_transaction();
        db_execute(
            $conn,
            "INSERT INTO gate_pass_requests (
                request_no, application_id, contractor_id, pass_type, from_date, to_date, status, created_at, updated_at
             ) VALUES (?, ?, ?, ?, ?, ?, 'draft', NOW(), NOW())",
            'ssisss',
            [$requestNo, $applicationNo, $contractorId, $passType, $validFrom, $validTo]
        );
        $requestId = (int)$conn->insert_id;
        db_execute(
            $conn,
            "INSERT INTO gate_pass_request_workers (request_id, workman_id, status, created_at) VALUES (?, ?, 'draft', NOW())",
            'ii',
            [$requestId, $workmanId]
        );
        $conn->commit();
        apiSuccess([
            'request_id' => $requestId,
            'request_no' => $requestNo,
            'workman_id' => $workmanId,
        ], 'Gate Pass document upload is ready.');
    }

    if (!in_array($action, ['save_draft', 'submit'], true)) {
        throw new Exception('Invalid Gate Pass action.');
    }
    if (!$requestId) {
        throw new Exception('Prepare the Gate Pass request before uploading documents.');
    }
    $draftRequest = db_single(
        $conn,
        "SELECT gpr.id, gpr.request_no, gpr.application_id, gpr.status
         FROM gate_pass_requests gpr
         JOIN gate_pass_request_workers gprw ON gprw.request_id = gpr.id
         WHERE gpr.id = ? AND gpr.contractor_id = ? AND gprw.workman_id = ?
         LIMIT 1",
        'iii',
        [$requestId, $contractorId, $workmanId]
    );
    if (!$draftRequest || strtolower((string)$draftRequest['status']) !== 'draft') {
        throw new Exception('Gate Pass draft not found or already submitted.');
    }

    $requiredDocs = clms_get_gate_pass_document_type_map($conn, true);
    $allDocs = clms_get_gate_pass_document_type_map($conn, false);
    $optionalDocs = array_diff_key($allDocs, $requiredDocs);

    if ($action === 'submit') {
        foreach ($requiredDocs as $key => $name) {
            $existingDocument = db_single(
                $conn,
                "SELECT id FROM documents
                 WHERE workman_id = ? AND gate_pass_request_id = ? AND document_type = ?
                 ORDER BY id DESC LIMIT 1",
                'iis',
                [$workmanId, $requestId, $name]
            );
            if (!$existingDocument && (empty($_FILES[$key]) || $_FILES[$key]['error'] !== UPLOAD_ERR_OK)) {
                throw new Exception("Mandatory document missing: $name");
            }
        }
    }

    $requestNo = $draftRequest['request_no'];
    $conn->begin_transaction();

    // Upload and insert documents against this specific gate pass request.
    foreach ($requiredDocs as $key => $docType) {
        uploadAnnexure6ADoc($conn, $workmanId, $requestId, $docType, $key);
    }

    foreach ($optionalDocs as $key => $docType) {
        uploadAnnexure6ADoc($conn, $workmanId, $requestId, $docType, $key);
    }

    if ($action === 'save_draft') {
        db_execute(
            $conn,
            "UPDATE gate_pass_requests SET updated_at = NOW() WHERE id = ? AND contractor_id = ? AND status = 'draft'",
            'ii',
            [$requestId, $contractorId]
        );
        $conn->commit();
        apiSuccess([
            'request_no' => $requestNo,
            'request_id' => $requestId,
            'workman_id' => $workmanId,
        ], 'Gate Pass draft saved successfully.');
    }

    db_execute(
        $conn,
        "UPDATE gate_pass_requests
         SET status = 'pending', from_date = ?, to_date = ?, updated_at = NOW()
         WHERE id = ? AND contractor_id = ? AND status = 'draft'",
        'ssii',
        [$validFrom, $validTo, $requestId, $contractorId]
    );
    db_execute(
        $conn,
        "UPDATE gate_pass_request_workers
         SET status = 'pending', updated_at = NOW()
         WHERE request_id = ? AND workman_id = ?",
        'ii',
        [$requestId, $workmanId]
    );

    // Trigger Notification for Welfare
    require_once __DIR__ . '/../include/NotificationEngine.php';
    NotificationEngine::sendRoleNotification(
        $conn, 
        'welfare', 
        "New Gate Pass Request ($requestNo) submitted for verification.", 
        'gatepass'
    );

    $wf = WorkflowEngine::performAction(
        $conn,
        $applicationNo,
        'request_gatepass',
        $_SESSION['role'] ?? 'contractor',
        $userId,
        "Annexure 5A gate pass request $requestNo submitted"
    );

    $conn->commit();

    apiSuccess([
        'request_no' => $requestNo,
        'request_id' => $requestId,
        'application_id' => $applicationNo,
        'workman_id' => $workmanId,
    ], 'Gate pass request submitted successfully with Annexure 6A documents.');
} catch (Throwable $e) {
    if (isset($conn) && method_exists($conn, 'rollback')) {
        @$conn->rollback();
    }
    error_log("Gate Pass Request Error: " . $e->getMessage());
    apiError($e->getMessage(), 400);
}
?>

