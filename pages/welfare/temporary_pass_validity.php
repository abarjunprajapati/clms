<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';
require_once __DIR__ . '/../../include/temporary_pass_validity.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Admin';

function renderContent() {
    global $conn;
    $validityRows = clms_get_temporary_pass_validity_rows($conn);
    $currentDays = clms_get_temporary_pass_validity_days($conn);
    ?>

<div class="content-header">
  <div>
    <h2 class="page-title">Temporary Pass Validity</h2>
  </div>
  <button type="button" class="btn btn-primary" id="btnShowValidityForm">
    <i class="fas fa-plus"></i> Add
  </button>
</div>

<div class="card glass validity-summary">
  <div class="validity-current">
    <span>Current Active Validity</span>
    <strong><?= (int)$currentDays ?> days</strong>
  </div>
  <div class="validity-note">Temporary pass validity is calculated from this active master value unless a shorter validity is selected during issue.</div>
</div>

<div class="card glass validity-form-panel" id="validityFormPanel" style="display:none;">
  <div class="card-header validity-form-header">
    <div class="card-title">Add Temporary Pass Validity</div>
    <button type="button" class="btn btn-secondary btn-sm" id="btnHideValidityForm">
      <i class="fas fa-times"></i> Cancel
    </button>
  </div>
  <div class="card-body">
    <form id="validityForm" class="validity-add-form">
      <div class="form-group" style="margin:0;">
        <label class="form-label">Date</label>
        <input type="date" class="form-control" name="system_date" value="<?= htmlspecialchars(date('Y-m-d')) ?>" readonly>
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label">Validity Days</label>
        <input type="number" class="form-control" name="validity_days" min="1" step="1" placeholder="Enter days" required>
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label">From Date</label>
        <input type="date" class="form-control" name="validity_from_date" value="<?= htmlspecialchars(date('Y-m-d')) ?>" required>
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label">To Date</label>
        <input type="date" class="form-control" name="validity_to_date" value="9999-12-31" required>
      </div>
      <button type="submit" class="btn btn-primary" id="btnSubmitValidity"><i class="fas fa-paper-plane"></i> Submit</button>
    </form>
  </div>
</div>

<div class="card glass">
  <div class="card-header">
    <div class="card-title">Temporary Pass Validity History</div>
  </div>
  <div class="card-body" style="padding:0;">
    <div class="validity-table-wrap">
      <table class="data-table validity-table">
        <thead>
          <tr>
            <th>SL No</th>
            <th>Validity Days</th>
            <th>From Date</th>
            <th>To Date</th>
            <th>Entry Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($validityRows): $sl = 1; foreach ($validityRows as $row): ?>
          <tr>
            <td><?= $sl++ ?></td>
            <td><strong><?= (int)$row['validity_days'] ?></strong></td>
            <td><?= htmlspecialchars(date('d/m/Y', strtotime($row['validity_from_date']))) ?></td>
            <td><?= htmlspecialchars($row['validity_to_date'] === '9999-12-31' ? '31/12/9999' : date('d/m/Y', strtotime($row['validity_to_date']))) ?></td>
            <td><?= !empty($row['created_at']) ? htmlspecialchars(date('d/m/Y', strtotime($row['created_at']))) : '-' ?></td>
            <td><span class="badge <?= strtolower((string)$row['status']) === 'active' ? 'badge-success' : 'badge-gray' ?>"><?= htmlspecialchars(ucfirst((string)$row['status'])) ?></span></td>
          </tr>
          <?php endforeach; else: ?>
          <tr><td colspan="6" style="text-align:center;color:var(--text-muted);">No validity configured.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<style>
  .content-header { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
  .form-label { display:block; font-size:13px; font-weight:600; margin-bottom:6px; }
  .form-control { width:100%; padding:10px 14px; border-radius:10px; border:1.5px solid var(--border-color); background:var(--input-bg, rgba(255,255,255,.05)); color:var(--text-primary); font-size:14px; box-sizing:border-box; }
  .validity-summary { margin-bottom:16px; padding:18px 20px; display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; }
  .validity-current { display:flex; flex-direction:column; gap:4px; }
  .validity-current span { font-size:12px; color:var(--text-muted); font-weight:700; text-transform:uppercase; }
  .validity-current strong { font-size:26px; line-height:1; color:#1f2937; }
  .validity-note { max-width:620px; font-size:13px; color:var(--text-muted); font-weight:600; }
  .validity-form-panel { margin-bottom:16px; }
  .validity-form-header { align-items:center; }
  .validity-add-form { display:grid; grid-template-columns:minmax(140px,.8fr) minmax(140px,.8fr) minmax(150px,1fr) minmax(150px,1fr) auto; gap:14px; align-items:end; }
  .validity-table-wrap { overflow-x:auto; }
  .validity-table { min-width:720px; }
  .toast-msg { position:fixed; bottom:30px; right:30px; z-index:9999; padding:14px 20px; border-radius:12px; display:flex; align-items:center; gap:10px; font-size:14px; font-weight:600; animation:slideUp .3s ease; box-shadow:0 8px 30px rgba(0,0,0,.2); }
  .toast-success { background:#10b981; color:white; }
  .toast-error { background:#ef4444; color:white; }
  @keyframes slideUp { from { transform:translateY(20px); opacity:0; } to { transform:translateY(0); opacity:1; } }
  @media (max-width:980px) { .validity-add-form { grid-template-columns:1fr; } .validity-add-form .btn { width:100%; } }
</style>

<script>
const validityFormPanel = document.getElementById('validityFormPanel');
const validityForm = document.getElementById('validityForm');
const btnShowValidityForm = document.getElementById('btnShowValidityForm');
const btnHideValidityForm = document.getElementById('btnHideValidityForm');
const btnSubmitValidity = document.getElementById('btnSubmitValidity');

btnShowValidityForm.addEventListener('click', () => {
  validityFormPanel.style.display = 'block';
  btnShowValidityForm.style.display = 'none';
  const firstField = validityForm.querySelector('input[name="validity_days"]');
  if (firstField) firstField.focus();
});

btnHideValidityForm.addEventListener('click', () => {
  validityFormPanel.style.display = 'none';
  btnShowValidityForm.style.display = 'inline-flex';
  validityForm.reset();
  validityForm.elements.system_date.value = '<?= htmlspecialchars(date('Y-m-d')) ?>';
  validityForm.elements.validity_from_date.value = '<?= htmlspecialchars(date('Y-m-d')) ?>';
  validityForm.elements.validity_to_date.value = '9999-12-31';
});

validityForm.onsubmit = async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const data = {};
  fd.forEach((v, k) => data[k] = v);
  btnSubmitValidity.disabled = true;
  btnSubmitValidity.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting';

  try {
    const res = await fetch('../../api/welfare/update_temporary_pass_validity.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': window.CLMS_CSRF_TOKEN || ''
      },
      body: JSON.stringify(data)
    });
    const raw = await res.text();
    let result = {};
    try { result = raw ? JSON.parse(raw) : {}; } catch (err) { result = { success:false, message: raw || 'Server returned invalid response.' }; }
    if (result.success) {
      showToast(result.message || 'Temporary pass validity added successfully.', 'success');
      setTimeout(() => location.reload(), 800);
    } else {
      showToast(result.message || 'Failed to add validity.', 'error');
      btnSubmitValidity.disabled = false;
      btnSubmitValidity.innerHTML = '<i class="fas fa-paper-plane"></i> Submit';
    }
  } catch (err) {
    showToast('Connection error. Please try again.', 'error');
    btnSubmitValidity.disabled = false;
    btnSubmitValidity.innerHTML = '<i class="fas fa-paper-plane"></i> Submit';
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

renderLayout('Temporary Pass Validity', 'renderContent', $role, $name);
