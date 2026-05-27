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
$userId = $_SESSION['user_id'] ?? 0;
$gatePassDocTypesSql = "'Medical Fitness Certificate','Police Clearance Certificate','Proof for Age','Proof for Address','Bank Account Proof','Insurance (ESI/WC)','Training Certificate'";

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
    $dbStatus = $status === 'reupload_required' ? 'rejected' : $status;
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

        // Count pending (non-approved) docs for THIS specific worker
        $pendingRes = $conn->query("
            SELECT COUNT(*) as pending 
            FROM documents d 
            WHERE d.workman_id = $workmanId
              AND d.document_type IN ($gatePassDocTypesSql)
              AND d.status != 'approved'
        ");
        $pendingRow = $pendingRes ? $pendingRes->fetch_assoc() : null;
        $totalPending = (int)($pendingRow['pending'] ?? 0);

        $allApproved = false;
        if ($totalPending === 0) {
            $workerRes = $conn->query("SELECT id, contractor_id, safety_training_status, training_status, worker_type FROM workmen WHERE id = $workmanId LIMIT 1");
            if ($workerRes && ($worker = $workerRes->fetch_assoc())) {
                $isPass = ((int)$worker['safety_training_status'] === 1 || in_array(strtolower($worker['training_status']), ['pass', 'passed', 'training_passed', 'qualified', 'completed']));
                if ($isPass) {
                    $allApproved = true;

                    // 1. Update worker status to verified
                    $conn->query("UPDATE workmen SET status = 'verified', pass_issuer_verified = 1, updated_at = NOW() WHERE id = $workmanId");

                    // 2. Update or auto-create gate_pass_request_workers entry → KEY FIX
                    $gprwRes = $conn->query("SELECT gprw.id, gprw.request_id FROM gate_pass_request_workers gprw WHERE gprw.workman_id = $workmanId ORDER BY gprw.id DESC LIMIT 1");
                    if ($gprwRes && ($gprw = $gprwRes->fetch_assoc())) {
                        $conn->query("UPDATE gate_pass_request_workers SET status = 'approved' WHERE workman_id = $workmanId");
                        $conn->query("UPDATE gate_pass_requests SET status = 'approved', updated_at = NOW() WHERE id = " . (int)$gprw['request_id']);
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

        // Check pending docs in BOTH tables for workers under this application
        $pendingRes = $conn->query("
            SELECT COUNT(*) as pending 
            FROM documents d 
            JOIN workmen w ON d.workman_id = w.id 
            WHERE w.application_no = '$safeAppId'
              AND d.document_type IN ($gatePassDocTypesSql)
              AND d.status != 'approved'
        ");
        $pendingRow = $pendingRes ? $pendingRes->fetch_assoc() : null;
        $totalPending = (int)($pendingRow['pending'] ?? 0);

        $allApproved = false;
        if ($totalPending === 0) {
            // All documents approved — check each worker and transition them
            $workersRes = $conn->query("SELECT id, contractor_id, safety_training_status, training_status, worker_type FROM workmen WHERE application_no = '$safeAppId'");
            while ($workersRes && ($worker = $workersRes->fetch_assoc())) {
                $wId = (int)$worker['id'];
                $isPass = ((int)$worker['safety_training_status'] === 1 || in_array(strtolower($worker['training_status']), ['pass', 'passed', 'training_passed', 'qualified', 'completed']));
                if (!$isPass) continue;

                $allApproved = true;

                // 1. Update worker status
                $conn->query("UPDATE workmen SET status = 'verified', pass_issuer_verified = 1, updated_at = NOW() WHERE id = $wId");

                // 2. Update or auto-create gate_pass_request_workers → KEY FIX
                $gprwRes = $conn->query("SELECT gprw.id, gprw.request_id FROM gate_pass_request_workers gprw WHERE gprw.workman_id = $wId ORDER BY gprw.id DESC LIMIT 1");
                if ($gprwRes && ($gprw = $gprwRes->fetch_assoc())) {
                    $conn->query("UPDATE gate_pass_request_workers SET status = 'approved' WHERE workman_id = $wId");
                    $conn->query("UPDATE gate_pass_requests SET status = 'approved', updated_at = NOW() WHERE id = " . (int)$gprw['request_id']);
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
        }

        echo json_encode(['success' => true, 'message' => 'Document status updated' . ($allApproved ? ' — Worker verified & moved to Pending Pass Requests.' : '.'), 'all_approved' => $allApproved]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Query preparation failed']);
}
?>

