<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'welfare_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/wage_settings.php';

header('Content-Type: application/json; charset=utf-8');

function wageSettingJson($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        wageSettingJson(['success' => false, 'message' => 'Invalid request payload.'], 400);
    }

    $minimumWage = clms_parse_wage_amount($data['minimum_wage'] ?? '');
    if ($minimumWage === null || $minimumWage < 0) {
        wageSettingJson(['success' => false, 'message' => 'Please enter a valid minimum wage rate.'], 400);
    }

    $saved = clms_set_minimum_certified_wage($conn, $minimumWage, (int)($_SESSION['user_id'] ?? 0));
    wageSettingJson([
        'success' => true,
        'message' => 'Minimum certified wage rate updated successfully.',
        'minimum_wage' => $saved,
    ]);
} catch (Throwable $e) {
    error_log('[UPDATE_WAGE_SETTING] ' . $e->getMessage());
    wageSettingJson(['success' => false, 'message' => 'Minimum wage update failed on server.'], 500);
}
