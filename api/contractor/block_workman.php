<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../api_helper.php';
require_once __DIR__ . '/../../include/session.php';

// Only Contractors
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'contractor') {
    sendJson(403, 'Unauthorized access.');
}

$data = getJsonInput();
$action = $data['action'] ?? ''; // 'block', 'bulk_block'
$reason = trim((string)($data['reason'] ?? ''));
$contractor_id = $_SESSION['user_id'];

if (empty($reason)) {
    sendJson(400, 'Reason is required for blocking.');
}

try {
    $conn->begin_transaction();
    
    $blocked_count = 0;
    
    if ($action === 'block') {
        $workman_id = (int)($data['workman_id'] ?? 0);
        if (!$workman_id) sendJson(400, 'Invalid workman ID.');
        
        $w_stmt = $conn->prepare("UPDATE workmen SET block_status = 'blocked_contractor', block_reason = ?, blocked_by = ?, blocked_at = ? WHERE id = ? AND contractor_id = ? AND block_status = 'none'");
        $block_time = date('Y-m-d H:i:s');
        $w_stmt->bind_param("sisii", $reason, $contractor_id, $block_time, $workman_id, $contractor_id);
        $w_stmt->execute();
        
        if ($w_stmt->affected_rows === 0) {
            throw new Exception("Workman not found or already blocked.");
        }
        $blocked_count = 1;
        
        // Mock SAP update
        $sap_log = $conn->prepare("INSERT INTO system_error_logs (error_type, error_message, file_name) VALUES ('SAP_INTEGRATION', ?, 'block_workman.php')");
        $msg = "SAP & Attendance Blocked for Workman ID $workman_id by Contractor. Reason: $reason";
        $sap_log->bind_param("s", $msg);
        $sap_log->execute();
        
    } elseif ($action === 'bulk_block') {
        $workmen_ids = $data['workmen_ids'] ?? [];
        if (!is_array($workmen_ids) || empty($workmen_ids)) {
            sendJson(400, 'No workmen selected.');
        }
        
        $ids = implode(',', array_map('intval', $workmen_ids));
        $block_time = date('Y-m-d H:i:s');
        
        $query = "UPDATE workmen SET block_status = 'blocked_contractor', block_reason = ?, blocked_by = ?, blocked_at = ? WHERE contractor_id = ? AND block_status = 'none' AND id IN ($ids)";
        $w_stmt = $conn->prepare($query);
        $w_stmt->bind_param("sisi", $reason, $contractor_id, $block_time, $contractor_id);
        $w_stmt->execute();
        
        $blocked_count = $w_stmt->affected_rows;
        
        if ($blocked_count > 0) {
            // Mock SAP update
            $sap_log = $conn->prepare("INSERT INTO system_error_logs (error_type, error_message, file_name) VALUES ('SAP_INTEGRATION', ?, 'block_workman.php')");
            $msg = "SAP & Attendance Bulk Blocked for Workmen IDs: $ids by Contractor. Reason: $reason";
            $sap_log->bind_param("s", $msg);
            $sap_log->execute();
        }
    } else {
        throw new Exception("Invalid action.");
    }

    $conn->commit();
    sendJson(200, "Successfully blocked $blocked_count workmen.");

} catch (Exception $e) {
    $conn->rollback();
    sendJson(500, 'Error: ' . $e->getMessage());
}
