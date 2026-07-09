<?php
header('Content-Type: text/plain');
// Read the live payment_csl.php content
$file = __DIR__ . '/include/payment_csl.php';
if (file_exists($file)) {
    echo "=== FILE EXISTS: " . filesize($file) . " bytes, modified: " . date('Y-m-d H:i:s', filemtime($file)) . " ===\n\n";
    echo file_get_contents($file);
} else {
    echo "FILE NOT FOUND: $file";
}
