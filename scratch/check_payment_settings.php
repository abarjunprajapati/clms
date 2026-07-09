<?php
require __DIR__ . '/../include/config.php';

echo "=== payment_settings TABLE ===\n";
$r = $conn->query("SELECT setting_key, setting_value FROM payment_settings ORDER BY setting_key");
if (!$r) {
    echo "Table not found or error: " . $conn->error . "\n";
} else {
    while ($row = $r->fetch_assoc()) {
        echo $row['setting_key'] . " = " . $row['setting_value'] . "\n";
    }
}

echo "\n=== system_settings (payment related) ===\n";
$r2 = $conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE '%payment%' OR setting_key LIKE '%csl%' OR setting_key LIKE '%razorpay%' OR setting_key LIKE '%gateway%'");
if (!$r2) {
    echo "Table not found or error: " . $conn->error . "\n";
} else {
    while ($row = $r2->fetch_assoc()) {
        echo $row['setting_key'] . " = " . $row['setting_value'] . "\n";
    }
}
