<?php
session_start();
include '../include/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$stmt = $conn->prepare("SELECT COUNT(*) as unread FROM notifications WHERE (user_id = ? OR role_target = ?) AND is_read = 0 AND is_deleted = 0");
$stmt->bind_param("is", $user_id, $role);
$stmt->execute();
$result = $stmt->get_result();
$count = $result->fetch_assoc()['unread'];

echo json_encode([
    "status" => "success",
    "unread" => (int)$count
]);
?>


