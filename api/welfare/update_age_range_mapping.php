<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/age_range_mapping.php';

header('Content-Type: application/json; charset=utf-8');

function ageRangeJson($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) ageRangeJson(['success' => false, 'message' => 'Invalid request payload.'], 400);

    clms_add_age_range_mapping(
        $conn,
        $data['min_age'] ?? 18,
        $data['max_age'] ?? 60,
        $data['effective_from'] ?? '',
        $data['effective_to'] ?? '9999-12-31',
        (int)($_SESSION['user_id'] ?? 0)
    );

    ageRangeJson([
        'success' => true,
        'message' => 'Age range mapping added successfully.',
        'current_range' => clms_get_active_age_range($conn),
    ]);
} catch (InvalidArgumentException $e) {
    ageRangeJson(['success' => false, 'message' => $e->getMessage()], 400);
} catch (Throwable $e) {
    error_log('[UPDATE_AGE_RANGE_MAPPING] ' . $e->getMessage());
    ageRangeJson(['success' => false, 'message' => 'Age range update failed on server.'], 500);
}
