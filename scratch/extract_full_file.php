<?php
$logFile = 'C:\\Users\\ARJUN KUMAR\\.gemini\\antigravity\\brain\\1d082b21-5c94-4819-ae9b-afbe1e0ad5b0\\.system_generated\\logs\\transcript.jsonl';
if (!file_exists($logFile)) {
    die("Log file not found.\n");
}

$handle = fopen($logFile, 'r');
$contents_found = [];
while (($line = fgets($handle)) !== false) {
    $data = json_decode($line, true);
    if (!$data) continue;
    
    // Look for tool calls or outputs for view_file on approve_contractors.php
    if (isset($data['type']) && $data['type'] === 'VIEW_FILE' && isset($data['content']) && strpos($data['content'], 'approve_contractors.php') !== false) {
        $contents_found[] = $data['content'];
    }
}
fclose($handle);

echo "Found " . count($contents_found) . " views.\n";
foreach ($contents_found as $idx => $content) {
    echo "View $idx size: " . strlen($content) . " bytes\n";
    file_put_contents("scratch/view_extract_$idx.txt", $content);
}
?>
