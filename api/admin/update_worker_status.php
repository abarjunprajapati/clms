<?php
/** Block/Unblock Worker from Super Admin */
require_once __DIR__ . '/admin_middleware.php';
$admin = requireAdmin();
$input = getJsonInput();

$workerId = $input['worker_id'] ?? 0;
$action = $input['action'] ?? ''; // block or unblock
$reason = $input['reason'] ?? 'Super Admin action';
$confirmOverride = $input['confirm_override'] ?? false;

if (!$workerId || !in_array($action, ['block', 'unblock'])) {
    jsonError('Worker ID and valid action (block/unblock) required');
}

$worker = db_single($conn, "SELECT * FROM workmen WHERE id=?", 'i', [$workerId]);
if (!$worker) jsonError('Worker not found');

// Approval safeguard
if ($action === 'block' && in_array($worker['status'], ['active', 'permanent_issued', 'acc_generated'])) {
    if (!$confirmOverride) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'requires_override' => true,
            'message' => "Worker is currently '{$worker['status']}'. Blocking will revoke active passes. Confirm override?"
        ]);
        exit;
    }
}

$oldStatus = $worker['status'];
$newStatus = ($action === 'block') ? 'blocked' : 'active';

// Update workmen status
db_execute($conn, "UPDATE workmen SET status=? WHERE id=?", 'si', [$newStatus, $workerId]);

// Log to worker_block_history
$blockAction = ($action === 'block') ? 'permanent_block' : 'unblock';
db_execute($conn, "INSERT INTO worker_block_history (workman_id, action, reason, action_by) VALUES (?,?,?,?)", 'issi', [$workerId, $blockAction, $reason, $admin['user_id']]);

// If blocking, also deactivate gate passes
if ($action === 'block') {
    db_execute($conn, "UPDATE gate_passes SET status='cancelled' WHERE workman_id=? AND status='active'", 'i', [$workerId]);
}

$severity = ($action === 'block') ? 'critical' : 'warning';
logAdminActivity($conn, "worker_{$action}", 'workmen', $workerId, 
    ['status' => $oldStatus], 
    ['status' => $newStatus, 'reason' => $reason], 
    $severity
);

jsonSuccess("Worker #{$workerId} {$action}ed successfully", ['old_status' => $oldStatus, 'new_status' => $newStatus]);
