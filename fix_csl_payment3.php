<?php
/**
 * CSL DB Fix v3 — Debug INSERT failure + force save via REPLACE INTO
 * Upload to: https://cslweb.teleconsystems.com/fix_csl_payment3.php
 * Run ONCE then DELETE.
 */
require_once __DIR__ . '/include/config.php';

echo "<pre><h2>CSL DB Fix v3</h2>\n";

// Check table structure first
$result = $conn->query("DESCRIBE system_settings");
echo "=== system_settings columns ===\n";
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " | " . $row['Type'] . " | Null:" . $row['Null'] . " | Default:" . $row['Default'] . " | Extra:" . $row['Extra'] . "\n";
}

echo "\n=== Trying REPLACE INTO for missing keys ===\n";

$settings = [
    'csl_ws_url'    => 'https://wsdev.cochinshipyard.in/api/cxf/paymentws/services/payment/createOrder',
    'csl_source_ip' => '52.172.135.84',
];

foreach ($settings as $key => $value) {
    // First check if row exists
    $stmt = $conn->prepare("SELECT id FROM system_settings WHERE setting_key = ? LIMIT 1");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $res = $stmt->get_result();
    $existing = $res->fetch_assoc();
    $stmt->close();

    if ($existing) {
        // UPDATE by id to avoid unique key conflict
        $id = $existing['id'];
        $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('si', $value, $id);
        $stmt->execute();
        echo ($stmt->affected_rows >= 0 ? "✅ UPDATED" : "❌ FAILED") . " (id=$id): $key\n";
        $stmt->close();
    } else {
        // INSERT — try without id first
        $conn->query("SET foreign_key_checks = 0");
        $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_group, description, updated_at) VALUES (?, ?, 'payment', 'CSL setting', NOW())");
        $stmt->bind_param('ss', $key, $value);
        $ok = $stmt->execute();
        $err = $stmt->error;
        $stmt->close();
        if ($ok) {
            echo "✅ INSERTED: $key = $value\n";
        } else {
            echo "❌ INSERT FAILED: $key\n   ERROR: $err\n";
            // Try with explicit NULL id
            $stmt2 = $conn->prepare("INSERT INTO system_settings (id, setting_key, setting_value, setting_group, description, updated_at) SELECT COALESCE(MAX(id),0)+1, ?, ?, 'payment', 'CSL setting', NOW() FROM system_settings");
            $stmt2->bind_param('ss', $key, $value);
            $ok2 = $stmt2->execute();
            $err2 = $stmt2->error;
            $stmt2->close();
            echo ($ok2 ? "✅ INSERTED (with computed id)" : "❌ STILL FAILED: $err2") . ": $key\n";
        }
    }
}

echo "\n=== Final DB Values ===\n";
$keys = ['csl_ws_url', 'csl_source_ip', 'payment_gateway_provider', 'payment_gateway_key_secret'];
foreach ($keys as $key) {
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $val = $r['setting_value'] ?? '(NOT IN DB)';
    if (str_contains($key, 'secret') && strlen($val) > 6) {
        $val = substr($val, 0, 6) . str_repeat('*', strlen($val) - 6);
    }
    echo "$key = $val\n";
}

echo "\n⚠️  DELETE this file after running!\n</pre>";
