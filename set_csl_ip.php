<?php
/**
 * One-time script to set CSL whitelisted IP in system_settings
 * DELETE THIS FILE after use!
 */
require_once __DIR__ . '/include/config.php';

// ← CHANGE THIS to your actual CSL-whitelisted IP
$whitelisted_ip = '52.172.135.84';

$existing = db_single($conn, "SELECT id FROM system_settings WHERE setting_key = 'csl_source_ip' LIMIT 1");
if ($existing) {
    $ok = db_execute($conn, "UPDATE system_settings SET setting_value = ? WHERE setting_key = 'csl_source_ip'", 's', [$whitelisted_ip]);
} else {
    $ok = db_execute($conn, "INSERT INTO system_settings (setting_key, setting_value, created_at) VALUES (?, ?, NOW())", 'ss', ['csl_source_ip', $whitelisted_ip]);
}

echo $ok
    ? "<b style='color:green'>✓ CSL Source IP set to: <code>$whitelisted_ip</code></b><br>Please delete this file now."
    : "<b style='color:red'>✗ Failed to set IP. Check DB connection.</b>";
