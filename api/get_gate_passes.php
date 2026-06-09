<?php
require_once 'helpers.php';
require_once __DIR__ . '/../include/config.php';

try {
    $input = getApiInput();
    $applicationNo = $input['application_id'] ?? getApplicationId($input);
    if (!$applicationNo) {
        apiError('application_id is required', 400);
    }

    $rows = db_fetch_all(
        $conn,
        "SELECT id, temp_id, name, worker_type AS role, trade, training_status,
                safety_training_status
         FROM workmen
         WHERE application_no = ?
           AND status NOT IN ('blocked','expired','rejected')
           AND (training_status IN ('pass','passed','training_passed','qualified','completed') OR safety_training_status = 1)
         ORDER BY name",
        's',
        [$applicationNo]
    );

    $data = array_map(function ($row) {
        return [
            'id' => (int)$row['id'],
            'workman_id' => (int)$row['id'],
            'name' => $row['name'] ?: 'N/A',
            'role' => $row['role'] ?: ($row['trade'] ?: 'workman'),
            'trade' => $row['trade'] ?: '',
            'temp_id' => $row['temp_id'] ?: '-',
            'training' => $row['training_status'] ?: ((int)$row['safety_training_status'] === 1 ? 'pass' : 'pending'),
            'training_result' => $row['training_status'] ?: 'pending',
            'attendance_status' => 'present',
        ];
    }, $rows);

    apiSuccess($data, count($data) . ' gate pass eligible workers found');
} catch (Throwable $e) {
    apiSuccess([], 'No eligible workers for gate pass');
}
?>

