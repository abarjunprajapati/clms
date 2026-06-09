<?php
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/auth_middleware.php';

require_role(['contractor','welfare','pass_officer','acc','admin']);

header('Content-Type: application/json');

$contractor_id = (int)($_GET['contractor_id'] ?? 0);
$status = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');

// Contractor can only see own workers; officers/admins can see all
if ($currentRole === 'contractor') {
    $contractor_id = $currentUserId;
}

$params = [];
$types = '';
$where = 'WHERE 1=1';

if ($contractor_id > 0) {
    $where .= ' AND w.contractor_id = ?';
    $params[] = $contractor_id;
    $types .= 'i';
}

if ($status !== '') {
    $where .= ' AND w.status = ?';
    $params[] = $status;
    $types .= 's';
}

if ($search !== '') {
    $where .= ' AND (w.name LIKE ? OR w.trade LIKE ? OR w.mobile LIKE ?)';
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

$result = $conn->query("
SELECT 
    w.id,
    w.name,
    w.aadhar,
    w.training_status,
    a.id as application_id,
    c.name as contractor_name
FROM workmen w
LEFT JOIN applications a ON w.application_id = a.id
LEFT JOIN contractors c ON a.contractor_id = c.id
");

$data = [];

while($row = $result->fetch_assoc()){
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'data' => $data]);
exit;


