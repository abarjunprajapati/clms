<?php
require_once '../../include/auth.php';
checkAuth(['contractor', 'customer']);
include '../../include/config.php';
include '../../include/customer_portal_context.php';
include '../../include/layout.php';
require_once '../../include/gate_pass_document_master.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';
$user_id = $_SESSION['user_id'];
clms_get_portal_contractor($conn);

function renderContent() {
    global $conn, $user_id;

    $contractor = db_single($conn, "SELECT id, contractor_name FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    $c_id = $contractor['id'] ?? null;

    // Training-passed workers eligible for gate pass
    $trained_workers = $c_id ? db_fetch_all($conn,
        "SELECT id, name, trade, skill, temp_id, aadhaar, training_valid_till FROM workmen
         WHERE contractor_id = ?
           AND (
                training_status IN ('pass','passed','training_passed','qualified','completed')
                OR safety_training_status IN ('1','TRAINING_PASSED','PASSED','pass','passed')
           )
           AND (training_valid_till IS NULL OR training_valid_till >= CURDATE())
         ORDER BY name",
        'i', [$c_id]) : [];

    // Existing gate pass requests (Annexure 5A / 6A)
    $existing_passes = $c_id ? db_fetch_all($conn,
        "SELECT tr.request_no as pass_no, tr.pass_type, tr.from_date as valid_from, tr.to_date as valid_to, 
                tr.status, tr.created_at, w.name as worker_name, w.trade, w.temp_id, gpw.gatepass_no,
                (
                    SELECT COUNT(*)
                    FROM documents d
                    WHERE d.workman_id = w.id
                      AND COALESCE(d.status, 'pending') IN ('rejected', 'reupload_required')
                ) AS rejected_doc_count
         FROM gate_pass_requests tr
         JOIN gate_pass_request_workers gpw ON tr.id = gpw.request_id
         JOIN workmen w ON gpw.workman_id = w.id
         WHERE tr.contractor_id = ?
         ORDER BY tr.created_at DESC",
        'i', [$c_id]) : [];

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
        <h2 class="page-title"><i class="fas fa-id-badge" style="color:#6366f1;margin-right:10px;"></i> Gate Pass Request </h2>
        <!-- <p class="page-subtitle">Raise gate pass requests for safety-trained workmen and upload Annexure 6A documents.</p> -->
      </div>
    </div>

    <?php if (!$c_id): ?>
    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i><div>Complete contractor registration first.</div></div>
    <?php return; endif; ?>

    <?php if (empty($trained_workers)): ?>
    <div class="alert alert-danger">
      <i class="fas fa-ban"></i>
      <div>
        <strong>No Safety-Cleared Workers Found.</strong> Training must be completed and approved by the Safety Department.
        <a href="training_request.php" style="color:white;text-decoration:underline;margin-left:8px;">Request Training →</a>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($reupload_docs)): ?>
    <div class="alert alert-danger" style="justify-content:space-between;align-items:center;margin-bottom:20px;">
      <div style="display:flex;align-items:center;gap:10px;">
        <i class="fas fa-file-circle-exclamation"></i>
        <div><strong><?= count($reupload_docs) ?> rejected document(s)</strong> need correction before this gate pass can proceed.</div>
      </div>
      <a href="gatepass-reupload.php" class="btn btn-sm btn-danger"><i class="fas fa-upload"></i> Re-upload Documents</a>
    </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:480px 1fr;gap:20px;align-items:start;">

      <!-- Gate Pass Form -->
      <div class="card glass">
        <div class="card-header"><div class="card-title"><i class="fas fa-file-signature"></i> New Gate Pass Request</div></div>
        <div class="card-body">
          <?php if (empty($trained_workers)): ?>
          <div class="empty-state" style="padding:30px 0;">
            <i class="fas fa-lock" style="font-size:40px;opacity:.2;display:block;margin-bottom:12px;"></i>
            <p>Apply for training first to unlock gate passes.</p>
          </div>
          <?php else: ?>
          <form id="gatePassForm" enctype="multipart/form-data">

            <div class="form-section-label">📋 Worker & Pass Details</div>
            <div class="form-group">
              <label class="form-label required">Select Worker</label>
              <select class="form-control" name="workman_id" id="workerSelect" required>
                <option value="">Choose a trained worker...</option>
                <?php foreach ($trained_workers as $w): ?>
                <option value="<?= $w['id'] ?>" data-name="<?= htmlspecialchars($w['name']) ?>">
                  <?= htmlspecialchars($w['name']) ?> — <?= htmlspecialchars($w['trade'] ?? '') ?>
                  <?= $w['temp_id'] ? ' | ' . htmlspecialchars($w['temp_id']) : '' ?>
                  <?= !empty($w['training_valid_till']) ? ' | Training valid till ' . date('d M Y', strtotime($w['training_valid_till'])) : '' ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label required">Pass Type</label>
              <div class="pass-type-grid">
                <label class="pass-type-card">
                  <input type="radio" name="pass_type" value="temporary" checked>
                  <div class="ptc-inner">
                    <i class="fas fa-clock"></i>
                    <div>Temporary Pass</div>
                    <small>30 Days validity</small>
                  </div>
                </label>
                <label class="pass-type-card">
                  <input type="radio" name="pass_type" value="permanent">
                  <div class="ptc-inner">
                    <i class="fas fa-id-card"></i>
                    <div>Permanent Pass</div>
                    <small>Full contract term</small>
                  </div>
                </label>
              </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
              <div class="form-group">
                <label class="form-label required">Valid From</label>
                <input type="date" class="form-control" name="valid_from" required min="<?= date('Y-m-d') ?>">
              </div>
              <div class="form-group">
                <label class="form-label required">Valid To</label>
                <input type="date" class="form-control" name="valid_to" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
              </div>
            </div>

            <div class="annexure-doc-title">ANNEXURE-6A</div>
            <div class="annexure-doc-subtitle">LIST OF DOCUMENTS TO BE SUBMITTED BY CONTRACTOR FOR GATE PASS</div>

            <?php
            $annexure6aDocs = clms_get_gate_pass_documents_for_form($conn);
            ?>
            <?php foreach ($annexure6aDocs as $index => $doc): ?>
            <div class="doc-upload-item">
              <div class="doc-upload-info">
                <i class="fas <?= $doc['icon'] ?>" style="color:<?= $doc['color'] ?>;"></i>
                <div>
                  <div class="doc-name">
                    <span class="doc-serial"><?= $index + 1 ?>.</span>
                    <?= htmlspecialchars($doc['label']) ?>
                    <span class="badge <?= $doc['required'] ? 'badge-danger' : 'badge-gray' ?> doc-status"><?= $doc['required'] ? 'Mandatory' : 'Optional' ?></span>
                  </div>
                  <div class="doc-hint"><?= htmlspecialchars($doc['hint']) ?></div>
                </div>
              </div>
              <input type="file" name="<?= htmlspecialchars($doc['key']) ?>" accept=".pdf,.jpg,.jpeg,.png" <?= $doc['required'] ? 'required' : '' ?> class="doc-file-input" id="doc-<?= htmlspecialchars($doc['id']) ?>">
              <label for="doc-<?= htmlspecialchars($doc['id']) ?>" class="btn btn-sm btn-outline doc-upload-btn"><i class="fas fa-upload"></i> Upload</label>
              <span class="doc-filename" id="fn-<?= htmlspecialchars($doc['id']) ?>">No file</span>
            </div>
            <?php endforeach; ?>

            <div class="form-group" style="margin-top:12px;">
              <label class="form-label">Additional Remarks</label>
              <textarea class="form-control" name="remarks" rows="2" placeholder="Any special notes..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;margin-top:16px;" id="submitGPBtn">
              <i class="fas fa-paper-plane"></i> Submit Gate Pass Request
            </button>
          </form>
          <?php endif; ?>
        </div>
      </div>

      <!-- Existing Pass Requests -->
      <div class="card glass">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-list-check"></i> Gate Pass Requests</div>
          <a href="pass_status.php" class="btn btn-sm btn-outline">Full Tracker</a>
        </div>
        <div class="card-body" style="padding:0;">
          <?php if (empty($existing_passes)): ?>
          <div class="empty-state" style="padding:40px 0;">
            <i class="fas fa-id-card" style="font-size:40px;opacity:.2;display:block;margin-bottom:12px;"></i>
            <p>No gate pass requests submitted yet.</p>
          </div>
          <?php else: ?>
          <table class="data-table">
            <thead><tr><th>Worker</th><th>Pass Type</th><th>Valid Period</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($existing_passes as $gp): ?>
            <tr>
              <td>
                <div style="font-weight:600;"><?= htmlspecialchars($gp['worker_name'] ?? '—') ?></div>
                <div style="font-size:11px;color:var(--text-muted);">
                    <?= htmlspecialchars($gp['trade'] ?? '') ?> 
                    <?= $gp['temp_id'] ? ' | ' . htmlspecialchars($gp['temp_id']) : '' ?>
                </div>
                <?php if ($gp['gatepass_no']): ?>
                <div style="font-size:10px; color:#10b981; font-weight:700;">Pass: <?= htmlspecialchars($gp['gatepass_no']) ?></div>
                <?php endif; ?>
              </td>
              <td><span class="badge badge-gray"><?= htmlspecialchars($gp['pass_type'] ?? '—') ?></span></td>
              <td style="font-size:12px;">
                <?= $gp['valid_from'] ? date('d M Y', strtotime($gp['valid_from'])) : '—' ?>
                <br>to <?= $gp['valid_to'] ? date('d M Y', strtotime($gp['valid_to'])) : '—' ?>
              </td>
              <td>
                <?php
                  $st = $gp['status'] ?? 'pending';
                  if ((int)($gp['rejected_doc_count'] ?? 0) > 0 && !in_array($st, ['approved', 'active', 'issued'], true)) {
                    $st = 'reupload_required';
                  }
                  $sc = ['active'=>'badge-success','pending'=>'badge-warning','reupload_required'=>'badge-danger','rejected'=>'badge-danger','expired'=>'badge-gray','approved'=>'badge-success'];
                ?>
                <span class="badge <?= $sc[$st] ?? 'badge-gray' ?>"><?= strtoupper(str_replace('_', ' ', $st)) ?></span>
              </td>
              <td>
                <a href="pass_status.php" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a>
              </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <style>
    .form-section-label { font-size:12px;font-weight:700;color:var(--text-muted);letter-spacing:.5px;text-transform:uppercase;margin-bottom:10px;margin-top:4px; }
    .form-group { margin-bottom:14px; }
    .form-label { display:block;font-size:13px;font-weight:600;margin-bottom:5px; }
    .form-label.required::after { content:' *';color:#ef4444; }
    .form-control { width:100%;padding:9px 13px;border-radius:8px;border:1.5px solid var(--border-color);background:var(--input-bg,rgba(255,255,255,.04));color:var(--text-primary);font-size:13px;transition:.2s;box-sizing:border-box; }
    .form-control:focus { outline:none;border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.12); }
    .pass-type-grid { display:grid;grid-template-columns:1fr 1fr;gap:10px; }
    .pass-type-card { cursor:pointer;position:relative; }
    .pass-type-card input[type="radio"] { position:absolute;opacity:0; }
    .ptc-inner { padding:14px;border:2px solid var(--border-color);border-radius:12px;text-align:center;transition:.2s;font-weight:600;font-size:13px; }
    .ptc-inner i { font-size:22px;margin-bottom:6px;display:block;color:var(--text-muted); }
    .ptc-inner small { font-size:11px;color:var(--text-muted);font-weight:400; }
    .pass-type-card input:checked ~ .ptc-inner { border-color:#6366f1;background:rgba(99,102,241,.08);color:#6366f1; }
    .pass-type-card input:checked ~ .ptc-inner i { color:#6366f1; }
    .annexure-doc-title { text-align:center;font-size:14px;font-weight:800;letter-spacing:.4px;margin-top:16px;color:var(--text-primary); }
    .annexure-doc-subtitle { text-align:center;font-size:11px;font-weight:700;margin:4px 0 10px;color:var(--text-muted); }
    .doc-upload-item { display:flex;align-items:center;gap:10px;padding:10px;border:1px solid var(--border-color);border-radius:10px;margin-bottom:8px;flex-wrap:wrap; }
    .doc-upload-info { display:flex;align-items:center;gap:10px;flex:1;min-width:0; }
    .doc-upload-info i { font-size:20px;flex:0 0 auto; }
    .doc-name { font-size:13px;font-weight:600;line-height:1.35; }
    .doc-serial { color:var(--text-muted);margin-right:3px; }
    .doc-hint { font-size:11px;color:var(--text-muted);margin-top:2px; }
    .doc-file-input { width:0.1px; height:0.1px; opacity:0; overflow:hidden; position:absolute; z-index:-1; }
    .doc-upload-btn { white-space:nowrap; }
    .doc-filename { font-size:11px;color:var(--text-muted);max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
    .doc-status { font-size:10px;margin-left:4px; }
    .empty-state { text-align:center;color:var(--text-muted); }
    </style>

    <script>
    // File input display
    document.querySelectorAll('.doc-file-input').forEach(input => {
      input.addEventListener('change', function() {
        const id = this.id.replace('doc-', '');
        const span = document.getElementById('fn-' + id);
        if (span && this.files[0]) {
          span.textContent = this.files[0].name;
          span.style.color = '#10b981';
        }
      });
    });

    // Auto set valid_to for temporary pass
    document.querySelectorAll('[name="pass_type"]').forEach(r => {
      r.addEventListener('change', function() {
        const from = document.querySelector('[name="valid_from"]').value;
        if (from && this.value === 'temporary') {
          const d = new Date(from);
          d.setDate(d.getDate() + 30);
          document.querySelector('[name="valid_to"]').value = d.toISOString().split('T')[0];
        }
      });
    });

    document.querySelector('[name="valid_from"]')?.addEventListener('change', function() {
      const passType = document.querySelector('[name="pass_type"]:checked')?.value;
      if (this.value && passType === 'temporary') {
        const d = new Date(this.value);
        d.setDate(d.getDate() + 30);
        document.querySelector('[name="valid_to"]').value = d.toISOString().split('T')[0];
      }
    });

    function showToast(msg, type='success') {
      let t = document.createElement('div');
      t.className='toast-msg toast-'+type;
      t.innerHTML=`<i class="fas fa-${type==='success'?'check-circle':'exclamation-circle'}"></i> ${msg}`;
      document.body.appendChild(t);
      setTimeout(()=>t.remove(),3500);
    }

    document.getElementById('gatePassForm')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = document.getElementById('submitGPBtn');
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

      const form = e.target;
      const formData = new FormData(form);

      try {
        const res = await fetch('../../api/save_gate_pass_request.php', {
          method: 'POST',
          body: formData   // multipart for file uploads
        });
        const result = await res.json();
        if (result.success) {
          showToast('Gate pass request submitted successfully!', 'success');
          setTimeout(() => location.reload(), 1800);
        } else {
          showToast('Error: ' + (result.message || 'Submission failed'), 'error');
        }
      } catch(err) {
        showToast('Network error — please try again.', 'error');
      }

      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Gate Pass Request';
    });
    </script>
    <style>
    .toast-msg { position:fixed;bottom:30px;right:30px;z-index:9999;padding:14px 20px;border-radius:12px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:600;animation:slideUp .3s ease;box-shadow:0 8px 30px rgba(0,0,0,.2); }
    .toast-success { background:#10b981;color:white; }
    .toast-error   { background:#ef4444;color:white; }
    @keyframes slideUp { from{transform:translateY(30px);opacity:0;}to{transform:translateY(0);opacity:1;} }
    </style>
    <?php
}

renderLayout("Gate Pass Application (6A)", 'renderContent', $role, $name);
