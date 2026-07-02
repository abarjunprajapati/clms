<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../api_helper.php';
require_once __DIR__ . '/../../include/session.php';

// Only Welfare Users (or admins)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['welfare_user', 'admin', 'super_admin'])) {
    sendJson(403, 'Unauthorized access.');
}

$data = getJsonInput();
$action = $data['action'] ?? ''; // 'permanent_block', 'temporary_block', 'unblock', 'mark_to_be_unblocked'
$workman_id = (int)($data['workman_id'] ?? 0);
$reason = trim((string)($data['reason'] ?? ''));
$welfare_id = $_SESSION['user_id'];

if (!$workman_id) {
    sendJson(400, 'Invalid workman ID.');
}

if (in_array($action, ['permanent_block', 'temporary_block', 'mark_to_be_unblocked']) && empty($reason)) {
    sendJson(400, 'Reason is required.');
}

try {
    $conn->begin_transaction();
    $block_time = date('Y-m-d H:i:s');

    if ($action === 'permanent_block') {
        $stmt = $conn->prepare("UPDATE workmen SET block_status = 'blocked_permanent_welfare', block_reason = ?, blocked_by = ?, blocked_at = ? WHERE id = ?");
        $stmt->bind_param("sisi", $reason, $welfare_id, $block_time, $workman_id);
        $stmt->execute();
        
        $msg = "SAP & Attendance Permanently Blocked for Workman ID $workman_id. Reason: $reason";
        
    } elseif ($action === 'temporary_block') {
        // Can only temporarily block if not permanently blocked
        $stmt = $conn->prepare("UPDATE workmen SET block_status = 'blocked_temporary_welfare', block_reason = ?, blocked_by = ?, blocked_at = ? WHERE id = ? AND block_status != 'blocked_permanent_welfare'");
        $stmt->bind_param("sisi", $reason, $welfare_id, $block_time, $workman_id);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Workman is already permanently blocked or not found.");
        }
        
        $msg = "SAP & Attendance Temporarily Blocked for Workman ID $workman_id. Reason: $reason";
        
    } elseif ($action === 'mark_to_be_unblocked') {
        // Marks a temporarily blocked worker to be unblocked
        $stmt = $conn->prepare("UPDATE workmen SET to_be_unblocked = 1, intended_unblock_reason = ? WHERE id = ? AND block_status = 'blocked_temporary_welfare'");
        $stmt->bind_param("si", $reason, $workman_id);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Workman is not temporarily blocked.");
        }
        $msg = "Workman $workman_id marked to be unblocked. Reason: $reason";
        
    } elseif ($action === 'unblock') {
        // Only unblock temporarily blocked
        $stmt = $conn->prepare("UPDATE workmen SET block_status = 'none', block_reason = NULL, blocked_by = NULL, blocked_at = NULL, to_be_unblocked = 0, intended_unblock_reason = NULL WHERE id = ? AND block_status = 'blocked_temporary_welfare'");
        $stmt->bind_param("i", $workman_id);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Workman is not temporarily blocked or cannot be unblocked.");
        }
        $msg = "SAP & Attendance Unblocked for Workman ID $workman_id.";
        
    } else {
        throw new Exception("Invalid action.");
    }

    // Mock SAP update for block/unblock (not for mark_to_be_unblocked)
    if ($action !== 'mark_to_be_unblocked') {
        $sap_log = $conn->prepare("INSERT INTO system_error_logs (error_type, error_message, file_name) VALUES ('SAP_INTEGRATION', ?, 'welfare/block_workman.php')");
        $sap_log->bind_param("s", $msg);
        $sap_log->execute();
    }

    $conn->commit();
    sendJson(200, "Action '$action' completed successfully.");

} catch (Exception $e) {
    $conn->rollback();
    sendJson(500, 'Error: ' . $e->getMessage());
}
