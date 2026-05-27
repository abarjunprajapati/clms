<?php
// Test reset password API
$data = json_encode([
    'token' => '5ace77cf304a5861d4788262ed62865b7d6eda56a7cdfd219c3db383356879b3',
    'otp' => '672903',
    'new_password' => 'newpassword123'
]);

$ch = curl_init('http://localhost/clms/api/reset_password.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";
?>
