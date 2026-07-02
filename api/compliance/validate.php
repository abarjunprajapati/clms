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

    // Check if muster roll is approved (verified)
    $mrStatus = db_single($conn, "SELECT status FROM muster_rolls WHERE contractor_id = ? AND month_year = ? LIMIT 1", 'is', [$contractorId, $monthYear]);
    if (!$mrStatus || $mrStatus['status'] !== 'verified') {
        throw new Exception("Muster Roll for this wage month ($monthYear) is not approved. Please get it verified first.");
    }

    $start = $monthYear . '-01';
    $end = date('Y-m-t', strtotime($start));

    $workerCount = db_count($conn, "SELECT COUNT(*) FROM workmen WHERE contractor_id=?", 'i', [$contractorId]);
    $attendanceDays = db_count(
        $conn,
        "SELECT COUNT(*) FROM attendance a JOIN workmen w ON a.workman_id=w.id WHERE w.contractor_id=? AND DATE(a.check_in) BETWEEN ? AND ?",
        'iss',
        [$contractorId, $start, $end]
    );
    // Calculate wages dynamically based on workmen certified_wage_rate and attendance check_ins
    $wagesRows = db_fetch_all($conn, "
        SELECT w.certified_wage_rate, COUNT(DISTINCT DATE(a.check_in)) AS attended_days
        FROM workmen w
        JOIN attendance a ON w.id = a.workman_id
        WHERE w.contractor_id = ? AND DATE(a.check_in) BETWEEN ? AND ?
        GROUP BY w.id
    ", 'iss', [$contractorId, $start, $end]);

    $calculatedWageTotal = 0.0;
    foreach ($wagesRows as $r) {
        $rate = (float)($r['certified_wage_rate'] ?? 0);
        $days = (int)($r['attended_days'] ?? 0);
        $calculatedWageTotal += ($rate * $days);
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'contractor_id' => $contractorId,
            'month_year' => $monthYear,
            'worker_count' => $workerCount,
            'attendance_days' => $attendanceDays,
            'wage_total' => $calculatedWageTotal,
            'status' => $workerCount > 0 && $attendanceDays > 0 ? 'ready' : 'missing_base_data',
        ],
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

