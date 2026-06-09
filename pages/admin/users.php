<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin', 'welfare_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    $employeeCol = clms_db_query($conn, "SHOW COLUMNS FROM users LIKE 'employee_code'");
    if (!$employeeCol || clms_db_num_rows($employeeCol) === 0) {
        @clms_db_query($conn, "ALTER TABLE users ADD COLUMN employee_code VARCHAR(50) NULL");
    }
    $users = db_fetch_all($conn, "SELECT id, contractor_id, employee_code, name, role, email, mobile, status, must_change_password, created_at FROM users ORDER BY created_at DESC");
    $currentUserId = $_SESSION['user_id'];
    
    // Fetch all available roles for the dropdown (Excluding contractor as they are created during registration)
    $allRoles = db_fetch_all($conn, "SELECT role_name, description FROM roles WHERE role_name != 'contractor' ORDER BY is_system DESC, role_name ASC");
    ?>

<div class="content-header">
  <div>
    <h2 class="page-title"><i class="fas fa-users-cog" style="color:#6366f1;margin-right:10px;"></i> User Management</h2>
    <!-- <p class="page-subtitle">Manage system access — Create, Edit, Delete users and control credentials.</p> -->
  </div>
  <div class="action-buttons" style="display:flex;gap:10px;">
    <a href="create_user.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Create New User</a>
  </div>
</div>

<!-- Stats Row -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px;">
  <?php
    $total = count($users);
    $active = count(array_filter($users, function($u) { return $u['status'] === 'active'; }));
    $inactive = $total - $active;
    $roles = [];
    foreach($users as $u) {
      $r = $u['role'];
      $roles[$r] = ($roles[$r] ?? 0) + 1;
    }
  ?>
  <div class="card glass" style="padding:20px;text-align:center;">
    <div style="font-size:28px;font-weight:800;color:#6366f1;"><?= $total ?></div>
    <div style="font-size:12px;color:var(--text-muted);font-weight:600;margin-top:4px;">Total Users</div>
  </div>
  <div class="card glass" style="padding:20px;text-align:center;">
    <div style="font-size:28px;font-weight:800;color:#10b981;"><?= $active ?></div>
    <div style="font-size:12px;color:var(--text-muted);font-weight:600;margin-top:4px;">Active</div>
  </div>
  <div class="card glass" style="padding:20px;text-align:center;">
    <div style="font-size:28px;font-weight:800;color:#ef4444;"><?= $inactive ?></div>
    <div style="font-size:12px;color:var(--text-muted);font-weight:600;margin-top:4px;">Inactive</div>
  </div>
  <div class="card glass" style="padding:20px;text-align:center;">
    <div style="font-size:28px;font-weight:800;color:#f59e0b;"><?= count($roles) ?></div>
    <div style="font-size:12px;color:var(--text-muted);font-weight:600;margin-top:4px;">Role Types</div>
  </div>
</div>

<!-- Users Table -->
<div class="card glass">
  <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
    <div class="card-title"><i class="fas fa-list"></i> All System Users</div>
  </div>
  <div class="card-body" style="padding:0;">
    <table class="data-table" id="usersTable">
      <thead>
        <tr>
          <th>User ID</th>
          <th>E-Code</th>
          <th>Name</th>
          <th>Role</th>
          <th>Contact</th>
          <th>Status</th>
          <th>Password</th>
          <th>Created</th>
          <th style="width:180px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($users as $user):
          $isSelf = ($user['id'] == $currentUserId);
          // Role Badge Color Mapping (PHP 7.0 Compatible)
          $roleColors = [
            'super_admin' => '#dc2626',
            'welfare_admin' => '#7c3aed',
            'welfare_user' => '#6366f1',
            'safety_user' => '#059669',
            'front_line_user' => '#d97706',
            'pass_user' => '#0284c7',
            'contractor' => '#64748b',
            'execution_officer' => '#0ea5e9'
          ];
          $roleBadgeColor = $roleColors[$user['role']] ?? '#94a3b8';

          // Role Label Mapping (PHP 7.0 Compatible)
          $roleLabels = [
            'super_admin' => 'Super Admin',
            'welfare_admin' => 'Welfare Admin',
            'welfare_user' => 'Welfare User',
            'safety_user' => 'Safety User',
            'front_line_user' => 'Frontline User',
            'pass_user' => 'Pass Issuer',
            'contractor' => 'Contractor',
            'execution_officer' => 'Execution Officer'
          ];
          $roleLabel = $roleLabels[$user['role']] ?? ucwords(str_replace('_', ' ', $user['role']));
          $mustChange = !empty($user['must_change_password']);
        ?>
        <tr id="user-row-<?= $user['id'] ?>">
          <td>
            <code style="background:rgba(99,102,241,0.1);padding:3px 8px;border-radius:6px;font-size:12px;font-weight:600;"><?= htmlspecialchars($user['contractor_id']) ?></code>
          </td>
          <td>
            <?php if(!empty($user['employee_code'])): ?>
              <code style="background:rgba(14,165,233,0.1);color:#0369a1;padding:3px 8px;border-radius:6px;font-size:12px;font-weight:700;"><?= htmlspecialchars($user['employee_code']) ?></code>
            <?php else: ?>
              <span style="opacity:0.4;">-</span>
            <?php endif; ?>
          </td>
          <td>
            <div style="font-weight:600;"><?= htmlspecialchars($user['name']) ?></div>
            <small style="opacity:0.6;"><?= htmlspecialchars($user['email']) ?></small>
          </td>
          <td>
            <span class="badge" style="background:<?= $roleBadgeColor ?>;color:#fff;font-size:11px;padding:4px 10px;">
              <?= $roleLabel ?>
            </span>
          </td>
          <td>
            <?php if($user['mobile']): ?>
              <i class="fas fa-phone" style="font-size:10px;color:#94a3b8;margin-right:4px;"></i><?= htmlspecialchars($user['mobile']) ?>
            <?php else: ?>
              <span style="opacity:0.4;">—</span>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge badge-<?= $user['status'] === 'active' ? 'success' : 'danger' ?>" style="cursor:<?= $isSelf ? 'default' : 'pointer' ?>;"
              <?php if(!$isSelf): ?>
              onclick="toggleStatus(<?= $user['id'] ?>, '<?= $user['status'] ?>')"
              title="Click to toggle status"
              <?php endif; ?>
            >
              <i class="fas fa-circle" style="font-size:6px;margin-right:4px;"></i>
              <?= strtoupper($user['status']) ?>
            </span>
          </td>
          <td>
            <?php if($mustChange): ?>
              <span class="badge badge-warning" style="font-size:10px;"><i class="fas fa-exclamation-triangle"></i> Pending</span>
            <?php else: ?>
              <span style="font-size:11px;color:#10b981;"><i class="fas fa-check-circle"></i> Set</span>
            <?php endif; ?>
          </td>
          <td style="font-size:12px;"><?= date('d M Y', strtotime($user['created_at'])) ?></td>
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
              <button class="btn btn-sm btn-outline" onclick='openEditModal(<?= json_encode($user) ?>)' title="Edit">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-outline" onclick="openResetPasswordModal(<?= $user['id'] ?>, '<?= htmlspecialchars(addslashes($user['name'])) ?>')" title="Reset Password" style="color:#f59e0b;border-color:#f59e0b;">
                <i class="fas fa-key"></i>
              </button>
              <?php if(!$isSelf): ?>
              <button class="btn btn-sm btn-outline text-danger" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars(addslashes($user['name'])) ?>')" title="Delete" style="border-color:#ef4444;">
                <i class="fas fa-trash"></i>
              </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- EDIT USER MODAL -->
<div class="um-modal-overlay" id="editModal" style="display:none;">
  <div class="um-modal">
    <div class="um-modal-header">
      <h3><i class="fas fa-user-edit" style="color:#6366f1;"></i> Edit User</h3>
      <button class="um-modal-close" onclick="closeModal('editModal')">&times;</button>
    </div>
    <div class="um-modal-body">
      <input type="hidden" id="edit-user-id">
      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label required">User ID</label>
          <input type="text" class="form-control" id="edit-contractor-id" readonly style="opacity:0.6;cursor:not-allowed;">
        </div>
        <div class="form-group">
          <label class="form-label" id="edit-employee-code-label">Employee E-Code</label>
          <input type="text" class="form-control" id="edit-employee-code" placeholder="E-Code" style="text-transform:uppercase;">
        </div>
        <div class="form-group">
          <label class="form-label required">Full Name</label>
          <input type="text" class="form-control" id="edit-name" placeholder="Full Name" required>
        </div>
        <div class="form-group">
          <label class="form-label required">Email Address</label>
          <input type="email" class="form-control" id="edit-email" placeholder="Email" required>
        </div>
        <div class="form-group">
          <label class="form-label">Mobile Number</label>
          <input type="text" class="form-control" id="edit-mobile" placeholder="Mobile Number">
        </div>
        <div class="form-group">
          <label class="form-label required">Role</label>
          <select class="form-control" id="edit-role" required>
            <?php foreach($allRoles as $r): ?>
              <option value="<?= $r['role_name'] ?>"><?= $roleLabels[$r['role_name']] ?? ucwords(str_replace('_', ' ', $r['role_name'])) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label required">Status</label>
          <select class="form-control" id="edit-status" required>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>
    </div>
    <div class="um-modal-footer">
      <button class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
      <button class="btn btn-primary" id="saveEditBtn" onclick="saveEdit()"><i class="fas fa-save"></i> Save Changes</button>
    </div>
  </div>
</div>

<!-- RESET PASSWORD MODAL -->
<div class="um-modal-overlay" id="resetPwdModal" style="display:none;">
  <div class="um-modal" style="max-width:460px;">
    <div class="um-modal-header">
      <h3><i class="fas fa-key" style="color:#f59e0b;"></i> Reset Password</h3>
      <button class="um-modal-close" onclick="closeModal('resetPwdModal')">&times;</button>
    </div>
    <div class="um-modal-body">
      <input type="hidden" id="reset-user-id">
      <div style="background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.3);border-radius:10px;padding:14px;margin-bottom:16px;font-size:13px;">
        <i class="fas fa-info-circle" style="color:#f59e0b;margin-right:6px;"></i>
        Resetting password for: <strong id="reset-user-name"></strong><br>
        <small style="opacity:0.7;">User will be forced to create a new password on next login.</small>
      </div>
      <div class="form-group">
        <label class="form-label required">New Temporary Password</label>
        <div style="position:relative;">
          <input type="password" class="form-control" id="reset-new-password" placeholder="Min 6 characters" minlength="6" required>
          <i class="fas fa-eye" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;color:#94a3b8;" onclick="toggleResetPwd()"></i>
        </div>
      </div>
      <div class="form-group" style="margin-top:12px;">
        <label class="form-label">Auto-generate</label>
        <button class="btn btn-sm btn-outline" type="button" onclick="generateTempPassword()" style="font-size:12px;">
          <i class="fas fa-random"></i> Generate Random Password
        </button>
      </div>
    </div>
    <div class="um-modal-footer">
      <button class="btn btn-outline" onclick="closeModal('resetPwdModal')">Cancel</button>
      <button class="btn btn-primary" style="background:#f59e0b;border-color:#f59e0b;" onclick="resetPassword()"><i class="fas fa-key"></i> Reset Password</button>
    </div>
  </div>
</div>

<!-- Toast -->
<div id="um-toast" class="um-toast" style="display:none;"></div>

<style>
  /* Modal Styles */
  .um-modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);
    z-index: 10000; display: flex; align-items: center; justify-content: center;
    animation: umFadeIn 0.2s ease;
  }
  @keyframes umFadeIn { from { opacity: 0; } to { opacity: 1; } }
  @keyframes umSlideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

  .um-modal {
    background: var(--card-bg, #fff); border-radius: 16px; width: 90%; max-width: 620px;
    box-shadow: 0 24px 64px rgba(0,0,0,0.2); animation: umSlideUp 0.3s ease;
    border: 1px solid var(--border-color, #e2e8f0);
  }
  .um-modal-header {
    display: flex; justify-content: space-between; align-items: center;
    padding: 20px 24px; border-bottom: 1px solid var(--border-color, #e2e8f0);
  }
  .um-modal-header h3 { margin: 0; font-size: 17px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
  .um-modal-close {
    width: 32px; height: 32px; border-radius: 8px; border: none; background: rgba(239,68,68,0.1);
    color: #ef4444; font-size: 18px; cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: all 0.2s;
  }
  .um-modal-close:hover { background: #ef4444; color: #fff; }
  .um-modal-body { padding: 24px; }
  .um-modal-footer {
    display: flex; justify-content: flex-end; gap: 10px;
    padding: 16px 24px; border-top: 1px solid var(--border-color, #e2e8f0);
  }

  .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  .form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--text-primary); }
  .form-label.required::after { content: ' *'; color: #ef4444; }

  /* Toast */
  .um-toast {
    position: fixed; bottom: 30px; right: 30px; z-index: 99999; padding: 14px 24px;
    border-radius: 12px; font-size: 14px; font-weight: 600; color: #fff;
    display: flex; align-items: center; gap: 10px; animation: umSlideUp 0.3s ease;
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
  }
  .um-toast.success { background: #10b981; }
  .um-toast.error { background: #ef4444; }
  .um-toast.warning { background: #f59e0b; }

  /* Badge clickable */
  .badge[onclick] { transition: all 0.2s; }
  .badge[onclick]:hover { transform: scale(1.05); filter: brightness(1.1); }
</style>

<script>
// ===== EDIT MODAL =====
function openEditModal(user) {
    document.getElementById('edit-user-id').value = user.id;
    document.getElementById('edit-contractor-id').value = user.contractor_id;
    document.getElementById('edit-employee-code').value = user.employee_code || '';
    document.getElementById('edit-name').value = user.name;
    document.getElementById('edit-email').value = user.email;
    document.getElementById('edit-mobile').value = user.mobile || '';
    document.getElementById('edit-role').value = user.role;
    document.getElementById('edit-status').value = user.status;
    syncEditEcodeRequirement();
    document.getElementById('editModal').style.display = 'flex';
}

function syncEditEcodeRequirement() {
    const role = document.getElementById('edit-role').value;
    const input = document.getElementById('edit-employee-code');
    const label = document.getElementById('edit-employee-code-label');
    const required = role === 'execution_officer';
    input.required = required;
    label.classList.toggle('required', required);
}

document.getElementById('edit-role').addEventListener('change', syncEditEcodeRequirement);
document.getElementById('edit-employee-code').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});

async function saveEdit() {
    const btn = document.getElementById('saveEditBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    const payload = {
        user_id: document.getElementById('edit-user-id').value,
        employee_code: document.getElementById('edit-employee-code').value.trim(),
        name: document.getElementById('edit-name').value,
        email: document.getElementById('edit-email').value,
        mobile: document.getElementById('edit-mobile').value,
        role: document.getElementById('edit-role').value,
        status: document.getElementById('edit-status').value
    };

    try {
        const data = await apiFetch('admin/update_user.php', payload);
        if (data.success) {
            showToast('User updated successfully!', 'success');
            closeModal('editModal');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Update failed', 'error');
        }
    } catch (err) {
        showToast('Network error: ' + err.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
    }
}

// ===== TOGGLE STATUS =====
async function toggleStatus(userId, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    if (!confirm(`Change user status to ${newStatus.toUpperCase()}?`)) return;

    try {
        const data = await apiFetch('admin/toggle_user_status.php', { user_id: userId });
        if (data.success) {
            showToast(`Status changed to ${newStatus}`, 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.message || 'Failed', 'error');
        }
    } catch (err) {
        showToast('Network error', 'error');
    }
}

// ===== DELETE USER =====
async function deleteUser(userId, name) {
    if (!confirm(`⚠️ DELETE user "${name}"?\n\nThis action cannot be undone!`)) return;
    if (!confirm(`Are you ABSOLUTELY sure you want to permanently delete "${name}"?`)) return;

    try {
        const data = await apiFetch('admin/delete_user.php', { user_id: userId });
        if (data.success) {
            showToast('User deleted successfully', 'success');
            const row = document.getElementById('user-row-' + userId);
            if (row) {
                row.style.transition = 'all 0.4s';
                row.style.opacity = '0';
                row.style.transform = 'translateX(40px)';
                setTimeout(() => row.remove(), 400);
            }
        } else {
            showToast(data.message || 'Delete failed', 'error');
        }
    } catch (err) {
        showToast('Network error', 'error');
    }
}

// ===== RESET PASSWORD MODAL =====
function openResetPasswordModal(userId, name) {
    document.getElementById('reset-user-id').value = userId;
    document.getElementById('reset-user-name').textContent = name;
    document.getElementById('reset-new-password').value = '';
    document.getElementById('resetPwdModal').style.display = 'flex';
}

function generateTempPassword() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789@#$';
    let pwd = '';
    // Ensure at least one upper, one lower, one number
    pwd += 'ABCDEFGHJKLMNPQRSTUVWXYZ'[Math.floor(Math.random() * 24)];
    pwd += 'abcdefghijkmnpqrstuvwxyz'[Math.floor(Math.random() * 23)];
    pwd += '23456789'[Math.floor(Math.random() * 8)];
    for (let i = 0; i < 5; i++) pwd += chars[Math.floor(Math.random() * chars.length)];
    // Shuffle
    pwd = pwd.split('').sort(() => Math.random() - 0.5).join('');
    document.getElementById('reset-new-password').value = pwd;
    document.getElementById('reset-new-password').type = 'text';
}

function toggleResetPwd() {
    const input = document.getElementById('reset-new-password');
    input.type = input.type === 'password' ? 'text' : 'password';
}

async function resetPassword() {
    const userId = document.getElementById('reset-user-id').value;
    const newPwd = document.getElementById('reset-new-password').value;

    if (!newPwd || newPwd.length < 6) {
        showToast('Password must be at least 6 characters', 'error');
        return;
    }

    try {
        const data = await apiFetch('admin/reset_user_password.php', { user_id: userId, new_password: newPwd });
        if (data.success) {
            showToast('Password reset! User must set new password on login.', 'success');
            closeModal('resetPwdModal');
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast(data.message || 'Reset failed', 'error');
        }
    } catch (err) {
        showToast('Network error', 'error');
    }
}

// ===== UTILITY =====
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

function showToast(msg, type = 'success') {
    const t = document.getElementById('um-toast');
    t.className = 'um-toast ' + type;
    const icons = { success: 'check-circle', error: 'exclamation-circle', warning: 'exclamation-triangle' };
    t.innerHTML = `<i class="fas fa-${icons[type] || 'info-circle'}"></i> ${msg}`;
    t.style.display = 'flex';
    setTimeout(() => { t.style.display = 'none'; }, 3500);
}

// Close modal on overlay click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('um-modal-overlay')) {
        e.target.style.display = 'none';
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.um-modal-overlay').forEach(m => m.style.display = 'none');
    }
});
</script>

    <?php
}

renderLayout("User Management", 'renderContent', $_SESSION['role'], $_SESSION['name']);
?>
