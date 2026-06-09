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
    
    if (!$worker_id) {
        throw new Exception("Worker ID required");
    }

    $query = "SELECT l.*, u.name as user_name 
              FROM worker_audit_logs l
              LEFT JOIN users u ON l.created_by = u.id
              WHERE l.worker_id = $worker_id
              ORDER BY l.created_at DESC";
              
    $result = clms_db_query($conn, $query);
    if (!$result) {
        throw new Exception(clms_db_error($conn));
    }
    
    $data = [];
    while ($row = clms_db_fetch_assoc($result)) {
        // Decode JSON values for frontend convenience
        $row['old_values'] = json_decode($row['old_values'], true);
        $row['new_values'] = json_decode($row['new_values'], true);
        $data[] = $row;
    }

    echo json_encode(['status' => 'success', 'data' => $data]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
