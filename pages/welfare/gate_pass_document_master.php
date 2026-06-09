<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';
require_once __DIR__ . '/../../include/gate_pass_document_master.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Admin';

function renderContent() {
    global $conn;
    $rows = clms_get_gate_pass_document_master_rows($conn, false);
    ?>

<div class="content-header">
  <div><h2 class="page-title">Gate Pass Document Master</h2></div>
  <button class="btn btn-primary" type="button" onclick="openDocForm({})"><i class="fas fa-plus"></i> Add</button>
</div>

<div class="card glass">
  <div class="card-header"><div class="card-title">Annexure-6A Documents</div></div>
  <div class="card-body" style="padding:0;overflow:auto;">
    <table class="data-table">
      <thead><tr><th>Order</th><th>Upload Key</th><th>Category</th><th>Document</th><th>Mandatory</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
        <tr>
          <td><?= (int)$row['sort_order'] ?></td>
          <td><code><?= htmlspecialchars($row['upload_key']) ?></code></td>
          <td><?= htmlspecialchars($row['category']) ?></td>
          <td>
            <strong><?= htmlspecialchars($row['document_type']) ?></strong>
            <?php if (!empty($row['hint'])): ?><div style="font-size:11px;color:var(--text-muted);margin-top:3px;"><?= htmlspecialchars($row['hint']) ?></div><?php endif; ?>
          </td>
          <td><span class="badge <?= (int)$row['is_mandatory'] ? 'badge-danger' : 'badge-gray' ?>"><?= (int)$row['is_mandatory'] ? 'Yes' : 'No' ?></span></td>
          <td><span class="badge <?= strtolower((string)$row['status']) === 'active' ? 'badge-success' : 'badge-gray' ?>"><?= htmlspecialchars(ucfirst((string)$row['status'])) ?></span></td>
          <td>
            <button class="btn btn-sm btn-outline-primary" type="button" onclick='openDocForm(<?= json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><i class="fas fa-edit"></i> Edit</button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="doc-modal" id="docModal" aria-hidden="true">
  <div class="doc-modal-dialog">
    <div class="doc-modal-head">
      <h3>Document Master</h3>
      <button type="button" onclick="closeDocForm()" aria-label="Close">&times;</button>
    </div>
    <form id="docMasterForm">
      <input type="hidden" name="id" id="docId">
      <div class="form-grid-2">
        <div><label class="form-label">Upload Key</label><input class="form-control" name="upload_key" id="docUploadKey" required></div>
        <div><label class="form-label">Category</label><select class="form-control" name="category" id="docCategory" required><option value="medical">medical</option><option value="pcc">pcc</option><option value="coverage">coverage</option></select></div>
      </div>
      <div><label class="form-label">Document Type</label><input class="form-control" name="document_type" id="docType" required></div>
      <div><label class="form-label">Hint</label><input class="form-control" name="hint" id="docHint"></div>
      <div class="form-grid-2">
        <div><label class="form-label">Sort Order</label><input class="form-control" type="number" name="sort_order" id="docSort" min="0" value="100"></div>
        <div><label class="form-label">Status</label><select class="form-control" name="status" id="docStatus"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
      </div>
      <label class="check-row"><input type="checkbox" name="is_mandatory" id="docMandatory" value="1"> Mandatory</label>
      <div class="doc-modal-foot">
        <button class="btn btn-outline" type="button" onclick="closeDocForm()">Cancel</button>
        <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Save</button>
      </div>
    </form>
  </div>
</div>

<style>
  .content-header { display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap; }
  .form-grid-2 { display:grid;grid-template-columns:1fr 1fr;gap:14px; }
  .form-label { display:block;font-size:13px;font-weight:600;margin:12px 0 6px; }
  .form-control { width:100%;padding:10px 14px;border-radius:10px;border:1.5px solid var(--border-color);background:var(--input-bg, rgba(255,255,255,.05));color:var(--text-primary);font-size:14px;box-sizing:border-box; }
  .check-row { display:flex;align-items:center;gap:8px;font-weight:700;margin:14px 0; }
  .doc-modal { display:none;position:fixed;inset:0;z-index:2000;background:rgba(15,23,42,.48);padding:24px 16px;overflow:auto; }
  .doc-modal.is-open { display:flex;justify-content:center;align-items:flex-start; }
  .doc-modal-dialog { width:min(620px,100%);background:#fff;border-radius:12px;border:1px solid #dbe4ef;box-shadow:0 24px 60px rgba(15,23,42,.24);overflow:hidden; }
  .doc-modal-head { display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #e5edf6; }
  .doc-modal-head h3 { margin:0;font-size:18px;font-weight:800;color:#1f2937; }
  .doc-modal-head button { width:34px;height:34px;border:1px solid #dbe4ef;border-radius:8px;background:#fff;font-size:22px;line-height:1;cursor:pointer; }
  #docMasterForm { padding:18px 22px 22px; }
  .doc-modal-foot { display:flex;justify-content:flex-end;gap:10px;padding-top:10px; }
  .toast-msg { position:fixed;bottom:30px;right:30px;z-index:9999;padding:14px 20px;border-radius:12px;font-size:14px;font-weight:600;box-shadow:0 8px 30px rgba(0,0,0,.2); }
  .toast-success { background:#10b981;color:white; } .toast-error { background:#ef4444;color:white; }
  @media (max-width:640px) { .form-grid-2 { grid-template-columns:1fr; } .doc-modal-foot { flex-direction:column-reverse; } .doc-modal-foot .btn { width:100%; } }
</style>

<script>
function openDocForm(row) {
  document.getElementById('docId').value = row.id || '';
  document.getElementById('docUploadKey').value = row.upload_key || '';
  document.getElementById('docCategory').value = row.category || 'medical';
  document.getElementById('docType').value = row.document_type || '';
  document.getElementById('docHint').value = row.hint || '';
  document.getElementById('docSort').value = row.sort_order || 100;
  document.getElementById('docStatus').value = row.status || 'active';
  document.getElementById('docMandatory').checked = String(row.is_mandatory || '0') === '1';
  document.getElementById('docModal').classList.add('is-open');
  document.getElementById('docModal').setAttribute('aria-hidden', 'false');
}
function closeDocForm() {
  document.getElementById('docModal').classList.remove('is-open');
  document.getElementById('docModal').setAttribute('aria-hidden', 'true');
}
document.getElementById('docMasterForm').onsubmit = async (e) => {
  e.preventDefault();
  const data = Object.fromEntries(new FormData(e.target).entries());
  data.is_mandatory = document.getElementById('docMandatory').checked ? '1' : '0';
  try {
    const res = await fetch('../../api/welfare/update_gate_pass_document_master.php', {
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
};
function showToast(msg, type) {
  const t = document.createElement('div');
  t.className = 'toast-msg toast-' + type;
  t.textContent = msg;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 3500);
}
</script>

<?php
}

renderLayout('Gate Pass Document Master', 'renderContent', $role, $name);
