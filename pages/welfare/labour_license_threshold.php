<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';
require_once __DIR__ . '/../../include/labour_license_threshold.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Admin';

function renderContent() {
    global $conn;
    $thresholdRows = clms_get_labour_license_threshold_rows($conn);
    $currentThreshold = clms_get_labour_license_threshold($conn);
    ?>

<div class="content-header">
  <div>
    <h2 class="page-title"><i class="fas fa-scale-balanced" style="color:#f59e0b;margin-right:10px;"></i> Labour License Threshold</h2>
  </div>
  <button type="button" class="btn btn-primary" id="btnShowThresholdForm">
    <i class="fas fa-plus"></i> Add
  </button>
</div>

<div class="card glass threshold-summary">
  <div class="threshold-current">
    <span>Current Active Threshold</span>
    <strong><?= (int)$currentThreshold ?> workmen</strong>
  </div>
  <div class="threshold-note">Labour Licence becomes mandatory when proposed/approved workmen count is equal to or greater than this active threshold.</div>
</div>

<div class="card glass threshold-form-panel" id="thresholdFormPanel" style="display:none;">
  <div class="card-header threshold-form-header">
    <div class="card-title"><i class="fas fa-plus-circle"></i> Add Labour License Threshold</div>
    <button type="button" class="btn btn-secondary btn-sm" id="btnHideThresholdForm">
      <i class="fas fa-times"></i> Cancel
    </button>
  </div>
  <div class="card-body">
    <form id="thresholdForm" class="threshold-add-form">
      <div class="form-group" style="margin:0;">
        <label class="form-label">Date</label>
        <input type="date" class="form-control" name="system_date" value="<?= htmlspecialchars(date('Y-m-d')) ?>" readonly>
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label">Threshold</label>
        <input type="number" class="form-control" name="threshold_value" min="1" step="1" placeholder="Enter count" required>
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label">From Date</label>
        <input type="date" class="form-control" name="threshold_from_date" value="<?= htmlspecialchars(date('Y-m-d')) ?>" required>
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label">To Date</label>
        <input type="date" class="form-control" name="threshold_to_date" value="9999-12-31" required>
      </div>
      <button type="submit" class="btn btn-primary" id="btnSubmitThreshold"><i class="fas fa-paper-plane"></i> Submit</button>
    </form>
  </div>
</div>

<div class="card glass">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-list"></i> Threshold History</div>
  </div>
  <div class="card-body" style="padding:0;">
    <div class="threshold-table-wrap">
      <table class="data-table threshold-table">
        <thead>
          <tr>
            <th>SL No</th>
            <th>Threshold</th>
            <th>From Date</th>
            <th>To Date</th>
            <th>Entry Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($thresholdRows): $sl = 1; foreach ($thresholdRows as $row): ?>
          <tr>
            <td><?= $sl++ ?></td>
            <td><strong><?= (int)$row['threshold_value'] ?></strong></td>
            <td><?= htmlspecialchars(date('d/m/Y', strtotime($row['threshold_from_date']))) ?></td>
            <td><?= htmlspecialchars($row['threshold_to_date'] === '9999-12-31' ? '31/12/9999' : date('d/m/Y', strtotime($row['threshold_to_date']))) ?></td>
            <td><?= !empty($row['created_at']) ? htmlspecialchars(date('d/m/Y', strtotime($row['created_at']))) : '-' ?></td>
            <td><span class="badge <?= strtolower((string)$row['status']) === 'active' ? 'badge-success' : 'badge-gray' ?>"><?= htmlspecialchars(ucfirst((string)$row['status'])) ?></span></td>
          </tr>
          <?php endforeach; else: ?>
          <tr><td colspan="6" style="text-align:center;color:var(--text-muted);">No threshold configured.</td></tr>
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
  .threshold-summary { margin-bottom:16px; padding:18px 20px; display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; }
  .threshold-current { display:flex; flex-direction:column; gap:4px; }
  .threshold-current span { font-size:12px; color:var(--text-muted); font-weight:700; text-transform:uppercase; }
  .threshold-current strong { font-size:26px; line-height:1; color:#1f2937; }
  .threshold-note { max-width:620px; font-size:13px; color:var(--text-muted); font-weight:600; }
  .threshold-form-panel { margin-bottom:16px; }
  .threshold-form-header { align-items:center; }
  .threshold-add-form { display:grid; grid-template-columns:minmax(140px,.8fr) minmax(140px,.8fr) minmax(150px,1fr) minmax(150px,1fr) auto; gap:14px; align-items:end; }
  .threshold-table-wrap { overflow-x:auto; }
  .threshold-table { min-width:720px; }
  .toast-msg { position:fixed; bottom:30px; right:30px; z-index:9999; padding:14px 20px; border-radius:12px; display:flex; align-items:center; gap:10px; font-size:14px; font-weight:600; animation:slideUp .3s ease; box-shadow:0 8px 30px rgba(0,0,0,.2); }
  .toast-success { background:#10b981; color:white; }
  .toast-error { background:#ef4444; color:white; }
  @keyframes slideUp { from { transform:translateY(20px); opacity:0; } to { transform:translateY(0); opacity:1; } }
  @media (max-width:980px) { .threshold-add-form { grid-template-columns:1fr; } .threshold-add-form .btn { width:100%; } }
</style>

<script>
const thresholdFormPanel = document.getElementById('thresholdFormPanel');
const thresholdForm = document.getElementById('thresholdForm');
const btnShowThresholdForm = document.getElementById('btnShowThresholdForm');
const btnHideThresholdForm = document.getElementById('btnHideThresholdForm');
const btnSubmitThreshold = document.getElementById('btnSubmitThreshold');

btnShowThresholdForm.addEventListener('click', () => {
  thresholdFormPanel.style.display = 'block';
  btnShowThresholdForm.style.display = 'none';
  const firstField = thresholdForm.querySelector('input[name="threshold_value"]');
  if (firstField) firstField.focus();
});

btnHideThresholdForm.addEventListener('click', () => {
  thresholdFormPanel.style.display = 'none';
  btnShowThresholdForm.style.display = 'inline-flex';
  thresholdForm.reset();
  thresholdForm.elements.system_date.value = '<?= htmlspecialchars(date('Y-m-d')) ?>';
  thresholdForm.elements.threshold_from_date.value = '<?= htmlspecialchars(date('Y-m-d')) ?>';
  thresholdForm.elements.threshold_to_date.value = '9999-12-31';
});

thresholdForm.onsubmit = async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const data = {};
  fd.forEach((v, k) => data[k] = v);
  btnSubmitThreshold.disabled = true;
  btnSubmitThreshold.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting';

  try {
    const res = await fetch('../../api/welfare/update_labour_license_threshold.php', {
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
      showToast(result.message || 'Threshold added successfully.', 'success');
      setTimeout(() => location.reload(), 800);
    } else {
      showToast(result.message || 'Failed to add threshold.', 'error');
      btnSubmitThreshold.disabled = false;
      btnSubmitThreshold.innerHTML = '<i class="fas fa-paper-plane"></i> Submit';
    }
  } catch (err) {
    showToast('Connection error. Please try again.', 'error');
    btnSubmitThreshold.disabled = false;
    btnSubmitThreshold.innerHTML = '<i class="fas fa-paper-plane"></i> Submit';
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

renderLayout('Labour License Threshold', 'renderContent', $role, $name);
