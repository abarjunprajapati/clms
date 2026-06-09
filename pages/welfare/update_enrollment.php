<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin', 'welfare_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../api/WorkflowEngine.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method");
}

$worker_id = (int)($_POST['worker_id'] ?? 0);
$action = $_POST['action'] ?? '';
$user_id = (int)($_SESSION['user_id'] ?? 0);

if (!$worker_id || !in_array($action, ['approve', 'reject'])) {
    die("Invalid parameters");
}

if ($action == 'approve') {
    db_execute($conn, "
        UPDATE workmen 
        SET status = 'verified', welfare_user_verified = 1, updated_at = NOW() 
        WHERE id = ?
    ", 'i', [$worker_id]);
    
    // Also update app status via WorkflowEngine if needed
    $worker = db_single($conn, "SELECT application_no FROM workmen WHERE id = ?", 'i', [$worker_id]);
    if ($worker && $worker['application_no']) {
        WorkflowEngine::performAction($conn, $worker['application_no'], 'verify', $_SESSION['role'], $user_id, 'Welfare Enrollment Approved');
    }
}

if ($action == 'reject') {
    db_execute($conn, "
        UPDATE workmen 
        SET status = 'rejected', welfare_user_verified = 0, updated_at = NOW() 
        WHERE id = ?
    ", 'i', [$worker_id]);
}

header("Location: enrollment_monitor.php");
exit;
?>

