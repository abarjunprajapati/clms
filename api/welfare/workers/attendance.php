<?php
require_once '../../../include/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    $worker_id = isset($_GET['worker_id']) ? (int)$_GET['worker_id'] : 0;
    $month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
    $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

    if (!$worker_id) {
        throw new Exception("Worker ID required");
    }

    $query = "SELECT * FROM worker_attendance 
              WHERE worker_id = $worker_id 
              AND MONTH(attendance_date) = $month 
              AND YEAR(attendance_date) = $year
              ORDER BY attendance_date ASC";
              
    $result = clms_db_query($conn, $query);
    if (!$result) {
        throw new Exception(clms_db_error($conn));
    }
    
    $data = [];
    $total_present = 0;
    while ($row = clms_db_fetch_assoc($result)) {
        $data[] = $row;
        if (!empty($row['in_time'])) {
            $total_present++;
        }
    }

    echo json_encode([
        'status' => 'success', 
        'data' => $data,
        'summary' => [
            'total_present' => $total_present,
            'month' => $month,
            'year' => $year
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
