<?php
session_start();
include '../include/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'contractor';

$sql = "SELECT * FROM notifications 
        WHERE (user_id = ? OR role_target = ?) 
        AND is_deleted = 0
        ORDER BY created_at DESC 
        LIMIT 20";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $user_id, $role);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        "id" => $row["id"],
        "title" => $row["title"],
        "message" => $row["message"],
        "notification_type" => $row["notification_type"],
        "is_read" => $row["is_read"] == 0 ? false : true,
        "created_at" => date('M j, Y g:i A', strtotime($row["created_at"])),
        "icon" => getIcon($row["notification_type"]),
        "channel" => "email,sms,push"
    ];
}

function getIcon($type) {
    switch($type) {
        case 'success': return '✅';
        case 'warning': return '⚠️';
        case 'error': return '❌';
        default: return 'ℹ️';
    }
}

echo json_encode([
    "status" => "success",
    "notifications" => $data,
    "total" => count($data)
]);
?>


