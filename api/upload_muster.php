<?php
/**
 * Muster Roll Upload API (Monthly Attendance)
 */
require_once 'api_helper.php';
require_once '../include/config.php';
require_once 'WorkflowEngine.php';

header('Content-Type: application/json');

try {
    $input = getApiInput();
    $application_id = $input['application_id'] ?? null;
    $month_year = $input['month_year'] ?? date('Y-m');
    $file = $input['file'] ?? null;
    $total_workers = (int)($input['total_workers'] ?? 0);
    $actual_present = (int)($input['actual_present'] ?? 0);

    if (!$application_id) apiError('application_id required');
    $contractor_id = $_SESSION['contractor_id'];

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $contractor_id . '_muster_' . $month_year . '.' . $ext;
    $target = '../uploads/muster/' . $filename;

    mkdir('../uploads/muster/', 0777, true);

    if (move_uploaded_file($file['tmp_name'], $target)) {
        $stmt = $conn->prepare("
            INSERT INTO muster_rolls (contractor_id, application_id, file_path, month_year, total_workers, actual_present, status)
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->bind_param('issiii', $contractor_id, $application_id, $filename, $month_year, $total_workers, $actual_present);
        $stmt->execute();

        // Trigger workflow if needed
        WorkflowEngine::performAction($conn, $application_id, 'upload_muster', 'contractor', $contractor_id);

        apiSuccess(['message' => 'Muster roll uploaded for verification.']);
    } else {
        apiError('File upload failed');
    }

} catch (Exception $e) {
    apiError($e->getMessage());
}
?>


