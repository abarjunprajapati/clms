<?php
session_start();
require_once 'helpers.php';
require_once __DIR__ . '/../include/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Get and validate query parameters
    $tab = $_GET['tab'] ?? 'pending';
    $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
    
    // Validate tab
    $valid_tabs = ['pending', 'approved', 'rejected', 'completed'];
    if (!in_array($tab, $valid_tabs, true)) {
        $tab = 'pending';
    }

    // Canonical workflow_status filters
    $status_conditions = [
        'pending' => "a.workflow_status = 'final_approval_pending'",
        'approved' => "a.workflow_status IN ('gatepass_approved', 'completed')", 
        'rejected' => "a.workflow_status = 'rejected'",
        'completed' => "a.workflow_status = 'completed'"
    ];
    
    $where_status = $status_conditions[$tab] ?? $status_conditions['pending'];
    
    $sql = "
        SELECT
            a.application_id,
            a.application_id AS ref_id,
            COALESCE(a.contractor_name, CONCAT('Application ', a.application_id)) AS contractor,
            a.contractor_id AS contractor_code,
            COALESCE(a.category_work, 'Contract Work') AS project,
            a.created_at AS submitted,
            a.workflow_status AS current_status,
            a.remarks,
            TIMESTAMPDIFF(DAY, a.created_at, NOW()) AS days_pending,
            CASE
                WHEN TIMESTAMPDIFF(DAY, a.created_at, NOW()) >= 7 THEN 'high'
                WHEN TIMESTAMPDIFF(DAY, a.created_at, NOW()) >= 3 THEN 'medium'
                ELSE 'low'
            END AS priority
        FROM annexure2a a
        WHERE $where_status
        ORDER BY a.updated_at DESC
        LIMIT ?
    ";

    $params = [$limit];
    $applications = db_fetch_all($conn, $sql, 'i', $params);

    // Counts using canonical statuses
    $counts = [
        'pending' => db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE workflow_status = 'final_approval_pending'"),
        'approved' => db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE workflow_status IN ('gatepass_approved', 'completed')"),
        'rejected' => db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE workflow_status = 'rejected'"),
        'completed' => db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE workflow_status = 'completed'")
    ];

    // Get recent events if remarks_history table exists
    $recent_events = [];
    $table = $conn->query("SHOW TABLES LIKE 'remarks_history'");
    if ($table && $table->num_rows > 0) {
        $recent_events = db_fetch_all($conn, "
            SELECT application_id, remark, created_by, created_at
            FROM remarks_history
            ORDER BY created_at DESC
            LIMIT 10
        ");
    }

    if (function_exists('jsonErrorFlush')) {
        jsonErrorFlush();
    }
    
    $response = [
        'success' => true,
        'data' => $applications,
        'counts' => $counts,
        'recent_events' => $recent_events,
        'tab' => $tab
    ];
    
    echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    if (function_exists('jsonErrorFlush')) {
        jsonErrorFlush();
    }
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage(),
        'hint' => 'Check database connection and tables'
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
?>


