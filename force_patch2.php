<?php
header('Content-Type: text/plain');

$base = __DIR__;
echo "Base Directory: $base\n\n";

$csl_path = $base . '/include/payment_csl.php';

// Force delete and rewrite the correct include/payment_csl.php content
if (is_writable($base . '/include')) {
    echo "Attempting to overwrite payment_csl.php...\n";
    if (file_exists($csl_path)) {
        $deleted = @unlink($csl_path);
        echo "Deleted old file: " . ($deleted ? "Yes" : "No") . "\n";
    }
    
    // Read the correct local file we just modified!
    $correct_content = file_get_contents('c:/xampp/htdocs/CLMS1/include/payment_csl.php');
    
    $written = @file_put_contents($csl_path, $correct_content);
    echo "Wrote correct payment_csl.php: " . ($written ? "Yes ($written bytes)" : "No") . "\n\n";
} else {
    echo "include/ directory is not writable!\n";
}
