<?php
/**
 * get_applications.php - Fixed canonical statuses + standardized JSON
 */
require_once 'helpers.php';
require_once '../include/config.php';

try {
    $input = getApiInput();
    $status = $input['status'] ?? '';

    // Canonical workflow statuses ONLY
    $allowedStatuses = [
        'submitted', 'under_review', 'approved', 'rejected', 
        'training_pending', 'training_completed', 'gatepass_pending',
        'gatepass_approved', 'final_approval_pending', 'completed'
    ];

    $where = "1=1";
    $params = [];
    $types = '';

    if ($status !== '') {
        $requested = array_filter(array_map('trim', explode(',', $status)));
        if (!empty($requested)) {
            $valid_requested = array_intersect($requested, $allowedStatuses);
            if (count($valid_requested) !== count($requested)) {
                sendResponse(false, [], 'Invalid status filters. Use only canonical statuses.');
            }
            $placeholders = implode(',', array_fill(0, count($valid_requested), '?'));
            $where .= ' AND a.workflow_status IN (' . $placeholders . ')';
            $params = $valid_requested;
            $types = str_repeat('s', count($valid_requested));
        }
    }

    $sql = "
        SELECT 
            a.application_id,
            COALESCE(c.name, a.contractor_name, 'N/A') AS contractor_name,
            COALESCE(p.project_name, a.project_name, 'N/A') AS project_name,
            a.created_at AS submitted_at,
            a.workflow_status,
            COALESCE(a.priority, 'low') AS priority
        FROM annexure2a a
        LEFT JOIN contractors c ON a.contractor_id = c.id
        LEFT JOIN projects p ON a.project_id = p.id
        WHERE $where
        ORDER BY a.updated_at DESC
        LIMIT 100
    ";

    $applications = db_fetch_all($conn, $sql, $types, $params);
    sendResponse(true, $applications, 'Applications loaded successfully');

} catch (Throwable $e) {
    http_response_code(500);
    sendResponse(false, [], $e->getMessage());
}
?>


