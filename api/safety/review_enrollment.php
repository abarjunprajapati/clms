<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user', 'super_admin']);
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/training_flow.php';

header('Content-Type: application/json; charset=utf-8');

function safetyEnrollmentJson($payload, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function safetyEnrollmentTableExists($conn, $table) {
    $safeTable = mysqli_real_escape_string($conn, $table);
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$safeTable'");
    return $result && mysqli_num_rows($result) > 0;
}

function safetyEnrollmentColumnExists($conn, $table, $column) {
    if (!safetyEnrollmentTableExists($conn, $table)) return false;
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$safeColumn'");
    return $result && mysqli_num_rows($result) > 0;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        safetyEnrollmentJson(['success' => false, 'message' => 'Invalid request method.'], 405);
    }
    if (!validate_csrf()) {
        safetyEnrollmentJson(['success' => false, 'message' => 'Security token expired. Please refresh the page.'], 419);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        safetyEnrollmentJson(['success' => false, 'message' => 'Invalid request payload.'], 400);
    }

    $workmanId = (int)($input['workman_id'] ?? 0);
    $decision = strtolower(trim((string)($input['decision'] ?? '')));
    $remarks = trim((string)($input['remarks'] ?? ''));
    $reviewerId = (int)($_SESSION['user_id'] ?? 0);

    if (!$workmanId || !in_array($decision, ['approved', 'rejected'], true)) {
        safetyEnrollmentJson(['success' => false, 'message' => 'Worker and decision are required.'], 422);
    }
    if ($decision === 'rejected' && $remarks === '') {
        safetyEnrollmentJson(['success' => false, 'message' => 'Rejection remarks are required for contractor correction.'], 422);
    }

    clms_training_ensure_schema($conn);

    $worker = db_single(
        $conn,
        "SELECT w.id, w.name, w.contractor_id, w.execution_training_status,
                COALESCE(w.safety_enrollment_status, 'pending') AS safety_enrollment_status
         FROM workmen w
         WHERE w.id = ?
         LIMIT 1",
        'i',
        [$workmanId]
    );
    if (!$worker) {
        safetyEnrollmentJson(['success' => false, 'message' => 'Worker not found.'], 404);
    }
    if (strtolower((string)$worker['execution_training_status']) !== 'approved') {
        safetyEnrollmentJson(['success' => false, 'message' => 'Executing Officer approval is required first.'], 409);
    }
    if (strtolower((string)$worker['safety_enrollment_status']) === 'approved') {
        safetyEnrollmentJson(['success' => false, 'message' => 'Safety enrollment is already approved.'], 409);
    }

    $request = db_single(
        $conn,
        "SELECT id, status
         FROM training_requests
         WHERE workman_id = ?
           AND LOWER(COALESCE(status, '')) IN ('pending_safety', 'welfare_pending')
         ORDER BY id DESC
         LIMIT 1",
        'i',
        [$workmanId]
    );
    if (!$request) {
        safetyEnrollmentJson(['success' => false, 'message' => 'Pending Safety approval request not found.'], 409);
    }

    $conn->begin_transaction();

    db_execute(
        $conn,
        "UPDATE workmen
         SET safety_enrollment_status = ?,
             safety_enrollment_remarks = ?,
             safety_enrollment_reviewed_by = ?,
             safety_enrollment_reviewed_at = NOW()
         WHERE id = ?",
        'ssii',
        [$decision, $remarks, $reviewerId, $workmanId]
    );

    $requestStatus = $decision === 'approved' ? 'pending' : 'safety_rejected';
    db_execute(
        $conn,
        "UPDATE training_requests
         SET status = ?, safety_remarks = ?, updated_at = NOW()
         WHERE id = ?",
        'ssi',
        [$requestStatus, $remarks, (int)$request['id']]
    );

    if (
        safetyEnrollmentTableExists($conn, 'notifications') &&
        safetyEnrollmentColumnExists($conn, 'notifications', 'user_id') &&
        safetyEnrollmentColumnExists($conn, 'notifications', 'message') &&
        safetyEnrollmentColumnExists($conn, 'notifications', 'type') &&
        safetyEnrollmentColumnExists($conn, 'notifications', 'is_read') &&
        safetyEnrollmentTableExists($conn, 'contractors') &&
        safetyEnrollmentColumnExists($conn, 'contractors', 'user_id')
    ) {
        $contractor = db_single(
            $conn,
            "SELECT user_id FROM contractors WHERE id = ? LIMIT 1",
            'i',
            [(int)$worker['contractor_id']]
        );
        $contractorUserId = (int)($contractor['user_id'] ?? 0);
        if ($contractorUserId > 0) {
            $message = $decision === 'approved'
                ? "Safety Department approved enrollment for {$worker['name']}. The worker is released for safety training scheduling."
                : "Safety Department rejected enrollment for {$worker['name']}. Please correct and resubmit. Remarks: {$remarks}";
            $type = $decision === 'approved' ? 'safety_enrollment_approved' : 'safety_enrollment_rejected';
            try {
                db_execute(
                    $conn,
                    "INSERT INTO notifications (user_id, message, type, is_read) VALUES (?, ?, ?, 0)",
                    'iss',
                    [$contractorUserId, $message, $type]
                );
            } catch (Throwable $notificationError) {
                error_log('[SAFETY_ENROLLMENT_NOTIFICATION] ' . $notificationError->getMessage());
            }
        }
    }

    $conn->commit();

    safetyEnrollmentJson([
        'success' => true,
        'message' => $decision === 'approved'
            ? 'Enrollment approved by Safety Department and released for training scheduling.'
            : 'Enrollment rejected and returned to Contractor for correction/resubmission.',
    ]);
} catch (Throwable $e) {
    if (isset($conn) && method_exists($conn, 'rollback')) {
        @$conn->rollback();
    }
    error_log('[SAFETY_ENROLLMENT_REVIEW] ' . $e->getMessage());
    safetyEnrollmentJson(['success' => false, 'message' => 'Safety enrollment action failed on server.'], 500);
}
