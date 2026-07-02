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
    $conn->begin_transaction();
    
    $stmt = $conn->prepare("SELECT id, workman_id, to_contractor_id, noc_status FROM noc_requests WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    
    if (!$req) {
        sendResponse(false, [], 'NOC request not found.');
    }
    
    $workman_id = $req['workman_id'];
    $new_contractor = $req['to_contractor_id'];
    
    if ($action === 'force_release') {
        // Welfare officer force releases workman to common pool
        // Requirements: "If existing contractor is not willing to approve NOC, Welfare Officer shall have authority to forcefully release workman to common pool."
        $upd = $conn->prepare("UPDATE noc_requests SET noc_status = 'forcefully_released', approved_by = ? WHERE id = ?");
        $upd->bind_param("ii", $welfare_id, $request_id);
        $upd->execute();
        
        // Update workmen table to place in common pool
        $w_upd = $conn->prepare("UPDATE workmen SET is_in_common_pool = 1, contractor_id = NULL, noc_issued_at = CURRENT_TIMESTAMP WHERE id = ?");
        $w_upd->bind_param("i", $workman_id);
        $w_upd->execute();
        
        $msg = "Workman forcefully released to Common Pool.";
        
    } elseif ($action === 'approve') {
        // Welfare approves the company change request
        if (!in_array($req['noc_status'], ['approved_by_contractor', 'forcefully_released', 'pending'])) {
            sendResponse(false, [], 'NOC cannot be approved in its current state.');
        }
        
        $upd = $conn->prepare("UPDATE noc_requests SET noc_status = 'approved_by_welfare', approved_by = ? WHERE id = ?");
        $upd->bind_param("ii", $welfare_id, $request_id);
        $upd->execute();
        
        // Assign workman to new contractor
        $w_upd = $conn->prepare("UPDATE workmen SET contractor_id = ?, is_in_common_pool = 0, noc_issued_at = NULL WHERE id = ?");
        $w_upd->bind_param("ii", $new_contractor, $workman_id);
        $w_upd->execute();
        
        // Mock SAP update
        $sap_log = $conn->prepare("INSERT INTO system_error_logs (error_type, error_message, file_name) VALUES ('SAP_INTEGRATION', ?, 'welfare/noc_action.php')");
        $msg_sap = "SAP Company Change: Workman ID $workman_id moved to Contractor ID $new_contractor";
        $sap_log->bind_param("s", $msg_sap);
        $sap_log->execute();
        
        $msg = "Company change approved successfully.";
        
    } elseif ($action === 'reject') {
        $upd = $conn->prepare("UPDATE noc_requests SET noc_status = 'rejected_by_welfare', approved_by = ? WHERE id = ?");
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
