<?php
/**
 * Welfare Compliance Verification API
 * Approves/rejects ECR/ESI/KLWF uploads → triggers 'verify_compliance'
 */
require_once 'api_helper.php';
require_once '../include/config.php';
require_once 'WorkflowEngine.php';
require_once '../include/NotificationEngine.php';

header('Content-Type: application/json');

try {
    check_role(['welfare', 'welfare_admin', 'admin']);

    $input = getApiInput();
    $upload_id = (int)($input['upload_id'] ?? 0);
    $status = $input['status']; // 'verified' or 'rejected'
    $application_id = $input['application_id'] ?? null;
    $remarks = $input['remarks'] ?? '';

    if (!$upload_id) apiError('upload_id required');
    if (!in_array($status, ['verified', 'rejected'])) apiError('Invalid status');
    if (!$application_id) apiError('application_id required');

    $conn->begin_transaction();

    // Update upload status
    $action = ($status === 'verified') ? 'verify_compliance' : 'reject_compliance';
    $update_sql = "UPDATE compliance_uploads SET status = ?, rejection_reason = ?, verified_by = ?, verified_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param('ssii', $status, $remarks, $_SESSION['user_id'], $upload_id);
    $stmt->execute();

    // Trigger workflow
    $wfResult = WorkflowEngine::performAction($conn, $application_id, $action, $_SESSION['role'], $_SESSION['user_id'], $remarks);

    // Notify contractor
    $notif = new NotificationEngine($conn);
    $contractor_id = db_single($conn, "SELECT contractor_id FROM compliance_uploads WHERE id = ?", 'i', [$upload_id])['contractor_id'];
    $msg = "Compliance $status: " . ucfirst($status) . " ($remarks)";
    $notif->send($contractor_id, $msg, $status);

    $conn->commit();

    if ($wfResult['success']) {
        apiSuccess([
            'message' => "Compliance $status successfully.",
            'new_status' => $wfResult['new_status']
        ]);
    } else {
        apiError($wfResult['message']);
    }

} catch (Exception $e) {
    $conn->rollback();
    apiError($e->getMessage());
}
?>


