<?php
$logPath = 'C:\\Users\\Telecon\\.gemini\\antigravity\\brain\\66f11f6b-6817-48aa-946e-116c762a643c\\.system_generated\\logs\\transcript.jsonl';
$fp = fopen($logPath, 'r');
while (($line = fgets($fp)) !== false) {
    if (strpos($line, 'SECTION') !== false || strpos($line, 'Section') !== false) {
        // Let's find matches and display a snippet around them
        preg_match_all('/(SECTION \d+|Section \d+|## \d+\.[^\r\n]+)/', $line, $matches);
        if (!empty($matches[0])) {
            echo "Found headings: " . implode(', ', $matches[0]) . "\n";
        }
    }
}
fclose($fp);
?>
