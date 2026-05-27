<?php
/**
 * api/welfare/get_welfare_applications.php - Welfare Approval Queue
 * Shows applications ready for welfare review: workflow_status='submitted'
 */
require_once '../../include/config.php';
require_once '../helpers.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $tab = $_GET['tab'] ?? 'pending';
    $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));

    $valid_tabs = ['pending', 'approved', 'rejected', 'resubmitted'];
    if (!in_array($tab, $valid_tabs, true)) {
        $tab = 'pending';
    }

    // Canonical filters for welfare
    $status_conditions = [
        'pending' => "a.workflow_status = 'submitted'",
        'approved' => "a.workflow_status = 'under_review'", 
        'rejected' => "a.workflow_status = 'rejected'",
        'resubmitted' => "a.workflow_status = 'submitted' AND a.updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)"
    ];

    $where_status = $status_conditions[$tab];

    $sql = "
        SELECT 
            a.application_id,
            COALESCE(a.contractor_name, 'N/A') AS contractor_name,
            a.contractor_id,
            a.category_work AS project,
            a.created_at AS submitted_at,
            a.workflow_status,
            a.remarks,
            DATEDIFF(NOW(), a.created_at) AS days_pending
        FROM annexure2a a
        WHERE $where_status
        ORDER BY a.updated_at DESC
        LIMIT ?
    ";

    $applications = db_fetch_all($conn, $sql, 'i', [$limit]);

    $counts = [
        'pending' => db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE workflow_status = 'submitted'"),
        'approved' => db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE workflow_status = 'under_review'"),
        'rejected' => db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE workflow_status = 'rejected'"),
        'resubmitted' => db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE workflow_status = 'submitted' AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")
    ];

    sendResponse(true, $applications, "Welfare applications loaded for tab: $tab", ['counts' => $counts]);

} catch (Throwable $e) {
    http_response_code(500);
    sendResponse(false, [], $e->getMessage());
}
?>


