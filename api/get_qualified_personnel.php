<?php
require_once 'json_error_handler.php';

$response = ['success' => false, 'status' => 'error', 'data' => [], 'count' => 0];

try {
    require_once __DIR__ . '/../include/config.php';

    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    $sql = "
        SELECT
            tr.workman_id AS id,
            tr.name,
            tr.trade AS role,
            COALESCE(w.tempId, CONCAT('TMP-', tr.workman_id)) AS tempId,
            tr.result AS training,
            tr.attendance_status
        FROM training_results tr
        LEFT JOIN workmen w ON w.id = tr.workman_id
        WHERE LOWER(tr.result) = 'qualified'
          AND LOWER(tr.attendance_status) = 'present'
        ORDER BY tr.name ASC
    ";

    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $response = [
        'success' => true,
        'status' => 'success',
        'data' => $data,
        'count' => count($data)
    ];
} catch (Throwable $e) {
    http_response_code(500);
    $response['error'] = $e->getMessage();
}

jsonErrorFlush();
echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>

