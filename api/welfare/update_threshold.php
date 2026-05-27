<?php
session_start();
header('Content-Type: application/json');
include __DIR__ . '/../../include/config.php';

$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['welfare_admin', 'super_admin'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized — only Welfare Admin can change this setting.']));
}

$threshold = intval($_POST['threshold'] ?? 0);
if ($threshold < 1) {
    die(json_encode(['success' => false, 'message' => 'Threshold must be at least 1.']));
}

$ok = db_execute($conn,
    "INSERT INTO system_settings (setting_key, setting_value, description, updated_by, updated_at)
     VALUES ('labour_license_threshold', ?, 'Min workers for mandatory Labour Licence', ?, NOW())
     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by), updated_at = NOW()",
    'si', [$threshold, $_SESSION['user_id'] ?? 0]
);

if ($ok) {
    echo json_encode(['success' => true, 'message' => "Threshold updated to $threshold workers."]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}
