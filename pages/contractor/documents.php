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

    $contractor = db_single($conn, "SELECT id, contractor_name FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    $c_id = $contractor['id'] ?? null;

    // Fetch uploaded documents
    $docs = $c_id ? db_fetch_all($conn,
        "SELECT * FROM contractor_documents WHERE contractor_id = ? ORDER BY uploaded_at DESC",
        'i', [$c_id]) : [];

    $required_docs = [
        ['key'=>'insurance_policy',     'label'=>'Insurance Policy',          'icon'=>'fa-umbrella',    'color'=>'#3b82f6', 'required'=>true,  'desc'=>'Workmen Compensation Insurance Policy'],
        ['key'=>'cla_license',          'label'=>'CLA License (>20 workers)', 'icon'=>'fa-certificate', 'color'=>'#f59e0b', 'required'=>true,  'desc'=>'Contract Labour Regulation Act License'],
        ['key'=>'workmen_compensation',  'label'=>'Workmen Compensation Cert', 'icon'=>'fa-file-shield', 'color'=>'#ef4444', 'required'=>true,  'desc'=>'Valid WC Certificate covering all workers'],
        ['key'=>'pan_card',             'label'=>'Company PAN Card',          'icon'=>'fa-id-card',     'color'=>'#10b981', 'required'=>true,  'desc'=>'Scanned copy of PAN card'],
        ['key'=>'gst_certificate',      'label'=>'GST Certificate',           'icon'=>'fa-receipt',     'color'=>'#8b5cf6', 'required'=>true,  'desc'=>'GST registration certificate'],
        ['key'=>'mou_agreement',        'label'=>'MOU / Work Order',          'icon'=>'fa-file-contract','color'=>'#6366f1', 'required'=>false, 'desc'=>'Signed work order or MOU with FACT'],
        ['key'=>'bank_statement',       'label'=>'Bank Statement',            'icon'=>'fa-university',  'color'=>'#14b8a6', 'required'=>false, 'desc'=>'Last 3 months bank statement'],
    ];

    // Map uploaded docs by key
    $uploaded = [];
    foreach ($docs as $d) { $uploaded[$d['doc_type']] = $d; }
    ?>

    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-folder-open" style="color:#f59e0b;margin-right:10px;"></i> Document Upload Centre</h2>
        <!-- <p class="page-subtitle">Upload all mandatory documents required for compliance and approval.</p> -->
      </div>
    </div>

    <?php if (!$c_id): ?>
    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i><div>Complete contractor registration first.</div></div>
    <?php return; endif; ?>

    <!-- Upload Progress -->
    <?php
    $req_count  = count(array_filter($required_docs, function($d) { return $d['required']; }));
    $uploaded_req = count(array_filter($required_docs, function($d) use ($uploaded) { return $d['required'] && isset($uploaded[$d['key']]); }));
    $pct = $req_count > 0 ? round(($uploaded_req / $req_count) * 100) : 0;
    ?>
    <div class="card glass" style="margin-bottom:20px;padding:20px;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
        <div style="font-weight:700;">Document Completion: <?= $uploaded_req ?>/<?= $req_count ?> required docs</div>
        <span class="badge <?= $pct === 100 ? 'badge-success' : 'badge-warning' ?>"><?= $pct ?>% Complete</span>
      </div>
      <div class="progress-bar-bg">
        <div class="progress-bar-fill" style="width:<?= $pct ?>%;background:<?= $pct===100 ? '#10b981' : '#f59e0b' ?>;"></div>
      </div>
    </div>

    <!-- Document Cards Grid -->
    <div class="doc-cards-grid">
    <?php foreach ($required_docs as $doc):
      $up = $uploaded[$doc['key']] ?? null;
    ?>
    <div class="doc-card glass <?= $up ? 'uploaded' : '' ?>">
      <div class="doc-card-icon" style="background:<?= $doc['color'] ?>18;color:<?= $doc['color'] ?>;">
        <i class="fas <?= $doc['icon'] ?>"></i>
      </div>
      <div class="doc-card-info">
        <div class="doc-card-title">
          <?= htmlspecialchars($doc['label']) ?>
          <?php if ($doc['required']): ?><span class="badge badge-danger" style="font-size:9px;margin-left:6px;">Required</span><?php endif; ?>
        </div>
        <div class="doc-card-desc"><?= htmlspecialchars($doc['desc']) ?></div>
        <?php if ($up): ?>
        <div class="doc-uploaded-info">
          <i class="fas fa-check-circle" style="color:#10b981;"></i>
          Uploaded: <?= date('d M Y', strtotime($up['uploaded_at'])) ?>
          <span class="badge <?= ($up['status']??'pending')==='verified' ? 'badge-success' : 'badge-warning' ?>" style="font-size:10px;margin-left:6px;">
            <?= strtoupper($up['status'] ?? 'pending') ?>
          </span>
        </div>
        <?php else: ?>
        <div class="doc-missing-info">
          <i class="fas fa-exclamation-circle" style="color:#f59e0b;"></i> Not yet uploaded
        </div>
        <?php endif; ?>
      </div>
      <div class="doc-card-actions">
        <?php if ($up): ?>
        <a href="<?= htmlspecialchars($up['file_path'] ?? '#') ?>" target="_blank" class="btn btn-sm btn-outline" title="View">
          <i class="fas fa-eye"></i>
        </a>
        <?php endif; ?>
        <button class="btn btn-sm btn-primary" onclick="openUpload('<?= $doc['key'] ?>', '<?= addslashes($doc['label']) ?>')" title="<?= $up ? 'Re-upload' : 'Upload' ?>">
          <i class="fas fa-upload"></i> <?= $up ? 'Re-upload' : 'Upload' ?>
        </button>
      </div>
    </div>
    <?php endforeach; ?>
    </div>

    <!-- Upload History -->
    <div class="card glass" style="margin-top:24px;">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-history"></i> Upload History</div>
      </div>
      <div class="card-body" style="padding:0;">
        <?php if (empty($docs)): ?>
        <div style="text-align:center;padding:30px;color:var(--text-muted);font-size:13px;">No documents uploaded yet.</div>
        <?php else: ?>
        <table class="data-table">
          <thead><tr><th>Document Type</th><th>Filename</th><th>Uploaded</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
          <?php foreach ($docs as $d): ?>
          <tr>
            <td style="font-weight:600;"><?= htmlspecialchars($d['doc_type']) ?></td>
            <td style="font-size:12px;"><?= htmlspecialchars($d['original_name'] ?? '—') ?></td>
            <td style="font-size:12px;"><?= date('d M Y, H:i', strtotime($d['uploaded_at'])) ?></td>
            <td><span class="badge <?= ($d['status']??'pending')==='verified' ? 'badge-success' : 'badge-warning' ?>"><?= strtoupper($d['status'] ?? 'pending') ?></span></td>
            <td><a href="<?= htmlspecialchars($d['file_path'] ?? '#') ?>" target="_blank" class="btn btn-sm btn-outline"><i class="fas fa-download"></i></a></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>

    <!-- Upload Modal -->
    <div id="uploadModal" class="modal-overlay hidden">
      <div class="modal-box" style="max-width:500px;">
        <div class="modal-header">
          <h3 class="modal-title"><i class="fas fa-upload"></i> Upload Document</h3>
          <button class="modal-close" onclick="closeUploadModal()">&times;</button>
        </div>
        <div style="padding:24px; max-height:80vh; overflow-y:auto;">
          <p id="uploadDocLabel" style="font-weight:700;margin-bottom:16px;color:#6366f1;"></p>
          
          <div id="claAlert" class="alert alert-warning" style="display:none; font-size:12px; margin-bottom:15px;">
            <i class="fas fa-info-circle"></i> If you engage 20 or more workmen on any given day, CLRA License is mandatory. The license validity will automatically be set to 1 year from the Issue Date.
          </div>

          <form id="uploadForm" enctype="multipart/form-data">
            <input type="hidden" name="doc_type" id="docTypeInput">
            <div class="form-group">
              <label class="form-label required">Select File</label>
              <input type="file" class="form-control" name="doc_file" accept=".pdf,.jpg,.jpeg,.png" required>
              <small class="form-hint">PDF, JPG or PNG — max 5MB</small>
            </div>
            <div class="form-group">
              <label class="form-label">Remarks</label>
              <input type="text" class="form-control" name="remarks" placeholder="e.g. Policy no., extra details...">
            </div>

            <!-- Specific fields for Workmen Compensation -->
            <div id="wcFields" style="display:none; background:#f8fafc; padding:15px; border-radius:8px; border:1px solid #e2e8f0; margin-bottom:15px;">
                <h5 style="margin-top:0; color:#334155; font-size:13px; margin-bottom:10px;">Policy Details (Required)</h5>
                <div style="display:flex; gap:10px;">
                    <div class="form-group" style="flex:1;">
                        <label class="form-label required">Valid From</label>
                        <input type="date" class="form-control" name="wc_valid_from" id="wc_valid_from">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label required">Valid To</label>
                        <input type="date" class="form-control" name="wc_valid_to" id="wc_valid_to">
                    </div>
                </div>
                <div style="display:flex; gap:10px;">
                    <div class="form-group" style="flex:1;">
                        <label class="form-label required">No. of Workers</label>
                        <input type="number" class="form-control" name="wc_workers" id="wc_workers" min="1">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label required">Rate of Wages (Rs)</label>
                        <input type="number" class="form-control" name="wc_wages" id="wc_wages" step="0.01" min="0">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label required">Does daily wage exceed ESIC coverage limit?</label>
                    <select name="wc_esic_override" class="form-control">
                        <option value="No">No</option>
                        <option value="Yes">Yes</option>
                    </select>
                </div>
            </div>

            <!-- Specific fields for CLA License -->
            <div id="claFields" style="display:none; background:#f8fafc; padding:15px; border-radius:8px; border:1px solid #e2e8f0; margin-bottom:15px;">
                <h5 style="margin-top:0; color:#334155; font-size:13px; margin-bottom:10px;">CLRA License Details</h5>
                <div class="form-group">
                    <label class="form-label required">License Number</label>
                    <input type="text" class="form-control" name="cla_license_no" id="cla_license_no">
                </div>
                <div class="form-group">
                    <label class="form-label required">Date of Issue</label>
                    <input type="date" class="form-control" name="cla_issue_date" id="cla_issue_date">
                </div>
                <div class="form-group">
                    <label class="form-label">Expiry Date</label>
                    <input type="text" class="form-control" id="cla_expiry_display" disabled placeholder="Automatically calculated (1 Year)">
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;margin-top:8px;" id="uploadBtn">
              <i class="fas fa-upload"></i> Upload Document
            </button>
          </form>
        </div>
      </div>
    </div>

    <style>
    .progress-bar-bg { height:8px;background:rgba(148,163,184,.15);border-radius:999px;overflow:hidden; }
    .progress-bar-fill { height:100%;border-radius:999px;transition:width .5s ease; }
    .doc-cards-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px; }
    .doc-card { border-radius:16px;padding:18px;display:flex;align-items:flex-start;gap:14px;border:1.5px solid var(--border-color);transition:.2s; }
    .doc-card:hover { border-color:#6366f1;transform:translateY(-2px); }
    .doc-card.uploaded { border-color:rgba(16,185,129,.4);background:rgba(16,185,129,.04); }
    .doc-card-icon { width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0; }
    .doc-card-info { flex:1;min-width:0; }
    .doc-card-title { font-size:14px;font-weight:700;margin-bottom:4px; }
    .doc-card-desc { font-size:12px;color:var(--text-muted);margin-bottom:8px; }
    .doc-uploaded-info, .doc-missing-info { font-size:12px;display:flex;align-items:center;gap:5px; }
    .doc-card-actions { display:flex;gap:6px;align-items:center;flex-shrink:0; }
    .form-group { margin-bottom:14px; }
    .form-label { display:block;font-size:13px;font-weight:600;margin-bottom:5px; }
    .form-label.required::after { content:' *';color:#ef4444; }
    .form-control { width:100%;padding:9px 13px;border-radius:8px;border:1.5px solid var(--border-color);background:var(--input-bg,rgba(255,255,255,.04));color:var(--text-primary);font-size:13px;box-sizing:border-box; }
    .form-hint { font-size:11px;color:var(--text-muted);margin-top:3px;display:block; }
    .modal-overlay { position:fixed;inset:0;background:rgba(0,0,0,.6);backdrop-filter:blur(4px);z-index:1000;display:flex;align-items:center;justify-content:center; }
    .modal-overlay.hidden { display:none; }
    .modal-box { background:white;border:1px solid var(--border-color);border-radius:20px;width:90%;animation:modalIn .25s ease; }
    @keyframes modalIn { from{transform:scale(.95);opacity:0;}to{transform:scale(1);opacity:1;} }
    .modal-header { display:flex;align-items:center;justify-content:space-between;padding:20px;border-bottom:1px solid var(--border-color); }
    .modal-title { font-size:16px;font-weight:700;margin:0; }
    .modal-close { background:none;border:none;font-size:22px;cursor:pointer;color:var(--text-muted); }
    .toast-msg { position:fixed;bottom:30px;right:30px;z-index:9999;padding:14px 20px;border-radius:12px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:600;animation:slideUp .3s ease;box-shadow:0 8px 30px rgba(0,0,0,.2); }
    .toast-success{background:#10b981;color:white;} .toast-error{background:#ef4444;color:white;}
    @keyframes slideUp { from{transform:translateY(30px);opacity:0;}to{transform:translateY(0);opacity:1;} }
    </style>
    <script>
    function openUpload(key, label) {
      document.getElementById('uploadDocLabel').textContent = label;
      document.getElementById('docTypeInput').value = key;
      document.getElementById('uploadForm').reset();
      
      const wcFields = document.getElementById('wcFields');
      const claFields = document.getElementById('claFields');
      const claAlert = document.getElementById('claAlert');
      
      wcFields.style.display = 'none';
      claFields.style.display = 'none';
      claAlert.style.display = 'none';
      
      // Make inputs unrequired by default
      document.querySelectorAll('#wcFields input').forEach(i => i.required = false);
      document.querySelectorAll('#claFields input').forEach(i => i.required = false);

      if (key === 'workmen_compensation' || key === 'insurance_policy') {
          wcFields.style.display = 'block';
          document.getElementById('wc_valid_from').required = true;
          document.getElementById('wc_valid_to').required = true;
          document.getElementById('wc_workers').required = true;
          document.getElementById('wc_wages').required = true;
      } else if (key === 'cla_license') {
          claFields.style.display = 'block';
          claAlert.style.display = 'block';
          document.getElementById('cla_license_no').required = true;
          document.getElementById('cla_issue_date').required = true;
      }

      document.getElementById('uploadModal').classList.remove('hidden');
    }

    document.getElementById('cla_issue_date')?.addEventListener('change', function(e) {
        if(this.value) {
            let d = new Date(this.value);
            d.setFullYear(d.getFullYear() + 1);
            d.setDate(d.getDate() - 1); // Expiry is 1 day before exactly 1 year
            document.getElementById('cla_expiry_display').value = d.toISOString().split('T')[0];
        } else {
            document.getElementById('cla_expiry_display').value = '';
        }
    });

    function closeUploadModal() { document.getElementById('uploadModal').classList.add('hidden'); }

    function showToast(msg, type='success') {
      let t = document.createElement('div');
      t.className='toast-msg toast-'+type;
      t.innerHTML=`<i class="fas fa-${type==='success'?'check-circle':'exclamation-circle'}"></i> ${msg}`;
      document.body.appendChild(t); setTimeout(()=>t.remove(),3500);
    }

    document.getElementById('uploadForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = document.getElementById('uploadBtn');
      btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
      const fd = new FormData(e.target);
      try {
        const res = await fetch('../../api/contractor/upload_document.php', { method:'POST', body:fd });
        const result = await res.json();
        if (result.success) {
          showToast('Document uploaded successfully!', 'success');
          closeUploadModal();
          setTimeout(() => location.reload(), 1500);
        } else {
          showToast('Error: '+(result.message||'Upload failed'), 'error');
        }
      } catch(err) { showToast('Network error.','error'); }
      btn.disabled = false; btn.innerHTML = '<i class="fas fa-upload"></i> Upload Document';
    });
    </script>
    <?php
}

renderLayout("Document Upload Centre", 'renderContent', $role, $name);
