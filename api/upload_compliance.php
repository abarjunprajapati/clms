<?php
/**
 * Contractor Compliance Upload API (ECR/ESI/KLWF)
 * Triggers 'upload_compliance' → compliance_pending
 */
require_once 'api_helper.php';
require_once '../include/config.php';
require_once 'WorkflowEngine.php';

header('Content-Type: application/json');

try {
    $input = getApiInput();
    $application_id = $input['application_id'] ?? null;
    $contractor_id = $_SESSION['contractor_id'] ?? 0;
    $month_year = $input['month_year'] ?? date('Y-m'); // YYYY-MM
    $uploads = $input['uploads'] ?? []; // [{type: 'ecr', file}, ...]

    if (!$application_id) apiError('application_id required');
    if (!$contractor_id) apiError('Contractor login required');
    if (empty($uploads)) apiError('At least one upload required');

    $conn->begin_transaction();

    $uploaded_files = [];
    foreach ($uploads as $upload) {
        $type = $upload['type'];
        $file = $upload['file'];
        if (!in_array($type, ['ecr', 'esi', 'klwf'])) apiError('Invalid type: ' . $type);

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $contractor_id . '_' . $type . '_' . $month_year . '.' . $ext;
        $target = '../uploads/compliance/' . $filename;

        if (!is_dir('../uploads/compliance/')) mkdir('../uploads/compliance/', 0777, true);

        if (move_uploaded_file($file['tmp_name'], $target)) {
            $stmt = $conn->prepare("
                INSERT INTO compliance_uploads (contractor_id, upload_type, file_path, month_year, status)
                VALUES (?, ?, ?, ?, 'pending')
            ");
            $stmt->bind_param('isss', $contractor_id, $type, $filename, $month_year);
            $stmt->execute();
            $uploaded_files[] = $filename;
        }
    }

    // Trigger workflow action 'upload_compliance'
    $wfResult = WorkflowEngine::performAction($conn, $application_id, 'upload_compliance', 'contractor', $contractor_id, 'Compliance uploaded for ' . $month_year);

    $conn->commit();

    if ($wfResult['success']) {
        apiSuccess([
            'message' => 'Compliance uploaded. Awaiting welfare verification.',
            'files' => $uploaded_files,
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


