<?php
ob_start();
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user', 'safety', 'super_admin']);
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/safety_training_control.php';

header('Content-Type: application/json; charset=utf-8');

function syncResultJson($payload, $statusCode = 200) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        syncResultJson(['success' => false, 'message' => 'Only POST requests are allowed.'], 405);
    }

    clms_safety_ensure_control_schema($conn);
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_session_workers (
        id INT NOT NULL AUTO_INCREMENT,
        session_id INT NULL,
        workman_id INT NOT NULL,
        training_request_id INT NOT NULL,
        attendance_status VARCHAR(30) DEFAULT 'pending',
        result VARCHAR(30) DEFAULT 'pending',
        theory_score INT NULL,
        practical_score INT NULL,
        total_score INT NULL,
        remarks TEXT NULL,
        valid_till DATE NULL,
        created_at DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_training_request (training_request_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    foreach ([
        'theory_score' => 'INT NULL',
        'practical_score' => 'INT NULL',
        'total_score' => 'INT NULL',
        'external_reference' => 'VARCHAR(100) NULL',
        'remarks' => 'TEXT NULL',
        'valid_till' => 'DATE NULL',
    ] as $column => $definition) {
        clms_safety_ensure_column($conn, 'training_session_workers', $column, $definition);
    }
    clms_safety_ensure_column($conn, 'training_batch_workers', 'external_reference', 'VARCHAR(100) NULL');
    foreach ([
        'training_request_id' => 'INT NULL',
        'training_token' => 'VARCHAR(20) NULL',
        'attendance_status' => "VARCHAR(30) DEFAULT 'present'",
        'total_score' => 'INT NULL',
        'external_reference' => 'VARCHAR(100) NULL',
        'remarks' => 'TEXT NULL',
    ] as $column => $definition) {
        clms_safety_ensure_column($conn, 'training_results', $column, $definition);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) $input = $_POST;

    $token = trim((string)($input['token'] ?? $input['training_token'] ?? ''));
    $status = strtolower(trim((string)($input['status'] ?? $input['result'] ?? '')));
    $score = isset($input['score']) ? (int)$input['score'] : (isset($input['total_score']) ? (int)$input['total_score'] : null);
    $externalRef = trim((string)($input['external_reference'] ?? $input['external_ref'] ?? $input['test_reference'] ?? ''));
    $remarks = trim((string)($input['remarks'] ?? 'Synced from external safety test application.'));
    $validTill = trim((string)($input['valid_till'] ?? ''));

    if ($token === '' || !in_array($status, ['pass', 'passed', 'fail', 'failed', 'absent'], true)) {
        syncResultJson(['success' => false, 'message' => 'token and status PASS/FAIL/ABSENT are required.'], 422);
    }

    $result = in_array($status, ['pass', 'passed'], true) ? 'pass' : (in_array($status, ['fail', 'failed'], true) ? 'fail' : 'absent');
    $attendance = $result === 'absent' ? 'absent' : 'present';

    $row = db_single(
        $conn,
        "SELECT tbw.training_request_id, tbw.workman_id, tbw.attempt_no, tr.scheduled_session_id, tr.contractor_id
         FROM training_batch_workers tbw
         JOIN training_requests tr ON tr.id = tbw.training_request_id
         WHERE tbw.training_token = ? OR tbw.token_number = ?
         ORDER BY tbw.id DESC LIMIT 1",
        'ss',
        [$token, $token]
    );
    if (!$row) {
        syncResultJson(['success' => false, 'message' => 'Training token not found.'], 404);
    }

    $sessionId = (int)($row['scheduled_session_id'] ?? 0);
    $requestId = (int)$row['training_request_id'];
    $workmanId = (int)$row['workman_id'];
    $validTillValue = $validTill !== '' ? $validTill : ($result === 'pass' ? date('Y-m-d', strtotime('+1 year')) : null);

    db_execute(
        $conn,
        "INSERT INTO training_session_workers
         (session_id, workman_id, training_request_id, attendance_status, result, theory_score, practical_score, total_score, remarks, valid_till, created_at)
         VALUES (?, ?, ?, ?, ?, NULL, NULL, ?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE
             session_id = VALUES(session_id),
             attendance_status = VALUES(attendance_status),
             result = VALUES(result),
             total_score = VALUES(total_score),
             remarks = VALUES(remarks),
             valid_till = VALUES(valid_till)",
        'iiississ',
        [$sessionId ?: null, $workmanId, $requestId, $attendance, $result, $score, $remarks, $validTillValue]
    );
    if ($externalRef !== '') {
        db_execute(
            $conn,
            "UPDATE training_session_workers SET external_reference = ? WHERE training_request_id = ?",
            'si',
            [$externalRef, $requestId]
        );
    }

    $appNo = '';
    $appRow = db_single(
        $conn,
        "SELECT COALESCE(c.application_no, '') AS application_no
         FROM training_requests tr
         LEFT JOIN contractors c ON c.id = tr.contractor_id
         WHERE tr.id = ? LIMIT 1",
        'i',
        [$requestId]
    );
    $appNo = (string)($appRow['application_no'] ?? '');
    db_execute(
        $conn,
        "INSERT INTO training_results (workman_id, application_no, result, recorded_by, training_request_id, training_token, attendance_status, total_score, external_reference, remarks, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
        'issiississ',
        [$workmanId, $appNo, $result === 'pass' ? 'passed' : ($result === 'fail' ? 'failed' : 'absent'), (int)($_SESSION['user_id'] ?? 0), $requestId, $token, $attendance, $score, $externalRef, $remarks]
    );
    db_execute($conn, "UPDATE training_batch_workers SET status = ?, external_reference = ? WHERE training_request_id = ?", 'ssi', [$result === 'pass' ? 'passed' : ($result === 'fail' ? 'failed' : 'absent'), $externalRef, $requestId]);

    if ($result === 'pass') {
        db_execute($conn, "UPDATE training_requests SET status = 'passed', updated_at = NOW() WHERE id = ?", 'i', [$requestId]);
        db_execute($conn, "UPDATE workmen SET training_status = 'passed', safety_training_status = 'TRAINING_PASSED', eligibility_status = 'ELIGIBLE', training_valid_till = ? WHERE id = ?", 'si', [$validTillValue, $workmanId]);
    } elseif ($result === 'fail') {
        db_execute($conn, "UPDATE training_requests SET status = 'failed', updated_at = NOW() WHERE id = ?", 'i', [$requestId]);
        db_execute($conn, "UPDATE workmen SET training_status = 'training_failed', safety_training_status = 'TRAINING_FAILED', eligibility_status = 'NOT ELIGIBLE' WHERE id = ?", 'i', [$workmanId]);
    } else {
        db_execute($conn, "UPDATE training_requests SET status = 'absent', updated_at = NOW() WHERE id = ?", 'i', [$requestId]);
        db_execute($conn, "UPDATE workmen SET training_status = 'absent', safety_training_status = 'ABSENT', eligibility_status = 'NOT ELIGIBLE' WHERE id = ?", 'i', [$workmanId]);
    }

    syncResultJson([
        'success' => true,
        'message' => 'Training result synced.',
        'token' => $token,
        'result' => strtoupper($result),
        'workman_id' => $workmanId,
        'training_request_id' => $requestId,
        'attempt_no' => (int)($row['attempt_no'] ?? 1),
    ]);
} catch (Throwable $e) {
    error_log('[sync_training_result] ' . $e->getMessage());
    syncResultJson(['success' => false, 'message' => $e->getMessage()], 500);
}
?>
