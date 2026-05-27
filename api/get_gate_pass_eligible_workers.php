<?php
/**
 * get_gate_pass_eligible_workers.php
 * Returns workmen eligible for gate pass from the `workmen` table
 * Eligible = active status, linked to an approved application
 *
 * GET params:
 *   contractor_id  int  optional (overridden for contractor role)
 *   application_id string optional
 */
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/auth_middleware.php';

require_role(['contractor', 'welfare', 'pass_officer', 'acc', 'admin']);

header('Content-Type: application/json');

$contractor_id  = (int)($_GET['contractor_id']  ?? 0);
$application_id = trim($_GET['application_id'] ?? '');

// Contractor can only see own workers
if ($currentRole === 'contractor') {
    $contractor_id = $currentUserId;
}

$params = [];
$types  = '';
$where  = "WHERE w.status = 'active'";

if ($contractor_id > 0) {
    $where   .= ' AND w.contractor_id = ?';
    $params[] = $contractor_id;
    $types   .= 'i';
}

if ($application_id !== '') {
    $where   .= ' AND w.application_no = ?';
    $params[] = $application_id;
    $types   .= 's';
}

// Only return workers whose application has completed safety training.
$where .= " AND EXISTS (
    SELECT 1 FROM annexure2a a
    WHERE a.application_id = w.application_no
      AND a.workflow_status IN ('safety_completed','pass_requested','pass_approved','temp_pass_generated','acc_generated','permanent_pass_generated', 'enrolment_done', 'training_done')
)";

$sql = "
SELECT
    w.id,
    w.name,
    w.worker_type as role,
    w.trade,
    w.aadhaar as aadhar,
    w.mobile as phone,
    w.photo,
    w.father_name,
    w.dob,
    w.gender,
    w.permanent_address as address,
    w.state,
    w.training_status,
    w.application_no as application_id,
    w.temp_id,
    c.contractor_name,
    c.vendor_code
FROM workmen w
JOIN contractors c ON c.id = w.contractor_id
JOIN training_results t ON t.workman_id = w.id
$where
  AND w.training_status IN ('pass', 'passed', 'qualified')
  AND t.result IN ('pass', 'passed', 'qualified')
ORDER BY w.name ASC
LIMIT 500
";

try {
    $workers = db_fetch_all($conn, $sql, $types, $params);

    echo json_encode([
        'success' => true,
        'data'    => $workers,
        'count'   => count($workers),
        'role'    => $currentRole
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}

