<?php
session_start();
require_once 'json_error_handler.php';
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/workflow_helpers.php';

header('Content-Type: application/json; charset=utf-8');

try {
    workflow_ensure_tables($conn);

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        throw new Exception('Invalid JSON body');
    }

    $application_id = trim($data['application_id'] ?? $data['ref_id'] ?? '');
    $status = trim($data['status'] ?? '');
    $remarks = trim($data['remarks'] ?? '');

    if ($application_id === '' || !in_array($status, ['approved', 'rejected'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid input']);
        exit;
    }

    workflow_seed_application($conn, $application_id);
    $user = $_SESSION['user_name'] ?? 'System';

    if ($status === 'approved') {
        $stmt = $conn->prepare("
            UPDATE application_workflow
            SET final_status = 'approved',
                overall_status = 'approved',
                current_stage = 'completed',
                remarks = ?
            WHERE application_id = ?
        ");
        $message = 'Final approval complete; permanent pass generated';
        $action = 'final_approved';
    } else {
        $stmt = $conn->prepare("
            UPDATE application_workflow
            SET final_status = 'rejected',
                overall_status = 'rejected',
                remarks = ?
            WHERE application_id = ?
        ");
        $message = 'Application rejected at final approval';
        $action = 'final_rejected';
    }

    $stmt->bind_param('ss', $remarks, $application_id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    $passes_created = 0;
    if ($status === 'approved') {
        $passes_created = workflow_create_permanent_passes($conn, $application_id);
    }

    workflow_log($conn, $application_id, $remarks ?: $message, $user, $action);

    echo json_encode([
        'success' => true,
        'message' => $message,
        'application_id' => $application_id,
        'current_stage' => $status === 'approved' ? 'completed' : 'final',
        'updated_rows' => $affected,
        'passes_created' => $passes_created
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

