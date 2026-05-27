<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin', 'welfare_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    // Exclude 'contractor' role as requested
    $allRoles = db_fetch_all($conn, "SELECT role_name, description FROM roles WHERE role_name != 'contractor' ORDER BY is_system DESC, role_name ASC");
    
    $roleLabels = [
        'super_admin'=>'Super Admin','welfare_admin'=>'Welfare Admin','welfare_user'=>'Welfare User',
        'safety_user'=>'Safety User','front_line_user'=>'Frontline User','pass_user'=>'Pass Issuer',
        'execution_officer'=>'Execution Officer'
    ];
    $roleColors = [
        'super_admin'=>'#dc2626','welfare_admin'=>'#7c3aed','welfare_user'=>'#6366f1',
        'safety_user'=>'#059669','front_line_user'=>'#d97706','pass_user'=>'#0284c7',
        'contractor'=>'#64748b','execution_officer'=>'#0ea5e9'
    ];
    ?>
<div class="content-header">
  <div>
    <h2 class="page-title"><i class="fas fa-user-plus" style="color:#6366f1;margin-right:10px;"></i> Create New User</h2>
    <p class="page-subtitle">Register a new system user. They will be required to set their own password on first login.</p>
  </div>
  <div class="action-buttons">
    <a href="users.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Users</a>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start;">

  <!-- Main Form -->
  <div class="card glass">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-id-card"></i> User Details</div>
    </div>
    <div class="card-body">
      <form id="createUserForm" novalidate>
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label required">User ID / Login ID</label>
            <input type="text" class="form-control" name="contractor_id" id="cu-contractor-id"
              placeholder="e.g. WEL-001, SAFE-002, CONT-2024-001" required
              style="text-transform:uppercase;" oninput="this.value=this.value.toUpperCase()">
            <small class="form-hint" style="font-size:11px;color:var(--text-muted);margin-top:4px;display:block;">
              This will be used as login credentials. Must be unique.
            </small>
          </div>

          <div class="form-group">
            <label class="form-label required">Full Name</label>
            <input type="text" class="form-control" name="name" id="cu-name" placeholder="Enter full name" required>
          </div>

          <div class="form-group">
            <label class="form-label required">Email Address</label>
            <input type="email" class="form-control" name="email" id="cu-email" placeholder="user@company.com" required>
          </div>

          <div class="form-group">
            <label class="form-label">Mobile Number</label>
            <input type="text" class="form-control" name="mobile" id="cu-mobile" placeholder="+91 9876543210" maxlength="15">
          </div>

          <div class="form-group">
            <label class="form-label required">System Role</label>
            <select class="form-control" name="role" id="cu-role" required>
              <option value="">-- Select Role --</option>
              <?php foreach($allRoles as $r): ?>
                <option value="<?= $r['role_name'] ?>"><?= $roleLabels[$r['role_name']] ?? ucwords(str_replace('_', ' ', $r['role_name'])) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Initial Status</label>
            <select class="form-control" name="status" id="cu-status">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>

        <!-- Password Section -->
        <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--border-color,#e2e8f0);">
          <h4 style="font-size:14px;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-lock" style="color:#f59e0b;"></i> Initial Password
          </h4>
          <div style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.2);border-radius:10px;padding:14px;margin-bottom:16px;font-size:13px;">
            <i class="fas fa-info-circle" style="color:#f59e0b;margin-right:6px;"></i>
            This is a <strong>temporary password</strong>. The user will be required to set their own password when they first login.
          </div>
          <div class="form-grid-2">
            <div class="form-group">
              <label class="form-label required">Temporary Password</label>
              <div style="position:relative;">
                <input type="password" class="form-control" name="password" id="cu-password"
                  placeholder="Min 6 characters" minlength="6" required>
                <i class="fas fa-eye" id="cu-pwd-toggle"
                  style="position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;color:#94a3b8;"
                  onclick="toggleCreatePwd()"></i>
              </div>
            </div>
            <div class="form-group" style="display:flex;align-items:flex-end;">
              <button type="button" class="btn btn-outline" onclick="autoGeneratePassword()" style="white-space:nowrap;">
                <i class="fas fa-random"></i> Auto Generate
              </button>
            </div>
          </div>
        </div>

        <!-- Submit -->
        <div style="margin-top:24px;display:flex;gap:12px;justify-content:flex-end;">
          <a href="users.php" class="btn btn-outline">Cancel</a>
          <button type="submit" class="btn btn-primary" id="cu-submit-btn">
            <i class="fas fa-user-plus"></i> Create User
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Sidebar Info -->
  <div>
    <div class="card glass" style="margin-bottom:16px;">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-info-circle"></i> Role Guide</div>
      </div>
      <div class="card-body" style="padding:16px;">
        <div class="cu-role-guide">
          <?php foreach($allRoles as $r): 
            if($r['role_name'] === 'contractor') continue; // Usually not created here
            $c = $roleColors[$r['role_name']] ?? '#94a3b8';
          ?>
          <div class="cu-role-item">
            <span class="cu-role-dot" style="background:<?= $c ?>;"></span>
            <div><strong><?= $roleLabels[$r['role_name']] ?? ucwords(str_replace('_', ' ', $r['role_name'])) ?></strong><br><small><?= htmlspecialchars($r['description']) ?></small></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="card glass">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-shield-alt"></i> Password Policy</div>
      </div>
      <div class="card-body" style="padding:16px;font-size:13px;">
        <ul style="list-style:none;padding:0;margin:0;" id="cu-pwd-rules">
          <li class="cu-pwd-rule" data-rule="length"><i class="fas fa-circle" style="font-size:6px;margin-right:8px;"></i> Minimum 6 characters</li>
          <li class="cu-pwd-rule" data-rule="upper"><i class="fas fa-circle" style="font-size:6px;margin-right:8px;"></i> At least 1 uppercase letter</li>
          <li class="cu-pwd-rule" data-rule="lower"><i class="fas fa-circle" style="font-size:6px;margin-right:8px;"></i> At least 1 lowercase letter</li>
          <li class="cu-pwd-rule" data-rule="number"><i class="fas fa-circle" style="font-size:6px;margin-right:8px;"></i> At least 1 number</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- Toast -->
<div id="cu-toast" class="um-toast" style="display:none;"></div>

<style>
  .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  .form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
  .form-label.required::after { content: ' *'; color: #ef4444; }
  .form-hint { font-size: 11px; color: var(--text-muted); }

  .cu-role-guide { display: flex; flex-direction: column; gap: 12px; }
  .cu-role-item { display: flex; align-items: flex-start; gap: 10px; font-size: 12px; }
  .cu-role-item small { opacity: 0.6; }
  .cu-role-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; margin-top: 4px; }

  .cu-pwd-rule { padding: 6px 0; color: var(--text-muted); transition: all 0.2s; }
  .cu-pwd-rule.valid { color: #10b981; }
  .cu-pwd-rule.valid i { color: #10b981; }

  .um-toast {
    position: fixed; bottom: 30px; right: 30px; z-index: 99999; padding: 14px 24px;
    border-radius: 12px; font-size: 14px; font-weight: 600; color: #fff;
    display: flex; align-items: center; gap: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    animation: umSlideUp 0.3s ease;
  }
  .um-toast.success { background: #10b981; }
  .um-toast.error { background: #ef4444; }
  @keyframes umSlideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

  .input-error { border-color: #ef4444 !important; box-shadow: 0 0 0 3px rgba(239,68,68,0.15) !important; }
</style>

<script>
// ===== PASSWORD TOGGLE =====
function toggleCreatePwd() {
    const input = document.getElementById('cu-password');
    const icon = document.getElementById('cu-pwd-toggle');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// ===== AUTO GENERATE PASSWORD =====
function autoGeneratePassword() {
    const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    const lower = 'abcdefghijkmnpqrstuvwxyz';
    const nums  = '23456789';
    const all   = upper + lower + nums + '@#$!';
    
    let pwd = '';
    pwd += upper[Math.floor(Math.random() * upper.length)];
    pwd += lower[Math.floor(Math.random() * lower.length)];
    pwd += nums[Math.floor(Math.random() * nums.length)];
    for (let i = 0; i < 5; i++) pwd += all[Math.floor(Math.random() * all.length)];
    pwd = pwd.split('').sort(() => Math.random() - 0.5).join('');

    const input = document.getElementById('cu-password');
    input.value = pwd;
    input.type = 'text';
    document.getElementById('cu-pwd-toggle').classList.replace('fa-eye', 'fa-eye-slash');
    validatePasswordRules(pwd);
}

// ===== PASSWORD STRENGTH VALIDATION (live) =====
document.getElementById('cu-password').addEventListener('input', function() {
    validatePasswordRules(this.value);
});

function validatePasswordRules(pwd) {
    const rules = {
        length: pwd.length >= 6,
        upper: /[A-Z]/.test(pwd),
        lower: /[a-z]/.test(pwd),
        number: /[0-9]/.test(pwd)
    };
    document.querySelectorAll('.cu-pwd-rule').forEach(el => {
        const rule = el.getAttribute('data-rule');
        el.classList.toggle('valid', rules[rule]);
    });
}

// ===== FORM SUBMIT =====
document.getElementById('createUserForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    // Validate required fields
    const fields = ['cu-contractor-id', 'cu-name', 'cu-email', 'cu-role', 'cu-password'];
    let hasError = false;
    fields.forEach(id => {
        const el = document.getElementById(id);
        if (!el.value.trim()) {
            el.classList.add('input-error');
            hasError = true;
        } else {
            el.classList.remove('input-error');
        }
    });

    if (hasError) {
        showToast('Please fill all required fields', 'error');
        return;
    }

    // Validate email format
    const email = document.getElementById('cu-email').value;
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        document.getElementById('cu-email').classList.add('input-error');
        showToast('Please enter a valid email address', 'error');
        return;
    }

    // Validate password
    const pwd = document.getElementById('cu-password').value;
    if (pwd.length < 6) {
        showToast('Password must be at least 6 characters', 'error');
        return;
    }

    const btn = document.getElementById('cu-submit-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';

    const payload = {
        contractor_id: document.getElementById('cu-contractor-id').value.trim(),
        name: document.getElementById('cu-name').value.trim(),
        email: email.trim(),
        mobile: document.getElementById('cu-mobile').value.trim(),
        role: document.getElementById('cu-role').value,
        status: document.getElementById('cu-status').value,
        password: pwd
    };

    try {
        const res = await fetch('../../api/register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            showToast('✅ User created successfully!', 'success');
            setTimeout(() => window.location.href = 'users.php', 1500);
        } else {
            showToast(data.message || 'Creation failed', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-user-plus"></i> Create User';
        }
    } catch (err) {
        showToast('Network error: ' + err.message, 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-user-plus"></i> Create User';
    }
});

function showToast(msg, type = 'success') {
    const t = document.getElementById('cu-toast');
    t.className = 'um-toast ' + type;
    const icons = { success: 'check-circle', error: 'exclamation-circle' };
    t.innerHTML = `<i class="fas fa-${icons[type] || 'info-circle'}"></i> ${msg}`;
    t.style.display = 'flex';
    setTimeout(() => { t.style.display = 'none'; }, 3500);
}
</script>

    <?php
}

renderLayout("Create User", 'renderContent', $_SESSION['role'], $_SESSION['name']);
?>
