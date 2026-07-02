<?php
session_start();
$_SESSION['user_id'] = 2; // Contractor
$_SESSION['role'] = 'contractor';
$session_id = session_id();
session_write_close();

$ch = curl_init('http://localhost/CLMS1/api/contractor/noc_request.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['search_term' => '1234']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_COOKIE, "PHPSESSID=" . $session_id);

$response = curl_exec($ch);
echo "RESPONSE:\n";
echo $response;
