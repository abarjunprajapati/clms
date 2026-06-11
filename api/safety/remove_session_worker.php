<?php
require_once __DIR__ . '/../../include/auth.php';
require_once __DIR__ . '/../../include/config.php';

checkAuth(['safety_user', 'super_admin']);
header('Content-Type: application/json; charset=utf-8');

function safetyRemoveJson($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    safetyRemoveJson(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$sessionId = (int)($_POST['session_id'] ?? 0);
$workmanId = (int)($_POST['workman_id'] ?? 0);
$reason = trim((string)($_POST['reason'] ?? 'Removed from scheduled training session by Safety.'));

if (!$sessionId || !$workmanId) {
    safetyRemoveJson(['success' => false, 'message' => 'Session and worker are required.'], 422);
}

$session = db_single($conn, "SELECT session_status FROM training_schedule WHERE id = ? LIMIT 1", 'i', [$sessionId]);
if (!$session) {
    safetyRemoveJson(['success' => false, 'message' => 'Session not found.'], 404);
}
if (strtolower((string)($session['session_status'] ?? 'open')) === 'completed') {
    safetyRemoveJson(['success' => false, 'message' => 'Completed session cannot be modified.'], 409);
}

$link = db_single(
    $conn,
    "SELECT id, training_request_id FROM training_session_workers WHERE session_id = ? AND workman_id = ? LIMIT 1",
    'ii',
    [$sessionId, $workmanId]
);
if (!$link) {
    safetyRemoveJson(['success' => false, 'message' => 'Worker is not assigned to this session.'], 404);
}

mysqli_begin_transaction($conn);
try {
    db_execute($conn, "DELETE FROM training_session_workers WHERE id = ?", 'i', [(int)$link['id']]);

    if (!empty($link['training_request_id'])) {
        db_execute(
            $conn,
            "UPDATE training_requests
             SET status = 'pending',
                 scheduled_date = NULL,
                 scheduled_shift = NULL,
                 scheduled_venue = NULL,
                 scheduled_time = NULL,
                 safety_remarks = ?,
                 updated_at = NOW()
             WHERE id = ?",
            'si',
            [$reason, (int)$link['training_request_id']]
        );
    }

    db_execute(
        $conn,
        "UPDATE workmen
         SET training_status = 'training_pending',
             safety_training_status = 'PENDING_TRAINING'
         WHERE id = ?",
        'i',
        [$workmanId]
    );
    db_execute(
        $conn,
        "UPDATE training_schedule
         SET enrolled_count = (
             SELECT COUNT(*)
             FROM training_session_workers tsw
             JOIN training_requests tr ON tr.id = tsw.training_request_id
             WHERE tsw.session_id = ? AND tr.status = 'contractor_confirmed'
         )
         WHERE id = ?",
        'ii',
        [$sessionId, $sessionId]
    );

    mysqli_commit($conn);
    safetyRemoveJson(['success' => true, 'message' => 'Worker removed from session and returned to scheduling queue.']);
} catch (Throwable $e) {
    mysqli_rollback($conn);
    safetyRemoveJson(['success' => false, 'message' => $e->getMessage()], 500);
}
?>
