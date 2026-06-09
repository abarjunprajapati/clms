<?php
// Test logout API - need to start session first
session_start();

// Simulate login
$_SESSION['user_id'] = 1;
$_SESSION['contractor_id'] = 'CONT-2024-001';
$_SESSION['role'] = 'contractor';
$_SESSION['name'] = 'Test Contractor';

$data = json_encode([]);

$ch = curl_init('http://localhost/clms/api/logout.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";
?>
