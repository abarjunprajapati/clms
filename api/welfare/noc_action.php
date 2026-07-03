<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../api_helper.php';
require_once __DIR__ . '/../../include/session.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['welfare_user', 'admin', 'super_admin'])) {
    sendResponse(false, [], 'Unauthorized access.');
}

$data = getApiInput();
$request_id = (int)($data['request_id'] ?? 0);
$action = $data['action'] ?? ''; // 'approve', 'reject', 'force_release'
$welfare_id = $_SESSION['user_id'];

if (!$request_id || !in_array($action, ['approve', 'reject', 'force_release'])) {
    sendResponse(false, [], 'Invalid input.');
}

try {
    // Automatically fix missing schema if needed (suppress error if column already exists)
    @$conn->query("ALTER TABLE noc_requests ADD COLUMN approved_by INT NULL");
    
    $conn->begin_transaction();
    
    $stmt = $conn->prepare("SELECT id, workman_id, to_contractor_id, noc_status FROM noc_requests WHERE id = ?");
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    
    if (!$req) {
        sendResponse(false, [], 'NOC request not found.');
    }
    
    $workman_id = $req['workman_id'];
    $new_contractor_user_id = $req['to_contractor_id'];
    
    // Convert to actual contractors.id
    $c_stmt = $conn->prepare("SELECT id FROM contractors WHERE user_id = ?");
    if (!$c_stmt) throw new Exception("Prepare failed (c_stmt): " . $conn->error);
    $c_stmt->bind_param("i", $new_contractor_user_id);
    $c_stmt->execute();
    $c_res = $c_stmt->get_result()->fetch_assoc();
    
    if (!$c_res) {
        throw new Exception("Contractor profile not found for the requesting user.");
    }
    $new_contractor_id = $c_res['id'];
    
    if ($action === 'force_release') {
        // Welfare officer force releases workman to common pool
        // Requirements: "If existing contractor is not willing to approve NOC, Welfare Officer shall have authority to forcefully release workman to common pool."
        $upd = $conn->prepare("UPDATE noc_requests SET noc_status = 'forcefully_released', approved_by = ? WHERE id = ?");
        if (!$upd) throw new Exception("Prepare failed (upd force_release): " . $conn->error);
        $upd->bind_param("ii", $welfare_id, $request_id);
        $upd->execute();
        
        // Update workmen table to place in common pool
        $w_upd = $conn->prepare("UPDATE workmen SET is_in_common_pool = 1, contractor_id = NULL, noc_issued_at = CURRENT_TIMESTAMP WHERE id = ?");
        if (!$w_upd) throw new Exception("Prepare failed (w_upd force_release): " . $conn->error);
        $w_upd->bind_param("i", $workman_id);
        $w_upd->execute();
        
        $msg = "Workman forcefully released to Common Pool.";
        
    } elseif ($action === 'approve') {
        // Welfare approves the company change request
        if (!in_array($req['noc_status'], ['approved_by_contractor', 'forcefully_released', 'pending'])) {
            sendResponse(false, [], 'NOC cannot be approved in its current state.');
        }
        
        $upd = $conn->prepare("UPDATE noc_requests SET noc_status = 'approved_by_welfare', approved_by = ? WHERE id = ?");
        if (!$upd) throw new Exception("Prepare failed (upd approve): " . $conn->error);
        $upd->bind_param("ii", $welfare_id, $request_id);
        $upd->execute();
        
        // Assign workman to new contractor
        $w_upd = $conn->prepare("UPDATE workmen SET contractor_id = ?, is_in_common_pool = 0, noc_issued_at = NULL WHERE id = ?");
        if (!$w_upd) throw new Exception("Prepare failed (w_upd approve): " . $conn->error);
        $w_upd->bind_param("ii", $new_contractor_id, $workman_id);
        $w_upd->execute();
        
        // Mock SAP update
        $sap_log = $conn->prepare("INSERT INTO system_error_logs (error_type, error_message, file_name) VALUES ('SAP_INTEGRATION', ?, 'welfare/noc_action.php')");
        if (!$sap_log) {
            // If the table doesn't exist, create it first
            $conn->query("CREATE TABLE IF NOT EXISTS system_error_logs (id INT AUTO_INCREMENT PRIMARY KEY, error_type VARCHAR(50), error_message TEXT, file_name VARCHAR(100), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $sap_log = $conn->prepare("INSERT INTO system_error_logs (error_type, error_message, file_name) VALUES ('SAP_INTEGRATION', ?, 'welfare/noc_action.php')");
            if (!$sap_log) throw new Exception("Prepare failed (sap_log): " . $conn->error);
        }
        $msg_sap = "SAP Company Change: Workman ID $workman_id moved to Contractor ID $new_contractor_id";
        $sap_log->bind_param("s", $msg_sap);
        $sap_log->execute();
        
        $msg = "Company change approved successfully.";
        
    } elseif ($action === 'reject') {
        $upd = $conn->prepare("UPDATE noc_requests SET noc_status = 'rejected_by_welfare', approved_by = ? WHERE id = ?");
        if (!$upd) throw new Exception("Prepare failed (upd reject): " . $conn->error);
        $upd->bind_param("ii", $welfare_id, $request_id);
        $upd->execute();
        
        $msg = "Company change rejected.";
    }

    $conn->commit();
    sendResponse(true, [], $msg);

} catch (\Throwable $e) {
    if (isset($conn)) $conn->rollback();
    sendResponse(false, [], 'Error: ' . $e->getMessage());
}
