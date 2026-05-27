<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    
    // Check roles table
    $roles = db_fetch_all($conn, "SELECT r.*, (SELECT COUNT(*) FROM users u WHERE u.role = r.role_name) as user_count FROM roles r ORDER BY r.id");
    if(empty($roles)) {
        echo '<div class="alert alert-warning"><i class="fas fa-info-circle"></i> No roles found. <a href="../../api/admin/init_admin_schema.php" target="_blank">Initialize Schema</a></div>';
        return;
    }
    
    $roleColors = ['super_admin'=>'#dc2626','welfare_admin'=>'#7c3aed','welfare_user'=>'#6366f1','safety_user'=>'#059669','front_line_user'=>'#d97706','pass_user'=>'#0284c7','contractor'=>'#64748b'];
    $roleIcons = ['super_admin'=>'fa-crown','welfare_admin'=>'fa-user-shield','welfare_user'=>'fa-user-check','safety_user'=>'fa-hard-hat','front_line_user'=>'fa-door-open','pass_user'=>'fa-id-badge','contractor'=>'fa-building'];
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-user-shield" style="color:#6366f1;margin-right:10px;"></i> Role Management</h2>
        <!-- <p class="page-subtitle">System roles with live user counts and descriptions.</p> -->
      </div>
      <div class="action-buttons">
        <a href="permissions.php" class="btn btn-primary"><i class="fas fa-key"></i> Edit Permissions Matrix</a>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px;">
    <?php foreach($roles as $role):
      $rn = $role['role_name'];
      $color = $roleColors[$rn] ?? '#94a3b8';
      $icon = $roleIcons[$rn] ?? 'fa-user';
      $count = $role['user_count'] ?? 0;
    ?>
    <div class="card glass" style="border-top:4px solid <?= $color ?>;">
      <div class="card-body" style="padding:20px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
          <div style="width:44px;height:44px;border-radius:12px;background:<?= $color ?>15;color:<?= $color ?>;display:flex;align-items:center;justify-content:center;font-size:20px;">
            <i class="fas <?= $icon ?>"></i>
          </div>
          <div>
            <div style="font-weight:700;font-size:16px;"><?= strtoupper(str_replace('_',' ',$rn)) ?></div>
            <div style="font-size:11px;color:<?= $color ?>;font-weight:600;"><?= ($role['is_system'] ?? 0) ? 'System Role' : 'Custom Role' ?></div>
          </div>
        </div>
        <p style="font-size:12px;opacity:0.7;min-height:40px;margin-bottom:12px;"><?= htmlspecialchars($role['description'] ?? 'No description.') ?></p>
        <div style="display:flex;justify-content:space-between;align-items:center;padding-top:12px;border-top:1px solid rgba(0,0,0,0.06);">
          <div>
            <span style="font-size:24px;font-weight:800;color:<?= $color ?>;"><?= $count ?></span>
            <span style="font-size:12px;opacity:0.6;margin-left:4px;">Active Users</span>
          </div>
          <a href="users.php?role=<?= $rn ?>" class="btn btn-sm btn-outline">View Users</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php
}

renderLayout("Role Management", 'renderContent', $_SESSION['role'], $_SESSION['name']);
