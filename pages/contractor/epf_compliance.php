<?php
require_once '../../include/auth.php';
checkAuth(['contractor', 'customer', 'welfare_user', 'welfare_admin', 'welfare', 'admin', 'super_admin']);
include '../../include/config.php';
include '../../include/customer_portal_context.php';
include '../../include/layout.php';
include '../../include/compliance_schema.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';
$user_id = $_SESSION['user_id'];
clms_get_portal_contractor($conn);

function renderContent() {
    global $conn, $user_id, $role;

    ensureComplianceSchema($conn);

    $contractorList = [];
    if ($role === 'contractor' || $role === 'customer') {
        $contractor = db_single($conn, "SELECT id, contractor_name FROM contractors WHERE user_id = ?", 'i', [$user_id]);
        $c_id = $contractor['id'] ?? null;
    } else {
        $c_id = isset($_GET['contractor_id']) ? intval($_GET['contractor_id']) : null;
        $contractorList = db_fetch_all($conn, "SELECT id, vendor_code, contractor_name FROM contractors WHERE status = 'approved' ORDER BY contractor_name ASC");
    }

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

    <?php if (!empty($contractorList)): ?>
    <div class="card glass" style="margin-bottom:20px;">
      <div class="card-body" style="padding:16px;">
        <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;">
          <div class="form-group" style="margin:0;min-width:260px;">
            <label class="form-label" style="font-size:11px;">Select Contractor</label>
            <select class="form-control" onchange="window.location.href='?contractor_id='+this.value+'&month='+document.getElementById('monthFilter')?.value">
              <option value="">-- Select Contractor --</option>
              <?php foreach($contractorList as $cl): ?>
                <option value="<?= $cl['id'] ?>" <?= $c_id == $cl['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cl['contractor_name']) ?> (<?= htmlspecialchars($cl['vendor_code']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!$c_id && ($role === 'contractor' || $role === 'customer')): ?>
    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i><div>Complete contractor registration first.</div></div>
    <?php return; endif; ?>

    <?php if (!$c_id): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle"></i><div>Please select a contractor to view EPF compliance records.</div></div>
    <?php return; endif; ?>



    <div style="margin-top: 20px;">
        <div class="card glass">
            <div class="card-header"><div class="card-title">EPF compliance</div></div>
            <div class="card-body">
                <form id="epfForm" enctype="multipart/form-data">
                    <input type="hidden" name="contribution_month" id="hidden_contribution_month" value="<?= htmlspecialchars($selectedMonth) ?>">
                    <div style="display:flex; gap:16px; align-items:center; margin-bottom: 20px;">
                        <label class="form-label" style="margin:0; font-weight:bold;">Select wage month</label>
                        <select class="form-control" id="contributionMonth_m" style="width:120px;" required>
                            <?php 
                            $months = ['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Aug','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dec'];
                            $cm = explode('-', $selectedMonth)[1] ?? date('m');
                            foreach($months as $k=>$v) echo "<option value='$k' ".($cm==$k?'selected':'').">$v</option>";
                            ?>
                        </select>
                        <select class="form-control" id="contributionMonth_y" style="width:100px;" required>
                            <?php
                            $cy = explode('-', $selectedMonth)[0] ?? date('Y');
                            for($i=2020; $i<=2030; $i++) echo "<option value='$i' ".($cy==$i?'selected':'').">$i</option>";
                            ?>
                        </select>
                    </div>

                    <table class="data-table" style="margin-bottom:30px;">
                        <thead>
                            <tr>
                                <th>SL NO</th>
                                <th>Wage Month</th>
                                <th style="text-align:center;">Download</th>
                                <th style="text-align:center;">Download text</th>
                                <th style="text-align:center;">Upload ECR</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1.</td>
                                <td><span id="display_wage_month"><?= date('M Y', strtotime($selectedMonth.'-01')) ?></span></td>
                                <td style="text-align:center;">
                                    <button type="button" class="btn btn-outline" style="border:1px solid #94a3b8; color:#334155; padding:6px 12px;" onclick="downloadTemplate('excel')">Download in Excel</button>
                                </td>
                                <td style="text-align:center;">
                                    <button type="button" class="btn btn-outline" style="border:1px solid #94a3b8; color:#334155; padding:6px 12px;" onclick="downloadTemplate('text')">Download Text</button>
                                </td>
                                <td style="text-align:center;">
                                    <input type="file" name="ecr_file" accept=".txt,.csv" style="display:none;" id="ecr_file_input" onchange="document.getElementById('epfForm').dispatchEvent(new Event('submit'))">
                                    <button type="button" class="btn btn-outline" style="border:1px solid #94a3b8; color:#334155; padding:6px 12px;" onclick="document.getElementById('ecr_file_input').click()">Upload ECR</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </form>

                <div style="display:flex; justify-content:flex-end; margin-bottom:10px;">
                    <button class="btn btn-outline" style="border-radius:20px; border:1px solid #94a3b8;" onclick="document.getElementById('ecr_file_input').click()"><i class="fas fa-plus"></i> Add anytime to upload</button>
                </div>
                
                <div style="font-weight:600; margin-bottom:10px;">History</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>SL NO</th>
                            <th>Wage month</th>
                            <th style="text-align:center;">Download</th>
                            <th style="text-align:center;">Upload</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($history)): ?>
                            <tr><td colspan="4" style="text-align:center;">No history found.</td></tr>
                        <?php else: ?>
                            <?php $idx=1; foreach($history as $h): ?>
                                <tr>
                                    <td><?= $idx++ ?></td>
                                    <td><?= date('M Y', strtotime($h['month_year'].'-01')) ?></td>
                                    <td style="text-align:center;">
                                        <button class="btn btn-outline" style="padding:4px 8px; font-size:12px; border:1px solid #cbd5e1; margin-right:4px;">Download in Excel</button>
                                        <button class="btn btn-outline" style="padding:4px 8px; font-size:12px; border:1px solid #cbd5e1;">Download Text</button>
                                    </td>
                                    <td style="text-align:center;">
                                        <button class="btn btn-outline" style="padding:4px 8px; font-size:12px; border:1px solid #cbd5e1;">Upload ECR after downloading from EPF site</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php if ($activeRecord && $activeRecord['validation_status'] === 'mismatch'): ?>
        <div class="card glass" style="margin-top: 20px; border-color: #f87171;">
          <div class="card-header" style="background: rgba(239, 68, 68, 0.08); display:flex; justify-content:space-between; align-items:center;">
              <div class="card-title" style="color: #ef4444; margin:0;"><i class="fas fa-exclamation-circle"></i> Report</div>
              <button class="btn btn-outline" style="border:1px solid #f87171; color:#ef4444; padding:4px 10px;" onclick="window.location.href='../../api/contractor/download_epf_report.php?compliance_id=<?= $activeRecord['id'] ?>'"><i class="fas fa-file-excel"></i> Export to Excel</button>
          </div>
          <div class="card-body">
            <div style="font-size: 13px; color: #ef4444; white-space: pre-line; line-height: 1.6;">
              <?= htmlspecialchars($activeRecord['validation_errors']) ?>
            </div>
          </div>
        </div>
        <?php endif; ?>
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
      const m = document.getElementById('contributionMonth_m').value;
      const y = document.getElementById('contributionMonth_y').value;
      const month = y + '-' + m;
      if (!m || !y) {
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

    const updateDisplayMonth = () => {
      const m = document.getElementById('contributionMonth_m').value;
      const y = document.getElementById('contributionMonth_y').value;
      if(m && y) {
          const url = new URL(window.location.href);
          url.searchParams.set('month', y + '-' + m);
          window.location.href = url.toString();
      }
    };
    document.getElementById('contributionMonth_m').addEventListener('change', updateDisplayMonth);
    document.getElementById('contributionMonth_y').addEventListener('change', updateDisplayMonth);

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
            const m = document.getElementById('contributionMonth_m').value;
            const y = document.getElementById('contributionMonth_y').value;
            url.searchParams.set('month', y + '-' + m);
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
