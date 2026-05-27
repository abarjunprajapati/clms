<?php
/** System Lockdown Control */
require_once __DIR__ . '/admin_middleware.php';
$admin = requireAdmin();
$input = getJsonInput();

$lockdownMode = $input['lockdown'] ?? null;
$message = $input['message'] ?? 'System is under maintenance. Please try again later.';

if ($lockdownMode === null) jsonError('Lockdown mode required (0 or 1)');

$lockdownMode = (int)$lockdownMode;

// Get current state
$current = db_single($conn, "SELECT setting_value FROM system_settings WHERE setting_key='system_lockdown'");
$oldVal = $current ? $current['setting_value'] : '0';

// Update lockdown state
db_execute($conn, "UPDATE system_settings SET setting_value=?, updated_by=? WHERE setting_key='system_lockdown'", 'si', [(string)$lockdownMode, $admin['user_id']]);
db_execute($conn, "UPDATE system_settings SET setting_value=?, updated_by=? WHERE setting_key='lockdown_message'", 'si', [$message, $admin['user_id']]);

$actionLabel = $lockdownMode ? 'ACTIVATED' : 'DEACTIVATED';
logAdminActivity($conn, "system_lockdown_{$actionLabel}", 'system', null, 
    ['lockdown' => $oldVal], 
    ['lockdown' => $lockdownMode, 'message' => $message], 
    'emergency'
);

jsonSuccess("System lockdown $actionLabel", ['lockdown' => $lockdownMode, 'message' => $message]);
