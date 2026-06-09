<?php
session_start();
$_SESSION['role'] = 'contractor';
$_SESSION['name'] = 'Test Contractor';
$_SESSION['user_id'] = 1;
$_SESSION['logged_in'] = true;
$_SESSION['last_activity'] = time();

// Output buffering is handled directly around the include block

// Include config and the page
require_once __DIR__ . '/../include/config.php';
chdir(__DIR__ . '/../pages/contractor');
ob_start();
try {
    include 'compliance.php';
} catch (Throwable $e) {
    echo "Error including: " . $e->getMessage() . "\n";
}
$captured_html = ob_get_clean();

if (isset($captured_html)) {
    $lines = explode("\n", $captured_html);
    echo "Total rendered lines: " . count($lines) . "\n";
    if (isset($lines[276])) { // 0-indexed line 277 is index 276
        echo "Line 276 (0-indexed): " . htmlspecialchars($lines[276]) . "\n";
        echo "Line 277 (0-indexed): " . htmlspecialchars($lines[277]) . "\n";
        echo "Line 278 (0-indexed): " . htmlspecialchars($lines[278]) . "\n";
        
        echo "\n=== Lines 260 to 290 ===\n";
        for ($i = 260; $i <= 290; $i++) {
            if (isset($lines[$i])) {
                echo ($i + 1) . ": " . $lines[$i] . "\n";
            }
        }
    } else {
        echo "Rendered HTML has less than 277 lines.\n";
    }
} else {
    echo "Failed to capture HTML.\n";
}
?>
