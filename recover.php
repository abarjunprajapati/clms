<?php
$lines = file('C:/Users/ARJUN KUMAR/.gemini/antigravity/brain/fcf9a05d-afe2-45b6-9237-a93286ce83d2/.system_generated/logs/transcript_full.jsonl');
$content = '';
foreach($lines as $line) {
    $data = json_decode($line, true);
    if(isset($data['content'])) {
        if(strpos($data['content'], 'File Path: `file:///c:/xampp/htdocs/CLMS1/include/layout.php`') !== false) {
            $content = $data['content'];
            break;
        }
    }
}
file_put_contents('layout_recovered.txt', $content);
echo "Recovered to layout_recovered.txt";
