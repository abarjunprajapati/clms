<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/temporary_pass_validity.php';

header('Content-Type: application/json; charset=utf-8');

function tempValidityJson($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        tempValidityJson(['success' => false, 'message' => 'Invalid request payload.'], 400);
    }

    clms_add_temporary_pass_validity(
        $conn,
        $data['validity_days'] ?? 0,
        $data['validity_from_date'] ?? '',
        $data['validity_to_date'] ?? '9999-12-31',
        (int)($_SESSION['user_id'] ?? 0)
    );

    tempValidityJson([
        'success' => true,
        'message' => 'Temporary pass validity added successfully.',
        'current_validity_days' => clms_get_temporary_pass_validity_days($conn),
    ]);
} catch (InvalidArgumentException $e) {
    tempValidityJson(['success' => false, 'message' => $e->getMessage()], 400);
} catch (Throwable $e) {
    error_log('[UPDATE_TEMPORARY_PASS_VALIDITY] ' . $e->getMessage());
    tempValidityJson(['success' => false, 'message' => 'Temporary pass validity update failed on server.'], 500);
}
