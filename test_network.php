<?php
echo "<h3>Live Server Outbound IP / CURL Test</h3>";

function runCurl($url, $options = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    // output headers
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    // add any extra options
    foreach ($options as $opt => $val) {
        curl_setopt($ch, $opt, $val);
    }
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'response' => $response,
        'error' => $error
    ];
}

echo "<h4>1. curl -v https://ifconfig.me</h4>";
$res1 = runCurl("https://ifconfig.me");
echo "<pre style='background:#f4f4f4; padding:10px; border:1px solid #ccc; white-space:pre-wrap;'>";
if ($res1['error']) echo "Error: " . $res1['error'] . "\n";
echo htmlspecialchars($res1['response']);
echo "</pre>";

echo "<h4>2. curl -v https://api.ipify.org</h4>";
$res2 = runCurl("https://api.ipify.org");
echo "<pre style='background:#f4f4f4; padding:10px; border:1px solid #ccc; white-space:pre-wrap;'>";
if ($res2['error']) echo "Error: " . $res2['error'] . "\n";
echo htmlspecialchars($res2['response']);
echo "</pre>";

echo "<h4>3. curl -I https://ws.cochinshipyard.in</h4>";
$res3 = runCurl("https://ws.cochinshipyard.in", [CURLOPT_NOBODY => true]);
echo "<pre style='background:#f4f4f4; padding:10px; border:1px solid #ccc; white-space:pre-wrap;'>";
if ($res3['error']) echo "Error: " . $res3['error'] . "\n";
echo htmlspecialchars($res3['response']);
echo "</pre>";

echo "<h4>4. curl -I https://wsdev.cochinshipyard.in</h4>";
$res4 = runCurl("https://wsdev.cochinshipyard.in", [CURLOPT_NOBODY => true]);
echo "<pre style='background:#f4f4f4; padding:10px; border:1px solid #ccc; white-space:pre-wrap;'>";
if ($res4['error']) echo "Error: " . $res4['error'] . "\n";
echo htmlspecialchars($res4['response']);
echo "</pre>";

?>
