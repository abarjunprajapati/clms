<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    
    // Ensure system_settings table exists
    $tableCheck = clms_db_query($conn, "SHOW TABLES LIKE 'system_settings'");
    if (clms_db_num_rows($tableCheck) == 0) {
        echo '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> System settings table not found. <a href="../../api/admin/init_admin_schema.php" target="_blank" class="btn btn-sm btn-primary" style="margin-left:10px;">Initialize Schema</a></div>';
        return;
    }
    
    $settings = db_fetch_all($conn, "SELECT * FROM system_settings ORDER BY setting_group, setting_key");
    $grouped = [];
    foreach($settings as $s) {
        $grouped[$s['setting_group']][] = $s;
    }
    
    $groupMeta = [
        'pass' => ['icon' => 'fa-id-card', 'label' => 'Pass & Validity Settings', 'color' => '#6366f1'],
        'training' => ['icon' => 'fa-graduation-cap', 'label' => 'Training Configuration', 'color' => '#059669'],
        'sap' => ['icon' => 'fa-sync', 'label' => 'SAP Integration', 'color' => '#0284c7'],
        'sms' => ['icon' => 'fa-sms', 'label' => 'SMS Notifications', 'color' => '#d97706'],
        'email' => ['icon' => 'fa-envelope', 'label' => 'Email Configuration', 'color' => '#8b5cf6'],
        'security' => ['icon' => 'fa-shield-alt', 'label' => 'Security & Sessions', 'color' => '#dc2626'],
        'attendance' => ['icon' => 'fa-calendar-check', 'label' => 'Attendance & Biometric', 'color' => '#10b981'],
        'compliance' => ['icon' => 'fa-file-shield', 'label' => 'Compliance Reminders', 'color' => '#f59e0b'],
        'emergency' => ['icon' => 'fa-exclamation-triangle', 'label' => 'Emergency Controls', 'color' => '#ef4444'],
        'general' => ['icon' => 'fa-cog', 'label' => 'General Settings', 'color' => '#64748b'],
    ];
    
    // Get lockdown status
    $lockdownVal = '0';
    foreach($settings as $s) { if($s['setting_key'] == 'system_lockdown') $lockdownVal = $s['setting_value']; }
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-cog" style="color:#6366f1;margin-right:10px;"></i> System Settings</h2>
        <!-- <p class="page-subtitle">Configure pass validity, SAP, SMS, security, and emergency controls.</p> -->
      </div>
      <div style="display:flex;gap:10px;">
        <?php if($lockdownVal == '1'): ?>
        <div class="btn btn-danger" style="pointer-events:none;animation:pulse 2s infinite;"><i class="fas fa-lock"></i> LOCKDOWN ACTIVE</div>
        <?php endif; ?>
        <button class="btn btn-primary" id="saveAllBtn" onclick="saveAllSettings()"><i class="fas fa-save"></i> Save All Changes</button>
      </div>
    </div>

    <!-- Emergency Lockdown Banner -->
    <div id="lockdownBanner" class="card glass" style="margin-bottom:20px;border:2px solid <?= $lockdownVal == '1' ? '#ef4444' : '#10b981' ?>;padding:20px;display:flex;align-items:center;justify-content:space-between;">
      <div style="display:flex;align-items:center;gap:15px;">
        <i class="fas fa-<?= $lockdownVal == '1' ? 'lock' : 'lock-open' ?>" style="font-size:28px;color:<?= $lockdownVal == '1' ? '#ef4444' : '#10b981' ?>;"></i>
        <div>
          <div style="font-weight:700;font-size:16px;">System Lockdown Mode</div>
          <div style="font-size:13px;opacity:0.7;"><?= $lockdownVal == '1' ? 'System is LOCKED. All operations are paused.' : 'System is operational. All modules are active.' ?></div>
        </div>
      </div>
      <div style="display:flex;gap:10px;">
        <?php if($lockdownVal == '0'): ?>
        <button class="btn btn-danger" onclick="toggleLockdown(1)"><i class="fas fa-lock"></i> Activate Lockdown</button>
        <?php else: ?>
        <button class="btn btn-success" onclick="toggleLockdown(0)"><i class="fas fa-lock-open"></i> Deactivate Lockdown</button>
        <?php endif; ?>
      </div>
    </div>

    <!-- Settings Groups -->
    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:20px;">
    <?php foreach($groupMeta as $grpKey => $meta):
        $items = $grouped[$grpKey] ?? [];
        if(empty($items)) continue;
    ?>
    <div class="card glass">
      <div class="card-header" style="border-left:4px solid <?= $meta['color'] ?>;">
        <div class="card-title" style="font-size:15px;"><i class="fas <?= $meta['icon'] ?>" style="color:<?= $meta['color'] ?>;"></i> <?= $meta['label'] ?></div>
      </div>
      <div class="card-body" style="padding:16px;">
        <?php foreach($items as $item):
          $key = htmlspecialchars($item['setting_key']);
          $val = htmlspecialchars($item['setting_value']);
          $desc = htmlspecialchars($item['description'] ?? '');
          $isToggle = in_array($val, ['0','1']) && (strpos($key, 'enabled') !== false || $key == 'system_lockdown' || strpos($key, 'biometric') !== false);
        ?>
        <div class="setting-row" style="margin-bottom:14px;">
          <label style="font-size:13px;font-weight:600;display:block;margin-bottom:4px;"><?= ucwords(str_replace('_', ' ', $key)) ?></label>
          <?php if($desc): ?><small style="display:block;margin-bottom:6px;opacity:0.6;font-size:11px;"><?= $desc ?></small><?php endif; ?>
          <?php if($isToggle): ?>
            <label class="toggle-switch">
              <input type="checkbox" class="setting-input" data-key="<?= $key ?>" <?= $val == '1' ? 'checked' : '' ?>>
              <span class="toggle-slider"></span>
            </label>
          <?php elseif(strpos($key, 'password') !== false || strpos($key, 'token') !== false || strpos($key, 'api_key') !== false): ?>
            <input type="password" class="form-control setting-input" data-key="<?= $key ?>" value="<?= $val ?>" style="font-size:13px;">
          <?php else: ?>
            <input type="text" class="form-control setting-input" data-key="<?= $key ?>" value="<?= $val ?>" style="font-size:13px;">
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
    </div>

    <div id="settings-toast" class="um-toast" style="display:none;"></div>

    <style>
    .toggle-switch { position:relative; display:inline-block; width:50px; height:26px; }
    .toggle-switch input { opacity:0; width:0; height:0; }
    .toggle-slider { position:absolute; cursor:pointer; inset:0; background:#cbd5e1; border-radius:26px; transition:0.3s; }
    .toggle-slider:before { content:''; position:absolute; width:20px; height:20px; left:3px; bottom:3px; background:#fff; border-radius:50%; transition:0.3s; }
    .toggle-switch input:checked + .toggle-slider { background:#10b981; }
    .toggle-switch input:checked + .toggle-slider:before { transform:translateX(24px); }
    .um-toast { position:fixed; bottom:30px; right:30px; z-index:99999; padding:14px 24px; border-radius:12px; font-size:14px; font-weight:600; color:#fff; display:flex; align-items:center; gap:10px; box-shadow:0 8px 24px rgba(0,0,0,0.2); }
    .um-toast.success { background:#10b981; }
    .um-toast.error { background:#ef4444; }
    @keyframes pulse { 0%{box-shadow:0 0 0 0 rgba(239,68,68,0.7)} 70%{box-shadow:0 0 0 10px rgba(239,68,68,0)} }
    </style>

    <script>
    function saveAllSettings() {
      const btn = document.getElementById('saveAllBtn');
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

      const settings = {};
      document.querySelectorAll('.setting-input').forEach(el => {
        const key = el.getAttribute('data-key');
        if (el.type === 'checkbox') {
          settings[key] = el.checked ? '1' : '0';
        } else {
          settings[key] = el.value;
        }
      });

      fetch('../../api/admin/save_settings.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({settings: settings})
      })
      .then(r => r.json())
      .then(data => {
        showToast(data.message || 'Settings saved!', data.success ? 'success' : 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save All Changes';
      })
      .catch(err => {
        showToast('Network error: ' + err.message, 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save All Changes';
      });
    }

    function toggleLockdown(mode) {
      const action = mode ? 'ACTIVATE' : 'DEACTIVATE';
      if (!confirm('⚠️ ' + action + ' System Lockdown?\n\nThis will ' + (mode ? 'STOP all gate passes, attendance, enrollment, and SAP sync.' : 'RESTORE all normal operations.') + '\n\nAre you sure?')) return;
      if (mode && !confirm('FINAL CONFIRMATION: This is an EMERGENCY action. Proceed?')) return;

      const msg = prompt('Lockdown message (shown to all users):', mode ? 'System under emergency maintenance.' : 'System restored.');
      
      fetch('../../api/admin/system_lockdown.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({lockdown: mode, message: msg || ''})
      })
      .then(r => r.json())
      .then(data => {
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) setTimeout(() => location.reload(), 1000);
      });
    }

    function showToast(msg, type) {
      const t = document.getElementById('settings-toast');
      t.className = 'um-toast ' + type;
      const icons = {success:'check-circle', error:'exclamation-circle'};
      t.innerHTML = '<i class="fas fa-' + (icons[type]||'info-circle') + '"></i> ' + msg;
      t.style.display = 'flex';
      setTimeout(() => t.style.display = 'none', 3500);
    }
    </script>
    <?php
}

renderLayout("System Settings", 'renderContent', $_SESSION['role'], $_SESSION['name']);
