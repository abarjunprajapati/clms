<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../api_helper.php';
require_once __DIR__ . '/../../include/session.php';

// Only Welfare Users (or admins)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['welfare_user', 'admin', 'super_admin'])) {
    sendJson(403, 'Unauthorized access.');
}

$data = getJsonInput();
$action = $data['action'] ?? ''; // 'block' or 'unblock'
$contractor_id = (int)($data['contractor_id'] ?? 0);
$reason = trim((string)($data['reason'] ?? ''));

if (!$contractor_id) {
    sendJson(400, 'Invalid contractor ID.');
}

if ($action === 'block' && empty($reason)) {
    sendJson(400, 'Reason is required for blocking.');
}

try {
    $conn->begin_transaction();

    if ($action === 'block') {
        // Block the contractor
        $stmt = $conn->prepare("UPDATE users SET block_status = 'blocked_welfare', block_reason = ? WHERE id = ? AND role = 'contractor'");
        $stmt->bind_param("si", $reason, $contractor_id);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Contractor not found or already blocked/not a contractor.");
        }

        // Block all workmen associated with this contractor
        $workmen_reason = "Contractor Blocked: " . $reason;
        $welfare_id = $_SESSION['user_id'];
        $block_time = date('Y-m-d H:i:s');
        
        $w_stmt = $conn->prepare("UPDATE workmen SET block_status = 'blocked_contractor', block_reason = ?, blocked_by = ?, blocked_at = ? WHERE contractor_id = ? AND block_status = 'none'");
        $w_stmt->bind_param("sisi", $workmen_reason, $welfare_id, $block_time, $contractor_id);
        $w_stmt->execute();

        // Simulate SAP and Attendance System integration
        $sap_log = $conn->prepare("INSERT INTO system_error_logs (error_type, error_message, file_name) VALUES ('SAP_INTEGRATION', ?, 'block_contractor.php')");
        $msg = "SAP & Attendance Blocked for Contractor ID $contractor_id and their Workmen due to: $reason";
        $sap_log->bind_param("s", $msg);
        $sap_log->execute();
        
        $message = "Contractor and associated workmen blocked successfully.";
        
    } elseif ($action === 'unblock') {
        // Unblock contractor
        $stmt = $conn->prepare("UPDATE users SET block_status = 'none', block_reason = NULL WHERE id = ?");
        $stmt->bind_param("i", $contractor_id);
        $stmt->execute();
        
        // Unblock their workmen that were blocked because of the contractor block
        // (Assuming we only unblock those who have 'blocked_contractor')
        $w_stmt = $conn->prepare("UPDATE workmen SET block_status = 'none', block_reason = NULL, blocked_by = NULL, blocked_at = NULL WHERE contractor_id = ? AND block_status = 'blocked_contractor'");
        $w_stmt->bind_param("i", $contractor_id);
        $w_stmt->execute();
        
        // Simulate SAP integration
        $sap_log = $conn->prepare("INSERT INTO system_error_logs (error_type, error_message, file_name) VALUES ('SAP_INTEGRATION', ?, 'block_contractor.php')");
        $msg = "SAP & Attendance Unblocked for Contractor ID $contractor_id";
        $sap_log->bind_param("s", $msg);
        $sap_log->execute();
        
        $message = "Contractor and associated workmen unblocked successfully.";
    } else {
        throw new Exception("Invalid action.");
    }

    $conn->commit();
    sendJson(200, $message);

} catch (Exception $e) {
    $conn->rollback();
    sendJson(500, 'Error: ' . $e->getMessage());
}
