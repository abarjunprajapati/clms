<?php
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/auth_middleware.php';

require_role(['pass_officer','welfare','acc','admin']);

header('Content-Type: application/json');

$tab = $_GET['tab'] ?? 'pending';
$limit = (int)($_GET['limit'] ?? 50);
$search = trim($_GET['search'] ?? '');

// Role-based status filtering
$roleStatusMap = [
    'pass_officer' => ['pending'],
    'welfare' => ['pending'],
    'acc' => ['approved'],
    'admin' => ['pending','approved','rejected','issued']
];

$allowedStatuses = $roleStatusMap[$currentRole] ?? ['pending'];

if ($tab === 'pending') {
    $statusIn = "'" . implode("','", $allowedStatuses) . "'";
    $where = "WHERE gpr.status IN ($statusIn)";
} elseif ($tab === 'approved') {
    $where = "WHERE gpr.status IN ('approved','issued')";
} elseif ($tab === 'rejected') {
    $where = "WHERE gpr.status = 'rejected'";
} else {
    $where = "WHERE gpr.status IN ($statusIn)";
}

$params = [];
$types = '';

if ($search !== '') {
    $where .= " AND (gpr.request_no LIKE ? OR u.name LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like];
    $types = 'ss';
}

$sql = "
SELECT 
    gpr.id,
    gpr.contractor_id,
    gpr.request_no,
    gpr.from_date,
    gpr.to_date,
    gpr.gate_name,
    gpr.shift_name,
    gpr.access_zone,
    gpr.status,
    gpr.created_at,
    u.name as contractor_name,
    (SELECT COUNT(*) FROM gate_pass_request_workers gpw WHERE gpw.request_id = gpr.id) as worker_count
FROM gate_pass_requests gpr
LEFT JOIN users u ON gpr.contractor_id = u.id
$where
ORDER BY gpr.created_at DESC
LIMIT $limit
";

$requests = db_fetch_all($conn, $sql, $types, $params);

// Tab counts
$allStatuses = "'" . implode("','", $allowedStatuses) . "'";
$counts = [
    'pending' => db_count($conn, "SELECT COUNT(*) c FROM gate_pass_requests gpr $where"),
    'approved' => db_count($conn, "SELECT COUNT(*) c FROM gate_pass_requests gpr WHERE status IN ('approved','issued')"),
    'rejected' => db_count($conn, "SELECT COUNT(*) c FROM gate_pass_requests gpr WHERE status = 'rejected'")
];

echo json_encode([
    'success' => true,
    'data' => $requests,
    'counts' => $counts,
    'tab' => $tab,
    'role' => $currentRole
]);


