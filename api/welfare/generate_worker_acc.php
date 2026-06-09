<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../include/auth.php';
checkAuth(['pass_user', 'super_admin', 'welfare_user', 'welfare_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../api/SapDemo.php';
include __DIR__ . '/../../api/WorkflowEngine.php';

$data = json_decode(file_get_contents('php://input'), true);
$workmanId = $data['workman_id'] ?? 0;
$userId = $_SESSION['user_id'] ?? 0;
$userRole = $_SESSION['role'] ?? 'admin';

function sendJson($data) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function generateEightDigitAcc($conn, $seedId) {
    $candidate = str_pad((string)max(1, (int)$seedId), 8, '0', STR_PAD_LEFT);
    $exists = db_single($conn, "SELECT id FROM workmen WHERE (acc_number = ? OR acc_card_number = ?) LIMIT 1", 'ss', [$candidate, $candidate]);
    if (!$exists) {
        return $candidate;
    }

    for ($i = 0; $i < 25; $i++) {
        $candidate = str_pad((string)random_int(1, 99999999), 8, '0', STR_PAD_LEFT);
        $exists = db_single($conn, "SELECT id FROM workmen WHERE (acc_number = ? OR acc_card_number = ?) LIMIT 1", 'ss', [$candidate, $candidate]);
        if (!$exists) {
            return $candidate;
        }
    }

    throw new Exception('Unable to generate unique 8-digit ACC number.');
}

if (!$workmanId) {
    sendJson(['success' => false, 'message' => 'Workman ID required']);
}

// Fetch worker to validate
$query = "SELECT w.*, c.contractor_name, c.work_awarding_department 
          FROM workmen w 
          LEFT JOIN contractors c ON w.contractor_id = c.id
          WHERE w.id = ?";
$row = db_single($conn, $query, 'i', [$workmanId]);

if (!$row) {
    sendJson(['success' => false, 'message' => 'Worker not found']);
}

if ($row['status'] !== 'temporary_issued' && $row['status'] !== 'acc_generated') {
    sendJson(['success' => false, 'message' => 'Worker is not in a valid state for ACC generation (Current: ' . $row['status'] . ')']);
}

$appId = $row['application_no'];

clms_db_begin_transaction($conn);
try {
    // Generate ACC Number if not already present
    $acc = $row['acc_number'];
    if (empty($acc)) {
        $acc = generateEightDigitAcc($conn, $row['id']);
    }
    $accSql = clms_db_real_escape_string($conn, $acc);
    
    // 1. Update workmen table: status, acc numbers, and reset biometric status to pending
    $updateWorker = db_execute($conn, 
        "UPDATE workmen SET acc_number = ?, acc_card_number = ?, status = 'acc_generated', biometric_status = 'pending' WHERE id = ?", 
        "ssi", [$acc, $acc, $workmanId]
    );
    
    if (!$updateWorker) throw new Exception("Failed to update workman status");

    // 2. Update gate_passes table
    db_execute($conn, 
        "UPDATE gate_passes SET acc_card_number = ? WHERE application_no = ? AND workman_id = ?", 
        "ssi", [$acc, $appId, $workmanId]
    );
    
    // 3. Update attendance map
    clms_db_query($conn, "INSERT IGNORE INTO acc_attendance_map (acc_number, worker_id) VALUES ('$accSql', " . (int)$workmanId . ")");
    
    // 4. Update Application Status to acc_generated (via WorkflowEngine to ensure consistency)
    // We use setStatus instead of performAction because performAction usually moves the whole application
    WorkflowEngine::setStatus($conn, $appId, 'acc_generated', $userRole, $userId, "ACC Generated for worker: " . $row['name']);

    // 5. SAP SYNC
    $aadhaar = $row['aadhaar'] ?? $row['aadhaar_no'] ?? '';
    $contractor = $row['contractor_name'] ?? '';
    $department = $row['work_awarding_department'] ?? '';
    SapDemo::syncWorker($conn, $acc, $row['name'], $aadhaar, $contractor, $department);
    
    // 6. Queue SAP Sync Log
    $payloadJson = json_encode(['workman_id' => $workmanId, 'acc_number' => $acc]);
    db_execute($conn, 
        "INSERT INTO sap_sync_queue (entity_type, entity_id, action, payload) VALUES (?, ?, ?, ?)", 
        "ssss", ['WORKMAN', $appId, 'ACC_GENERATED', $payloadJson]
    );
    
    clms_db_commit($conn);
    
    sendJson(['success' => true, 'message' => 'ACC ' . $acc . ' generated and status updated to ACC_GENERATED', 'acc_number' => $acc]);
} catch (Exception $e) {
    clms_db_rollback($conn);
    sendJson(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
