<?php
require_once '../../include/config.php';
require_once '../api_helper.php';

header('Content-Type: application/json');

if (!in_array($_SESSION['role'] ?? '', ['super_admin', 'admin', 'welfare_admin'])) {
    apiError('Unauthorized', 403);
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    if ($action === 'create') {
        $wo_no = $data['work_order_no'] ?? '';
        $c_code = $data['customer_code'] ?? '';
        $v_code = $data['vendor_code'] ?? '';
        $proj = $data['project_name'] ?? 'General Project';
        $dept = $data['department'] ?? 'General';
        $start = $data['start_date'] ?? date('Y-m-d');
        $end = $data['end_date'] ?? date('Y-m-d', strtotime('+1 year'));

        if (empty($wo_no) || empty($c_code) || empty($v_code)) {
            apiError('Work Order No, Customer, and Vendor are required.');
        }

        $sql = "INSERT INTO work_orders (work_order_no, customer_code, vendor_code, project_name, department, start_date, end_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $ok = db_execute($conn, $sql, 'sssssss', [$wo_no, $c_code, $v_code, $proj, $dept, $start, $end]);
        if (!$ok) {
            throw new Exception("Database insert failed: " . clms_db_error($conn));
        }
        apiSuccess(['message' => 'Work Order Mapping created successfully.']);

    } elseif ($action === 'delete') {
        $id = $data['id'] ?? 0;
        $ok = db_execute($conn, "DELETE FROM work_orders WHERE id = ?", 'i', [$id]);
        if (!$ok) throw new Exception("Database delete failed: " . clms_db_error($conn));
        apiSuccess(['message' => 'Mapping deleted.']);

    } elseif ($action === 'toggle_status') {
        $id = $data['id'] ?? 0;
        $row = db_single($conn, "SELECT wo_status FROM work_orders WHERE id = ?", 'i', [$id]);
        $new_status = ($row['wo_status'] === 'ACTIVE') ? 'CLOSED' : 'ACTIVE';
        $ok = db_execute($conn, "UPDATE work_orders SET wo_status = ? WHERE id = ?", 'si', [$new_status, $id]);
        if (!$ok) throw new Exception("Database update failed: " . clms_db_error($conn));
        apiSuccess(['message' => "Status updated to $new_status"]);
    } else {
        apiError('Invalid action');
    }
} catch (Throwable $e) {
    apiError($e->getMessage(), 500);
}
?>
