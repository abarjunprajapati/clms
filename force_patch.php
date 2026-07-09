<?php
header('Content-Type: text/plain');

$base = __DIR__;
echo "Base Directory: $base\n\n";

$csl_path = $base . '/include/payment_csl.php';
$order_path = $base . '/api/payments/create_training_order.php';

echo "Is include/ writable: " . (is_writable($base . '/include') ? "Yes" : "No") . "\n";
echo "Is api/payments/ writable: " . (is_writable($base . '/api/payments') ? "Yes" : "No") . "\n\n";

// Skipped recreating payment_csl.php to avoid breaking dependencies. Please upload include/payment_csl.php directly if needed.

// Try deleting create_training_order.php and recreating it
if (is_writable($base . '/api/payments')) {
    echo "Attempting to delete and recreate create_training_order.php...\n";
    if (file_exists($order_path)) {
        $deleted = @unlink($order_path);
        echo "Deleted old file: " . ($deleted ? "Yes" : "No") . "\n";
    }
    
    // Let's write the correct file content (from csl_payment_order.php)
    $order_content = @file_get_contents($base . '/csl_payment_order.php');
    if ($order_content) {
        $written = @file_put_contents($order_path, $order_content);
        echo "Wrote new create_training_order.php: " . ($written ? "Yes ($written bytes)" : "No") . "\n";
    } else {
        echo "Could not load csl_payment_order.php to copy.\n";
    }
}
