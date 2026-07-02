<?php
$ch = curl_init('https://cslweb.teleconsystems.com/api/contractor/noc_request.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['search_term' => '1234']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$out = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $out);

$response = curl_exec($ch);
$info = curl_getinfo($ch);

rewind($out);
$debug = stream_get_contents($out);
fclose($out);

echo "INFO:\n";
print_r($info);
echo "\nRESPONSE:\n";
echo $response;
echo "\nDEBUG:\n";
echo $debug;
