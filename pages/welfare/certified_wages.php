<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'welfare_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';
require_once __DIR__ . '/../../include/wage_settings.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Admin';

function renderContent() {
    global $conn;
    $certifiedWageRates = clms_get_certified_wage_rates($conn);
    $wageRatesByCategory = ['Skilled' => [], 'Semi-Skilled' => [], 'Unskilled' => []];
    foreach ($certifiedWageRates as $rateRow) {
        $category = clms_normalize_wage_category($rateRow['category'] ?? '');
        if ($category !== '' && isset($wageRatesByCategory[$category])) {
            $wageRatesByCategory[$category][] = $rateRow;
        }
    }
    ?>

<div class="content-header">
  <div>
    <h2 class="page-title"><i class="fas fa-indian-rupee-sign" style="color:#16a34a;margin-right:10px;"></i> Certified Wage Rate</h2>
  </div>
  <button type="button" class="btn btn-primary" id="btnShowWageForm">
    <i class="fas fa-plus"></i> Add
  </button>
</div>

<div class="card glass wage-form-panel" id="wageFormPanel" style="display:none;">
  <div class="card-header wage-form-header">
    <div class="card-title"><i class="fas fa-plus-circle"></i> Add Wage Rate</div>
    <button type="button" class="btn btn-secondary btn-sm" id="btnHideWageForm">
      <i class="fas fa-times"></i> Cancel
    </button>
  </div>
  <div class="card-body">
    <form id="certifiedWageForm" class="wage-add-form">
      <div class="form-group" style="margin:0;">
        <label class="form-label">Date</label>
        <input type="date" class="form-control" name="system_date" value="<?= htmlspecialchars(date('Y-m-d')) ?>" readonly>
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label">Skilled Category</label>
        <select class="form-control" name="category" required>
          <option value="">Select</option>
          <option value="Skilled">Skilled</option>
          <option value="Semi-Skilled">Semi-Skilled</option>
          <option value="Unskilled">Unskilled</option>
        </select>
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label">Wage From Dt</label>
        <input type="date" class="form-control" name="wage_from_date" value="<?= htmlspecialchars(date('Y-m-d')) ?>" required>
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label">Wage To Dt</label>
        <input type="date" class="form-control" name="wage_to_date" value="9999-12-31" required>
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label">Wage</label>
        <input type="number" class="form-control" name="wage_rate" min="0.01" step="0.01" placeholder="Enter amount" required>
      </div>
      <button type="submit" class="btn btn-primary" id="btnSubmitWage"><i class="fas fa-paper-plane"></i> Submit</button>
    </form>
  </div>
</div>

<div class="wage-category-grid">
  <?php foreach ($wageRatesByCategory as $category => $rows): ?>
  <section class="card glass wage-category-panel">
    <div class="card-header wage-category-head">
      <div class="card-title"><?= htmlspecialchars($category) ?></div>
      <span><?= count($rows) ?> records</span>
    </div>
    <div class="card-body" style="padding:0;">
      <div class="wage-table-wrap">
        <table class="data-table wage-table">
          <thead>
            <tr>
              <th>SL No</th>
              <th>Wage From Dt</th>
              <th>Wage To Dt</th>
              <th>Wage Rate</th>
              <th>Entry Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($rows): $sl = 1; foreach ($rows as $row): ?>
            <tr>
              <td><?= $sl++ ?></td>
              <td><?= htmlspecialchars(date('d/m/Y', strtotime($row['wage_from_date']))) ?></td>
              <td><?= htmlspecialchars($row['wage_to_date'] === '9999-12-31' ? '31/12/9999' : date('d/m/Y', strtotime($row['wage_to_date']))) ?></td>
              <td><strong><?= number_format((float)$row['wage_rate'], 2) ?></strong></td>
              <td><?= !empty($row['created_at']) ? htmlspecialchars(date('d/m/Y', strtotime($row['created_at']))) : '-' ?></td>
              <td><span class="badge <?= strtolower((string)$row['status']) === 'active' ? 'badge-success' : 'badge-gray' ?>"><?= htmlspecialchars(ucfirst((string)$row['status'])) ?></span></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="6" style="text-align:center;color:var(--text-muted);">No wage rate configured.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
  <?php endforeach; ?>
</div>

<style>
  .form-label { display:block; font-size:13px; font-weight:600; margin-bottom:6px; }
  .form-control { width:100%; padding:10px 14px; border-radius:10px; border:1.5px solid var(--border-color); background:var(--input-bg, rgba(255,255,255,.05)); color:var(--text-primary); font-size:14px; box-sizing:border-box; }
  .content-header { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
  .wage-form-panel { margin-bottom:16px; }
  .wage-form-header { align-items:center; }
  .wage-add-form { display:grid; grid-template-columns:minmax(140px,.8fr) minmax(160px,1.1fr) minmax(150px,1fr) minmax(150px,1fr) minmax(150px,1fr) auto; gap:14px; align-items:end; }
  .wage-category-grid { display:grid; gap:16px; }
  .wage-category-panel { overflow:hidden; }
  .wage-category-head { align-items:center; }
  .wage-category-head span { font-size:12px; color:var(--text-muted); font-weight:700; }
  .wage-table-wrap { overflow-x:auto; }
  .wage-table { min-width:720px; }
  .toast-msg { position:fixed; bottom:30px; right:30px; z-index:9999; padding:14px 20px; border-radius:12px; display:flex; align-items:center; gap:10px; font-size:14px; font-weight:600; animation:slideUp .3s ease; box-shadow:0 8px 30px rgba(0,0,0,.2); }
  .toast-success { background:#10b981; color:white; }
  .toast-error { background:#ef4444; color:white; }
  @keyframes slideUp { from { transform:translateY(20px); opacity:0; } to { transform:translateY(0); opacity:1; } }
  @media (max-width:980px) { .wage-add-form { grid-template-columns:1fr; } .wage-add-form .btn { width:100%; } }
</style>

<script>
const wageFormPanel = document.getElementById('wageFormPanel');
const certifiedWageForm = document.getElementById('certifiedWageForm');
const btnShowWageForm = document.getElementById('btnShowWageForm');
const btnHideWageForm = document.getElementById('btnHideWageForm');
const btnSubmitWage = document.getElementById('btnSubmitWage');

btnShowWageForm.addEventListener('click', () => {
  wageFormPanel.style.display = 'block';
  btnShowWageForm.style.display = 'none';
  const firstField = certifiedWageForm.querySelector('select, input');
  if (firstField) firstField.focus();
});

btnHideWageForm.addEventListener('click', () => {
  wageFormPanel.style.display = 'none';
  btnShowWageForm.style.display = 'inline-flex';
  certifiedWageForm.reset();
  certifiedWageForm.elements.system_date.value = '<?= htmlspecialchars(date('Y-m-d')) ?>';
  certifiedWageForm.elements.wage_from_date.value = '<?= htmlspecialchars(date('Y-m-d')) ?>';
  certifiedWageForm.elements.wage_to_date.value = '9999-12-31';
});

certifiedWageForm.onsubmit = async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const data = {};
  fd.forEach((v, k) => data[k] = v);
  btnSubmitWage.disabled = true;
  btnSubmitWage.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting';

  try {
    const res = await fetch('../../api/welfare/update_wage_setting.php', {
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
      showToast(result.message || 'Certified wage rate added successfully.', 'success');
      setTimeout(() => location.reload(), 800);
    } else {
      showToast(result.message || 'Failed to add certified wage rate.', 'error');
      btnSubmitWage.disabled = false;
      btnSubmitWage.innerHTML = '<i class="fas fa-paper-plane"></i> Submit';
    }
  } catch (err) {
    showToast('Connection error. Please try again.', 'error');
    btnSubmitWage.disabled = false;
    btnSubmitWage.innerHTML = '<i class="fas fa-paper-plane"></i> Submit';
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

renderLayout("Certified Wage Rate", 'renderContent', $role, $name);
