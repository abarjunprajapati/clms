<?php
require_once __DIR__ . '/../../include/auth.php';
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/workflow_engine.php';

checkAuth(['safety_user', 'super_admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

$session_id = intval($_POST['session_id'] ?? 0);

if (!$session_id) {
    header("Location: ../../pages/safety/training_schedule.php?error=Invalid session");
    exit;
}

clms_db_begin_transaction($conn);
try {
    $pendingCount = db_count(
        $conn,
        "SELECT COUNT(*) c
         FROM training_session_workers tsw
         JOIN training_requests tr ON tr.id = tsw.training_request_id
         WHERE tsw.session_id = ?
           AND tr.status = 'contractor_confirmed'
           AND (
               LOWER(COALESCE(tsw.attendance_status, 'pending')) = 'pending'
               OR LOWER(COALESCE(tsw.result, 'pending')) NOT IN ('pass', 'fail', 'passed', 'failed')
           )",
        'i',
        [$session_id]
    );

    if ($pendingCount > 0) {
        throw new Exception("Please save attendance and marks/results for all workers before finalizing. Pending: $pendingCount");
    }

    // 1. Mark session as completed
    db_execute($conn, "UPDATE training_schedule SET session_status='completed' WHERE id=?", 'i', [$session_id]);
    
    // 2. Mark corresponding training requests as completed
    $workers = db_fetch_all(
        $conn,
        "SELECT tsw.workman_id, tsw.training_request_id, tsw.result
         FROM training_session_workers tsw
         JOIN training_requests tr ON tr.id = tsw.training_request_id
         WHERE tsw.session_id = ?
           AND tr.status = 'contractor_confirmed'",
        'i',
        [$session_id]
    );
    foreach ($workers as $w) {
        $result = strtolower((string)($w['result'] ?? 'fail'));
        $requestStatus = in_array($result, ['pass', 'passed'], true) ? 'passed' : 'failed';
        db_execute($conn, "UPDATE training_requests SET status=?, updated_at=NOW() WHERE id=? AND status = 'contractor_confirmed'", 'si', [$requestStatus, (int)$w['training_request_id']]);
        
        // Notify contractor of completion
        // triggerNotification($conn, ...);
    }
    
    logAuditAction($conn, $_SESSION['user_id'], $_SESSION['role'], "completed_session", "safety", "Session ID: $session_id finalized");

    clms_db_commit($conn);
    header("Location: ../../pages/safety/manage_session.php?id=$session_id&success=Session finalized and locked");
} catch (Exception $e) {
    clms_db_rollback($conn);
    header("Location: ../../pages/safety/manage_session.php?id=$session_id&error=" . urlencode($e->getMessage()));
}

