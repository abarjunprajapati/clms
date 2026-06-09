<?php
require_once '../../../include/config.php';
header('Content-Type: application/json');

try {
    // Basic server-side pagination and filtering for Enrolled Workers
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;

    $search = isset($_GET['search']) ? clms_db_real_escape_string($conn, $_GET['search']) : '';
    $status = isset($_GET['status']) ? clms_db_real_escape_string($conn, $_GET['status']) : '';
    $contractor_id = isset($_GET['contractor_id']) ? (int)$_GET['contractor_id'] : 0;
    $department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
    $trade = isset($_GET['trade']) ? clms_db_real_escape_string($conn, $_GET['trade']) : '';
    
    $where = "w.worker_status != 'Deleted'";
    if ($search) {
        $where .= " AND (w.aadhaar_no LIKE '%$search%' OR w.acc_no LIKE '%$search%' OR w.mobile_no LIKE '%$search%' OR EXISTS (SELECT 1 FROM workmen wm WHERE wm.id = w.worker_id AND wm.name LIKE '%$search%'))";
    }
    if ($status) {
        $where .= " AND w.worker_status = '$status'";
    }
    if ($contractor_id) {
        $where .= " AND w.contractor_id = $contractor_id";
    }
    if ($department_id) {
        $where .= " AND w.department_id = $department_id";
    }
    if ($trade) {
        $where .= " AND w.trade = '$trade'";
    }

    $query = "SELECT w.*, wm.name as worker_name, wm.gender, wm.mobile as workmen_mobile, c.contractor_name as contractor_name 
              FROM worker_master w 
              LEFT JOIN workmen wm ON w.worker_id = wm.id
              LEFT JOIN contractors c ON w.contractor_id = c.id
              WHERE $where 
              ORDER BY w.created_at DESC 
              LIMIT $offset, $limit";
              
    $result = clms_db_query($conn, $query);
    
    if (!$result) {
        throw new Exception(clms_db_error($conn));
    }
    
    $data = [];
    while ($row = clms_db_fetch_assoc($result)) {
        $data[] = $row;
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM worker_master w WHERE $where";
    $countResult = clms_db_query($conn, $countQuery);
    $total = clms_db_fetch_assoc($countResult)['total'];

    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
