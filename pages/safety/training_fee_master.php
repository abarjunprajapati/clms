<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';
require_once __DIR__ . '/../../include/safety_training_control.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Safety Officer';
clms_safety_ensure_control_schema($conn);

function renderContent() {
    global $conn;
    $rows = db_fetch_all($conn, "SELECT * FROM training_fee_masters ORDER BY from_date DESC, id DESC");
    $feeRatesBySource = ['PWO' => [], 'PO' => [], 'SO' => []];
    foreach ($rows as $r) {
        $src = strtoupper(trim($r['fee_source'] ?? ''));
        if (isset($feeRatesBySource[$src])) {
            $feeRatesBySource[$src][] = $r;
        }
    }
    ?>

<div class="content-header">
  <div>
    <h2 class="page-title"><i class="fas fa-indian-rupee-sign" style="color:#0f766e;margin-right:10px;"></i> Training Fee Master</h2>
  </div>
  <button type="button" class="btn btn-primary" id="btnShowFeeForm">
    <i class="fas fa-plus"></i> Add
  </button>
</div>

<div class="card glass fee-form-panel" id="feeFormPanel" style="display:none; margin-bottom:16px;">
  <div class="card-header fee-form-header" style="display:flex; justify-content:space-between; align-items:center;">
    <div class="card-title"><i class="fas fa-plus-circle"></i> Add Fee Rate</div>
    <button type="button" class="btn btn-secondary btn-sm" id="btnHideFeeForm">
      <i class="fas fa-times"></i> Cancel
    </button>
  </div>
  <div class="card-body">
    <form id="trainingFeeForm" class="fee-add-form">
      <div class="form-group" style="margin:0;">
        <label class="form-label">Fee Source</label>
        <select class="form-control" name="fee_source" required>
          <option value="">Select</option>
          <option value="PWO">PWO</option>
          <option value="PO">PO</option>
          <option value="SO">SO</option>
        </select>
      </div>
      
      <div class="form-group" style="margin:0;">
        <label class="form-label">Amount</label>
        <input type="number" class="form-control" name="amount" min="0.00" step="0.01" placeholder="Enter amount" required>
      </div>

      <div class="form-group" style="margin:0;">
        <label class="form-label">From Date</label>
        <input type="date" class="form-control" name="from_date" value="<?= htmlspecialchars(date('Y-m-d')) ?>" required>
      </div>

      <div class="form-group" style="margin:0;">
        <label class="form-label">To Date</label>
        <input type="date" class="form-control" name="to_date" value="9999-12-31" required>
      </div>

      <button type="submit" class="btn btn-primary" id="btnSubmitFee" style="height:38px;"><i class="fas fa-paper-plane"></i> Save</button>
    </form>
  </div>
</div>

<div class="fee-source-grid" style="display:grid; gap:16px;">
  <?php foreach ($feeRatesBySource as $source => $sourceRows): ?>
  <section class="card glass fee-source-panel" style="overflow:hidden; margin-bottom:16px;">
    <div class="card-header fee-source-head" style="display:flex; justify-content:space-between; align-items:center;">
      <div class="card-title"><?= htmlspecialchars($source) ?> Fee Rates</div>
      <span style="font-size:12px; color:var(--text-muted); font-weight:700;"><?= count($sourceRows) ?> records</span>
    </div>
    <div class="card-body" style="padding:0;">
      <div class="fee-table-wrap" style="overflow-x:auto;">
        <table class="data-table fee-table" style="width:100%; min-width:720px; border-collapse:collapse;">
          <thead>
            <tr>
              <th style="padding:10px; text-align:left;">SL No</th>
              <th style="padding:10px; text-align:left;">From Date</th>
              <th style="padding:10px; text-align:left;">To Date</th>
              <th style="padding:10px; text-align:left;">Amount (Rs.)</th>
              <th style="padding:10px; text-align:left;">Entry Date</th>
              <th style="padding:10px; text-align:left;">Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($sourceRows): $sl = 1; foreach ($sourceRows as $row): $active = strtolower((string)$row['status']) === 'active'; ?>
            <tr>
              <td style="padding:10px;"><?= $sl++ ?></td>
              <td style="padding:10px;"><?= htmlspecialchars(date('d/m/Y', strtotime($row['from_date']))) ?></td>
              <td style="padding:10px;"><?= htmlspecialchars($row['to_date'] === '9999-12-31' ? '31/12/9999' : date('d/m/Y', strtotime($row['to_date']))) ?></td>
              <td style="padding:10px;"><strong><?= number_format((float)$row['amount'], 2) ?></strong></td>
              <td style="padding:10px;"><?= !empty($row['created_at']) ? htmlspecialchars(date('d/m/Y', strtotime($row['created_at']))) : '-' ?></td>
              <td style="padding:10px;"><span class="badge <?= $active ? 'badge-success' : 'badge-gray' ?>"><?= htmlspecialchars(ucfirst($row['status'])) ?></span></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="6" style="text-align:center; padding:20px; color:var(--text-muted);">No fee rate configured for <?= htmlspecialchars($source) ?>.</td></tr>
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
  .content-header { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:16px; }
  .fee-add-form { display:grid; grid-template-columns:repeat(4, 1fr) auto; gap:14px; align-items:end; }
  .toast-msg { position:fixed; bottom:30px; right:30px; z-index:9999; padding:14px 20px; border-radius:12px; display:flex; align-items:center; gap:10px; font-size:14px; font-weight:600; animation:slideUp .3s ease; box-shadow:0 8px 30px rgba(0,0,0,.2); }
  .toast-success { background:#10b981; color:white; }
  .toast-error { background:#ef4444; color:white; }
  @keyframes slideUp { from { transform:translateY(20px); opacity:0; } to { transform:translateY(0); opacity:1; } }
  @media (max-width:980px) { .fee-add-form { grid-template-columns:1fr; } .fee-add-form .btn { width:100%; } }
</style>

<script>
const feeFormPanel = document.getElementById('feeFormPanel');
const trainingFeeForm = document.getElementById('trainingFeeForm');
const btnShowFeeForm = document.getElementById('btnShowFeeForm');
const btnHideFeeForm = document.getElementById('btnHideFeeForm');
const btnSubmitFee = document.getElementById('btnSubmitFee');

btnShowFeeForm.addEventListener('click', () => {
  feeFormPanel.style.display = 'block';
  btnShowFeeForm.style.display = 'none';
  const firstField = trainingFeeForm.querySelector('select, input');
  if (firstField) firstField.focus();
});

btnHideFeeForm.addEventListener('click', () => {
  feeFormPanel.style.display = 'none';
  btnShowFeeForm.style.display = 'inline-flex';
  trainingFeeForm.reset();
  trainingFeeForm.elements.from_date.value = '<?= htmlspecialchars(date('Y-m-d')) ?>';
  trainingFeeForm.elements.to_date.value = '9999-12-31';
});

trainingFeeForm.onsubmit = async (e) => {
  e.preventDefault();
  
  const fd = new FormData(trainingFeeForm);
  const data = {};
  fd.forEach((v, k) => data[k] = v);
  
  btnSubmitFee.disabled = true;
  btnSubmitFee.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving';

  try {
    const res = await fetch('../../api/safety/update_fee_setting.php', {
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
      showToast(result.message || 'Fee saved successfully.', 'success');
      setTimeout(() => location.reload(), 800);
    } else {
      showToast(result.message || 'Failed to save fee.', 'error');
      btnSubmitFee.disabled = false;
      btnSubmitFee.innerHTML = '<i class="fas fa-paper-plane"></i> Save';
    }
  } catch (err) {
    showToast('Connection error. Please try again.', 'error');
    btnSubmitFee.disabled = false;
    btnSubmitFee.innerHTML = '<i class="fas fa-paper-plane"></i> Save';
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

renderLayout('Training Fee Master', 'renderContent', $role, $name);
?>