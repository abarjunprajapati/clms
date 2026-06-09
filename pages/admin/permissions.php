<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    
    $tableCheck = clms_db_query($conn, "SHOW TABLES LIKE 'role_permissions'");
    $hasTable = ($tableCheck && clms_db_num_rows($tableCheck) > 0);
    
    $roles = ['welfare_admin','welfare_user','safety_user','front_line_user','pass_user','contractor'];
    $modules = ['dashboard','users','contractors','workmen','documents','training','gate_pass','compliance','attendance','reports','sap','settings','master_data','audit_logs','notifications','blocking'];
    $permCols = ['can_view','can_create','can_edit','can_delete','can_approve','can_block','can_export','can_override','can_sync_sap','can_manage_settings','can_assign_roles'];
    
    $perms = [];
    if ($hasTable) {
        $rows = db_fetch_all($conn, "SELECT * FROM role_permissions");
        foreach($rows as $r) {
            $perms[$r['role_name']][$r['module']] = $r;
        }
    }
    
    $roleLabels = [
        'welfare_admin'=>'Welfare Admin','welfare_user'=>'Welfare User','safety_user'=>'Safety User',
        'front_line_user'=>'Frontline','pass_user'=>'Pass Issuer','contractor'=>'Contractor'
    ];
    $permLabels = [
        'can_view'=>'View','can_create'=>'Create','can_edit'=>'Edit','can_delete'=>'Delete',
        'can_approve'=>'Approve','can_block'=>'Block','can_export'=>'Export','can_override'=>'Override',
        'can_sync_sap'=>'SAP Sync','can_manage_settings'=>'Settings','can_assign_roles'=>'Assign Roles'
    ];
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-key" style="color:#6366f1;margin-right:10px;"></i> Role Permission Matrix</h2>
        <!-- <p class="page-subtitle">Granular control — Super Admin always has ALL permissions. Configure other roles below.</p> -->
      </div>
      <div style="display:flex;gap:10px;">
        <select class="form-control" id="roleFilter" onchange="filterByRole()" style="width:200px;">
          <option value="">All Roles</option>
          <?php foreach($roles as $r): ?>
          <option value="<?= $r ?>"><?= $roleLabels[$r] ?? ucfirst($r) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" id="savePerm" onclick="savePermissions()"><i class="fas fa-save"></i> Save Changes</button>
      </div>
    </div>

    <?php if(!$hasTable): ?>
    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Permission table not found. <a href="../../api/admin/init_admin_schema.php" target="_blank">Initialize Schema</a></div>
    <?php else: ?>
    
    <?php foreach($roles as $role): ?>
    <div class="card glass role-section" data-role="<?= $role ?>" style="margin-bottom:16px;">
      <div class="card-header" style="padding:12px 20px;">
        <div class="card-title" style="font-size:14px;">
          <span class="badge" style="background:<?= $role=='welfare_admin'?'#7c3aed':($role=='safety_user'?'#059669':'#6366f1') ?>;color:#fff;font-size:11px;margin-right:8px;"><?= $roleLabels[$role] ?? $role ?></span>
          Module Permissions
        </div>
      </div>
      <div class="card-body" style="padding:0;overflow-x:auto;">
        <table class="data-table" style="font-size:12px;">
          <thead>
            <tr>
              <th style="min-width:140px;">Module</th>
              <?php foreach($permLabels as $pk => $pl): ?>
              <th style="text-align:center;min-width:70px;"><?= $pl ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach($modules as $mod):
              $p = $perms[$role][$mod] ?? [];
            ?>
            <tr>
              <td><strong><?= ucwords(str_replace('_',' ',$mod)) ?></strong></td>
              <?php foreach($permCols as $col):
                $checked = !empty($p[$col]) ? 'checked' : '';
              ?>
              <td style="text-align:center;">
                <input type="checkbox" class="perm-check" data-role="<?= $role ?>" data-module="<?= $mod ?>" data-perm="<?= $col ?>" <?= $checked ?>>
              </td>
              <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <div id="perm-toast" class="um-toast" style="display:none;"></div>

    <style>
    .perm-check { width:16px; height:16px; cursor:pointer; accent-color:#6366f1; }
    .um-toast { position:fixed; bottom:30px; right:30px; z-index:99999; padding:14px 24px; border-radius:12px; font-size:14px; font-weight:600; color:#fff; display:flex; align-items:center; gap:10px; box-shadow:0 8px 24px rgba(0,0,0,0.2); }
    .um-toast.success { background:#10b981; }
    .um-toast.error { background:#ef4444; }
    </style>

    <script>
    function filterByRole() {
      const val = document.getElementById('roleFilter').value;
      document.querySelectorAll('.role-section').forEach(s => {
        s.style.display = (!val || s.getAttribute('data-role') === val) ? 'block' : 'none';
      });
    }

    function savePermissions() {
      const btn = document.getElementById('savePerm');
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

      const permMap = {};
      document.querySelectorAll('.perm-check').forEach(cb => {
        const role = cb.getAttribute('data-role');
        const mod = cb.getAttribute('data-module');
        const perm = cb.getAttribute('data-perm');
        const key = role + '|' + mod;
        if (!permMap[key]) permMap[key] = {role_name: role, module: mod};
        permMap[key][perm] = cb.checked ? 1 : 0;
      });

      const permissions = Object.values(permMap);

      fetch('../../api/admin/save_permissions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({permissions})
      })
      .then(r => r.json())
      .then(data => {
        showToast(data.message, data.success ? 'success' : 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
      })
      .catch(err => {
        showToast('Error: ' + err.message, 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
      });
    }

    function showToast(msg, type) {
      const t = document.getElementById('perm-toast');
      t.className = 'um-toast ' + type;
      t.innerHTML = '<i class="fas fa-' + (type==='success'?'check-circle':'exclamation-circle') + '"></i> ' + msg;
      t.style.display = 'flex';
      setTimeout(() => t.style.display = 'none', 3500);
    }
    </script>
    <?php
}

renderLayout("Permissions Matrix", 'renderContent', $_SESSION['role'], $_SESSION['name']);
