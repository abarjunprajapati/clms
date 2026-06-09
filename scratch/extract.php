<?php
$logPath = 'C:\\Users\\Telecon\\.gemini\\antigravity\\brain\\66f11f6b-6817-48aa-946e-116c762a643c\\.system_generated\\logs\\transcript.jsonl';
if (!file_exists($logPath)) {
    die("Log file not found at: $logPath\n");
}
$firstLine = fgets(fopen($logPath, 'r'));
$json = json_decode($firstLine, true);
if (isset($json['content'])) {
    file_put_contents('scratch/original_request.txt', $json['content']);
    echo "Successfully extracted request to scratch/original_request.txt\n";
} else {
    echo "Error: No 'content' field in the first line of transcript.\n";
}
?>
