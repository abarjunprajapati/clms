<?php
ob_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once 'include/auth.php';
require_once 'include/config.php';
checkAuth(['contractor', 'customer', 'super_admin', 'welfare_admin', 'welfare_user']);
require_csrf();


if (!$conn) {
    echo json_encode([
        "success" => false,
        "error" => "Database connection failed"
    ]);
    exit;
}

// ✅ read input (JSON or form-data दोनों support)
$data = json_decode(file_get_contents("php://input"), true);

$id = (int)($data['id'] ?? $_POST['id'] ?? 0);
$type = $data['type'] ?? $_POST['type'] ?? '';

if (!$id || !$type) {
    echo json_encode([
        "success" => false,
        "error" => "Invalid params"
    ]);
    exit;
}

// ✅ table mapping
switch ($type) {
    case 'workman':
        $table = 'workmen';
        break;
    case 'supervisor':
        $table = 'supervisors';
        break;
    case 'representative':
        $table = 'representatives';
        break;
    default:
        echo json_encode([
            "success" => false,
            "error" => "Invalid type"
        ]);
        exit;
}

// ✅ prepare statement
$stmt = $conn->prepare("DELETE FROM `$table` WHERE `id` = ?");

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "error" => $conn->error
    ]);
    exit;
}

$stmt->bind_param("i", $id);

// ✅ execute
if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "deleted" => $stmt->affected_rows
    ]);
} else {
    echo json_encode([
        "success" => false,
        "error" => $stmt->error
    ]);
}

$stmt->close();
$conn->close();

ob_end_flush();
exit;
