<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_user', 'welfare_admin', 'super_admin', 'pass_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../api/WorkflowEngine.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$docId = $data['doc_id'] ?? 0;
$status = $data['status'] ?? 'approved';
$remarks = $data['remarks'] ?? '';
$sourceTable = $data['source_table'] ?? 'document_verifications';
$requestId = (int)($data['request_id'] ?? 0);
$userId = $_SESSION['user_id'] ?? 0;
$gatePassDocTypes = [
    'Medical Fitness Certificate',
    'Online Police Clearance Certificate (PCC) for Employment Pass / Deck Hand including officer for Emergency Pass (Template Upload)',
    'Proof of forwarding PCC to Thane Police Station',
    'Proof of forwarding PCC to CISF',
    'Name of Police Station from where PCC has been obtained',
    'Employee Compensation Policy if not covered under ESI',
    'ESI / EPF Undertaking if not covered under ESI / EPF',
];
$gatePassDocTypesSql = "'" . implode("','", array_map([$conn, 'real_escape_string'], $gatePassDocTypes)) . "'";
$gatePassTrainingStatuses = ['pass', 'passed', 'training_passed', 'qualified', 'completed'];

@$conn->query("ALTER TABLE gate_pass_requests MODIFY status VARCHAR(30) DEFAULT 'pending'");
@$conn->query("ALTER TABLE gate_pass_request_workers MODIFY status VARCHAR(30) DEFAULT 'pending'");

function gatePassTrainingPassed($worker) {
    global $gatePassTrainingStatuses;

    $trainingStatus = strtolower(trim((string)($worker['training_status'] ?? '')));
    $safetyTrainingStatus = strtolower(trim((string)($worker['safety_training_status'] ?? '')));

    return in_array($trainingStatus, $gatePassTrainingStatuses, true)
        || in_array($safetyTrainingStatus, array_merge(['1'], $gatePassTrainingStatuses), true);
}

function latestGatePassDocsApproved($conn, $workmanId) {
    global $gatePassDocTypesSql;

    $workmanId = (int)$workmanId;
    $docs = [];
    $res = $conn->query("
        SELECT
            d.document_type,
            COALESCE(d.status, 'pending') AS status
        FROM documents d
        JOIN (
            SELECT document_type, MAX(id) AS latest_id
            FROM documents
            WHERE workman_id = $workmanId
              AND document_type IN ($gatePassDocTypesSql)
            GROUP BY document_type
        ) latest_docs ON latest_docs.latest_id = d.id
    ");
    while ($res && ($row = $res->fetch_assoc())) {
        $docs[] = $row;
    }

    return gatePassDocSetApproved($docs);
}

function requestGatePassDocsApproved($conn, $workmanId, $requestId) {
    global $gatePassDocTypesSql;

    $workmanId = (int)$workmanId;
    $requestId = (int)$requestId;
    if (!$requestId) return latestGatePassDocsApproved($conn, $workmanId);

    $docs = [];
    $res = $conn->query("
        SELECT
            d.document_type,
            COALESCE(d.status, 'pending') AS status
        FROM documents d
        JOIN (
            SELECT document_type, MAX(id) AS latest_id
            FROM documents
            WHERE workman_id = $workmanId
              AND document_type IN ($gatePassDocTypesSql)
            GROUP BY document_type
        ) latest_docs ON latest_docs.latest_id = d.id
    ");
    while ($res && ($row = $res->fetch_assoc())) {
        $docs[] = $row;
    }

    return gatePassDocSetApproved($docs);
}

function gatePassDocSetApproved(array $docs) {
    $hasMedical = false;
    $hasPcc = false;
    $hasCoverage = false;

    foreach ($docs as $doc) {
        $type = strtolower((string)($doc['document_type'] ?? ''));
        $status = strtolower((string)($doc['status'] ?? 'pending'));

        if ($status !== 'approved') {
            return false;
        }

        if (strpos($type, 'medical fitness') !== false) {
            $hasMedical = true;
        }
        if (strpos($type, 'pcc') !== false || strpos($type, 'police clearance') !== false || strpos($type, 'police station') !== false) {
            $hasPcc = true;
        }
        if (strpos($type, 'employee compensation') !== false || strpos($type, 'esi') !== false || strpos($type, 'epf') !== false) {
            $hasCoverage = true;
        }
    }

    return $hasMedical && $hasPcc && $hasCoverage;
}

if (!$docId) {
    echo json_encode(['success' => false, 'message' => 'Missing document ID']);
    exit;
}

if ($sourceTable === 'contractor_documents') {
    $conn->query("CREATE TABLE IF NOT EXISTS contractor_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contractor_id INT NULL,
        annexure3a_id INT NULL,
        doc_type VARCHAR(100) NULL,
        file_path VARCHAR(255) NULL,
        original_name VARCHAR(255) NULL,
        status VARCHAR(30) DEFAULT 'pending',
        remarks TEXT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL,
        KEY idx_contractor (contractor_id),
        KEY idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("ALTER TABLE contractor_documents MODIFY status VARCHAR(30) DEFAULT 'pending'");

    if ($status === 'reupload_required' && trim($remarks) === '') {
        echo json_encode(['success' => false, 'message' => 'Remarks are required when requesting re-upload.']);
        exit;
    }

    $oldRes = $conn->query("
        SELECT cd.id, cd.contractor_id, cd.doc_type, cd.status, c.contractor_name
        FROM contractor_documents cd
        LEFT JOIN contractors c ON cd.contractor_id = c.id
        WHERE cd.id = " . (int)$docId
    );
    $oldData = $oldRes ? $oldRes->fetch_assoc() : null;

    if (!$oldData) {
        echo json_encode(['success' => false, 'message' => 'Contractor document record not found']);
        exit;
    }

    $dbStatus = $status === 'approved' ? 'verified' : $status;
    $stmt = $conn->prepare("UPDATE contractor_documents SET status = ?, remarks = ?, updated_at = NOW() WHERE id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Query preparation failed']);
        exit;
    }

    $stmt->bind_param('ssi', $dbStatus, $remarks, $docId);
    if ($stmt->execute()) {
        include_once __DIR__ . '/../../include/AuditLogger.php';
        AuditLogger::log(
            $conn,
            'CONTRACTOR_DOCUMENT_VERIFIED',
            'contractor_documents',
            $oldData['status'],
            $dbStatus,
            "Contractor: {$oldData['contractor_name']}, Doc: {$oldData['doc_type']}, Remark: $remarks"
        );
        echo json_encode(['success' => true, 'message' => 'Contractor document status updated.', 'status' => $dbStatus]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    $stmt->close();
    exit;
}

if ($sourceTable === 'documents') {
    $dbStatus = $status === 'reupload_required' ? 'reupload_required' : $status;
    $oldRes = $conn->query("
        SELECT COALESCE(w.application_no, CONCAT('WORKMAN-', d.workman_id)) AS application_id, d.document_type, d.status, d.workman_id
        FROM documents d
        LEFT JOIN workmen w ON d.workman_id = w.id
        WHERE d.id = " . (int)$docId
    );
    $oldData = $oldRes ? $oldRes->fetch_assoc() : null;

    if (!$oldData) {
        echo json_encode(['success' => false, 'message' => 'Document record not found']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE documents SET status = ?, remarks = ? WHERE id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Query preparation failed']);
        exit;
    }
    $stmt->bind_param('ssi', $dbStatus, $remarks, $docId);
    if ($stmt->execute()) {
        include_once __DIR__ . '/../../include/AuditLogger.php';
        AuditLogger::log($conn, 'DOCUMENT_VERIFIED', 'documents', $oldData['status'], $dbStatus, "Doc: {$oldData['document_type']}, App: {$oldData['application_id']}, Remark: $remarks");
        $stmt->close();

        // [AUTO-FLOW LOGIC] — Per-worker check, not per-application
        $workmanId = (int)$oldData['workman_id'];
        $appId = $oldData['application_id'];

        if ($dbStatus === 'reupload_required') {
            $requestFilter = $requestId ? " AND gprw.request_id = $requestId" : "";
            $gprwRes = $conn->query("SELECT gprw.request_id FROM gate_pass_request_workers gprw WHERE gprw.workman_id = $workmanId $requestFilter ORDER BY gprw.id DESC LIMIT 1");
            if ($gprwRes && ($gprw = $gprwRes->fetch_assoc())) {
                $rid = (int)$gprw['request_id'];
                $safeRemarks = $conn->real_escape_string($remarks);
                $conn->query("UPDATE gate_pass_request_workers SET status = 'reupload_required', updated_at = NOW() WHERE request_id = $rid AND workman_id = $workmanId");
                $conn->query("UPDATE gate_pass_requests SET status = 'reupload_required', rejection_reason = '$safeRemarks', updated_at = NOW() WHERE id = $rid");
            }

            echo json_encode(['success' => true, 'message' => 'Document rejected and sent for contractor re-upload.', 'all_approved' => false, 'status' => $dbStatus]);
            exit;
        }

        $allApproved = false;
        if (requestGatePassDocsApproved($conn, $workmanId, $requestId)) {
            $workerRes = $conn->query("SELECT id, contractor_id, safety_training_status, training_status, worker_type FROM workmen WHERE id = $workmanId LIMIT 1");
            if ($workerRes && ($worker = $workerRes->fetch_assoc())) {
                if (gatePassTrainingPassed($worker)) {
                    $allApproved = true;

                    // 1. Update worker status to verified
                    $conn->query("UPDATE workmen SET status = 'verified', pass_issuer_verified = 1, updated_at = NOW() WHERE id = $workmanId");

                    // 2. Update or auto-create gate_pass_request_workers entry → KEY FIX
                    $requestFilter = $requestId ? " AND gprw.request_id = $requestId" : "";
                    $gprwRes = $conn->query("SELECT gprw.id, gprw.request_id FROM gate_pass_request_workers gprw WHERE gprw.workman_id = $workmanId $requestFilter ORDER BY gprw.id DESC LIMIT 1");
                    if ($gprwRes && ($gprw = $gprwRes->fetch_assoc())) {
                        $rid = (int)$gprw['request_id'];
                        $conn->query("UPDATE gate_pass_request_workers SET status = 'approved', updated_at = NOW() WHERE request_id = $rid AND workman_id = $workmanId");
                        $conn->query("UPDATE gate_pass_requests SET status = 'approved', updated_at = NOW() WHERE id = $rid");
                    } else {
                        $contractorId = (int)$worker['contractor_id'];
                        $workerRole = ucfirst($worker['worker_type'] ?? 'Workmen');
                        $autoReqNo = 'GPR-AUTO-' . date('Ymd') . '-' . rand(1000, 9999);
                        $safeAppId = $conn->real_escape_string($appId);
                        $conn->query("INSERT INTO gate_pass_requests (request_no, application_id, contractor_id, pass_type, status, created_at, updated_at) VALUES ('$autoReqNo', '$safeAppId', $contractorId, '$workerRole', 'approved', NOW(), NOW())");
                        $newReqId = $conn->insert_id;
                        if ($newReqId) {
                            $conn->query("INSERT INTO gate_pass_request_workers (request_id, workman_id, status, created_at) VALUES ($newReqId, $workmanId, 'approved', NOW())");
                        }
                    }

                    // 3. Trigger Workflow Engine
                    include_once __DIR__ . '/../../api/WorkflowEngine.php';
                    WorkflowEngine::performAction($conn, $appId, 'verify_documents', 'welfare_admin', $userId, "All documents approved for worker #$workmanId. Moved to Pending Pass Requests.");
                }
            }
        }

        echo json_encode(['success' => true, 'message' => 'Document status updated' . ($allApproved ? ' — Worker verified & moved to Pending Pass Requests.' : '.'), 'all_approved' => $allApproved]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}

$conn->query("CREATE TABLE IF NOT EXISTS document_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id VARCHAR(50) NOT NULL,
    contractor_id INT NULL,
    document_type VARCHAR(100) NOT NULL,
    status ENUM('pending','approved','rejected','reupload_required') DEFAULT 'pending',
    remarks TEXT,
    verified_by INT,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_status (status),
    KEY idx_application (application_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Fetch old state for audit
$oldRes = $conn->query("SELECT application_id, document_type, status FROM document_verifications WHERE id = " . (int)$docId);
$oldData = $oldRes ? $oldRes->fetch_assoc() : null;

if (!$oldData) {
    echo json_encode(['success' => false, 'message' => 'Document record not found']);
    exit;
}

$stmt = $conn->prepare("UPDATE document_verifications SET status = ?, remarks = ?, verified_by = ?, verified_at = NOW() WHERE id = ?");

if ($stmt) {
    $stmt->bind_param('ssii', $status, $remarks, $userId, $docId);
    if ($stmt->execute()) {
        // Log Audit (Point 23)
        include_once __DIR__ . '/../../include/AuditLogger.php';
        AuditLogger::log($conn, 'DOCUMENT_VERIFIED', 'document_verifications', $oldData['status'], $status, "Doc: {$oldData['document_type']}, App: {$oldData['application_id']}, Remark: $remarks");
        
        $stmt->close();

        // [AUTO-FLOW LOGIC] — Per-worker check via document_verifications
        $appId = $oldData['application_id'];
        $safeAppId = $conn->real_escape_string($appId);

        $allApproved = false;
        // Check each worker and transition only workers whose latest documents are all approved.
            $workersRes = $conn->query("SELECT id, contractor_id, safety_training_status, training_status, worker_type FROM workmen WHERE application_no = '$safeAppId'");
            while ($workersRes && ($worker = $workersRes->fetch_assoc())) {
                $wId = (int)$worker['id'];
                if (!gatePassTrainingPassed($worker) || !requestGatePassDocsApproved($conn, $wId, $requestId)) continue;

                $allApproved = true;

                // 1. Update worker status
                $conn->query("UPDATE workmen SET status = 'verified', pass_issuer_verified = 1, updated_at = NOW() WHERE id = $wId");

                // 2. Update or auto-create gate_pass_request_workers → KEY FIX
                $requestFilter = $requestId ? " AND gprw.request_id = $requestId" : "";
                $gprwRes = $conn->query("SELECT gprw.id, gprw.request_id FROM gate_pass_request_workers gprw WHERE gprw.workman_id = $wId $requestFilter ORDER BY gprw.id DESC LIMIT 1");
                if ($gprwRes && ($gprw = $gprwRes->fetch_assoc())) {
                    $rid = (int)$gprw['request_id'];
                    $conn->query("UPDATE gate_pass_request_workers SET status = 'approved', updated_at = NOW() WHERE request_id = $rid AND workman_id = $wId");
                    $conn->query("UPDATE gate_pass_requests SET status = 'approved', updated_at = NOW() WHERE id = $rid");
                } else {
                    $contractorId = (int)$worker['contractor_id'];
                    $workerRole = ucfirst($worker['worker_type'] ?? 'Workmen');
                    $autoReqNo = 'GPR-AUTO-' . date('Ymd') . '-' . rand(1000, 9999);
                    $conn->query("INSERT INTO gate_pass_requests (request_no, application_id, contractor_id, pass_type, status, created_at, updated_at) VALUES ('$autoReqNo', '$safeAppId', $contractorId, '$workerRole', 'approved', NOW(), NOW())");
                    $newReqId = $conn->insert_id;
                    if ($newReqId) {
                        $conn->query("INSERT INTO gate_pass_request_workers (request_id, workman_id, status, created_at) VALUES ($newReqId, $wId, 'approved', NOW())");
                    }
                }
            }

            if ($allApproved) {
                include_once __DIR__ . '/../../api/WorkflowEngine.php';
                WorkflowEngine::performAction($conn, $appId, 'verify_documents', 'welfare_admin', $userId, "All documents approved. Workers moved to Pending Pass Requests.");
            }

        echo json_encode(['success' => true, 'message' => 'Document status updated' . ($allApproved ? ' — Worker verified & moved to Pending Pass Requests.' : '.'), 'all_approved' => $allApproved]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Query preparation failed']);
}
?>
