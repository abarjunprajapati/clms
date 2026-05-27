<?php
session_start();
require_once 'json_error_handler.php';
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/workflow_helpers.php';
require_once __DIR__ . '/helpers.php';

try {
    workflow_ensure_tables($conn);

    $application_id = trim($_GET['application_id'] ?? '');
    $where = '';
    $types = '';
    $params = [];

if ($application_id !== '') {
        $where = 'WHERE pgp.application_id = ?';
        $types = 's';
        $params[] = $application_id;
    }

    $passes = db_fetch_all($conn, "
        SELECT 
            pgp.id, 
            pgp.pass_no as pass_number, 
            pgp.application_id, 
            w.name as worker_name, 
            w.trade, 
            COALESCE(a.contractor_name, 'N/A') as contractor, 
            pgp.valid_from as issue_date, 
            pgp.valid_till, 
            pgp.status
        FROM permanent_gate_passes pgp
        JOIN workmen w ON pgp.worker_id = w.id
        LEFT JOIN annexure2a a ON pgp.application_id = a.application_id
        $where
        ORDER BY pgp.created_at DESC
    ", $types, $params);


    jsonErrorFlush();
    sendResponse(true, [
        'passes' => $passes,
        'count' => count($passes)
    ], 'Permanent passes fetched');
} catch (Throwable $e) {
    apiError($e->getMessage(), 500);
}
?>

