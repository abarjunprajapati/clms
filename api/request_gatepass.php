<?php
/**
 * request_gatepass.php
 * Request gate passes for qualified workers (training passed)
 */
require_once 'helpers.php';
require_once __DIR__ . '/../include/config.php';

try {
    $input = getApiInput();
    
    $application_id = $input['application_id'] ?? null;
    $contractor_id = $input['contractor_id'] ?? null;
    $gate_name = trim($input['gate_name'] ?? '');
    $shift_name = trim($input['shift_name'] ?? '');
    $access_zone = trim($input['access_zone'] ?? 'Zone A & B');
    $from_date = trim($input['from_date'] ?? '');
    $to_date = trim($input['to_date'] ?? '');
    $worker_ids = $input['worker_ids'] ?? [];
    
    // Validation
    if (!$application_id) {
        apiError('application_id is required', 400);
    }

    // ========== CONTRACTOR BLOCKING CHECK ==========
    $appData = db_single($conn, "SELECT id, contractor_id FROM annexure2a WHERE application_id = ?", 's', [$application_id]);
    if ($appData) {
        $cid = (int)$appData['contractor_id'];
        $blocked = db_single($conn, "SELECT is_blocked, block_reason FROM contractors WHERE id = ?", 'i', [$cid]);
        if ($blocked && $blocked['is_blocked']) {
            apiError("Your firm is BLOCKED. Gate pass request denied. Reason: " . ($blocked['block_reason'] ?: 'Security'), 403);
        }
    }
    // ==============================================

    if (!$gate_name) {
        apiError('gate_name is required', 400);
    }
    if (!$from_date || !$to_date) {
        apiError('from_date and to_date are required', 400);
    }
    
    // Check workflow status - must be training_done to request gate pass
    $statusCheck = $conn->prepare("SELECT workflow_status FROM annexure2a WHERE application_id = ? LIMIT 1");
    if ($statusCheck) {
        $statusCheck->bind_param('s', $application_id);
        $statusCheck->execute();
        $statusResult = $statusCheck->get_result();
        $statusRow = $statusResult->fetch_assoc();
        $statusCheck->close();
        
        if (!$statusRow || $statusRow['workflow_status'] !== 'training_done') {
            apiError('Gate pass can only be requested after training is completed', 400);
        }
    }
    
    // ========== ANNEXURE 5/A GATE PASS VALIDATION ==========
    require_once __DIR__ . '/../include/pass_limit_validator.php';
    
    $cid = (int)($appData['contractor_id'] ?? 0);
    
    if ($cid && !empty($worker_ids)) {
        try {
            validatePassLimit($conn, $cid, 'Workman', count($worker_ids));
        } catch (Exception $limitEx) {
            apiError("Annexure 5/A: " . $limitEx->getMessage(), 400);
        }
    }
    // ======================================================
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    $conn->begin_transaction();
    
    // Generate request number
    $request_no = 'GP-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(rand())), 0, 6));
    
    // Insert gate pass request
    $stmt = $conn->prepare("
        INSERT INTO gate_pass_requests 
        (request_no, application_id, contractor_id, gate_name, shift_name, access_zone, from_date, to_date, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param('ssisssss', $request_no, $application_id, $contractor_id, $gate_name, $shift_name, $access_zone, $from_date, $to_date);
    
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
    $request_id = $conn->insert_id;
    $stmt->close();
    
    // Get qualified workers if none specified
    if (empty($worker_ids)) {
        $workersStmt = $conn->prepare("
            SELECT id FROM workmen 
            WHERE application_id = ? 
            AND LOWER(COALESCE(training_status, '')) IN ('qualified', 'completed', 'passed')
        ");
        
        if ($workersStmt) {
            $workersStmt->bind_param('s', $application_id);
            $workersStmt->execute();
            $workersResult = $workersStmt->get_result();
            
            while ($row = $workersResult->fetch_assoc()) {
                $worker_ids[] = $row['id'];
            }
            $workersStmt->close();
        }
    }
    
    $inserted = 0;
    
    // Insert worker associations
    foreach ($worker_ids as $workman_id) {
        $workerStmt = $conn->prepare("
            INSERT INTO gate_pass_request_workers (request_id, worker_id, status, created_at)
            VALUES (?, ?, 'pending', NOW())
        ");
        
        if ($workerStmt) {
            $workerStmt->bind_param('ii', $request_id, $workman_id);
            if ($workerStmt->execute()) {
                $inserted++;
            }
            $workerStmt->close();
        }
    }
    
    // Update workflow status using WorkflowEngine
    require_once 'WorkflowEngine.php';
    $wfResult = WorkflowEngine::performAction($conn, $application_id, 'request_gatepass', 'contractor', intval($_SESSION['user_id'] ?? 0), 'Gate pass requested');
    
    $conn->commit();
    
    // Return success
    $result = [
        'request_id' => $request_id,
        'request_no' => $request_no,
        'application_id' => $application_id,
        'gate_name' => $gate_name,
        'shift_name' => $shift_name,
        'access_zone' => $access_zone,
        'from_date' => $from_date,
        'to_date' => $to_date,
        'workers_count' => $inserted,
        'status' => 'pending'
    ];
    
    apiSuccess($result, 'Gate pass request submitted successfully. ' . $inserted . ' workers eligible.');
    
} catch (Exception $e) {
    if ($conn && $conn->in_transaction) {
        $conn->rollback();
    }
    apiError($e->getMessage(), 500);
}
?>
