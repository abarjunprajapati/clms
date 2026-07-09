<?php
header('Content-Type: text/plain');
$log = __DIR__ . '/include/csl_debug.log';
if (file_exists($log)) {
    echo "=== CSL DEBUG LOG ===\n";
    echo file_get_contents($log);
} else {
    echo "No log file found at $log\n";
    // Let's check other locations
    $log2 = __DIR__ . '/csl_debug.log';
    if (file_exists($log2)) {
        echo "=== CSL DEBUG LOG (root) ===\n";
        echo file_get_contents($log2);
    } else {
        echo "No log file found at root either.\n";
    }
}
