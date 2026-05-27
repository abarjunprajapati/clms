<?php
/** Save System Settings — Grouped */
require_once __DIR__ . '/admin_middleware.php';
$admin = requireAdmin();
$input = getJsonInput();

if (empty($input['settings']) || !is_array($input['settings'])) {
    jsonError('No settings provided');
}

$updated = 0;
foreach ($input['settings'] as $key => $value) {
    $exists = db_count($conn, "SELECT COUNT(*) c FROM system_settings WHERE setting_key=?", 's', [$key]);
    if ($exists) {
        db_execute($conn, "UPDATE system_settings SET setting_value=?, updated_by=? WHERE setting_key=?", 'sis', [$value, $admin['user_id'], $key]);
    } else {
        $group = $input['group'] ?? 'general';
        db_execute($conn, "INSERT INTO system_settings (setting_key, setting_value, setting_group, updated_by) VALUES (?,?,?,?)", 'sssi', [$key, $value, $group, $admin['user_id']]);
    }
    $updated++;
}

logAdminActivity($conn, 'settings_updated', 'settings', null, null, $input['settings'], 'info');
jsonSuccess("$updated settings saved successfully");
