<?php
session_start();
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/customer_portal_context.php';
require_once __DIR__ . '/api_helper.php';
require_once __DIR__ . '/WorkflowEngine.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError('Only POST requests are allowed', 405);
}

function uploadAnnexure6ADoc($conn, $workmanId, $docType, $key) {
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

    $stmt = $conn->prepare("INSERT INTO documents (workman_id, document_type, file_path, status, uploaded_at) VALUES (?, ?, ?, 'pending', NOW())");
    $stmt->bind_param("iss", $workmanId, $docType, $filename);
    $stmt->execute();
    $stmt->close();
    
    return true;
}

try {
    clms_get_portal_contractor($conn);
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $contractor = $userId ? db_single($conn, "SELECT id, application_no FROM contractors WHERE user_id = ? ORDER BY id DESC LIMIT 1", 'i', [$userId]) : null;
    if (!$contractor) {
        throw new Exception('Contractor registration not found');
    }

    $contractorId = (int)$contractor['id'];
    $app = db_single($conn, "SELECT application_id FROM annexure2a WHERE contractor_id = ? ORDER BY id DESC LIMIT 1", 'i', [$contractorId]);
    $applicationNo = $contractor['application_no'] ?: ($app['application_id'] ?? ('APP-' . $contractorId));

    $workmanId = (int)($_POST['workman_id'] ?? 0);
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

    $worker = db_single(
        $conn,
        "SELECT id, name, training_status, safety_training_status FROM workmen WHERE id = ? AND contractor_id = ? LIMIT 1",
        'ii',
        [$workmanId, $contractorId]
    );
    if (!$worker) {
        throw new Exception('Worker not found for this contractor');
    }

    $trainingStatus = strtolower((string)$worker['training_status']);
    if (!in_array($trainingStatus, ['pass', 'passed', 'training_passed', 'qualified', 'completed'], true) && (int)$worker['safety_training_status'] !== 1) {
        throw new Exception('Safety training is not passed. Annexure 5A gate pass request is blocked.');
    }

    $requiredDocs = [
        'medical_certificate' => 'Medical Fitness Certificate',
        'police_clearance_certificate' => 'Online Police Clearance Certificate (PCC) for Employment Pass / Deck Hand including officer for Emergency Pass (Template Upload)',
        'employee_compensation_policy' => 'Employee Compensation Policy if not covered under ESI'
    ];

    $optionalDocs = [
        'pcc_forwarded_police' => 'Proof of forwarding PCC to Thane Police Station',
        'pcc_forwarded_cisf' => 'Proof of forwarding PCC to CISF',
        'pcc_police_station_name' => 'Name of Police Station from where PCC has been obtained',
        'esi_epf_undertaking' => 'ESI / EPF Undertaking if not covered under ESI / EPF'
    ];

    foreach ($requiredDocs as $key => $name) {
        if (empty($_FILES[$key]) || $_FILES[$key]['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Mandatory document missing: $name ($key)");
        }
    }

    $requestNo = 'GPR-' . date('Ymd') . '-' . random_int(1000, 9999);
    $conn->begin_transaction();

    // Upload and insert documents
    foreach ($requiredDocs as $key => $docType) {
        uploadAnnexure6ADoc($conn, $workmanId, $docType, $key);
    }

    foreach ($optionalDocs as $key => $docType) {
        uploadAnnexure6ADoc($conn, $workmanId, $docType, $key);
    }

    // Insert Gate Pass Request
    db_execute(
        $conn,
        "INSERT INTO gate_pass_requests (
            request_no, application_id, contractor_id, pass_type, from_date, to_date, status, created_at, updated_at
         ) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())",
        'ssisss',
        [$requestNo, $applicationNo, $contractorId, $passType, $validFrom, $validTo]
    );
    $requestId = $conn->insert_id;

    // Link worker to request
    db_execute(
        $conn,
        "INSERT INTO gate_pass_request_workers (request_id, workman_id, status, created_at) VALUES (?, ?, 'pending', NOW())",
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

