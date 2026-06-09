<?php
session_start();
header('Content-Type: application/json');
include '../include/config.php';
include '../include/functions.php';

$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "User ID required"]);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(["status" => "error", "message" => "User not found"]);
    exit;
}

// Generate new OTP using shared function
$result = generateOTP($conn, $user_id);

if (is_array($result) && isset($result['error'])) {
    echo json_encode([
        "status" => "error",
        "message" => $result['error']
    ]);
} else {
    echo json_encode([
        "status" => "otp_sent",
        "message" => "New OTP sent",
        "expires_in" => 300
    ]);
}
?>


