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
    // Total assigned contractors
    $totalContractors = db_count($conn, "SELECT COUNT(*) FROM execution_officer_contractors WHERE execution_officer_id = ?", 'i', [$officerId]);
    
    // Active workers in assigned contractors/work orders
    $activeWorkers = db_count($conn, "SELECT COUNT(*) FROM execution_worker_deployments WHERE execution_officer_id = ? AND status = 'active'", 'i', [$officerId]);
    
    // Present today
    $presentToday = db_count($conn, "SELECT COUNT(DISTINCT workman_id) FROM attendance WHERE workman_id IN (SELECT workman_id FROM execution_worker_deployments WHERE execution_officer_id = ? AND status = 'active') AND DATE(check_in) = CURDATE()", 'i', [$officerId]);
    
    // Observations
    $totalObservations = db_count($conn, "SELECT COUNT(*) FROM execution_observations WHERE execution_officer_id = ?", 'i', [$officerId]);
    $highSeverityObs = db_count($conn, "SELECT COUNT(*) FROM execution_observations WHERE execution_officer_id = ? AND severity = 'high'", 'i', [$officerId]);
    
    // Escalations
    $pendingEscalations = db_count($conn, "SELECT COUNT(*) FROM execution_actions WHERE execution_officer_id = ? AND action_type = 'escalation'", 'i', [$officerId]);

    // Idle Workers (Present today but NOT deployed)
    $idleWorkers = db_count($conn, "SELECT COUNT(DISTINCT a.workman_id) FROM attendance a 
                                JOIN workmen w ON a.workman_id = w.id 
                                WHERE DATE(a.check_in) = CURDATE() 
                                AND w.contractor_id IN (SELECT contractor_id FROM execution_officer_contractors WHERE execution_officer_id = ?)
                                AND a.workman_id NOT IN (SELECT workman_id FROM execution_worker_deployments WHERE status = 'active')", 'i', [$officerId]);

    // Attendance Exceptions (Ghost attendance, etc.)
    $exceptions = db_count($conn, "SELECT COUNT(*) FROM attendance_exceptions WHERE DATE(created_at) = CURDATE()");

    // Observation Trends
    $obsTrends = db_fetch_all($conn, "SELECT observation_type, COUNT(*) as count FROM execution_observations WHERE execution_officer_id = ? GROUP BY observation_type", 'i', [$officerId]);

    // Contractor Productivity
    $contractorProductivity = db_fetch_all($conn, "SELECT c.contractor_name, 
                                                (SELECT COUNT(*) FROM execution_worker_deployments WHERE contractor_id = c.id AND execution_officer_id = ? AND status = 'active') as deployed,
                                                (SELECT COUNT(DISTINCT workman_id) FROM attendance WHERE workman_id IN (SELECT workman_id FROM execution_worker_deployments WHERE contractor_id = c.id AND execution_officer_id = ? AND status = 'active') AND DATE(check_in) = CURDATE()) as present
                                                FROM contractors c 
                                                JOIN execution_officer_contractors eoc ON c.id = eoc.contractor_id 
                                                WHERE eoc.execution_officer_id = ?", 'iii', [$officerId, $officerId, $officerId]);

    echo json_encode([
        'status' => true,
        'stats' => [
            'total_contractors' => $totalContractors,
            'active_workers' => $activeWorkers,
            'present_today' => $presentToday,
            'idle_workers' => $idleWorkers,
            'total_observations' => $totalObservations,
            'high_severity_obs' => $highSeverityObs,
            'pending_escalations' => $pendingEscalations,
            'attendance_exceptions' => $exceptions
        ],
        'charts' => [
            'utilization' => [
                'deployed' => $activeWorkers,
                'present' => $presentToday,
                'idle' => $idleWorkers
            ],
            'observations' => $obsTrends,
            'productivity' => $contractorProductivity
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}
?>


