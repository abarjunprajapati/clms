<?php
session_start();
include '../../include/config.php';

header('Content-Type: application/json');

// Skip session check for direct API testing
// if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['welfare_user', 'authority'])) {
//     http_response_code(403);
//     echo json_encode(['success' => false, 'error' => 'Unauthorized']);
//     exit;
// }


$data = [];

// Stats (matching welfare-verification.php exactly)
$stats = [
    "total" => 0,
    "pending" => 0,           // submitted + query_raised
    "verification" => 0,       // under_verification
    "approved" => 0,           // forwarded/verified
    "rejected" => 0
];

$q1 = $conn->query("
    SELECT 
        COUNT(*) total,
        SUM(status='submitted') submitted,
        SUM(status='query_raised') query_raised,
        SUM(status='under_verification') verification,
        SUM(status='verified') verified,
        SUM(status='forwarded') approved,
        SUM(status='rejected') rejected
    FROM annexure2a
");

if ($row = $q1->fetch_assoc()) {
    $stats['total'] = (int)$row['total'];
    $stats['pending'] = (int)$row['submitted'] + (int)$row['query_raised'];
    $stats['verification'] = (int)$row['verification'];
    $stats['approved'] = (int)$row['verified'] + (int)$row['approved'];
    $stats['rejected'] = (int)$row['rejected'];
}

// Applications list with extra fields
$applications = [];
$q2 = $conn->query("
    SELECT 
        ref_id, 
        contractor_name, 
        contractor_id, 
        created_at, 
        status,
        DATEDIFF(NOW(), created_at) as days_pending
    FROM annexure2a
    ORDER BY id DESC
    LIMIT 20
");

while ($r = $q2->fetch_assoc()) {
    // Priority logic
    $priority = 'low';
    if (in_array($r['status'], ['submitted', 'query_raised'])) $priority = 'high';
    elseif (in_array($r['status'], ['under_verification', 'verified'])) $priority = 'medium';

    $applications[] = [
        "id" => $r['ref_id'],
        "contractor" => $r['contractor_name'],
        "code" => $r['contractor_id'],
        "project" => "General Project", // Static fallback
        "submitted" => date('d M Y', strtotime($r['created_at'])),
        "submitted_raw" => $r['created_at'],
        "status" => $r['status'],
        "days_pending" => (int)$r['days_pending'],
        "priority" => $priority
    ];
}

echo json_encode([
    "success" => true,
    "stats" => $stats,
    "applications" => $applications
], JSON_PRETTY_PRINT);
?>


