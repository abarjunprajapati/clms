<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
require_once __DIR__ . '/../../include/config.php';

header('Content-Type: application/json');

$reportId = $_GET['id'] ?? '';

if (!$reportId) {
    echo json_encode(['success' => false, 'message' => 'Report ID missing']);
    exit;
}

$reportCategories = [
    'workforce' => [
        'reports' => [
            ['Contractor-wise Worker Count', 'contractors', "SELECT c.contractor_name, COUNT(w.id) as workers FROM contractors c LEFT JOIN workmen w ON w.contractor_id = c.id GROUP BY c.id ORDER BY workers DESC"],
            ['Department-wise Deployment', 'workmen', "SELECT department, COUNT(*) as count FROM workmen WHERE department IS NOT NULL GROUP BY department"],
            ['Trade-wise Distribution', 'workmen', "SELECT trade, COUNT(*) as count FROM workmen WHERE trade IS NOT NULL GROUP BY trade"],
            ['Worker Transfer (NOC) Report', 'noc_requests', "SELECT * FROM noc_requests ORDER BY created_at DESC"],
        ]
    ],
    'safety' => [
        'reports' => [
            ['Training Pass/Fail Summary', 'training_results', "SELECT result, COUNT(*) as count FROM training_results GROUP BY result"],
            ['Safety Training Failed Workers', 'training_results', "SELECT tr.*, w.name FROM training_results tr JOIN workmen w ON tr.workman_id = w.id WHERE tr.result = 'fail'"],
            ['Biometric Enrollment Report', 'acc_attendance_map', "SELECT biometric_status, COUNT(*) as count FROM acc_attendance_map GROUP BY biometric_status"],
        ]
    ],
    'pass' => [
        'reports' => [
            ['Expired Pass Report', 'gate_passes', "SELECT g.*, w.name FROM gate_passes g JOIN workmen w ON g.workman_id = w.id WHERE g.valid_to < CURDATE() AND g.status = 'active'"],
            ['Active Pass Summary', 'gate_passes', "SELECT pass_type, status, COUNT(*) as count FROM gate_passes GROUP BY pass_type, status"],
        ]
    ],
    'compliance' => [
        'reports' => [
            ['Compliance Pending Contractors', 'compliance', "SELECT DISTINCT c.contractor_name FROM contractors c WHERE c.id NOT IN (SELECT contractor_id FROM compliance WHERE month_year = DATE_FORMAT(CURDATE(),'%Y-%m'))"],
            ['Compliance Rejection Summary', 'compliance', "SELECT c.contractor_name, comp.type, comp.month_year, comp.validation_errors FROM compliance comp JOIN contractors c ON comp.contractor_id = c.id WHERE comp.status = 'rejected'"],
        ]
    ],
    'blocking' => [
        'reports' => [
            ['Blocked Workers Report', 'workmen', "SELECT w.name, w.aadhaar, c.contractor_name FROM workmen w LEFT JOIN contractors c ON w.contractor_id = c.id WHERE w.status = 'blocked'"],
            ['Blocked Contractors Report', 'contractors', "SELECT contractor_name, block_reason, blocked_at FROM contractors WHERE is_blocked = 1 OR status = 'blocked'"],
            ['Block/Unblock History', 'contractor_block_history', "SELECT * FROM contractor_block_history ORDER BY created_at DESC LIMIT 100"],
        ]
    ],
    'attendance' => [
        'reports' => [
            ['Daily Attendance Summary', 'attendance', "SELECT DATE(check_in) as dt, COUNT(*) as present FROM attendance WHERE check_in >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY DATE(check_in) ORDER BY dt DESC"],
            ['Attendance Mismatch Report', 'attendance', "SELECT a.*, w.name FROM attendance a JOIN workmen w ON a.workman_id = w.id WHERE a.check_out IS NULL AND DATE(a.check_in) < CURDATE()"],
        ]
    ],
];

// Parse ID like "workforce_0"
$parts = explode('_', $reportId);
if (count($parts) < 2) {
    echo json_encode(['success' => false, 'message' => 'Invalid Report ID format']);
    exit;
}

$cat = $parts[0];
$idx = (int)$parts[1];

if (!isset($reportCategories[$cat]['reports'][$idx])) {
    echo json_encode(['success' => false, 'message' => 'Report not found']);
    exit;
}

$report = $reportCategories[$cat]['reports'][$idx];
$query = $report[2];

try {
    $data = db_fetch_all($conn, $query);
    echo json_encode([
        'success' => true,
        'title' => $report[0],
        'data' => $data
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
