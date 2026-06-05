<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/labour_license_threshold.php';

header('Content-Type: application/json; charset=utf-8');

function labourThresholdJson($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        labourThresholdJson(['success' => false, 'message' => 'Invalid request payload.'], 400);
    }

    clms_add_labour_license_threshold(
        $conn,
        $data['threshold_value'] ?? 0,
        $data['threshold_from_date'] ?? '',
        $data['threshold_to_date'] ?? '9999-12-31',
        (int)($_SESSION['user_id'] ?? 0)
    );

    labourThresholdJson([
        'success' => true,
        'message' => 'Labour license threshold added successfully.',
        'current_threshold' => clms_get_labour_license_threshold($conn),
    ]);
} catch (InvalidArgumentException $e) {
    labourThresholdJson(['success' => false, 'message' => $e->getMessage()], 400);
} catch (Throwable $e) {
    error_log('[UPDATE_LABOUR_LICENSE_THRESHOLD] ' . $e->getMessage());
    labourThresholdJson(['success' => false, 'message' => 'Threshold update failed on server.'], 500);
}
