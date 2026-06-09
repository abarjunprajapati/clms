<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/execution_context.php';
require_once __DIR__ . '/../auth_middleware.php';

header('Content-Type: application/json');
enforceRole(['execution_officer', 'execution', 'super_admin']);

$userId = $_SESSION['user_id'];

// Get or create execution officer context for this login
$officerId = clms_execution_get_officer_id($conn, $userId);

if (!$officerId) {
    echo json_encode(['status' => false, 'message' => 'Officer record not found']);
    exit;
}

try {
    // Contractor Productivity Metrics
    $stats = db_fetch_all($conn, "SELECT c.contractor_name, 
                                 (SELECT COUNT(*) FROM execution_worker_deployments WHERE contractor_id = c.id AND execution_officer_id = ? AND status = 'active') as deployed,
                                 (SELECT COUNT(DISTINCT workman_id) FROM attendance WHERE workman_id IN (SELECT workman_id FROM execution_worker_deployments WHERE contractor_id = c.id AND execution_officer_id = ? AND status = 'active') AND DATE(check_in) = CURDATE()) as present
                                 FROM contractors c 
                                 JOIN execution_officer_contractors eoc ON c.id = eoc.contractor_id 
                                 WHERE eoc.execution_officer_id = ?", 'iii', [$officerId, $officerId, $officerId]);

    // Trend Data (Mocking historical data for now)
    $trend = [
        ['day' => 'Mon', 'efficiency' => 82],
        ['day' => 'Tue', 'efficiency' => 85],
        ['day' => 'Wed', 'efficiency' => 78],
        ['day' => 'Thu', 'efficiency' => 90],
        ['day' => 'Fri', 'efficiency' => 88],
        ['day' => 'Sat', 'efficiency' => 92],
        ['day' => 'Sun', 'efficiency' => 89]
    ];

    echo json_encode([
        'status' => true,
        'contractors' => $stats,
        'trend' => $trend
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}
?>


