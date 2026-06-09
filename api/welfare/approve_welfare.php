<?php
/**
 * approve_welfare.php
 * Welfare officer approves/rejects/verifies an application
 * POST: { application_id, status, remarks }
 * Status values: pending | under_review | verified | rejected | approved
 */
session_start();
header('Content-Type: application/json');

require_once '../../include/config.php';
require_once __DIR__ . '/../workflow_action.php';

try {
    // Role check — accept both 'welfare' and 'welfare_user'
    $role = $_SESSION['role'] ?? '';
    if (!in_array($role, ['welfare', 'welfare_user', 'welfare_admin', 'admin'])) {
        throw new Exception('Unauthorized access. Welfare role required.');
    }

    $input   = json_decode(file_get_contents('php://input'), true) ?? [];
    $app_id  = trim($input['application_id'] ?? '');
    $status  = trim($input['status']         ?? '');
    $remarks = trim($input['remarks']        ?? '');

    if (empty($app_id)) {
        throw new Exception('application_id is required');
    }

    $allowed = ['pending', 'under_review', 'under_verification', 'verified', 'rejected', 'approved', 'welfare_pending'];
    if (!in_array($status, $allowed)) {
        throw new Exception("Invalid status '$status'. Allowed: " . implode(', ', $allowed));
    }

    // Map incoming legacy UI status to canonical workflow_status values.
    $workflowStatusMap = [
        'verified'             => 'submitted',
        'approved'             => 'approved',
        'rejected'             => 'rejected',
        'under_review'         => 'submitted',
        'pending'              => 'submitted',
        'under_verification'   => 'submitted',
        'welfare_pending'      => 'submitted',
    ];
    $new_workflow_status = $workflowStatusMap[$status] ?? 'submitted';

    // Verify application exists
    $app = db_single($conn, 'SELECT id, application_id, workflow_status FROM annexure2a WHERE application_id = ? LIMIT 1', 's', [$app_id]);
    if (!$app) {
        throw new Exception("Application not found: $app_id");
    }

    $verified_by = $_SESSION['name'] ?? $_SESSION['user_name'] ?? $role;

    $workflow = workflowSetStatus($app_id, $new_workflow_status, $remarks);

    // 2. Upsert welfare_verification record
    $existing_wv = db_single($conn, 'SELECT id FROM welfare_verification WHERE application_id = ? LIMIT 1', 's', [$app_id]);
    if ($existing_wv) {
        db_execute($conn,
            'UPDATE welfare_verification SET status = ?, remarks = ?, verified_by = ?, verified_date = NOW() WHERE application_id = ?',
            'ssss', [$status, $remarks, $verified_by, $app_id]
        );
    } else {
        db_execute($conn,
            'INSERT INTO welfare_verification (application_id, status, remarks, verified_by, verified_date) VALUES (?, ?, ?, ?, NOW())',
            'ssss', [$app_id, $status, $remarks, $verified_by]
        );
    }

    echo json_encode([
        'success' => true,
        'message' => "Status updated to '$status'",
        'data'    => [
            'application_id'  => $app_id,
            'new_status'      => $status,
            'workflow_status' => $new_workflow_status,
            'workflow'        => $workflow,
            'updated_by'      => $verified_by,
            'timestamp'       => date('Y-m-d H:i:s'),
        ]
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

