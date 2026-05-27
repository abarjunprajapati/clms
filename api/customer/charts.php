<?php
require_once '../../include/config.php';
require_once '../api_helper.php';

header('Content-Type: application/json');

if (($_SESSION['role'] ?? '') !== 'customer') {
    apiError('Unauthorized', 403);
}

$customer_code = $_SESSION['customer_code'] ?? '';

function customerColumnExists(mysqli $conn, string $table, string $column): bool {
    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    return $result && mysqli_num_rows($result) > 0;
}

try {
    // 1. Attendance Trend (Last 14 Days)
    $attendance_trend = [];
    for ($i = 13; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $count = db_count($conn, "
            SELECT COUNT(DISTINCT a.workman_id)
            FROM attendance a
            JOIN workmen w ON w.id = a.workman_id
            JOIN contractors c ON c.id = w.contractor_id
            JOIN work_orders wo ON wo.vendor_code = c.vendor_code
            WHERE DATE(a.check_in) = ? AND wo.customer_code = ? AND wo.wo_status = 'ACTIVE'
        ", 'ss', [$date, $customer_code]);
        
        $attendance_trend[] = [
            'date' => date('d M', strtotime($date)),
            'count' => $count
        ];
    }

    // 2. Worker Distribution (by Contractor)
    $distribution = db_fetch_all($conn, "
        SELECT v.vendor_name as label, COUNT(w.id) as value
        FROM workmen w
        JOIN contractors c ON c.id = w.contractor_id
        LEFT JOIN sap_vendor_master v ON v.vendor_code = c.vendor_code
        JOIN work_orders wo ON wo.vendor_code = c.vendor_code
        WHERE wo.customer_code = ? AND wo.wo_status = 'ACTIVE'
        GROUP BY c.vendor_code, v.vendor_name
    ", 's', [$customer_code]);

    // 3. Pass Status Summary
    $pass_stats = db_fetch_all($conn, "
        SELECT gp.status as label, COUNT(*) as value
        FROM gate_passes gp
        JOIN workmen w ON w.id = gp.workman_id
        JOIN contractors c ON c.id = w.contractor_id
        JOIN work_orders wo ON wo.vendor_code = c.vendor_code
        WHERE wo.customer_code = ? AND wo.wo_status = 'ACTIVE'
        GROUP BY gp.status
    ", 's', [$customer_code]);

    // 4. Safety Qualification Summary
    if (customerColumnExists($conn, 'workmen', 'training_status')) {
        $safety_stats = db_fetch_all($conn, "
            SELECT 
                CASE 
                    WHEN w.training_status IN ('pass','passed','completed','training_passed','qualified') THEN 'Qualified'
                    WHEN w.training_status IN ('fail','failed','training_failed') THEN 'Failed'
                    ELSE 'Pending'
                END as label, 
                COUNT(*) as value
            FROM workmen w
            JOIN contractors c ON c.id = w.contractor_id
            JOIN work_orders wo ON wo.vendor_code = c.vendor_code
            WHERE wo.customer_code = ? AND wo.wo_status = 'ACTIVE'
            GROUP BY label
        ", 's', [$customer_code]);
    } else {
        $pending = db_count($conn, "
            SELECT COUNT(*)
            FROM workmen w
            JOIN contractors c ON c.id = w.contractor_id
            JOIN work_orders wo ON wo.vendor_code = c.vendor_code
            WHERE wo.customer_code = ? AND wo.wo_status = 'ACTIVE'
        ", 's', [$customer_code]);
        $safety_stats = [['label' => 'Pending', 'value' => $pending]];
    }

    // 5. Department-wise Manpower
    $dept_distribution = db_fetch_all($conn, "
        SELECT department as label, COUNT(*) as value
        FROM work_orders
        WHERE customer_code = ? AND wo_status = 'ACTIVE'
        GROUP BY department
    ", 's', [$customer_code]);

    apiSuccess([
        'attendance_trend' => $attendance_trend,
        'distribution' => $distribution,
        'pass_stats' => $pass_stats,
        'safety_stats' => $safety_stats,
        'dept_distribution' => $dept_distribution
    ]);

} catch (Throwable $e) {
    apiError($e->getMessage(), 500);
}
?>
