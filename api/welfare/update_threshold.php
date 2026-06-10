<?php
session_start();
header('Content-Type: application/json');
include __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/labour_license_threshold.php';

$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['welfare_admin', 'super_admin'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized — only Welfare Admin can change this setting.']));
}

$threshold = intval($_POST['threshold'] ?? 0);
if ($threshold < 1) {
    die(json_encode(['success' => false, 'message' => 'Threshold must be at least 1.']));
}

try {
    clms_add_labour_license_threshold($conn, $threshold, date('Y-m-d'), '9999-12-31', (int)($_SESSION['user_id'] ?? 0));
    echo json_encode(['success' => true, 'message' => "Threshold updated to $threshold workers."]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
