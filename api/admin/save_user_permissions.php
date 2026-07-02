<?php
/**
 * Save User Extra Permissions
 * Allows Super Admin to grant individual users special access beyond their role
 */
require_once __DIR__ . '/admin_middleware.php';
$admin = requireAdmin();
$input = getJsonInput();

if (empty($input['permissions']) || !is_array($input['permissions'])) {
    jsonError('No permissions data provided');
}

$saved = 0;
$superAdminId = $_SESSION['user_id'] ?? 0;

foreach ($input['permissions'] as $perm) {
    $user_id = (int)($perm['user_id'] ?? 0);
    $module  = trim($perm['module'] ?? '');
    if (!$user_id || !$module) continue;

    $exists = db_count($conn, "SELECT COUNT(*) c FROM user_extra_permissions WHERE user_id=? AND module=?", 'is', [$user_id, $module]);

    if ($exists) {
        db_execute($conn,
            "UPDATE user_extra_permissions SET can_view=?, can_create=?, can_edit=?, can_delete=?, can_approve=?, can_block=?, can_export=?, can_override=?, granted_by=?, granted_at=NOW() WHERE user_id=? AND module=?",
            'iiiiiiiiiis',
            [
                $perm['can_view'] ?? 0, $perm['can_create'] ?? 0, $perm['can_edit'] ?? 0,
                $perm['can_delete'] ?? 0, $perm['can_approve'] ?? 0, $perm['can_block'] ?? 0,
                $perm['can_export'] ?? 0, $perm['can_override'] ?? 0,
                $superAdminId,
                $user_id, $module
            ]
        );
    } else {
        db_execute($conn,
            "INSERT INTO user_extra_permissions (user_id, module, can_view, can_create, can_edit, can_delete, can_approve, can_block, can_export, can_override, granted_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)",
            'isiiiiiiiii',
            [
                $user_id, $module,
                $perm['can_view'] ?? 0, $perm['can_create'] ?? 0, $perm['can_edit'] ?? 0,
                $perm['can_delete'] ?? 0, $perm['can_approve'] ?? 0, $perm['can_block'] ?? 0,
                $perm['can_export'] ?? 0, $perm['can_override'] ?? 0,
                $superAdminId
            ]
        );
    }
    $saved++;
}

logAdminActivity($conn, 'user_permissions_updated', 'user_extra_permissions', null, null, ['count' => $saved, 'granted_by' => $superAdminId], 'warning');
jsonSuccess("$saved user permission entries saved successfully.");
?>
