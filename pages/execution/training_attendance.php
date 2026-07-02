<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['execution_officer', 'execution', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/execution_context.php';
include __DIR__ . '/../../include/training_flow.php';
include __DIR__ . '/../../include/payment_flow.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Execution Officer';
$userId = (int)($_SESSION['user_id'] ?? 0);
$officerId = clms_execution_get_officer_id($conn, $userId);

function executionTrainingDeskContext($conn, $officerId, $userId) {
    $officer = db_single($conn, "SELECT employee_code FROM execution_officers WHERE id = ? LIMIT 1", 'i', [(int)$officerId]);
    $employeeExpr = clms_execution_column_exists($conn, 'users', 'employee_code') ? 'employee_code' : "'' AS employee_code";
    $loginUser = db_single($conn, "SELECT contractor_id, $employeeExpr FROM users WHERE id = ? LIMIT 1", 'i', [(int)$userId]);
    $codes = array_values(array_unique(array_filter(array_map(function($code) {
        return strtoupper(trim((string)$code));
    }, [
        $officer['employee_code'] ?? '',
        $loginUser['employee_code'] ?? '',
        $loginUser['contractor_id'] ?? '',
    ]))));
    $names = array_values(array_unique(array_filter(array_map(function($name) {
        return strtoupper(trim((string)$name));
    }, [$_SESSION['name'] ?? '']))));

    $codePlaceholders = implode(',', array_fill(0, max(1, count($codes)), '?'));
    $namePlaceholders = implode(',', array_fill(0, max(1, count($names)), '?'));
    return [
        'where' => "(w.executing_officer_id IN (?, ?) OR UPPER(COALESCE(w.executing_officer_code, '')) IN ($codePlaceholders) OR UPPER(COALESCE(w.executing_officer_name, '')) IN ($namePlaceholders))",
        'types' => 'ii' . str_repeat('s', max(1, count($codes))) . str_repeat('s', max(1, count($names))),
        'params' => array_merge([(int)$officerId, (int)$userId], $codes ?: [''], $names ?: ['']),
    ];
}

function executionTrainingDocUrl($path) {
    $path = trim((string)$path);
    if ($path === '') return '';
    if (preg_match('~^https?://~i', $path)) return $path;
    return '../../uploads/workers/' . rawurlencode(basename($path));
}

function renderContent() {
    global $conn, $officerId, $userId;
    clms_training_ensure_schema($conn);
    clms_ensure_payment_flow($conn);
    clms_release_all_paid_training_payments($conn, (int)($userId ?? 0));
    $ctx = executionTrainingDeskContext($conn, $officerId, $userId);

    $autoApproveRows = db_fetch_all($conn, "
        SELECT w.id
        FROM workmen w
        WHERE COALESCE(w.training_approval_doc, '') <> ''
          AND COALESCE(w.execution_training_status, 'pending_eo') IN ('pending_eo','pending')
          AND (
            UPPER(COALESCE(w.work_order_source, '')) <> 'PWO'
            OR EXISTS (
              SELECT 1
              FROM training_payment_request_workers pw
              JOIN training_payment_requests pr ON pr.id = pw.payment_request_id
              WHERE pw.workman_id = w.id
                AND pr.status = 'paid'
            )
          )
          AND {$ctx['where']}
        LIMIT 100
    ", $ctx['types'], $ctx['params']);
    foreach ($autoApproveRows as $autoRow) {
        clms_training_auto_approve_attached_document(
            $conn,
            (int)$autoRow['id'],
            (int)($officerId ?: $userId),
            'Auto-approved because Training Attendance Approval document is attached.'
        );
    }

    clms_training_seed_approved_queue($conn);

    $rows = db_fetch_all($conn, "
        SELECT w.*, c.contractor_name
        FROM workmen w
        LEFT JOIN contractors c ON c.id = w.contractor_id
        WHERE COALESCE(w.execution_training_status, 'pending_eo') IN ('pending_eo','pending','approved')
          AND EXISTS (
              SELECT 1
              FROM training_requests tr_submit
              WHERE tr_submit.workman_id = w.id
                AND tr_submit.status IN ('pending_eo','pending_safety','welfare_pending','pending','scheduled','contractor_confirmed','passed')
          )
          AND (
            UPPER(COALESCE(w.work_order_source, '')) <> 'PWO'
            OR EXISTS (
              SELECT 1
              FROM training_payment_request_workers pw
              JOIN training_payment_requests pr ON pr.id = pw.payment_request_id
              WHERE pw.workman_id = w.id
                AND pr.status = 'paid'
            )
          )
          AND {$ctx['where']}
        ORDER BY COALESCE(w.execution_training_reviewed_at, w.created_at) DESC
        LIMIT 100
    ", $ctx['types'], $ctx['params']);
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-file-signature" style="color:#6366f1;margin-right:10px"></i>Workmen Enrollment Details</h2>
      </div>
      <a class="btn btn-outline" href="dashboard.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
    </div>

    <div class="card glass">
      <div class="card-header">
        <div class="card-title">Workmen Enrollment Details</div>
        <span class="badge badge-warning"><?= count($rows) ?> Records</span>
      </div>
      <div class="card-body" style="padding:0">
        <table class="data-table">
          <thead>
            <tr>
              <th>Person Details</th>
              <th>Aadhaar</th>
              <th>Pass / Role</th>
              <th>Department / Work</th>
              <th>Temp ID</th>
              <th>Contractor</th>
              <th>Document / E-Code</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rows)): ?>
              <tr><td colspan="9" style="text-align:center;padding:28px;color:#64748b;">No training attendance approvals pending.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
              <?php
                $docUrl = executionTrainingDocUrl($r['training_approval_doc'] ?? '');
                $hasDoc = $docUrl !== '';
                $reviewedBy = (int)($r['execution_training_reviewed_by'] ?? 0);
                $statusApproved = strtolower((string)($r['execution_training_status'] ?? '')) === 'approved';
                $approved = $statusApproved;
              ?>
              <tr id="training-row-<?= (int)$r['id'] ?>">
                <td>
                  <strong><?= htmlspecialchars($r['name'] ?? '') ?></strong><br>
                  <small><?= htmlspecialchars($r['gender'] ?? '') ?><?= !empty($r['dob']) ? ' | ' . htmlspecialchars($r['dob']) : '' ?></small>
                </td>
                <td><code><?= htmlspecialchars($r['aadhaar'] ?? '') ?></code></td>
                <td><span class="badge badge-gray"><?= htmlspecialchars(str_replace(' Pass', '', (string)($r['worker_type'] ?? 'Workman'))) ?></span><br><small><?= htmlspecialchars($r['trade'] ?? '') ?></small></td>
                <td><div><?= htmlspecialchars($r['department'] ?? '') ?></div><small><?= htmlspecialchars($r['nature_of_work'] ?? '') ?></small></td>
                <td><code class="text-primary"><?= htmlspecialchars($r['temp_id'] ?? 'PENDING') ?></code></td>
                <td><?= htmlspecialchars($r['contractor_name'] ?? '-') ?></td>
                <td>
                  <?php if ($hasDoc): ?>
                    <a class="btn btn-sm btn-outline" target="_blank" href="<?= htmlspecialchars($docUrl) ?>"><i class="fas fa-file-pdf"></i> View</a>
                  <?php else: ?>
                    <span class="badge badge-warning">No attachment</span>
                  <?php endif; ?>
                  <div style="margin-top:4px;"><code style="background:rgba(14,165,233,0.1);color:#0369a1;padding:3px 8px;border-radius:6px;font-size:12px;font-weight:700;"><?= htmlspecialchars($r['executing_officer_code'] ?? '') ?></code></div>
                  <div style="font-size:11px;color:#64748b;margin-top:3px;"><?= htmlspecialchars($r['executing_officer_name'] ?? '') ?></div>
                </td>
                <td>
                  <?php if ($approved): ?>
                    <span class="badge badge-success">Approved</span><br><small>Forwarded to Safety Training</small>
                  <?php else: ?>
                    <span class="badge badge-warning">EO Pending</span>
                  <?php endif; ?>
                </td>
                <td>
                  <button type="button" class="btn-view btn-view-workman" data-workman='<?= htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8') ?>'><i class="fas fa-eye"></i> View Details</button>
                  <?php if ($hasDoc): ?>
                    <a class="btn-view" target="_blank" href="<?= htmlspecialchars($docUrl) ?>"><i class="fas fa-eye"></i> View Doc</a>
                  <?php endif; ?>
                  <?php if (!$approved): ?>
                    <button class="btn btn-sm btn-success" onclick="reviewTraining(<?= (int)$r['id'] ?>, 'approved')"><i class="fas fa-check"></i> Approve</button>
                    <button class="btn btn-sm btn-danger" onclick="reviewTraining(<?= (int)$r['id'] ?>, 'rejected')"><i class="fas fa-times"></i> Reject</button>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- View Workman Details Modal (Popup Style) -->
    <style>
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.6);
      backdrop-filter: blur(4px);
      z-index: 9999;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .modal-overlay.show {
      display: flex !important;
    }
    .modal-box {
      background: #fff;
      border-radius: 12px;
      max-width: 1000px;
      width: 96%;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      max-height: 90vh;
      animation: modalFadeIn 0.25s ease-out;
    }
    @keyframes modalFadeIn {
      from { transform: scale(0.95); opacity: 0; }
      to { transform: scale(1); opacity: 1; }
    }
    .modal-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 24px;
      border-bottom: 1px solid #e2e8f0;
      background: #f8fafc;
    }
    .modal-title {
      margin: 0;
      font-size: 16px;
      font-weight: 700;
      color: #0f172a;
    }
    .modal-close {
      background: none;
      border: none;
      font-size: 24px;
      cursor: pointer;
      color: #94a3b8;
      line-height: 1;
      transition: color 0.15s;
    }
    .modal-close:hover {
      color: #475569;
    }
    .modal-body {
      padding: 24px;
      overflow-y: auto;
      flex-grow: 1;
    }
    .preview-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 24px;
    }
    .preview-section {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 18px;
      margin-bottom: 20px;
    }
    .preview-section-title {
      font-size: 13px;
      font-weight: 700;
      color: #475569;
      border-bottom: 1px solid #e2e8f0;
      padding-bottom: 6px;
      margin-bottom: 12px;
      text-transform: uppercase;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .preview-table {
      width: 100%;
      border-collapse: collapse;
    }
    .preview-table th {
      width: 40%;
      text-align: left;
      font-size: 12px;
      color: #64748b;
      padding: 6px 0;
      font-weight: 600;
      vertical-align: top;
    }
    .preview-table td {
      font-size: 12px;
      color: #0f172a;
      padding: 6px 0;
      vertical-align: top;
    }
    .preview-docs-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 12px;
      margin-top: 10px;
    }
    .preview-doc-card {
      padding: 10px;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      background: #fff;
      display: flex;
      flex-direction: column;
      gap: 6px;
    }
    .preview-doc-label {
      font-size: 11px;
      font-weight: 600;
      color: #475569;
    }
    </style>

    <div id="workmanModal" class="modal-overlay" onclick="handleOverlayClick(event)">
      <div class="modal-box">
        <div class="modal-header">
          <h3 class="modal-title"><i class="fas fa-id-card" style="color:#6366f1;margin-right:6px;"></i> Workman Enrollment Profile Details</h3>
          <button class="modal-close" onclick="closeWorkmanModal()">&times;</button>
        </div>
        <div class="modal-body">
          <div class="preview-grid">
            <!-- Col 1 -->
            <div>
              <!-- Personal Details -->
              <div class="preview-section">
                <div class="preview-section-title"><i class="fas fa-user-circle"></i> Personal Information</div>
                <table class="preview-table">
                  <tr><th>Full Name</th><td id="wm-name">-</td></tr>
                  <tr><th>Father's Name</th><td id="wm-father">-</td></tr>
                  <tr><th>Gender / DOB</th><td id="wm-gender-dob">-</td></tr>
                  <tr><th>Marital Status</th><td id="wm-marital">-</td></tr>
                  <tr><th>Nationality</th><td id="wm-nationality">-</td></tr>
                  <tr><th>Blood Group</th><td id="wm-blood">-</td></tr>
                  <tr><th>Religion</th><td id="wm-religion">-</td></tr>
                  <tr><th>PWD Status</th><td id="wm-pwd">-</td></tr>
                  <tr><th>Passport No</th><td id="wm-passport">-</td></tr>
                  <tr><th>Driving Licence No</th><td id="wm-dl">-</td></tr>
                  <tr><th>Email ID</th><td id="wm-email">-</td></tr>
                </table>
              </div>

              <!-- Address Details -->
              <div class="preview-section">
                <div class="preview-section-title"><i class="fas fa-map-marker-alt"></i> Contact & Address</div>
                <table class="preview-table">
                  <tr><th>Mobile Number</th><td id="wm-mobile">-</td></tr>
                  <tr><th>WhatsApp Number</th><td id="wm-whatsapp">-</td></tr>
                  <tr><th>Emergency Contact</th><td id="wm-emergency">-</td></tr>
                  <tr><th>Present Address</th><td id="wm-present-addr">-</td></tr>
                  <tr><th>Permanent Address</th><td id="wm-permanent-addr">-</td></tr>
                  <tr><th>Location / Region</th><td id="wm-location">-</td></tr>
                </table>
              </div>
            </div>

            <!-- Col 2 -->
            <div>
              <!-- Job Details -->
              <div class="preview-section">
                <div class="preview-section-title"><i class="fas fa-briefcase"></i> Employment & Work Details</div>
                <table class="preview-table">
                  <tr><th>Contractor</th><td id="wm-contractor">-</td></tr>
                  <tr><th>Work Order No</th><td id="wm-wo-no">-</td></tr>
                  <tr><th>Project Name (WBS)</th><td id="wm-project">-</td></tr>
                  <tr><th>Department</th><td id="wm-dept">-</td></tr>
                  <tr><th>Trade</th><td id="wm-trade">-</td></tr>
                  <tr><th>Skill Category</th><td id="wm-skill-cat">-</td></tr>
                  <tr><th>Nature of Work</th><td id="wm-nature-work">-</td></tr>
                  <tr><th>Experience</th><td id="wm-experience">-</td></tr>
                  <tr><th>Safety Preferred Lang</th><td id="wm-safety-lang">-</td></tr>
                  <tr><th>Executing Officer</th><td id="wm-exec-officer">-</td></tr>
                </table>
              </div>

              <!-- Statutory Details -->
              <div class="preview-section">
                <div class="preview-section-title"><i class="fas fa-university"></i> Statutory & Banking Details</div>
                <table class="preview-table">
                  <tr><th>Aadhaar Number</th><td id="wm-aadhaar">-</td></tr>
                  <tr><th>EPF Registered</th><td id="wm-epf">-</td></tr>
                  <tr><th>PF No / UAN</th><td id="wm-pf-uan">-</td></tr>
                  <tr><th>ESI Registered</th><td id="wm-esi">-</td></tr>
                  <tr><th>ESIC Number</th><td id="wm-esic">-</td></tr>
                  <tr><th>Bank Account No</th><td id="wm-bank-acc">-</td></tr>
                  <tr><th>Bank IFSC Code</th><td id="wm-bank-ifsc">-</td></tr>
                  <tr><th>Certified Wage Rate</th><td id="wm-wage-rate">-</td></tr>
                  <tr><th>Payment Option</th><td id="wm-pay-option">-</td></tr>
                </table>
              </div>
            </div>
          </div>

          <!-- Documents -->
          <div class="preview-section" style="margin-bottom:0;">
            <div class="preview-section-title"><i class="fas fa-file-alt"></i> Uploaded Documents (Annexure 4A / 6A)</div>
            <div class="preview-docs-grid" id="wm-docs-container">
              <!-- Dynamically populated -->
            </div>
          </div>
        </div>
        <div style="padding: 14px 24px; border-top: 1px solid #e2e8f0; background: #f8fafc; display: flex; justify-content: flex-end;">
          <button class="btn btn-outline" onclick="closeWorkmanModal()">Close</button>
        </div>
      </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
      document.querySelectorAll('.btn-view-workman').forEach(btn => {
        btn.addEventListener('click', function() {
          try {
            const data = JSON.parse(this.getAttribute('data-workman'));
            showWorkmanDetails(data);
          } catch(e) {
            console.error('Error parsing workman data:', e);
          }
        });
      });
    });

    function showWorkmanDetails(data) {
      document.getElementById('wm-name').textContent = data.name || 'N/A';
      document.getElementById('wm-father').textContent = data.father_name || 'N/A';
      document.getElementById('wm-gender-dob').textContent = (data.gender || 'N/A') + ' / ' + (data.dob || 'N/A');
      document.getElementById('wm-marital').textContent = data.marital_status || 'N/A';
      document.getElementById('wm-nationality').textContent = data.nationality || 'N/A';
      document.getElementById('wm-blood').textContent = data.blood_group || 'N/A';
      document.getElementById('wm-religion').textContent = data.region || 'N/A';
      document.getElementById('wm-pwd').textContent = data.pwd_status || 'N/A';
      document.getElementById('wm-passport').textContent = data.passport_no || 'N/A';
      document.getElementById('wm-dl').textContent = data.driving_licence_no || 'N/A';
      document.getElementById('wm-email').textContent = data.email || data.contact_email || 'N/A';

      document.getElementById('wm-mobile').textContent = data.mobile || 'N/A';
      document.getElementById('wm-whatsapp').textContent = data.whatsapp_no || 'N/A';
      document.getElementById('wm-emergency').textContent = data.emergency_contact || 'N/A';
      document.getElementById('wm-present-addr').innerHTML = (data.present_address || 'N/A').replace(/\n/g, '<br>');
      document.getElementById('wm-permanent-addr').innerHTML = (data.permanent_address || 'N/A').replace(/\n/g, '<br>');
      document.getElementById('wm-location').textContent = (data.district || '') + ', ' + (data.state || '') + ' - ' + (data.pincode || '');

      document.getElementById('wm-contractor').textContent = data.contractor_name || 'N/A';
      document.getElementById('wm-wo-no').textContent = data.work_order_no || 'N/A';
      document.getElementById('wm-project').textContent = data.project_name || 'N/A';
      document.getElementById('wm-dept').textContent = data.department || 'N/A';
      document.getElementById('wm-trade').textContent = data.trade || 'N/A';
      document.getElementById('wm-skill-cat').textContent = data.skill_category || 'N/A';
      document.getElementById('wm-nature-work').textContent = data.nature_of_work || 'N/A';
      document.getElementById('wm-experience').textContent = (data.experience || '0') + ' Years';
      document.getElementById('wm-safety-lang').textContent = data.safety_language || 'N/A';
      document.getElementById('wm-exec-officer').textContent = (data.executing_officer_name || 'N/A') + ' (E-Code: ' + (data.executing_officer_code || 'N/A') + ')';

      document.getElementById('wm-aadhaar').textContent = data.aadhaar || 'N/A';
      document.getElementById('wm-epf').textContent = data.epf_registered_worker || 'N/A';
      document.getElementById('wm-pf-uan').textContent = (data.pf_no || 'N/A') + ' / ' + (data.uan_number || 'N/A');
      document.getElementById('wm-esi').textContent = data.esi_registered_worker || 'N/A';
      document.getElementById('wm-esic').textContent = data.esic_number || data.esi_no || 'N/A';
      document.getElementById('wm-bank-acc').textContent = data.bank_account || 'N/A';
      document.getElementById('wm-bank-ifsc').textContent = data.ifsc || 'N/A';
      document.getElementById('wm-wage-rate').textContent = 'INR ' + (data.certified_wage_rate || '0.00');
      document.getElementById('wm-pay-option').textContent = (data.safety_fee_payment_option || 'N/A').replace(/_/g, ' ').toUpperCase();

      // Render Documents Grid
      const docContainer = document.getElementById('wm-docs-container');
      docContainer.innerHTML = '';

      const docsList = [
        { key: 'aadhaar_doc', label: 'Aadhaar (4A)' },
        { key: 'medical_doc', label: 'Medical (4A)' },
        { key: 'police_doc', label: 'Police Verification (4A)' },
        { key: 'insurance_doc', label: 'Insurance (4A)' },
        { key: 'education_doc', label: 'Education (4A)' },
        { key: 'educational_doc', label: 'Education (Alt)' },
        { key: 'photo', label: 'Worker Photo' },
        { key: 'signature_doc', label: 'Worker Signature' },
        { key: 'bank_doc', label: 'Bank Proof' },
        { key: 'gatepass_doc', label: 'Gate Pass Doc' },
        { key: 'skill_cert_doc', label: 'Skill Certificate' },
        { key: 'training_approval_doc', label: 'Training Approval Doc' }
      ];

      let docCount = 0;
      docsList.forEach(doc => {
        const file = data[doc.key];
        if (file) {
          docCount++;
          const fileUrl = '../../uploads/workers/' + encodeURIComponent(file);
          const card = document.createElement('div');
          card.className = 'preview-doc-card';
          card.innerHTML = `
            <span class="preview-doc-label">${doc.label}</span>
            <a href="${fileUrl}" target="_blank" class="btn btn-sm btn-outline" style="text-align:center;width:100%;margin-top:auto;font-size:11px;padding:4px 6px;">
              <i class="fas fa-external-link-alt"></i> View File
            </a>
          `;
          docContainer.appendChild(card);
        }
      });

      if (docCount === 0) {
        docContainer.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:15px;color:#64748b;font-size:12px;">No uploaded documents found.</div>';
      }

      const modal = document.getElementById('workmanModal');
      modal.classList.add('show');
    }

    function closeWorkmanModal() {
      const modal = document.getElementById('workmanModal');
      modal.classList.remove('show');
    }

    function handleOverlayClick(e) {
      if (e.target.id === 'workmanModal') {
        closeWorkmanModal();
      }
    }

    async function reviewTraining(workmanId, decision) {
      const title = decision === 'approved' ? 'Recommend for Safety Training?' : 'Reject training attendance?';
      const prompt = await Swal.fire({
        icon: decision === 'approved' ? 'question' : 'warning',
        title,
        input: 'textarea',
        inputPlaceholder: 'Remarks',
        showCancelButton: true,
        confirmButtonText: decision === 'approved' ? 'Approve' : 'Reject',
        confirmButtonColor: decision === 'approved' ? '#10b981' : '#ef4444'
      });
      if (!prompt.isConfirmed) return;

      try {
        const response = await fetch('../../api/execution/approve_training_attendance.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ workman_id: workmanId, decision, remarks: prompt.value || '' })
        });
        const result = await response.json();
        if (result.status) {
          Swal.fire('Updated', result.message || 'Training attendance reviewed.', 'success').then(() => location.reload());
        } else {
          Swal.fire('Action Failed', result.message || 'Unable to update training attendance.', 'error');
        }
      } catch (err) {
        Swal.fire('Connection Error', 'Server response could not be processed.', 'error');
      }
    }
    </script>
    <?php
}

renderLayout('Workmen Enrollment Details', 'renderContent', $role, $name);
?>
