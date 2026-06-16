<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/training_flow.php';
require_once __DIR__ . '/../../include/training_venue_master.php';
require_once __DIR__ . '/../../include/training_type_master.php';
require_once __DIR__ . '/../../include/safety_training_control.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Safety Officer';

function safetyApprovalTableExists($conn, $table) {
    $table = mysqli_real_escape_string($conn, $table);
    $res = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return $res && mysqli_num_rows($res) > 0;
}

function safetyApprovalColumnExists($conn, $table, $column) {
    if (!safetyApprovalTableExists($conn, $table)) return false;
    $safeTable = str_replace('`', '``', $table);
    $column = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$column'");
    return $res && mysqli_num_rows($res) > 0;
}

function renderContent() {
    global $conn;
    clms_safety_ensure_control_schema($conn);

    $hasRequests = safetyApprovalTableExists($conn, 'training_requests');
    $hasWorkmen = safetyApprovalTableExists($conn, 'workmen');

    $safetyApprovalRequests = [];
    if ($hasRequests && $hasWorkmen) {
        $approvalContractorNameParts = [];
        foreach (['contractor_name', 'vendor_name', 'name'] as $column) {
            if (safetyApprovalColumnExists($conn, 'contractors', $column)) {
                $approvalContractorNameParts[] = "c.`$column`";
            }
        }
        $approvalContractorNameParts[] = "CONCAT('Contractor #', w.contractor_id)";
        $approvalContractorNameExpr = "COALESCE(" . implode(', ', $approvalContractorNameParts) . ")";
        $safetyApprovalRequests = db_fetch_all($conn, "
            SELECT tr.id AS request_id, tr.status AS request_status, tr.source, tr.remarks AS request_remarks,
                   w.*, w.id AS workman_id, w.name AS worker_name,
                   COALESCE(w.safety_enrollment_status, 'pending') AS safety_enrollment_status,
                   $approvalContractorNameExpr AS contractor_name
            FROM training_requests tr
            JOIN workmen w ON w.id = tr.workman_id
            LEFT JOIN contractors c ON c.id = w.contractor_id
            WHERE LOWER(COALESCE(tr.status, '')) IN ('pending_safety', 'welfare_pending')
              AND LOWER(COALESCE(w.execution_training_status, '')) = 'approved'
              AND LOWER(COALESCE(w.safety_enrollment_status, 'pending')) <> 'approved'
              AND tr.id = (
                  SELECT tr2.id
                  FROM training_requests tr2
                  WHERE tr2.workman_id = tr.workman_id
                  ORDER BY tr2.id DESC
                  LIMIT 1
              )
            ORDER BY COALESCE(tr.updated_at, tr.created_at) ASC, tr.id ASC
        ");
    }
    $safetyApprovalPending = count($safetyApprovalRequests);
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-user-check" style="color:#6366f1;margin-right:10px"></i>Safety Department Enrollment Approval Inbox</h2>
      </div>
      <a class="btn btn-outline" href="dashboard.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
    </div>

    <div class="card glass safety-approval-card" id="enrollment-approval-inbox" style="margin-top:20px;">
      <div class="card-header">
        <div>
          <div class="card-title">Pending Enrollments</div>
          <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">EO-approved enrollments must be approved here before Safety training scheduling.</div>
        </div>
        <span class="badge badge-warning"><?= $safetyApprovalPending ?> Pending</span>
      </div>
      <div class="card-body" style="padding:0">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Worker</th>
                <th>Contractor / Work</th>
                <th>Executing Officer</th>
                <th>Submission</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($safetyApprovalRequests as $approval): ?>
              <tr id="safety-approval-row-<?= (int)$approval['workman_id'] ?>">
                <td>
                  <strong><?= htmlspecialchars($approval['worker_name'] ?? '') ?></strong>
                  <div style="font-size:11px;color:var(--text-muted);margin-top:3px;"><code><?= htmlspecialchars($approval['temp_id'] ?: ('W-' . $approval['workman_id'])) ?></code></div>
                  <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">Aadhaar: <?= htmlspecialchars($approval['aadhaar'] ?? '-') ?></div>
                </td>
                <td>
                  <strong><?= htmlspecialchars($approval['contractor_name'] ?? 'N/A') ?></strong>
                  <div style="font-size:11px;color:var(--text-muted);margin-top:3px;"><?= htmlspecialchars($approval['department'] ?? '-') ?> / <?= htmlspecialchars($approval['nature_of_work'] ?? '-') ?></div>
                  <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">Language: <?= htmlspecialchars($approval['safety_language'] ?? '-') ?></div>
                </td>
                <td>
                  <code><?= htmlspecialchars($approval['executing_officer_code'] ?? '-') ?></code>
                  <div style="font-size:11px;color:var(--text-muted);margin-top:3px;"><?= htmlspecialchars($approval['executing_officer_name'] ?? '') ?></div>
                  <span class="badge badge-success" style="margin-top:4px;display:inline-block;">EO Approved</span>
                </td>
                <td>
                  <span class="badge badge-warning">Safety Approval Pending</span>
                  <div style="font-size:11px;color:var(--text-muted);margin-top:4px;"><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string)($approval['source'] ?? 'enrolment')))) ?></div>
                  <?php if (!empty($approval['training_approval_doc'])): ?>
                    <a class="btn btn-sm btn-outline" style="margin-top:6px;display:inline-block;" target="_blank" href="../../uploads/workers/<?= rawurlencode(basename((string)$approval['training_approval_doc'])) ?>"><i class="fas fa-file-pdf"></i> View Attachment</a>
                  <?php endif; ?>
                </td>
                <td>
                  <div style="display:flex;gap:6px;align-items:center;white-space:nowrap;">
                    <button type="button" class="btn btn-sm btn-outline btn-view-workman" data-workman='<?= htmlspecialchars(json_encode($approval), ENT_QUOTES, 'UTF-8') ?>' style="margin:0;"><i class="fas fa-user"></i> View Details</button>
                    <button class="btn btn-sm btn-success" type="button" onclick="reviewSafetyEnrollment(<?= (int)$approval['workman_id'] ?>, 'approved')" style="margin:0;"><i class="fas fa-check"></i> Approve</button>
                    <button class="btn btn-sm btn-danger" type="button" onclick="reviewSafetyEnrollment(<?= (int)$approval['workman_id'] ?>, 'rejected')" style="margin:0;"><i class="fas fa-times"></i> Reject</button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($safetyApprovalRequests)): ?>
              <tr><td colspan="5" style="text-align:center;padding:26px;color:var(--text-muted)">No enrollment is waiting for Safety Department approval.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
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

    async function reviewSafetyEnrollment(workmanId, decision) {
      const rejecting = decision === 'rejected';
      const prompt = await Swal.fire({
        icon: rejecting ? 'warning' : 'question',
        title: rejecting ? 'Reject enrollment?' : 'Approve enrollment?',
        text: rejecting
          ? 'The enrollment will return to the contractor for correction and resubmission.'
          : 'The worker will be released for Safety training scheduling.',
        input: 'textarea',
        inputLabel: rejecting ? 'Correction / rejection remarks' : 'Approval remarks (optional)',
        inputPlaceholder: rejecting ? 'Clearly mention what the contractor must correct.' : 'Enter remarks if required',
        showCancelButton: true,
        confirmButtonText: rejecting ? 'Reject & Return' : 'Approve Enrollment',
        confirmButtonColor: rejecting ? '#dc2626' : '#16a34a',
        inputValidator: value => rejecting && !String(value || '').trim()
          ? 'Rejection remarks are required.'
          : undefined
      });
      if (!prompt.isConfirmed) return;

      try {
        const response = await fetch('../../api/safety/review_enrollment.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': window.CLMS_CSRF_TOKEN || ''
          },
          body: JSON.stringify({
            workman_id: workmanId,
            decision,
            remarks: String(prompt.value || '').trim()
          })
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
          throw new Error(result.message || 'Unable to update enrollment approval.');
        }
        await Swal.fire('Updated', result.message, 'success');
        location.reload();
      } catch (error) {
        Swal.fire('Action Failed', error.message || 'Server response could not be processed.', 'error');
      }
    }
    </script>
    <?php
}

renderLayout('Safety Department Enrollment Approval Inbox', 'renderContent', $role, $name);
?>
