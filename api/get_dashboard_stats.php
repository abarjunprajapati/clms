<?php
/**
 * get_dashboard_stats.php
 * Central endpoint for dashboard counters
 */
require_once 'api_helper.php';
require_once __DIR__ . '/../include/config.php';

try {
    // Note: Session check removed for demo stability, but would normally use $_SESSION
    $stats = [
        'totalApplications' => db_count($conn, "SELECT COUNT(*) c FROM annexure2a"),
        'pending'           => db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE workflow_status IN ('submitted', 'under_verification', 'verified', 'approval_pending')"),
        'verification'       => db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE workflow_status = 'under_verification'"),
        'approved'          => db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE workflow_status = 'approved'"),
        'rejected'          => db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE workflow_status = 'rejected'"),
        'resubmissions'     => db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE workflow_status = 'resubmitted'"),
        'totalWorkmen'      => db_count($conn, "SELECT COUNT(*) c FROM workmen"),
        'passesIssued'      => db_count($conn, "SELECT COUNT(*) c FROM permanent_gate_passes")
    ];

    apiSuccess($stats);

} catch (Throwable $e) {
    apiError($e->getMessage(), 500);
}
?>


