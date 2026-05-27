<?php
require_once __DIR__ . '/../../include/auth.php';
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/compliance_schema.php';

header('Content-Type: application/json; charset=utf-8');

try {
    ensureComplianceSchema($conn);
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $contractorId = (int)($input['contractor_id'] ?? 0);
    $monthYear = trim($input['month_year'] ?? date('Y-m'));
    if (!$contractorId) {
        throw new Exception('contractor_id required');
    }

    [$month, $year, $monthYear] = complianceMonthParts($monthYear);
    $start = $monthYear . '-01';
    $end = date('Y-m-t', strtotime($start));

    $workerCount = db_count($conn, "SELECT COUNT(*) FROM workmen WHERE contractor_id=?", 'i', [$contractorId]);
    $attendanceDays = db_count(
        $conn,
        "SELECT COUNT(*) FROM attendance a JOIN workmen w ON a.workman_id=w.id WHERE w.contractor_id=? AND DATE(a.check_in) BETWEEN ? AND ?",
        'iss',
        [$contractorId, $start, $end]
    );
    $wageRow = db_single($conn, "SELECT COALESCE(SUM(salary),0) total FROM wages WHERE contractor_id=? AND month_year=?", 'is', [$contractorId, $monthYear]);

    echo json_encode([
        'success' => true,
        'data' => [
            'contractor_id' => $contractorId,
            'month_year' => $monthYear,
            'worker_count' => $workerCount,
            'attendance_days' => $attendanceDays,
            'wage_total' => (float)($wageRow['total'] ?? 0),
            'status' => $workerCount > 0 && $attendanceDays > 0 ? 'ready' : 'missing_base_data',
        ],
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

