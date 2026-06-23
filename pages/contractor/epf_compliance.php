<?php
require_once '../../include/auth.php';
checkAuth(['contractor', 'customer']);
include '../../include/config.php';
include '../../include/customer_portal_context.php';
include '../../include/layout.php';
include '../../include/compliance_schema.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';
$user_id = $_SESSION['user_id'];
clms_get_portal_contractor($conn);

function renderContent() {
    global $conn, $user_id;

    $contractor = db_single($conn, "SELECT id, contractor_name FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    $c_id = $contractor['id'] ?? null;
    ensureComplianceSchema($conn);

    $selectedMonth = $_GET['month'] ?? date('Y-m', strtotime('-1 month'));

    $history = $c_id ? db_fetch_all($conn, "SELECT * FROM compliance WHERE contractor_id = ? AND type = 'epf' ORDER BY uploaded_at DESC", 'i', [$c_id]) : [];
    
    // Find active record for current selected month to show status
    $activeRecord = null;
    foreach ($history as $h) {
        if ($h['month_year'] === $selectedMonth) {
            $activeRecord = $h;
            break;
        }
    }
    ?>

    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-file-invoice-dollar" style="color:#3b82f6;margin-right:10px;"></i> EPF Statutory Compliance</h2>
        <div style="font-size:12px;color:#64748b;margin-top:4px">Download pre-filled EPFO ECR templates, upload returned ECR statements, and check compliance.</div>
      </div>
    </div>

    <?php if (!$c_id): ?>
    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i><div>Complete contractor registration first.</div></div>
    <?php return; endif; ?>

    <div style="display:grid;grid-template-columns:1fr 380px;gap:20px;align-items:start;">

      <!-- Main Action Card -->
      <div>
        <div class="card glass">
          <div class="card-header"><div class="card-title"><i class="fas fa-file-excel"></i> Prepare & Upload ECR</div></div>
          <div class="card-body">
            <div class="alert alert-info" style="margin-bottom:16px;">
              <i class="fas fa-info-circle"></i>
              <div>
                <strong>Workflow:</strong><br>
                1. Select the wage month and download the pre-filled Excel or Text template.<br>
                2. Upload this template to the EPFO portal to file your return.<br>
                3. Download the final returned ECR statement (.txt) from EPFO and upload it here.
              </div>
            </div>

            <form id="epfForm" enctype="multipart/form-data">
              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label required">Wage Month</label>
                  <input type="month" class="form-control" name="contribution_month" id="contributionMonth" required value="<?= htmlspecialchars($selectedMonth) ?>">
                </div>

                <div class="form-group" style="display: flex; gap: 8px; align-items: flex-end;">
                  <button type="button" class="btn btn-outline" onclick="downloadTemplate('excel')" style="flex:1;">
                    <i class="fas fa-file-csv" style="color:#10b981;"></i> Prefill Excel
                  </button>
                  <button type="button" class="btn btn-outline" onclick="downloadTemplate('text')" style="flex:1;">
                    <i class="fas fa-file-alt" style="color:#2563eb;"></i> Prefill Text (#~#)
                  </button>
                </div>

                <div class="form-group span-2" style="margin-top: 14px;">
                  <label class="form-label required">Upload Official EPFO ECR File (.txt / .csv)</label>
                  <input type="file" class="form-control" name="ecr_file" accept=".txt,.csv" required>
                  <small class="form-hint">Submit the official .txt file with #~# separators downloaded from the EPF site.</small>
                </div>
              </div>

              <button type="submit" class="btn btn-primary" style="margin-top:16px;" id="epfBtn">
                <i class="fas fa-upload"></i> Submit ECR & Verify Compliance
              </button>
            </form>
          </div>
        </div>

        <!-- Mismatch details if active record has mismatch -->
        <?php if ($activeRecord && $activeRecord['validation_status'] === 'mismatch'): ?>
        <div class="card glass" style="margin-top: 20px; border-color: #f87171;">
          <div class="card-header" style="background: rgba(239, 68, 68, 0.08);"><div class="card-title" style="color: #ef4444;"><i class="fas fa-exclamation-circle"></i> ECR Compliance Mismatch Report - <?= htmlspecialchars($selectedMonth) ?></div></div>
          <div class="card-body">
            <div style="font-size: 13px; color: #ef4444; white-space: pre-line; line-height: 1.6;">
              <?= htmlspecialchars($activeRecord['validation_errors']) ?>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Compliance History & Status Sidebar -->
      <div>
        <div class="card glass">
          <div class="card-header"><div class="card-title"><i class="fas fa-history"></i> ECR Submission History</div></div>
          <div class="card-body" style="padding:0;">
            <?php if ($history): ?>
            <table class="data-table" style="width:100%;">
              <thead>
                <tr>
                  <th>Month</th>
                  <th>Workers</th>
                  <th>EPF Wages</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($history as $row): ?>
                <?php
                  $status = $row['status'] ?? 'pending';
                  $valStatus = $row['validation_status'] ?? 'pending';
                  $valBadge = $valStatus === 'passed' ? 'badge-success' : ($valStatus === 'mismatch' ? 'badge-danger' : 'badge-warning');
                  $monthText = date('M Y', strtotime($row['month_year'] . '-01'));
                ?>
                <tr>
                  <td><strong><?= htmlspecialchars($monthText) ?></strong></td>
                  <td><?= (int)$row['challan_worker_count'] ?></td>
                  <td>₹<?= number_format((float)$row['wage_total'], 2) ?></td>
                  <td>
                    <span class="badge <?= $valBadge ?>"><?= strtoupper($valStatus) ?></span>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <?php else: ?>
            <div style="text-align:center;padding:30px 0;color:var(--text-muted);">
              <i class="fas fa-history" style="font-size:32px;opacity:.15;display:block;margin-bottom:8px;"></i>
              <p>No EPF compliance records submitted.</p>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>

    <style>
    .form-grid { display:grid;grid-template-columns:1fr 1fr;gap:14px; }
    .form-group { margin-bottom:0; }
    .span-2 { grid-column:span 2; }
    .form-label { display:block;font-size:13px;font-weight:600;margin-bottom:5px; }
    .form-label.required::after { content:' *';color:#ef4444; }
    .form-control { width:100%;padding:9px 13px;border-radius:8px;border:1.5px solid var(--border-color);background:var(--input-bg,rgba(255,255,255,.04));color:var(--text-primary);font-size:13px;transition:.2s;box-sizing:border-box; }
    .form-control:focus { outline:none;border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.12); }
    .form-hint { font-size:11px;color:var(--text-muted);margin-top:3px;display:block; }
    .toast-msg { position:fixed;bottom:30px;right:30px;z-index:9999;padding:14px 20px;border-radius:12px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:600;animation:slideUp .3s ease;box-shadow:0 8px 30px rgba(0,0,0,.2); }
    .toast-success { background:#10b981;color:white; }
    .toast-error { background:#ef4444;color:white; }
    @keyframes slideUp { from{transform:translateY(30px);opacity:0;}to{transform:translateY(0);opacity:1;} }
    </style>

    <script>
    function downloadTemplate(format) {
      const month = document.getElementById('contributionMonth').value;
      if (!month) {
        showToast('Please select a wage month first.', 'error');
        return;
      }
      window.location.href = `../../api/contractor/download_epf_ecr.php?format=${format}&month=${month}`;
    }

    function showToast(msg, type='success') {
      let t = document.createElement('div');
      t.className='toast-msg toast-'+type;
      t.innerHTML=`<i class="fas fa-${type==='success'?'check-circle':'exclamation-circle'}"></i> ${msg}`;
      document.body.appendChild(t); setTimeout(()=>t.remove(),3500);
    }

    document.getElementById('contributionMonth').addEventListener('change', (e) => {
      const url = new URL(window.location.href);
      url.searchParams.set('month', e.target.value);
      window.location.href = url.toString();
    });

    document.getElementById('epfForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = document.getElementById('epfBtn');
      const originalText = btn.innerHTML;
      btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
      
      const fd = new FormData(e.target);
      try {
        const res = await fetch('../../api/contractor/save_epf_compliance.php', { method:'POST', body:fd });
        const result = await res.json();
        if (result.success) {
          showToast('EPF compliance ECR submitted and verified successfully!', 'success');
          setTimeout(() => {
            const url = new URL(window.location.href);
            url.searchParams.set('month', document.getElementById('contributionMonth').value);
            window.location.href = url.toString();
          }, 1800);
        } else {
          showToast('Error: ' + (result.message || 'Verification Failed'), 'error');
        }
      } catch(err) {
        showToast('Network error.', 'error');
      }
      btn.disabled = false;
      btn.innerHTML = originalText;
    });
    </script>
    <?php
}

renderLayout("EPF Compliance Monitor", 'renderContent', $role, $name);
