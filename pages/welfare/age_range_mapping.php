<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';
require_once __DIR__ . '/../../include/age_range_mapping.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Admin';

function renderContent() {
    global $conn;
    $rows = clms_get_age_range_rows($conn);
    $current = clms_get_active_age_range($conn);
    ?>

<div class="content-header">
  <div>
    <h2 class="page-title">Age Range Mapping</h2>
  </div>
  <button type="button" class="btn btn-primary" id="btnShowAgeForm"><i class="fas fa-plus"></i> Add</button>
</div>

<div class="card glass age-summary">
  <div><span>Current Active Range</span><strong><?= (int)$current['min_age'] ?> - <?= (int)$current['max_age'] ?> years</strong></div>
</div>

<div class="card glass" id="ageFormPanel" style="display:none;margin-bottom:16px;">
  <div class="card-header">
    <div class="card-title">Add Age Range</div>
    <button type="button" class="btn btn-secondary btn-sm" id="btnHideAgeForm"><i class="fas fa-times"></i> Cancel</button>
  </div>
  <div class="card-body">
    <form id="ageRangeForm" class="age-form">
      <div><label class="form-label">Min Age</label><input class="form-control" type="number" name="min_age" min="0" value="18" required></div>
      <div><label class="form-label">Max Age</label><input class="form-control" type="number" name="max_age" min="1" value="60" required></div>
      <div><label class="form-label">From Date</label><input class="form-control" type="date" name="effective_from" value="<?= htmlspecialchars(date('Y-m-d')) ?>" required></div>
      <div><label class="form-label">To Date</label><input class="form-control" type="date" name="effective_to" value="9999-12-31" required></div>
      <button class="btn btn-primary" type="submit" id="btnSubmitAge"><i class="fas fa-paper-plane"></i> Submit</button>
    </form>
  </div>
</div>

<div class="card glass">
  <div class="card-header"><div class="card-title">Age Range History</div></div>
  <div class="card-body" style="padding:0;overflow:auto;">
    <table class="data-table">
      <thead><tr><th>SL No</th><th>Min Age</th><th>Max Age</th><th>From Date</th><th>To Date</th><th>Status</th></tr></thead>
      <tbody>
        <?php if ($rows): $sl = 1; foreach ($rows as $row): ?>
        <tr>
          <td><?= $sl++ ?></td>
          <td><strong><?= (int)$row['min_age'] ?></strong></td>
          <td><strong><?= (int)$row['max_age'] ?></strong></td>
          <td><?= htmlspecialchars(date('d/m/Y', strtotime($row['effective_from']))) ?></td>
          <td><?= htmlspecialchars($row['effective_to'] === '9999-12-31' ? '31/12/9999' : date('d/m/Y', strtotime($row['effective_to']))) ?></td>
          <td><span class="badge <?= strtolower((string)$row['status']) === 'active' ? 'badge-success' : 'badge-gray' ?>"><?= htmlspecialchars(ucfirst((string)$row['status'])) ?></span></td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="6" style="text-align:center;color:var(--text-muted);">No age range configured.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<style>
  .content-header { display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap; }
  .age-summary { margin-bottom:16px;padding:18px 20px; }
  .age-summary span { display:block;font-size:12px;color:var(--text-muted);font-weight:700;text-transform:uppercase;margin-bottom:4px; }
  .age-summary strong { font-size:26px;line-height:1;color:#1f2937; }
  .age-form { display:grid;grid-template-columns:repeat(4,minmax(140px,1fr)) auto;gap:14px;align-items:end; }
  .form-label { display:block;font-size:13px;font-weight:600;margin-bottom:6px; }
  .form-control { width:100%;padding:10px 14px;border-radius:10px;border:1.5px solid var(--border-color);background:var(--input-bg, rgba(255,255,255,.05));color:var(--text-primary);font-size:14px;box-sizing:border-box; }
  .toast-msg { position:fixed;bottom:30px;right:30px;z-index:9999;padding:14px 20px;border-radius:12px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:600;box-shadow:0 8px 30px rgba(0,0,0,.2); }
  .toast-success { background:#10b981;color:white; } .toast-error { background:#ef4444;color:white; }
  @media (max-width:980px) { .age-form { grid-template-columns:1fr; } .age-form .btn { width:100%; } }
</style>

<script>
const ageFormPanel = document.getElementById('ageFormPanel');
document.getElementById('btnShowAgeForm').onclick = () => { ageFormPanel.style.display = 'block'; document.getElementById('btnShowAgeForm').style.display = 'none'; };
document.getElementById('btnHideAgeForm').onclick = () => { ageFormPanel.style.display = 'none'; document.getElementById('btnShowAgeForm').style.display = 'inline-flex'; };
document.getElementById('ageRangeForm').onsubmit = async (e) => {
  e.preventDefault();
  const data = Object.fromEntries(new FormData(e.target).entries());
  const btn = document.getElementById('btnSubmitAge');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting';
  try {
    const res = await fetch('../../api/welfare/update_age_range_mapping.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json','X-CSRF-Token': window.CLMS_CSRF_TOKEN || ''},
      body: JSON.stringify(data)
    });
    const result = await res.json();
    showToast(result.message || (result.success ? 'Saved.' : 'Failed.'), result.success ? 'success' : 'error');
    if (result.success) setTimeout(() => location.reload(), 800);
  } catch (err) {
    showToast('Connection error. Please try again.', 'error');
  }
  btn.disabled = false;
  btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit';
};
function showToast(msg, type) {
  const t = document.createElement('div');
  t.className = 'toast-msg toast-' + type;
  t.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${msg}`;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 3500);
}
</script>

<?php
}

renderLayout('Age Range Mapping', 'renderContent', $role, $name);
