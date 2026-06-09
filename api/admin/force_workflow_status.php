<?php
/** Force Workflow Status — with approval safeguard */
require_once __DIR__ . '/admin_middleware.php';
$admin = requireAdmin();
$input = getJsonInput();

$appId = $input['app_id'] ?? 0;
$newStatus = $input['new_status'] ?? '';
$reason = $input['reason'] ?? 'Super Admin Override';
$confirmOverride = $input['confirm_override'] ?? false;

if (!$appId || !$newStatus) jsonError('Application ID and new status required');

// Check approval safeguard
$safeguard = checkApprovalSafeguard($conn, 'applications', $appId, 'current_status');
if ($safeguard['protected'] && !$confirmOverride) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'requires_override' => true,
        'message' => $safeguard['message'],
        'current_status' => $safeguard['current_status']
    ]);
    exit;
}

// Get old data for audit
$oldApp = db_single($conn, "SELECT * FROM applications WHERE id=?", 'i', [$appId]);
if (!$oldApp) jsonError('Application not found');

$oldStatus = $oldApp['current_status'];

// Update applications table
db_execute($conn, "UPDATE applications SET current_status=? WHERE id=?", 'si', [$newStatus, $appId]);

// Update application_workflow table if exists
$appNo = $oldApp['application_no'];
if ($appNo) {
    db_execute($conn, "UPDATE application_workflow SET current_stage=?, overall_status=?, remarks=? WHERE application_id=?", 'ssss', [$newStatus, $newStatus, "OVERRIDE by Super Admin: $reason", $appNo]);
}

// Log with high severity
$severity = $safeguard['protected'] ? 'critical' : 'warning';
logAdminActivity($conn, 'workflow_force_override', 'applications', $appId, 
    ['status' => $oldStatus], 
    ['status' => $newStatus, 'reason' => $reason], 
    $severity
);

jsonSuccess("Application #$appId status changed from '$oldStatus' to '$newStatus'", [
    'old_status' => $oldStatus,
    'new_status' => $newStatus,
    'was_protected' => $safeguard['protected']
]);
