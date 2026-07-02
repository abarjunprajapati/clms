<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user', 'super_admin']);
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/training_flow.php';
require_once __DIR__ . '/../helpers.php';

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

function safetyEnrollmentSendApprovalEmails($conn, array $approvedWorkers) {
    if (empty($approvedWorkers) || !function_exists('sendEmailNotification')) {
        return ['attempted' => 0, 'sent' => 0, 'failed' => 0];
    }

    $byContractor = [];
    foreach ($approvedWorkers as $worker) {
        $contractorId = (int)($worker['contractor_id'] ?? 0);
        if ($contractorId <= 0) {
            continue;
        }
        if (!isset($byContractor[$contractorId])) {
            $byContractor[$contractorId] = [];
        }
        $byContractor[$contractorId][] = $worker;
    }

    $summary = ['attempted' => 0, 'sent' => 0, 'failed' => 0];
    foreach ($byContractor as $contractorId => $workers) {
        $contractor = db_single(
            $conn,
            "SELECT c.id, c.contractor_name, c.vendor_name, c.vendor_code,
                    c.email AS contractor_email, c.email_address,
                    u.email AS user_email, u.name AS user_name,
                    svm.email_address AS sap_email_address,
                    svm.vendor_name AS sap_vendor_name
             FROM contractors c
             LEFT JOIN users u ON u.id = c.user_id OR u.contractor_id = c.vendor_code
             LEFT JOIN sap_vendor_master svm ON TRIM(svm.vendor_code) = TRIM(c.vendor_code)
             WHERE c.id = ? OR TRIM(c.vendor_code) = TRIM(?)
             LIMIT 1",
            'is',
            [$contractorId, (string)$contractorId]
        );

        if (!$contractor && safetyEnrollmentTableExists($conn, 'sap_vendor_master')) {
            $contractor = db_single(
                $conn,
                "SELECT NULL AS id, vendor_name AS contractor_name, vendor_name,
                        vendor_code, NULL AS contractor_email, NULL AS email_address,
                        NULL AS user_email, NULL AS user_name,
                        email_address AS sap_email_address,
                        vendor_name AS sap_vendor_name
                 FROM sap_vendor_master
                 WHERE TRIM(vendor_code) = TRIM(?)
                 LIMIT 1",
                's',
                [(string)$contractorId]
            );
        }

        if (!$contractor) {
            error_log('[SAFETY_ENROLLMENT_EMAIL] Contractor lookup failed for contractor_id/vendor_code=' . $contractorId);
            continue;
        }

        $recipientEmail = '';
        foreach ([$contractor['sap_email_address'] ?? '', $contractor['contractor_email'] ?? '', $contractor['email_address'] ?? '', $contractor['user_email'] ?? ''] as $candidate) {
            $candidate = trim((string)$candidate);
            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                $recipientEmail = $candidate;
                break;
            }
        }

        if ($recipientEmail === '') {
            error_log('[SAFETY_ENROLLMENT_EMAIL] No valid contractor email for contractor_id=' . $contractorId);
            continue;
        }

        $contractorName = trim((string)($contractor['contractor_name'] ?: ($contractor['vendor_name'] ?: ($contractor['user_name'] ?: 'Contractor'))));
        $vendorCode = trim((string)($contractor['vendor_code'] ?? ''));
        $workerLines = [];
        foreach ($workers as $worker) {
            $workerName = trim((string)($worker['name'] ?? 'Worker'));
            $workerRef = trim((string)($worker['temp_id'] ?? ''));
            if ($workerRef === '') {
                $workerRef = 'W-' . (int)($worker['id'] ?? 0);
            }
            $workerLines[] = '- ' . $workerName . ' (' . $workerRef . ')';
        }

        $subject = 'CLMS: Workmen Enrollment Approved by Safety';
        $message = "Dear {$contractorName},\n\n"
            . "Safety Department has approved the enrollment for the following workmen:\n\n"
            . implode("\n", $workerLines) . "\n\n"
            . "These workmen are now released for safety training scheduling in CLMS.\n";
        if ($vendorCode !== '') {
            $message .= "\nVendor Code: {$vendorCode}\n";
        }
        $message .= "\nRegards,\nCLMS";

        $summary['attempted']++;
        try {
            $result = sendEmailNotification($recipientEmail, $subject, $message, 'safety_enrollment_approved', $contractorName);
            if (!empty($result['success'])) {
                $summary['sent']++;
            } else {
                $summary['failed']++;
                error_log('[SAFETY_ENROLLMENT_EMAIL] ' . ($result['message'] ?? 'Email send failed'));
            }
        } catch (Throwable $emailError) {
            $summary['failed']++;
            error_log('[SAFETY_ENROLLMENT_EMAIL] ' . $emailError->getMessage());
        }
    }

    return $summary;
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

    $workmanIds = [];
    if (isset($input['workman_ids']) && is_array($input['workman_ids'])) {
        foreach ($input['workman_ids'] as $id) {
            $workmanIds[] = (int)$id;
        }
    } elseif (isset($input['workman_id'])) {
        $workmanIds[] = (int)$input['workman_id'];
    }

    $decision = strtolower(trim((string)($input['decision'] ?? '')));
    $remarks = trim((string)($input['remarks'] ?? ''));
    $reviewerId = (int)($_SESSION['user_id'] ?? 0);

    if (empty($workmanIds) || !in_array($decision, ['approved', 'rejected'], true)) {
        safetyEnrollmentJson(['success' => false, 'message' => 'Worker and decision are required.'], 422);
    }
    if ($decision === 'rejected' && $remarks === '') {
        safetyEnrollmentJson(['success' => false, 'message' => 'Rejection remarks are required for contractor correction.'], 422);
    }

    clms_training_ensure_schema($conn);

    $conn->begin_transaction();
    $processedCount = 0;
    $approvedEmailWorkers = [];

    foreach ($workmanIds as $workmanId) {
        $worker = db_single(
            $conn,
            "SELECT w.id, w.temp_id, w.name, w.contractor_id, w.execution_training_status,
                    COALESCE(w.safety_enrollment_status, 'pending') AS safety_enrollment_status
             FROM workmen w
             WHERE w.id = ?
             LIMIT 1",
            'i',
            [$workmanId]
        );
        if (!$worker) {
            continue; // Skip if worker not found
        }
        if (strtolower((string)$worker['execution_training_status']) !== 'approved') {
            continue; // Skip if EO approval is missing
        }
        if (strtolower((string)$worker['safety_enrollment_status']) === 'approved') {
            continue; // Skip if already approved
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
            continue; // Skip if no pending request
        }

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
        if ($decision === 'approved') {
            $approvedEmailWorkers[] = [
                'id' => (int)$worker['id'],
                'temp_id' => (string)($worker['temp_id'] ?? ''),
                'name' => (string)($worker['name'] ?? 'Worker'),
                'contractor_id' => (int)$worker['contractor_id'],
            ];
        }
        $processedCount++;
    }

    if ($processedCount === 0) {
        $conn->rollback();
        safetyEnrollmentJson(['success' => false, 'message' => 'No eligible pending enrollments could be processed.'], 400);
    }

    $conn->commit();
    $emailSummary = $decision === 'approved'
        ? safetyEnrollmentSendApprovalEmails($conn, $approvedEmailWorkers)
        : ['attempted' => 0, 'sent' => 0, 'failed' => 0];

    $message = $decision === 'approved'
        ? "{$processedCount} enrollment(s) approved by Safety Department and released for training scheduling."
        : "{$processedCount} enrollment(s) rejected and returned to Contractor for correction/resubmission.";
    if ($decision === 'approved') {
        if ($emailSummary['sent'] > 0) {
            $message .= " Email sent to {$emailSummary['sent']} contractor(s).";
        } elseif ($emailSummary['attempted'] > 0) {
            $message .= " Email attempted; please check notification logs for delivery status.";
        }
    }

    safetyEnrollmentJson([
        'success' => true,
        'message' => $message,
        'email' => $emailSummary,
    ]);
} catch (Throwable $e) {
    if (isset($conn) && method_exists($conn, 'rollback')) {
        @$conn->rollback();
    }
    error_log('[SAFETY_ENROLLMENT_REVIEW] ' . $e->getMessage());
    safetyEnrollmentJson(['success' => false, 'message' => 'Safety enrollment action failed on server.'], 500);
}
