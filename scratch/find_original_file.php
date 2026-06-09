<?php
$logFile = 'C:\\Users\\ARJUN KUMAR\\.gemini\\antigravity\\brain\\1d082b21-5c94-4819-ae9b-afbe1e0ad5b0\\.system_generated\\logs\\transcript.jsonl';
if (!file_exists($logFile)) {
    die("Log file not found at: $logFile\n");
}

$handle = fopen($logFile, 'r');
if (!$handle) {
    die("Failed to open log file.\n");
}

$original_content = null;
while (($line = fgets($handle)) !== false) {
    $data = json_decode($line, true);
    if (!$data) continue;
    
    // Look for tool outputs containing the file contents of approve_contractors.php
    if (isset($data['content']) && strpos($data['content'], 'File Path: `file:///c:/xampp/htdocs/clms/pages/welfare/approve_contractors.php`') !== false) {
        $original_content = $data['content'];
    }
}
fclose($handle);

if ($original_content) {
    echo "Found content snippet of size " . strlen($original_content) . " bytes.\n";
    file_put_contents('scratch/original_content.txt', $original_content);
    echo "Saved to scratch/original_content.txt\n";
} else {
    echo "Could not find original content in transcript.jsonl.\n";
}
?>
