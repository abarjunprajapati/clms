<?php
require_once __DIR__ . '/../../include/auth.php';
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/workflow_engine.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

$session_id = intval($_POST['session_id'] ?? 0);

if (!$session_id) {
    header("Location: ../../pages/safety/training_schedule.php?error=Invalid session");
    exit;
}

mysqli_begin_transaction($conn);
try {
    // 1. Mark session as completed
    db_execute($conn, "UPDATE training_schedule SET session_status='completed' WHERE id=?", 'i', [$session_id]);
    
    // 2. Mark corresponding training requests as completed
    $workers = db_fetch_all($conn, "SELECT workman_id FROM training_session_workers WHERE session_id=?", 'i', [$session_id]);
    foreach ($workers as $w) {
        db_execute($conn, "UPDATE training_requests SET status='completed' WHERE workman_id=? AND status='scheduled'", 'i', [$w['workman_id']]);
        
        // Notify contractor of completion
        // triggerNotification($conn, ...);
    }
    
    logAuditAction($conn, $_SESSION['user_id'], $_SESSION['role'], "completed_session", "safety", "Session ID: $session_id finalized");

    mysqli_commit($conn);
    header("Location: ../../pages/safety/manage_session.php?id=$session_id&success=Session finalized and locked");
} catch (Exception $e) {
    mysqli_rollback($conn);
    header("Location: ../../pages/safety/manage_session.php?id=$session_id&error=" . urlencode($e->getMessage()));
}

