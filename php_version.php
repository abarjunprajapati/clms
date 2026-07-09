<?php
echo "PHP Version: " . PHP_VERSION . "\n";
echo "IP via file_get_contents: " . @file_get_contents('https://api.ipify.org') . "\n";

// Fetch via cURL
$ch = curl_init('https://api.ipify.org');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$curl_ip = curl_exec($ch);
curl_close($ch);
echo "IP via cURL: " . ($curl_ip ?: 'Failed to fetch') . "\n";
?>
