<?php
require_once '../../include/auth.php';
checkAuth(['contractor', 'customer']);
include '../../include/config.php';
include '../../include/customer_portal_context.php';
include '../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';
$user_id = $_SESSION['user_id'];
clms_get_portal_contractor($conn);

function renderContent() {
    global $conn, $user_id;

    $contractor = db_single($conn, "SELECT id FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    $c_id = $contractor['id'] ?? null;

    $reupload_docs = $c_id ? db_fetch_all($conn,
        "SELECT
            d.id,
            d.document_type,
            d.file_path,
            COALESCE(d.status, 'pending') AS status,
            COALESCE(d.remarks, '') AS remarks,
            d.uploaded_at,
            w.name AS worker_name,
            w.temp_id,
            gpr.request_no
         FROM documents d
         JOIN workmen w ON w.id = d.workman_id
         JOIN gate_pass_request_workers gprw ON gprw.workman_id = w.id
         JOIN gate_pass_requests gpr ON gpr.id = gprw.request_id
         WHERE w.contractor_id = ?
           AND COALESCE(d.status, 'pending') IN ('rejected', 'reupload_required')
           AND COALESCE(gpr.status, 'pending') IN ('pending', 'reupload_required')
         ORDER BY d.uploaded_at DESC, d.id DESC",
        'i', [$c_id]) : [];
    ?>

    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-file-circle-exclamation" style="color:#ef4444;margin-right:10px;"></i> Rejected Gate Pass Documents</h2>
      </div>
      <a href="gatepass-6a.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Gate Pass</a>
    </div>

    <?php if (!$c_id): ?>
      <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i><div>Complete contractor registration first.</div></div>
      <?php return; ?>
    <?php endif; ?>

    <div class="card glass">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-upload"></i> Documents Pending Re-upload</div>
      </div>
      <div class="card-body">
        <?php if (empty($reupload_docs)): ?>
          <div class="empty-state">
            <i class="fas fa-check-circle"></i>
            <strong>No rejected documents</strong>
            <span>There are no gate pass documents pending re-upload.</span>
          </div>
        <?php else: ?>
          <div class="reupload-doc-grid">
            <?php foreach ($reupload_docs as $doc): ?>
            <div class="reupload-doc-card" id="reupload-doc-<?= (int)$doc['id'] ?>">
              <div class="reupload-doc-head">
                <div>
                  <div class="reupload-doc-title"><?= htmlspecialchars($doc['document_type']) ?></div>
                  <div class="reupload-doc-meta">
                    <?= htmlspecialchars($doc['worker_name'] ?? 'Worker') ?>
                    <?= !empty($doc['temp_id']) ? ' | ' . htmlspecialchars($doc['temp_id']) : '' ?>
                    <?= !empty($doc['request_no']) ? ' | ' . htmlspecialchars($doc['request_no']) : '' ?>
                  </div>
                </div>
                <span class="badge badge-danger">Rejected</span>
              </div>
              <?php if (!empty($doc['remarks'])): ?>
              <div class="reupload-remarks"><i class="fas fa-comment-dots"></i> <?= htmlspecialchars($doc['remarks']) ?></div>
              <?php endif; ?>
              <?php
                $docPath = !empty($doc['file_path'])
                  ? (strpos($doc['file_path'], '/') === false && strpos($doc['file_path'], '\\') === false
                      ? '../../uploads/documents/' . $doc['file_path']
                      : '../../' . ltrim($doc['file_path'], '/\\'))
                  : '';
              ?>
              <?php if ($docPath): ?>
              <a class="btn btn-sm btn-outline" href="<?= htmlspecialchars($docPath) ?>" target="_blank"><i class="fas fa-eye"></i> View Rejected File</a>
              <?php endif; ?>
              <form class="reupload-doc-form" data-doc-id="<?= (int)$doc['id'] ?>">
                <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" required class="form-control">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-upload"></i> Re-upload</button>
              </form>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <style>
      .reupload-doc-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px; }
      .reupload-doc-card { border:1px solid var(--border-color);border-radius:10px;padding:14px;background:var(--card-bg); }
      .reupload-doc-head { display:flex;justify-content:space-between;gap:10px;align-items:flex-start;margin-bottom:8px; }
      .reupload-doc-title { font-size:13px;font-weight:700;line-height:1.35; }
      .reupload-doc-meta { font-size:11px;color:var(--text-muted);margin-top:3px; }
      .reupload-remarks { font-size:12px;color:#dc2626;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.18);border-radius:8px;padding:8px;margin:8px 0;line-height:1.4; }
      .reupload-doc-form { display:flex;gap:8px;align-items:center;margin-top:10px; }
      .reupload-doc-form .form-control { padding:7px 10px;font-size:12px; }
      .empty-state { padding:42px 0;text-align:center;color:var(--text-muted);display:flex;flex-direction:column;gap:8px;align-items:center; }
      .empty-state i { font-size:42px;color:#10b981;opacity:.8; }
    </style>

    <script>
      function showToast(msg, type='success') {
        let t = document.createElement('div');
        t.className='toast-msg toast-'+type;
        t.innerHTML=`<i class="fas fa-${type==='success'?'check-circle':'exclamation-circle'}"></i> ${msg}`;
        document.body.appendChild(t);
        setTimeout(()=>t.remove(),3500);
      }

      document.querySelectorAll('.reupload-doc-form').forEach(form => {
        form.addEventListener('submit', async (e) => {
          e.preventDefault();
          const docId = form.dataset.docId;
          const btn = form.querySelector('button[type="submit"]');
          const oldText = btn.innerHTML;
          btn.disabled = true;
          btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

          const formData = new FormData(form);
          formData.append('doc_id', docId);

          try {
            const res = await fetch('../../api/reupload_gatepass_document.php', {
              method: 'POST',
              body: formData
            });
            const result = await res.json();
            if (result.success) {
              showToast(result.message || 'Document re-uploaded successfully.', 'success');
              document.getElementById('reupload-doc-' + docId)?.remove();
              if (!document.querySelector('.reupload-doc-card')) {
                setTimeout(() => location.reload(), 1000);
              }
            } else {
              showToast('Error: ' + (result.message || 'Re-upload failed'), 'error');
            }
          } catch (err) {
            showToast('Network error - please try again.', 'error');
          }

          btn.disabled = false;
          btn.innerHTML = oldText;
        });
      });
    </script>
    <style>
      .toast-msg { position:fixed;bottom:30px;right:30px;z-index:9999;padding:14px 20px;border-radius:12px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:600;animation:slideUp .3s ease;box-shadow:0 8px 30px rgba(0,0,0,.2); }
      .toast-success { background:#10b981;color:white; }
      .toast-error { background:#ef4444;color:white; }
      @keyframes slideUp { from{transform:translateY(30px);opacity:0;}to{transform:translateY(0);opacity:1;} }
    </style>
    <?php
}

renderLayout("Rejected Gate Pass Documents", 'renderContent', $role, $name);
?>
