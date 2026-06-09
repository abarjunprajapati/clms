<?php
/**
 * Annexure 5/A - Pass Category Limit Configuration (Welfare Admin)
 * Full CRUD UI for managing pass type limits per contractor.
 */
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'welfare_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

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
    $column = clms_db_real_escape_string($conn, $column);
    $result = clms_db_query($conn, "SHOW COLUMNS FROM `pass_limits` LIKE '$column'");
    return $result && clms_db_num_rows($result) > 0;
}

function welfarePassLimitIdAutoIncrement($conn) {
    $idResult = clms_db_query($conn, "SHOW COLUMNS FROM `pass_limits` LIKE 'id'");
    $idMeta = $idResult ? clms_db_fetch_assoc($idResult) : null;
    return $idMeta && stripos($idMeta['Extra'] ?? '', 'auto_increment') !== false;
}

function welfarePassLimitNextId($conn) {
    $row = db_single($conn, "SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM pass_limits");
    return (int)($row['next_id'] ?? 1);
}

function welfarePassLimitEnsureDefaults($conn) {
    clms_db_query($conn, "CREATE TABLE IF NOT EXISTS pass_limits (
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

    $idResult = clms_db_query($conn, "SHOW COLUMNS FROM `pass_limits` LIKE 'id'");
    $idMeta = $idResult ? clms_db_fetch_assoc($idResult) : null;
    if ($idMeta && stripos($idMeta['Extra'] ?? '', 'auto_increment') === false) {
        clms_db_query($conn, "ALTER TABLE `pass_limits` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT");
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
            clms_db_query($conn, $sql);
        }
    }

    $hasAutoId = welfarePassLimitIdAutoIncrement($conn);
    $stmt = clms_db_prepare($conn, $hasAutoId ? "
        INSERT INTO pass_limits
            (contractor_id, pass_type, max_allowed, ratio_per_workmen, rule, description, override_allowed, current_count)
        VALUES
            (0, ?, ?, ?, ?, ?, ?, 0)
    " : "
        INSERT INTO pass_limits
            (id, contractor_id, pass_type, max_allowed, ratio_per_workmen, rule, description, override_allowed, current_count)
        VALUES
            (?, 0, ?, ?, ?, ?, ?, ?, 0)
    ");
    if (!$stmt) {
        return;
    }

    foreach (welfarePassLimitDefaultRules() as $rule) {
        $existsForType = db_count($conn, "SELECT COUNT(*) c FROM pass_limits WHERE contractor_id = 0 AND pass_type = ?", 's', [$rule['pass_type']]);
        if ($existsForType > 0) {
            continue;
        }
        $passType = $rule['pass_type'];
        $maxAllowed = $rule['max_allowed'];
        $ratio = $rule['ratio_per_workmen'];
        $ruleText = $rule['rule'];
        $description = $rule['description'];
        $override = (int)$rule['override_allowed'];
        if ($hasAutoId) {
            clms_db_stmt_bind_param($stmt, 'siissi', $passType, $maxAllowed, $ratio, $ruleText, $description, $override);
        } else {
            $nextId = welfarePassLimitNextId($conn);
            clms_db_stmt_bind_param($stmt, 'isiissi', $nextId, $passType, $maxAllowed, $ratio, $ruleText, $description, $override);
        }
        clms_db_stmt_execute($stmt);
    }
    clms_db_stmt_close($stmt);
}

function renderContent() {
    global $conn;
    welfarePassLimitEnsureDefaults($conn);
    
    // Get global defaults
    $defaults = db_fetch_all($conn, "SELECT * FROM pass_limits WHERE contractor_id = 0 ORDER BY id");
    if (empty($defaults)) {
        $defaults = welfarePassLimitDefaultRules();
    }
    ?>

<div class="content-header">
  <div>
    <h2 class="page-title">Maximum pass no allowed in each category</h2>
    <!-- <p class="page-subtitle">Configure maximum allowed passes for each contractor and category per PDF rules.</p> -->
  </div>
</div>

<div class="card glass" style="margin-bottom:24px;">
  <div class="card-header">
    <div class="card-title">Pass Category Limit</div>
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
          <th>Action</th>
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
          <td>
            <button class="btn btn-sm btn-outline-primary"
              onclick='openDefaultEdit(<?= json_encode([
                'pass_type' => $d['pass_type'],
                'max_allowed' => $d['max_allowed'],
                'ratio_per_workmen' => $d['ratio_per_workmen'],
                'rule' => $d['rule'] ?? '',
                'description' => $d['description'] ?? '',
                'override_allowed' => (int)($d['override_allowed'] ?? 1),
              ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
              <i class="fas fa-edit"></i> Edit
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div id="defaultLimitModal" class="pl-modal" aria-hidden="true">
  <div class="pl-modal__dialog">
    <div class="pl-modal__header">
      <h3><i class="fas fa-sliders-h"></i> Edit Default Rule</h3>
      <button type="button" class="pl-modal__close" onclick="closeDefaultEdit()" aria-label="Close">&times;</button>
    </div>
    <form id="defaultLimitForm">
      <input type="hidden" name="contractor_id" value="0">
      <input type="hidden" name="pass_type" id="defaultPassType">
      <div class="form-group">
        <label class="form-label">Pass Type</label>
        <input type="text" class="form-control" id="defaultPassTypeLabel" readonly>
      </div>
      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Max Allowed</label>
          <input type="number" class="form-control" name="max_allowed" id="defaultMaxAllowed" min="1" placeholder="Blank for dynamic/no limit">
        </div>
        <div class="form-group">
          <label class="form-label">Ratio Per Workmen</label>
          <input type="number" class="form-control" name="ratio_per_workmen" id="defaultRatio" min="1" placeholder="Only for supervisor">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Rule</label>
        <input type="text" class="form-control" name="rule" id="defaultRule" required>
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea class="form-control" name="description" id="defaultDescription" rows="2"></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Override Allowed?</label>
        <div style="display:flex;gap:16px;align-items:center;height:38px;">
          <label><input type="radio" name="override_allowed" value="1" id="defaultOverrideYes"> Yes</label>
          <label><input type="radio" name="override_allowed" value="0" id="defaultOverrideNo"> No</label>
        </div>
      </div>
      <div class="pl-modal__footer">
        <button type="button" class="btn btn-outline" onclick="closeDefaultEdit()">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Default</button>
      </div>
    </form>
  </div>
</div>

<style>
  .form-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
  .form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
  .form-control { width: 100%; padding: 10px 14px; border-radius: 10px; border: 1.5px solid var(--border-color); background: var(--input-bg, rgba(255,255,255,.05)); color: var(--text-primary); font-size: 14px; box-sizing: border-box; }
  .form-hint { font-size: 11px; color: var(--text-muted); margin-top: 4px; display: block; }
  .toast-msg { position: fixed; bottom: 30px; right: 30px; z-index: 9999; padding: 14px 20px; border-radius: 12px; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; animation: slideUp .3s ease; box-shadow: 0 8px 30px rgba(0,0,0,.2); }
  @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
  .toast-success { background: #10b981; color: white; }
  .toast-error { background: #ef4444; color: white; }
  .pl-modal { display:none; position:fixed; inset:0; z-index:2000; background:rgba(15,23,42,.48); padding:24px 16px; overflow-y:auto; }
  .pl-modal.is-open { display:flex; align-items:flex-start; justify-content:center; }
  .pl-modal__dialog { width:min(560px,100%); margin:24px auto; background:#fff; border:1px solid #dbe4ef; border-radius:12px; box-shadow:0 24px 60px rgba(15,23,42,.24); overflow:hidden; }
  .pl-modal__header { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:18px 22px; border-bottom:1px solid #e5edf6; }
  .pl-modal__header h3 { margin:0; font-size:18px; font-weight:800; color:#1f2937; }
  .pl-modal__close { width:34px; height:34px; border:1px solid #dbe4ef; border-radius:8px; background:#fff; color:#334155; font-size:22px; line-height:1; cursor:pointer; }
  #defaultLimitForm { padding:20px 22px 22px; }
  .pl-modal__footer { display:flex; justify-content:flex-end; gap:10px; padding-top:8px; }
  @media (max-width: 640px) { .form-grid-2 { grid-template-columns: 1fr; } .pl-modal__footer { flex-direction:column-reverse; } .pl-modal__footer .btn { width:100%; } }
</style>

<script>
function openDefaultEdit(rule) {
  document.getElementById('defaultPassType').value = rule.pass_type || '';
  document.getElementById('defaultPassTypeLabel').value = rule.pass_type || '';
  document.getElementById('defaultMaxAllowed').value = rule.max_allowed ?? '';
  document.getElementById('defaultRatio').value = rule.ratio_per_workmen ?? '';
  document.getElementById('defaultRule').value = rule.rule || '';
  document.getElementById('defaultDescription').value = rule.description || '';
  document.getElementById('defaultOverrideYes').checked = String(rule.override_allowed ?? 1) !== '0';
  document.getElementById('defaultOverrideNo').checked = String(rule.override_allowed ?? 1) === '0';
  document.getElementById('defaultLimitModal').classList.add('is-open');
  document.getElementById('defaultLimitModal').setAttribute('aria-hidden', 'false');
}

function closeDefaultEdit() {
  document.getElementById('defaultLimitModal').classList.remove('is-open');
  document.getElementById('defaultLimitModal').setAttribute('aria-hidden', 'true');
}

document.getElementById('defaultLimitForm').onsubmit = async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const data = {};
  fd.forEach((v, k) => data[k] = v);
  if (!data.max_allowed) data.max_allowed = null;
  if (!data.ratio_per_workmen) data.ratio_per_workmen = null;

  try {
    const res = await fetch('../../api/welfare/update_pass_limit.php', {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json',
        'X-CSRF-Token': window.CLMS_CSRF_TOKEN || ''
      },
      body: JSON.stringify(data)
    });
    const raw = await res.text();
    let result = {};
    try { result = raw ? JSON.parse(raw) : {}; } catch (err) { result = { success:false, error: raw || 'Server returned invalid response.' }; }
    if (result.success) {
      showToast('Default rule updated successfully.', 'success');
      setTimeout(() => location.reload(), 800);
    } else {
      showToast(result.error || 'Failed to update default rule', 'error');
    }
  } catch (err) {
    showToast('Connection error. Please try again.', 'error');
  }
};

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

renderLayout("Pass Category Limit", 'renderContent', $role, $name);
