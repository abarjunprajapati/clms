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
    $active_tab = $_GET['tab'] ?? 'esi';
    ensureComplianceSchema($conn);
    $history = $c_id ? db_fetch_all($conn, "SELECT * FROM compliance WHERE contractor_id = ? ORDER BY uploaded_at DESC", 'i', [$c_id]) : [];
    $latestByType = ['ESI' => null, 'PF' => null, 'KLWF' => null];
    foreach ($history as $h) {
        if (!$latestByType['ESI'] && (float)$h['esi_amount'] > 0) $latestByType['ESI'] = $h;
        if (!$latestByType['PF'] && (float)$h['pf_amount'] > 0) $latestByType['PF'] = $h;
        if (!$latestByType['KLWF'] && (float)$h['klwf_amount'] > 0) $latestByType['KLWF'] = $h;
    }
    ?>

    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-shield-check" style="color:#10b981;margin-right:10px;"></i> Statutory Compliance</h2>
        <!-- <p class="page-subtitle">Submit ESI, PF, and KLWF challans. Attendance-based validation is applied automatically.</p> -->
      </div>
    </div>

    <?php if (!$c_id): ?>
    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i><div>Complete contractor registration first.</div></div>
    <?php return; endif; ?>

    <!-- Tabs -->
    <div class="compliance-tabs">
      <a href="?tab=esi"  class="comp-tab <?= $active_tab==='esi'  ? 'active' : '' ?>"><i class="fas fa-hospital"></i> ESI Contribution</a>
      <a href="?tab=pf"   class="comp-tab <?= $active_tab==='pf'   ? 'active' : '' ?>"><i class="fas fa-piggy-bank"></i> PF Contribution</a>
      <a href="?tab=klwf" class="comp-tab <?= $active_tab==='klwf' ? 'active' : '' ?>"><i class="fas fa-balance-scale"></i> KLWF</a>
      <a href="?tab=history" class="comp-tab <?= $active_tab==='history' ? 'active' : '' ?>"><i class="fas fa-history"></i> Submission History</a>
    </div>

    <div style="display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start;">

      <!-- Main Content -->
      <div>

        <?php if ($active_tab === 'esi'): ?>
        <div class="card glass">
          <div class="card-header"><div class="card-title"><i class="fas fa-hospital"></i> ESI Monthly Contribution</div></div>
          <div class="card-body">
            <div class="alert alert-info" style="margin-bottom:16px;">
              <i class="fas fa-info-circle"></i>
              <div>ESI contribution: Employer 3.25% + Employee 0.75% of gross wages. Due by 15th of following month.</div>
            </div>
            <form id="esiForm">
              <input type="hidden" name="type" value="esi">
              <div class="form-grid-2">
                <div class="form-group">
                  <label class="form-label required">Contribution Month</label>
                  <input type="month" class="form-control" name="contribution_month" required value="<?= date('Y-m', strtotime('-1 month')) ?>">
                </div>
                <div class="form-group">
                  <label class="form-label required">Challan Number</label>
                  <input type="text" class="form-control" name="challan_no" required placeholder="ESI Challan Reference No.">
                </div>
                <div class="form-group">
                  <label class="form-label required">Challan Date</label>
                  <input type="date" class="form-control" name="challan_date" required>
                </div>
                <div class="form-group">
                  <label class="form-label required">No. of Employees Covered</label>
                  <input type="number" class="form-control" name="employees_count" required min="1" placeholder="Total employees">
                </div>
                <div class="form-group">
                  <label class="form-label required">Total Gross Wages (₹)</label>
                  <input type="number" class="form-control" name="gross_wages" required step="0.01" placeholder="0.00" id="esiGross">
                </div>
                <div class="form-group">
                  <label class="form-label required">Employer Contribution (₹)</label>
                  <input type="number" class="form-control" name="employer_contribution" required step="0.01" id="esiEmployer" placeholder="Auto-calc: 3.25%">
                </div>
                <div class="form-group">
                  <label class="form-label required">Employee Contribution (₹)</label>
                  <input type="number" class="form-control" name="employee_contribution" required step="0.01" id="esiEmployee" placeholder="Auto-calc: 0.75%">
                </div>
                <div class="form-group">
                  <label class="form-label required">Total Contribution (₹)</label>
                  <input type="number" class="form-control" name="total_contribution" required step="0.01" id="esiTotal" placeholder="Total amount" style="font-weight:700;color:#10b981;">
                </div>
                <div class="form-group span-2">
                  <label class="form-label required">Upload Challan (PDF)</label>
                  <input type="file" class="form-control" name="challan_file" accept=".pdf" required>
                  <small class="form-hint">PDF format only, max 5MB</small>
                </div>
              </div>
              <button type="submit" class="btn btn-primary" style="margin-top:8px;" id="esiBtn">
                <i class="fas fa-upload"></i> Submit ESI Compliance
              </button>
            </form>
          </div>
        </div>

        <?php elseif ($active_tab === 'pf'): ?>
        <div class="card glass">
          <div class="card-header"><div class="card-title"><i class="fas fa-piggy-bank"></i> PF Monthly Contribution</div></div>
          <div class="card-body">
            <div class="alert alert-info" style="margin-bottom:16px;">
              <i class="fas fa-info-circle"></i>
              <div>PF contribution: Both employer and employee contribute 12% of basic wages. ECR upload required.</div>
            </div>
            <form id="pfForm">
              <input type="hidden" name="type" value="pf">
              <div class="form-grid-2">
                <div class="form-group">
                  <label class="form-label required">Contribution Month</label>
                  <input type="month" class="form-control" name="contribution_month" required value="<?= date('Y-m', strtotime('-1 month')) ?>">
                </div>
                <div class="form-group">
                  <label class="form-label required">Challan TRRN Number</label>
                  <input type="text" class="form-control" name="challan_no" required placeholder="EPFO TRRN Reference">
                </div>
                <div class="form-group">
                  <label class="form-label required">Challan Date</label>
                  <input type="date" class="form-control" name="challan_date" required>
                </div>
                <div class="form-group">
                  <label class="form-label required">No. of Members</label>
                  <input type="number" class="form-control" name="members_count" required min="1">
                </div>
                <div class="form-group">
                  <label class="form-label required">Total Wages (₹)</label>
                  <input type="number" class="form-control" name="total_wages" required step="0.01" id="pfWages">
                </div>
                <div class="form-group">
                  <label class="form-label required">EPF Contribution (₹)</label>
                  <input type="number" class="form-control" name="epf_contribution" required step="0.01" id="pfEPF" placeholder="12% of wages">
                </div>
                <div class="form-group">
                  <label class="form-label required">EPS Contribution (₹)</label>
                  <input type="number" class="form-control" name="eps_contribution" required step="0.01" id="pfEPS" placeholder="8.33% of wages">
                </div>
                <div class="form-group">
                  <label class="form-label required">Total PF Amount (₹)</label>
                  <input type="number" class="form-control" name="total_pf" required step="0.01" id="pfTotal" style="font-weight:700;color:#10b981;">
                </div>
                <div class="form-group span-2">
                  <label class="form-label required">Upload PF Challan (PDF)</label>
                  <input type="file" class="form-control" name="challan_file" accept=".pdf" required>
                </div>
                <div class="form-group span-2">
                  <label class="form-label">Upload ECR File (Excel/Text)</label>
                  <input type="file" class="form-control" name="ecr_file" accept=".xlsx,.xls,.txt,.csv">
                  <small class="form-hint">Electronic Challan cum Return from EPFO portal</small>
                </div>
              </div>
              <button type="submit" class="btn btn-primary" style="margin-top:8px;" id="pfBtn">
                <i class="fas fa-upload"></i> Submit PF Compliance
              </button>
            </form>
          </div>
        </div>

        <?php elseif ($active_tab === 'klwf'): ?>
        <div class="card glass">
          <div class="card-header"><div class="card-title"><i class="fas fa-balance-scale"></i> Kerala Labour Welfare Fund (KLWF)</div></div>
          <div class="card-body">
            <div class="alert alert-info" style="margin-bottom:16px;">
              <i class="fas fa-info-circle"></i>
              <div>KLWF is collected twice a year (June & December). Contribution: Employee ₹40 + Employer ₹160 per worker.</div>
            </div>
            <form id="klwfForm">
              <input type="hidden" name="type" value="klwf">
              <div class="form-grid-2">
                <div class="form-group">
                  <label class="form-label required">Period</label>
                  <select class="form-control" name="period" required>
                    <option value="January-June <?= date('Y') ?>">January–June <?= date('Y') ?></option>
                    <option value="July-December <?= date('Y') ?>">July–December <?= date('Y') ?></option>
                    <option value="January-June <?= date('Y')-1 ?>">January–June <?= date('Y')-1 ?></option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label required">Challan Number</label>
                  <input type="text" class="form-control" name="challan_no" required placeholder="KLWF challan reference">
                </div>
                <div class="form-group">
                  <label class="form-label required">Payment Date</label>
                  <input type="date" class="form-control" name="payment_date" required>
                </div>
                <div class="form-group">
                  <label class="form-label required">Number of Workers</label>
                  <input type="number" class="form-control" name="worker_count" required min="1" id="klwfCount">
                </div>
                <div class="form-group">
                  <label class="form-label required">Employee Contribution (₹)</label>
                  <input type="number" class="form-control" name="employee_contribution" required step="0.01" id="klwfEmployee" placeholder="₹40 × workers">
                </div>
                <div class="form-group">
                  <label class="form-label required">Employer Contribution (₹)</label>
                  <input type="number" class="form-control" name="employer_contribution" required step="0.01" id="klwfEmployer" placeholder="₹160 × workers">
                </div>
                <div class="form-group">
                  <label class="form-label required">Total KLWF Amount (₹)</label>
                  <input type="number" class="form-control" name="total_amount" required step="0.01" id="klwfTotal" style="font-weight:700;color:#10b981;">
                </div>
                <div class="form-group">
                  <label class="form-label required">Upload KLWF Challan</label>
                  <input type="file" class="form-control" name="challan_file" accept=".pdf" required>
                </div>
              </div>
              <button type="submit" class="btn btn-primary" style="margin-top:8px;" id="klwfBtn">
                <i class="fas fa-upload"></i> Submit KLWF Compliance
              </button>
            </form>
          </div>
        </div>

        <?php elseif ($active_tab === 'history'): ?>
        <div class="card glass">
          <div class="card-header"><div class="card-title"><i class="fas fa-history"></i> Submission History</div></div>
          <div class="card-body" style="padding:0;">
            <?php if ($history): ?>
            <table class="data-table">
              <thead>
                <tr>
                  <th>Month</th>
                  <th>ESI</th>
                  <th>PF</th>
                  <th>KLWF</th>
                  <th>Validation</th>
                  <th>Status</th>
                  <th>Remarks</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($history as $row): ?>
                <?php
                  $status = $row['status'] ?? 'pending';
                  $badge = ['verified' => 'badge-success', 'rejected' => 'badge-danger', 'pending' => 'badge-warning'][$status] ?? 'badge-gray';
                  $vBadge = ($row['validation_status'] ?? '') === 'passed' ? 'badge-success' : (($row['validation_status'] ?? '') === 'mismatch' ? 'badge-danger' : 'badge-warning');
                ?>
                <tr>
                  <td><?= htmlspecialchars($row['month_year'] ?: (($row['month'] ?? '') . ' ' . ($row['year'] ?? ''))) ?></td>
                  <td><?= (float)$row['esi_amount'] > 0 ? 'Rs ' . number_format((float)$row['esi_amount'], 2) : '-' ?></td>
                  <td><?= (float)$row['pf_amount'] > 0 ? 'Rs ' . number_format((float)$row['pf_amount'], 2) : '-' ?></td>
                  <td><?= (float)$row['klwf_amount'] > 0 ? 'Rs ' . number_format((float)$row['klwf_amount'], 2) : '-' ?></td>
                  <td>
                    <span class="badge <?= $vBadge ?>"><?= htmlspecialchars($row['validation_status'] ?? 'pending') ?></span>
                    <?php if (!empty($row['validation_errors'])): ?>
                      <div style="font-size:11px;color:var(--danger);white-space:pre-line;max-width:260px;"><?= htmlspecialchars($row['validation_errors']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td><span class="badge <?= $badge ?>"><?= strtoupper($status) ?></span></td>
                  <td><?= htmlspecialchars($row['remarks'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <?php else: ?>
            <div style="text-align:center;padding:40px 0;color:var(--text-muted);">
              <i class="fas fa-history" style="font-size:40px;opacity:.15;display:block;margin-bottom:12px;"></i>
              <p>No compliance records submitted yet.</p>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>

      </div>

      <!-- Compliance Status Sidebar -->
      <div>
        <div class="card glass">
          <div class="card-header"><div class="card-title"><i class="fas fa-chart-pie"></i> Compliance Status</div></div>
          <div class="card-body">
            <?php
            $items = [];
            foreach (['ESI' => '#10b981', 'PF' => '#3b82f6', 'KLWF' => '#f59e0b'] as $label => $color) {
              $latest = $latestByType[$label];
              $items[] = [
                'label' => $label,
                'color' => $color,
                'status' => $latest ? ucfirst($latest['status']) : 'Due',
                'due' => $latest ? ($latest['month_year'] ?: 'Submitted') : date('15 M Y', strtotime('first day of next month')),
              ];
            }
            foreach ($items as $ci): ?>
            <div class="comp-status-item">
              <div class="csi-icon" style="background:<?= $ci['color'] ?>20;color:<?= $ci['color'] ?>;"><?= $ci['label'] ?></div>
              <div style="flex:1;">
                <div style="font-size:13px;font-weight:600;"><?= $ci['label'] ?> Contribution</div>
                <div style="font-size:11px;color:var(--text-muted);">Due: <?= $ci['due'] ?></div>
              </div>
              <span class="badge <?= $ci['status']==='Verified' ? 'badge-success' : ($ci['status']==='Rejected' ? 'badge-danger' : 'badge-warning') ?>" style="font-size:10px;"><?= $ci['status'] ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="card glass" style="margin-top:16px;">
          <div class="card-header"><div class="card-title"><i class="fas fa-info-circle"></i> Validation Rules</div></div>
          <div class="card-body">
            <div class="validation-rules">
              <div class="vr-item">
                <i class="fas fa-check-circle" style="color:#10b981;"></i>
                <span>ESI contribution must match attendance records</span>
              </div>
              <div class="vr-item">
                <i class="fas fa-check-circle" style="color:#10b981;"></i>
                <span>PF based on number of active enrolled workers</span>
              </div>
              <div class="vr-item">
                <i class="fas fa-check-circle" style="color:#10b981;"></i>
                <span>Challan date must be within due date</span>
              </div>
              <div class="vr-item">
                <i class="fas fa-exclamation-triangle" style="color:#f59e0b;"></i>
                <span>Non-compliance may suspend gate passes</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <style>
    .compliance-tabs { display:flex;gap:4px;margin-bottom:20px;background:var(--card-bg);border:1px solid var(--border-color);border-radius:12px;padding:6px; }
    .comp-tab { padding:10px 18px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;color:var(--text-muted);display:flex;align-items:center;gap:8px;transition:.2s; }
    .comp-tab:hover { background:rgba(148,163,184,.1); }
    .comp-tab.active { background:#10b981;color:white; }
    .form-grid-2 { display:grid;grid-template-columns:1fr 1fr;gap:14px; }
    .form-group { margin-bottom:0; }
    .span-2 { grid-column:span 2; }
    .form-label { display:block;font-size:13px;font-weight:600;margin-bottom:5px; }
    .form-label.required::after { content:' *';color:#ef4444; }
    .form-control { width:100%;padding:9px 13px;border-radius:8px;border:1.5px solid var(--border-color);background:var(--input-bg,rgba(255,255,255,.04));color:var(--text-primary);font-size:13px;transition:.2s;box-sizing:border-box; }
    .form-control:focus { outline:none;border-color:#10b981;box-shadow:0 0 0 3px rgba(16,185,129,.12); }
    .form-hint { font-size:11px;color:var(--text-muted);margin-top:3px;display:block; }
    .comp-status-item { display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border-color); }
    .comp-status-item:last-child { border-bottom:none; }
    .csi-icon { width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800; }
    .validation-rules { display:flex;flex-direction:column;gap:10px; }
    .vr-item { display:flex;align-items:flex-start;gap:8px;font-size:13px; }
    .vr-item i { margin-top:2px;flex-shrink:0; }
    .toast-msg { position:fixed;bottom:30px;right:30px;z-index:9999;padding:14px 20px;border-radius:12px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:600;animation:slideUp .3s ease;box-shadow:0 8px 30px rgba(0,0,0,.2); }
    .toast-success { background:#10b981;color:white; }
    .toast-error { background:#ef4444;color:white; }
    @keyframes slideUp { from{transform:translateY(30px);opacity:0;}to{transform:translateY(0);opacity:1;} }
    </style>

    <script>
    // ESI auto-calc
    document.getElementById('esiGross')?.addEventListener('input', function() {
      const v = parseFloat(this.value) || 0;
      document.getElementById('esiEmployer').value = (v * 0.0325).toFixed(2);
      document.getElementById('esiEmployee').value = (v * 0.0075).toFixed(2);
      document.getElementById('esiTotal').value     = (v * 0.04).toFixed(2);
    });

    // PF auto-calc
    document.getElementById('pfWages')?.addEventListener('input', function() {
      const v = parseFloat(this.value) || 0;
      document.getElementById('pfEPF').value   = (v * 0.12).toFixed(2);
      document.getElementById('pfEPS').value   = (v * 0.0833).toFixed(2);
      document.getElementById('pfTotal').value = (v * 0.24).toFixed(2);
    });

    // KLWF auto-calc
    document.getElementById('klwfCount')?.addEventListener('input', function() {
      const n = parseInt(this.value) || 0;
      document.getElementById('klwfEmployee').value = (n * 40).toFixed(2);
      document.getElementById('klwfEmployer').value = (n * 160).toFixed(2);
      document.getElementById('klwfTotal').value    = (n * 200).toFixed(2);
    });

    function showToast(msg, type='success') {
      let t = document.createElement('div');
      t.className='toast-msg toast-'+type;
      t.innerHTML=`<i class="fas fa-${type==='success'?'check-circle':'exclamation-circle'}"></i> ${msg}`;
      document.body.appendChild(t); setTimeout(()=>t.remove(),3500);
    }

    // Form submissions
    ['esiForm','pfForm','klwfForm'].forEach(fid => {
      const form = document.getElementById(fid);
      if (!form) return;
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btnId = fid.replace('Form','Btn');
        const btn = document.getElementById(btnId);
        if (!btn) return; // guard: button not in DOM (only active tab renders its form+btn)
        const originalText = btn.innerHTML;
        btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
        const fd = new FormData(form);
        try {
          const res = await fetch('../../api/contractor/save_compliance.php', { method:'POST', body:fd });
          const result = await res.json();
          if (result.success) {
            showToast('Compliance submitted successfully!', 'success');
            setTimeout(() => window.location.href='?tab=history', 1800);
          } else {
            showToast('Error: '+(result.message||'Failed'), 'error');
          }
        } catch(err) { showToast('Network error.','error'); }
        btn.disabled = false;
        btn.innerHTML = originalText;
      });
    });
    </script>
    <?php
}

renderLayout("Statutory Compliance", 'renderContent', $role, $name);
