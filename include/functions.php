<?php

function generateOTP($conn, $user_id)
{
    if (!$conn || !$user_id) {
        return ['error' => 'Invalid request'];
    }

    $otp = rand(100000, 999999);
    $expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

    $stmt = $conn->prepare("
        UPDATE users 
        SET otp = ?, otp_expiry = ?
        WHERE id = ?
    ");

    $stmt->bind_param("ssi", $otp, $expiry, $user_id);

    if ($stmt->execute()) {
        return [
            'otp' => $otp,
            'expiry' => $expiry
        ];
    }

    return ['error' => 'OTP generation failed'];
}
?>
