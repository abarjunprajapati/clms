<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

error_log("API HIT: get_pass_officer_data - " . json_encode($_REQUEST));

$response = ['success' => false, 'data' => [], 'error' => ''];

try {
    include '../include/config.php';

    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    $sql = "
    SELECT 
      gp.id,
      gp.application_no,
      gp.workman_id,
      gp.pass_type,
      gp.valid_from,
      gp.valid_to,
      gp.status,
      gp.acc_card_number,
      w.name,
      w.trade,
      w.temp_id,
      tr.result AS training_result,
      tr.certificate_no,
      tr.attendance_status
    FROM gate_passes gp
    LEFT JOIN workmen w ON gp.workman_id = w.id
    LEFT JOIN training_results tr ON gp.workman_id = tr.workman_id
    WHERE gp.status = 'under_verification'
    ORDER BY gp.id DESC
    ";

    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $response['success'] = true;
    $response['data'] = $data;

} catch (Throwable $e) {
    http_response_code(500);
    $response['error'] = 'Error at line ' . $e->getLine() . ': ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);


