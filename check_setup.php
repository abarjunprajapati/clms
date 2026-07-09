<?php
header('Content-Type: text/plain');

$base = __DIR__;
echo "Base Directory: $base\n\n";

// 1. Check .htaccess
$htaccess = $base . '/.htaccess';
if (file_exists($htaccess)) {
    echo "=== .htaccess CONTENT ===\n";
    echo file_get_contents($htaccess);
    echo "\n=========================\n\n";
} else {
    echo ".htaccess not found at $htaccess\n\n";
}

// 2. Check override files
$override = $base . '/csl_override.php';
echo "csl_override.php exists: " . (file_exists($override) ? "Yes (" . filesize($override) . " bytes)" : "No") . "\n\n";

// 3. Test if we can make the include directory writable or patch it
$targetFile = $base . '/include/payment_csl.php';
if (file_exists($targetFile)) {
    echo "payment_csl.php permissions: " . substr(sprintf('%o', fileperms($targetFile)), -4) . "\n";
    echo "Is payment_csl.php writable: " . (is_writable($targetFile) ? "Yes" : "No") . "\n";
    echo "Is include/ directory writable: " . (is_writable($base . '/include') ? "Yes" : "No") . "\n";
    
    // Try chmod to make it writable
    @chmod($targetFile, 0666);
    echo "After chmod 0666 - Is writable: " . (is_writable($targetFile) ? "Yes" : "No") . "\n";
} else {
    echo "payment_csl.php not found at $targetFile\n";
}
