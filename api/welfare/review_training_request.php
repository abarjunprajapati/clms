<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin', 'welfare_user']);
include __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../helpers.php';

header('Content-Type: application/json; charset=utf-8');

function welfareTrainingReviewJson($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function welfareTrainingReviewColumnExists($conn, $table, $column) {
    $safeTable = str_replace('`', '``', $table);
    $column = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$column'");
    return $res && mysqli_num_rows($res) > 0;
}

function welfareTrainingReviewEnsureColumn($conn, $table, $column, $definition) {
    if (welfareTrainingReviewColumnExists($conn, $table, $column)) return;
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = str_replace('`', '``', $column);
    @mysqli_query($conn, "ALTER TABLE `$safeTable` ADD COLUMN `$safeColumn` $definition");
}

function welfareTrainingReviewEnsureSchema($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_requests (
        id INT NOT NULL AUTO_INCREMENT,
        workman_id INT NOT NULL,
        contractor_id INT NOT NULL,
        training_type VARCHAR(100) NULL,
        requested_date DATE NULL,
        preferred_date DATE NULL,
        preferred_shift VARCHAR(20) DEFAULT 'morning',
        remarks TEXT NULL,
        source VARCHAR(30) NULL,
        requested_by INT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    foreach ([
        'status' => "VARCHAR(50) DEFAULT 'pending'",
        'welfare_remarks' => 'TEXT NULL',
        'welfare_reviewed_by' => 'INT NULL',
        'welfare_reviewed_at' => 'DATETIME NULL',
        'updated_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    ] as $column => $definition) {
        welfareTrainingReviewEnsureColumn($conn, 'training_requests', $column, $definition);
    }
    @mysqli_query($conn, "ALTER TABLE training_requests MODIFY COLUMN status VARCHAR(50) DEFAULT 'pending'");

    foreach ([
        'training_status' => "VARCHAR(50) DEFAULT 'pending'",
        'safety_training_status' => "VARCHAR(50) DEFAULT 'PENDING_TRAINING'",
        'eligibility_status' => "VARCHAR(50) DEFAULT 'NOT ELIGIBLE'",
        'execution_training_status' => "VARCHAR(30) DEFAULT 'pending'",
        'execution_training_remarks' => 'TEXT NULL',
        'updated_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    ] as $column => $definition) {
        welfareTrainingReviewEnsureColumn($conn, 'workmen', $column, $definition);
    }
    @mysqli_query($conn, "ALTER TABLE workmen MODIFY COLUMN training_status VARCHAR(50) DEFAULT 'pending'");
    @mysqli_query($conn, "ALTER TABLE workmen MODIFY COLUMN safety_training_status VARCHAR(50) DEFAULT 'PENDING_TRAINING'");
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        welfareTrainingReviewJson(['success' => false, 'message' => 'Only POST allowed.'], 405);
    }

    welfareTrainingReviewEnsureSchema($conn);

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        welfareTrainingReviewJson(['success' => false, 'message' => 'Invalid request payload.'], 400);
    }

    $requestId = (int)($input['request_id'] ?? 0);
    $decision = strtolower(trim((string)($input['decision'] ?? '')));
    $remarks = trim((string)($input['remarks'] ?? ''));

    if (!$requestId || !in_array($decision, ['approve', 'reject'], true)) {
        welfareTrainingReviewJson(['success' => false, 'message' => 'Request and decision are required.'], 400);
    }
    if ($decision === 'reject' && $remarks === '') {
        welfareTrainingReviewJson(['success' => false, 'message' => 'Reject reason required.'], 400);
    }

    $request = db_single(
        $conn,
        "SELECT tr.id, tr.workman_id, tr.contractor_id, tr.status, w.name AS worker_name,
                w.temp_id, w.email AS worker_email,
                c.contractor_name, c.email AS contractor_email,
                u.name AS contractor_user_name, u.email AS contractor_user_email
         FROM training_requests tr
         JOIN workmen w ON w.id = tr.workman_id
         LEFT JOIN contractors c ON c.id = tr.contractor_id
         LEFT JOIN users u ON u.id = c.user_id
         WHERE tr.id = ? LIMIT 1",
        'i',
        [$requestId]
    );
    if (!$request) {
        welfareTrainingReviewJson(['success' => false, 'message' => 'Training request not found.'], 404);
    }
    if (($request['status'] ?? '') !== 'welfare_pending') {
        welfareTrainingReviewJson(['success' => false, 'message' => 'This request is not pending with Welfare.'], 409);
    }

    if ($decision === 'reject') {
        db_execute(
            $conn,
            "UPDATE training_requests
             SET status = 'welfare_rejected',
                 welfare_remarks = ?,
                 welfare_reviewed_by = ?,
                 welfare_reviewed_at = NOW(),
                 updated_at = NOW()
             WHERE workman_id = ?
               AND LOWER(TRIM(COALESCE(status, ''))) IN ('', 'pending', 'welfare_pending')",
            'sii',
            [$remarks, (int)($_SESSION['user_id'] ?? 0), (int)$request['workman_id']]
        );
        db_execute(
            $conn,
            "UPDATE workmen
             SET training_status = 'correction_required',
                 safety_training_status = 'SAFETY_REJECTED',
                 eligibility_status = 'NOT ELIGIBLE',
                 execution_training_remarks = ?,
                 updated_at = NOW()
             WHERE id = ?",
            'si',
            ['Safety Department rejected. Return to Contractor for Correction / Resubmission. Reason: ' . $remarks, (int)$request['workman_id']]
        );
    } else {
        db_execute(
            $conn,
            "UPDATE training_requests
             SET status = 'enrollment_approved',
                 welfare_remarks = ?,
                 welfare_reviewed_by = ?,
                 welfare_reviewed_at = NOW(),
                 updated_at = NOW()
             WHERE id = ?",
            'sii',
            [$remarks, (int)($_SESSION['user_id'] ?? 0), $requestId]
        );
        db_execute(
            $conn,
            "UPDATE workmen
             SET status = 'active',
                 training_status = 'approved',
                 safety_training_status = 'ENROLLMENT_APPROVED',
                 eligibility_status = 'ELIGIBLE',
                 updated_at = NOW()
             WHERE id = ?",
            'i',
            [(int)$request['workman_id']]
        );

        $to = trim((string)($request['contractor_user_email'] ?: ($request['contractor_email'] ?: $request['worker_email'])));
        if ($to !== '' && function_exists('sendEmailNotification')) {
            $workerName = trim((string)($request['worker_name'] ?? 'Worker'));
            $tempId = trim((string)($request['temp_id'] ?? ''));
            $message = "Dear Contractor,\n\n"
                . "Worker enrollment has been approved by the Safety Department.\n"
                . "Worker: {$workerName}\n"
                . ($tempId !== '' ? "Temporary ID: {$tempId}\n" : '')
                . "\nEnrollment Approved.\n\nThis is an automated confirmation mail.";
            @sendEmailNotification($to, 'CLMS Enrollment Approved', $message, 'worker_enrollment_approved', $request['contractor_user_name'] ?? 'Contractor');
        }
    }

    welfareTrainingReviewJson([
        'success' => true,
        'message' => $decision === 'approve'
            ? 'Safety Department approved. Confirmation mail sent to contractor. Enrollment Approved.'
            : 'Safety Department rejected. Returned to Contractor for Correction / Resubmission.',
    ]);
} catch (Throwable $e) {
    error_log('[WELFARE_TRAINING_REVIEW] ' . $e->getMessage());
    welfareTrainingReviewJson(['success' => false, 'message' => 'Welfare training review failed on server.'], 500);
}
