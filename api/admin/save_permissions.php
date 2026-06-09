<?php
/** Save Role Permissions Matrix */
require_once __DIR__ . '/admin_middleware.php';
$admin = requireAdmin();
$input = getJsonInput();

if (empty($input['permissions']) || !is_array($input['permissions'])) {
    jsonError('No permissions data provided');
}

$saved = 0;
foreach ($input['permissions'] as $perm) {
    $role = $perm['role_name'] ?? '';
    $module = $perm['module'] ?? '';
    if (!$role || !$module) continue;
    
    $exists = db_count($conn, "SELECT COUNT(*) c FROM role_permissions WHERE role_name=? AND module=?", 'ss', [$role, $module]);
    
    if ($exists) {
        db_execute($conn, "UPDATE role_permissions SET can_view=?, can_create=?, can_edit=?, can_delete=?, can_approve=?, can_block=?, can_export=?, can_override=?, can_sync_sap=?, can_manage_settings=?, can_assign_roles=? WHERE role_name=? AND module=?",
            'iiiiiiiiiiiss',
            [
                $perm['can_view'] ?? 0, $perm['can_create'] ?? 0, $perm['can_edit'] ?? 0,
                $perm['can_delete'] ?? 0, $perm['can_approve'] ?? 0, $perm['can_block'] ?? 0,
                $perm['can_export'] ?? 0, $perm['can_override'] ?? 0, $perm['can_sync_sap'] ?? 0,
                $perm['can_manage_settings'] ?? 0, $perm['can_assign_roles'] ?? 0,
                $role, $module
            ]);
    } else {
        db_execute($conn, "INSERT INTO role_permissions (role_name, module, can_view, can_create, can_edit, can_delete, can_approve, can_block, can_export, can_override, can_sync_sap, can_manage_settings, can_assign_roles) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
            'ssiiiiiiiiiii',
            [
                $role, $module,
                $perm['can_view'] ?? 0, $perm['can_create'] ?? 0, $perm['can_edit'] ?? 0,
                $perm['can_delete'] ?? 0, $perm['can_approve'] ?? 0, $perm['can_block'] ?? 0,
                $perm['can_export'] ?? 0, $perm['can_override'] ?? 0, $perm['can_sync_sap'] ?? 0,
                $perm['can_manage_settings'] ?? 0, $perm['can_assign_roles'] ?? 0
            ]);
    }
    $saved++;
}

logAdminActivity($conn, 'permissions_updated', 'role_permissions', null, null, ['count' => $saved], 'warning');
jsonSuccess("$saved permission entries saved");
