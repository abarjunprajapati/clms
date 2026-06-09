<?php
/**
 * ANNEXURE 5/A - DASHBOARD & REPORTING
 * 
 * Shows pass limit status for all contractors
 * Displays audit logs for overrides
 * Shows statistics and trends
 */

require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/pass_limit_validator.php';

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'summary';

try {
    
    if ($action === 'summary') {
        // ========== GET OVERALL SUMMARY ==========
        
        $summary = [
            'timestamp' => date('Y-m-d H:i:s'),
            'total_contractors' => 0,
            'total_workmen' => 0,
            'total_supervisors' => 0,
            'total_representatives' => 0,
            'total_overrides' => 0,
            'pass_limits_rules' => [],
            'contractor_status' => []
        ];

        // Get pass limit rules
        $result = clms_db_query($conn, "SELECT * FROM pass_limits WHERE contractor_id = 0");
        while ($row = clms_db_fetch_assoc($result)) {
            $summary['pass_limits_rules'][] = [
                'pass_type' => $row['pass_type'],
                'max_allowed' => $row['max_allowed'],
                'ratio_per_workmen' => $row['ratio_per_workmen'],
                'rule' => $row['rule'],
                'override_allowed' => (bool)$row['override_allowed']
            ];
        }

        // Get contractor statistics
        $result = clms_db_query($conn, "SELECT id, name FROM contractors LIMIT 20");
        while ($contractor = clms_db_fetch_assoc($result)) {
            $cid = $contractor['id'];
            
            // Count each type
            $workmen = db_count($conn, "SELECT COUNT(*) FROM workmen WHERE contractor_id = ? AND status = 'active'", 'i', [$cid]);
            $supervisors = db_count($conn, "SELECT COUNT(*) FROM workmen WHERE contractor_id = ? AND type = 'Supervisor'", 'i', [$cid]);
            $representatives = db_count($conn, "SELECT COUNT(*) FROM workmen WHERE contractor_id = ? AND type = 'Representative'", 'i', [$cid]);
            
            // Calculate supervisor limit
            $sup_calc = calculateAllowed($conn, $cid, 'Supervisor');
            
            $summary['contractor_status'][] = [
                'contractor_id' => $cid,
                'contractor_name' => $contractor['name'],
                'workmen' => [
                    'current' => $workmen,
                    'allowed' => null,
                    'status' => 'OK'
                ],
                'supervisors' => [
                    'current' => $supervisors,
                    'allowed' => $sup_calc['allowed'],
                    'status' => $supervisors <= $sup_calc['allowed'] ? 'OK' : 'EXCEEDED'
                ],
                'representatives' => [
                    'current' => $representatives,
                    'allowed' => 1,
                    'status' => $representatives <= 1 ? 'OK' : 'EXCEEDED'
                ]
            ];
            
            $summary['total_contractors']++;
            $summary['total_workmen'] += $workmen;
            $summary['total_supervisors'] += $supervisors;
            $summary['total_representatives'] += $representatives;
        }

        // Get override count
        $summary['total_overrides'] = db_count($conn, "SELECT COUNT(*) FROM audit_log WHERE action = 'pass_limit_override'", '', []);

        echo json_encode(['success' => true, 'data' => $summary], JSON_PRETTY_PRINT);

    } elseif ($action === 'audit_logs') {
        // ========== GET AUDIT LOGS ==========
        
        $limit = (int)($_GET['limit'] ?? 50);
        $contractor_id = isset($_GET['contractor_id']) ? (int)$_GET['contractor_id'] : null;

        $sql = "SELECT * FROM audit_log";
        $params = [];
        $types = '';

        if ($contractor_id) {
            $sql .= " WHERE contractor_id = ?";
            $types = 'i';
            $params[] = $contractor_id;
        }

        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $types .= 'i';
        $params[] = $limit;

        $result = db_fetch_all($conn, $sql, $types, $params);

        echo json_encode([
            'success' => true,
            'data' => [
                'total_records' => count($result),
                'records' => $result
            ]
        ], JSON_PRETTY_PRINT);

    } elseif ($action === 'contractor_detail') {
        // ========== GET CONTRACTOR DETAILS ==========
        
        $contractor_id = (int)($_GET['contractor_id'] ?? 0);
        if (!$contractor_id) {
            throw new Exception('contractor_id required');
        }

        $contractor = db_single($conn, "SELECT * FROM contractors WHERE id = ?", 'i', [$contractor_id]);
        if (!$contractor) {
            throw new Exception('Contractor not found');
        }

        $detail = [
            'contractor' => $contractor,
            'enrollment' => [
                'workmen' => [
                    'current' => db_count($conn, "SELECT COUNT(*) FROM workmen WHERE contractor_id = ? AND status = 'active'", 'i', [$contractor_id]),
                    'allowed' => null,
                    'rule' => 'No limit'
                ],
                'supervisors' => [],
                'representatives' => [],
                'contractors' => [
                    'current' => db_count($conn, "SELECT COUNT(*) FROM contractors WHERE id = ?", 'i', [$contractor_id]),
                    'allowed' => 2,
                    'rule' => 'Fixed'
                ]
            ],
            'audit_trail' => []
        ];

        // Get supervisor details
        $sups = db_fetch_all($conn, "SELECT id, name, aadhar FROM workmen WHERE contractor_id = ? AND type = 'Supervisor'", 'i', [$contractor_id]);
        $sup_calc = calculateAllowed($conn, $contractor_id, 'Supervisor');
        $detail['enrollment']['supervisors'] = [
            'list' => $sups,
            'current' => count($sups),
            'allowed' => $sup_calc['allowed'],
            'rule' => $sup_calc['rule']
        ];

        // Get representative details
        $reps = db_fetch_all($conn, "SELECT id, name, aadhar FROM workmen WHERE contractor_id = ? AND type = 'Representative'", 'i', [$contractor_id]);
        $detail['enrollment']['representatives'] = [
            'list' => $reps,
            'current' => count($reps),
            'allowed' => 1,
            'rule' => 'Fixed'
        ];

        // Get audit trail
        $detail['audit_trail'] = db_fetch_all($conn, "SELECT * FROM audit_log WHERE contractor_id = ? ORDER BY created_at DESC LIMIT 10", 'i', [$contractor_id]);

        echo json_encode(['success' => true, 'data' => $detail], JSON_PRETTY_PRINT);

    } else {
        throw new Exception('Unknown action: ' . $action);
    }

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}

?>

