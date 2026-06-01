<?php
/**
 * Annexure 5/A — Pass Limits Configuration (Welfare Admin)
 * Full CRUD UI for managing pass type limits per contractor.
 */
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'welfare_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';
include __DIR__ . '/../../include/pass_limit_validator.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Admin';

function welfarePassLimitDefaultRules() {
    return [
        [
            'id' => 0,
            'contractor_id' => 0,
            'pass_type' => 'Contractor',
            'max_allowed' => 2,
            'ratio_per_workmen' => null,
            'rule' => 'Fixed - Max 2',
            'description' => 'Maximum 2 contractor/self passes per firm',
            'override_allowed' => 1,
        ],
        [
            'id' => 0,
            'contractor_id' => 0,
            'pass_type' => 'Representative',
            'max_allowed' => 1,
            'ratio_per_workmen' => null,
            'rule' => 'Fixed - Max 1',
            'description' => 'Only 1 representative pass per firm',
            'override_allowed' => 1,
        ],
        [
            'id' => 0,
            'contractor_id' => 0,
            'pass_type' => 'Supervisor',
            'max_allowed' => null,
            'ratio_per_workmen' => 10,
            'rule' => 'Ratio - 1 per 10 workmen + 1 additional',
            'description' => 'Dynamic supervisor limit based on workmen count',
            'override_allowed' => 1,
        ],
        [
            'id' => 0,
            'contractor_id' => 0,
            'pass_type' => 'Workman',
            'max_allowed' => null,
            'ratio_per_workmen' => null,
            'rule' => 'No fixed pass limit',
            'description' => 'Controlled by work order/project rules',
            'override_allowed' => 1,
        ],
    ];
}

function welfarePassLimitColumnExists($conn, $column) {
    $column = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `pass_limits` LIKE '$column'");
    return $result && mysqli_num_rows($result) > 0;
}

function welfarePassLimitEnsureDefaults($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS pass_limits (
        id INT NOT NULL AUTO_INCREMENT,
        contractor_id INT NOT NULL DEFAULT 0,
        pass_type VARCHAR(50) NOT NULL,
        max_allowed INT DEFAULT NULL,
        rule VARCHAR(150) NOT NULL DEFAULT 'Fixed',
        description TEXT DEFAULT NULL,
        ratio_per_workmen INT DEFAULT NULL,
        override_allowed TINYINT(1) NOT NULL DEFAULT 1,
        current_count INT DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $idResult = mysqli_query($conn, "SHOW COLUMNS FROM `pass_limits` LIKE 'id'");
    $idMeta = $idResult ? mysqli_fetch_assoc($idResult) : null;
    if ($idMeta && stripos($idMeta['Extra'] ?? '', 'auto_increment') === false) {
        mysqli_query($conn, "ALTER TABLE `pass_limits` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT");
    }

    $columns = [
        'rule' => "ALTER TABLE `pass_limits` ADD COLUMN `rule` VARCHAR(150) NOT NULL DEFAULT 'Fixed' AFTER `max_allowed`",
        'description' => "ALTER TABLE `pass_limits` ADD COLUMN `description` TEXT DEFAULT NULL AFTER `rule`",
        'ratio_per_workmen' => "ALTER TABLE `pass_limits` ADD COLUMN `ratio_per_workmen` INT DEFAULT NULL AFTER `description`",
        'override_allowed' => "ALTER TABLE `pass_limits` ADD COLUMN `override_allowed` TINYINT(1) NOT NULL DEFAULT 1 AFTER `ratio_per_workmen`",
        'current_count' => "ALTER TABLE `pass_limits` ADD COLUMN `current_count` INT DEFAULT 0 AFTER `override_allowed`",
        'updated_at' => "ALTER TABLE `pass_limits` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    ];
    foreach ($columns as $column => $sql) {
        if (!welfarePassLimitColumnExists($conn, $column)) {
            mysqli_query($conn, $sql);
        }
    }

    $existing = db_count($conn, "SELECT COUNT(*) c FROM pass_limits WHERE contractor_id = 0");
    if ($existing > 0) {
        return;
    }

    $stmt = mysqli_prepare($conn, "
        INSERT INTO pass_limits
            (contractor_id, pass_type, max_allowed, ratio_per_workmen, rule, description, override_allowed, current_count)
        VALUES
            (0, ?, ?, ?, ?, ?, ?, 0)
    ");
    if (!$stmt) {
        return;
    }

    foreach (welfarePassLimitDefaultRules() as $rule) {
        $passType = $rule['pass_type'];
        $maxAllowed = $rule['max_allowed'];
        $ratio = $rule['ratio_per_workmen'];
        $ruleText = $rule['rule'];
        $description = $rule['description'];
        $override = (int)$rule['override_allowed'];
        mysqli_stmt_bind_param($stmt, 'siissi', $passType, $maxAllowed, $ratio, $ruleText, $description, $override);
        mysqli_stmt_execute($stmt);
    }
    mysqli_stmt_close($stmt);
}

function renderContent() {
    global $conn;
    welfarePassLimitEnsureDefaults($conn);
    
    $activeContractorUserSql = "
        EXISTS (
            SELECT 1
            FROM users u
            WHERE u.role = 'contractor'
              AND u.status = 'active'
              AND (u.id = c.user_id OR u.contractor_id = c.vendor_code)
        )
    ";

    $contractors = db_fetch_all($conn, "
        SELECT c.id, c.contractor_name, c.vendor_code
        FROM contractors c
        WHERE $activeContractorUserSql
        ORDER BY c.contractor_name
    ");
    
    // Get all configured limits with contractor info
    $limits = db_fetch_all($conn, 
        "SELECT pl.*, c.contractor_name, c.vendor_code 
         FROM pass_limits pl 
         JOIN contractors c ON pl.contractor_id = c.id
         WHERE $activeContractorUserSql
         ORDER BY c.contractor_name, pl.pass_type"
    );
    
    // Get global defaults
    $defaults = db_fetch_all($conn, "SELECT * FROM pass_limits WHERE contractor_id = 0 ORDER BY id");
    if (empty($defaults)) {
        $defaults = welfarePassLimitDefaultRules();
    }
    ?>

<div class="content-header">
  <div>
    <h2 class="page-title"><i class="fas fa-sliders-h" style="color:#6366f1;margin-right:10px;"></i> Pass Limits</h2>
    <!-- <p class="page-subtitle">Configure maximum allowed passes for each contractor and category per PDF rules.</p> -->
  </div>
</div>

<!-- Global Defaults Card -->
<div class="card glass" style="margin-bottom:24px;">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-globe"></i> Global Default Rules</div>
  </div>
  <div class="card-body" style="padding:0;">
    <table class="data-table">
      <thead>
        <tr>
          <th>SL No</th>
          <th>Pass Type</th>
          <th>Max Allowed</th>
          <th>Rule</th>
          <th>Override Allowed</th>
        </tr>
      </thead>
      <tbody>
        <?php $sl = 1; foreach ($defaults as $d): ?>
        <tr>
          <td><?= $sl++ ?></td>
          <td><span class="badge <?= getBadgeClass($d['pass_type']) ?>"><?= $d['pass_type'] ?></span></td>
          <td><strong><?= $d['max_allowed'] ?? '<em>Dynamic</em>' ?></strong></td>
          <td>
            <strong><?= htmlspecialchars($d['rule'] ?? '') ?></strong>
            <?php if (!empty($d['description'])): ?>
              <div style="font-size:11px;color:var(--text-muted);margin-top:3px;"><?= htmlspecialchars($d['description']) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($d['override_allowed']): ?>
              <span class="badge badge-success">Yes</span>
            <?php else: ?>
              <span class="badge badge-danger">No</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Set Limit Form -->
<div class="card glass" style="margin-bottom:24px;">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-plus-circle"></i> Set / Override Limit for Contractor</div>
  </div>
  <div class="card-body">
    <form id="limitForm">
      <div class="form-grid-4">
        <div class="form-group">
          <label class="form-label">Contractor</label>
          <select class="form-control" name="contractor_id" id="selContractor" required>
            <option value="">Select Contractor</option>
            <?php foreach ($contractors as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['contractor_name']) ?> (<?= $c['vendor_code'] ?? 'N/A' ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Pass Type</label>
          <select class="form-control" name="pass_type" id="selPassType" required>
            <option value="Contractor">Contractor (Max 2)</option>
            <option value="Representative">Representative (Max 1)</option>
            <option value="Supervisor">Supervisor (1:10 ratio)</option>
            <option value="Workman">Workman (No limit)</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Max Allowed</label>
          <input type="number" class="form-control" name="max_allowed" id="inpMaxAllowed" min="1" placeholder="e.g. 2">
          <small class="form-hint" id="ruleHint">Fixed limit</small>
        </div>
        <div class="form-group">
          <label class="form-label">Override Allowed?</label>
          <div style="display:flex;gap:12px;align-items:center;height:42px;">
            <label><input type="radio" name="override_allowed" value="1" checked> Yes</label>
            <label><input type="radio" name="override_allowed" value="0"> No</label>
            <button type="submit" class="btn btn-primary" style="margin-left:auto;"><i class="fas fa-save"></i> Set Limit</button>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Live Limits per Contractor -->
<div class="card glass" style="margin-bottom:24px;">
  <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
    <div class="card-title"><i class="fas fa-chart-bar"></i> Live Limits & Utilization</div>
    <select class="form-control" id="filterContractor" style="width:250px;">
      <option value="">All Contractors</option>
      <?php foreach ($contractors as $c): ?>
      <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['contractor_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="card-body" style="padding:0;">
    <table class="data-table" id="limitsTable">
      <thead>
        <tr>
          <th>Contractor</th>
          <th>Pass Type</th>
          <th>Max Allowed</th>
          <th>Current Count</th>
          <th>Utilization</th>
          <th>Rule</th>
          <th>Override</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($limits as $l):
          $calc = calculateAllowed($conn, (int)$l['contractor_id'], $l['pass_type']);
          $max = $calc['allowed'];
          $cur = getCurrentPassCount($conn, (int)$l['contractor_id'], $l['pass_type']);
          $util = $max > 0 ? round(($cur / $max) * 100) : 0;
          $barColor = $util > 90 ? '#ef4444' : ($util > 70 ? '#f59e0b' : '#10b981');
        ?>
        <tr data-cid="<?= $l['contractor_id'] ?>">
          <td>
            <strong><?= htmlspecialchars($l['contractor_name']) ?></strong>
            <div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($l['vendor_code'] ?? '') ?></div>
          </td>
          <td><span class="badge <?= getBadgeClass($l['pass_type']) ?>"><?= $l['pass_type'] ?></span></td>
          <td><strong><?= $max === null ? '<em>Dynamic</em>' : (int)$max ?></strong></td>
          <td><?= $cur ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px;">
              <div style="width:80px;background:rgba(148,163,184,.15);border-radius:4px;height:8px;overflow:hidden;">
                <div style="width:<?= min($util, 100) ?>%;background:<?= $barColor ?>;height:100%;border-radius:4px;transition:.3s;"></div>
              </div>
              <span style="font-size:11px;font-weight:600;"><?= $util ?>%</span>
            </div>
          </td>
          <td style="font-size:12px;"><?= htmlspecialchars($calc['rule'] ?? ($l['rule'] ?? 'Fixed')) ?></td>
          <td>
            <?php if ($l['override_allowed'] ?? 1): ?>
              <span class="badge badge-success" style="font-size:10px;">Yes</span>
            <?php else: ?>
              <span class="badge badge-danger" style="font-size:10px;">No</span>
            <?php endif; ?>
          </td>
          <td>
            <button class="btn btn-sm btn-outline text-danger" onclick="deleteLimit(<?= $l['id'] ?>)"><i class="fas fa-trash"></i></button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<style>
  .form-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
  .form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
  .form-control { width: 100%; padding: 10px 14px; border-radius: 10px; border: 1.5px solid var(--border-color); background: var(--input-bg, rgba(255,255,255,.05)); color: var(--text-primary); font-size: 14px; box-sizing: border-box; }
  .form-hint { font-size: 11px; color: var(--text-muted); margin-top: 4px; display: block; }
  .toast-msg { position: fixed; bottom: 30px; right: 30px; z-index: 9999; padding: 14px 20px; border-radius: 12px; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; animation: slideUp .3s ease; box-shadow: 0 8px 30px rgba(0,0,0,.2); }
  @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
  .toast-success { background: #10b981; color: white; }
  .toast-error { background: #ef4444; color: white; }
  @media (max-width: 900px) { .form-grid-4 { grid-template-columns: 1fr 1fr; } }
</style>

<script>
// Dynamic hint based on pass type selection
document.getElementById('selPassType').addEventListener('change', function() {
  const hints = {
    'Contractor': 'PDF Rule: Max 2 per firm',
    'Representative': 'PDF Rule: Max 1 per firm',
    'Supervisor': 'PDF Rule: 1 per 10 workmen + 1 additional',
    'Workman': 'PDF Rule: No fixed limit (project based)'
  };
  const defaults = { 'Contractor': 2, 'Representative': 1, 'Supervisor': '', 'Workman': '' };
  document.getElementById('ruleHint').textContent = hints[this.value] || '';
  document.getElementById('inpMaxAllowed').value = defaults[this.value] || '';
  document.getElementById('inpMaxAllowed').placeholder = this.value === 'Workman' ? 'Leave empty for no limit' : 'Enter max';
});

// Filter table by contractor
document.getElementById('filterContractor').addEventListener('change', function() {
  const cid = this.value;
  document.querySelectorAll('#limitsTable tbody tr').forEach(row => {
    if (!cid || row.dataset.cid === cid) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
});

// Submit form
document.getElementById('limitForm').onsubmit = async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const data = {};
  fd.forEach((v, k) => data[k] = v);
  
  // For Workman with empty max, set null
  if (data.pass_type === 'Workman' && !data.max_allowed) {
    data.max_allowed = null;
  }

  try {
    const res = await fetch('../../api/welfare/update_pass_limit.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const result = await res.json().catch(() => ({ success: false, error: 'Server returned an invalid response.' }));
    if (result.success) {
      showToast('Limit set successfully!', 'success');
      setTimeout(() => location.reload(), 1000);
    } else {
      showToast(result.error || 'Failed to set limit', 'error');
    }
  } catch (err) {
    showToast('Connection error. Please try again.', 'error');
  }
};

async function deleteLimit(id) {
  if (!confirm('Remove this pass limit? Global defaults will apply instead.')) return;
  try {
    const res = await fetch('../../api/welfare/delete_pass_limit.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    });
    const result = await res.json();
    if (result.success) {
      showToast('Limit removed', 'success');
      setTimeout(() => location.reload(), 800);
    } else {
      showToast(result.error || 'Failed to delete', 'error');
    }
  } catch (err) {
    showToast('Error deleting limit', 'error');
  }
}

function showToast(msg, type) {
  let t = document.createElement('div');
  t.className = 'toast-msg toast-' + type;
  t.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${msg}`;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 3500);
}
</script>

<?php
}

function getBadgeClass($type) {
    $map = [
        'Contractor' => 'badge-success',
        'Representative' => 'badge-warning',
        'Supervisor' => 'badge-info',
        'Workman' => 'badge-primary'
    ];
    return $map[$type] ?? 'badge-gray';
}

renderLayout("Pass Limits (Annexure 5A)", 'renderContent', $role, $name);
