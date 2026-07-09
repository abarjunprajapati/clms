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
        SELECT w.*, c.contractor_name,
            (
                SELECT pr.status
                FROM training_payment_request_workers pw
                JOIN training_payment_requests pr ON pr.id = pw.payment_request_id
                WHERE pw.workman_id = w.id
                ORDER BY pr.created_at DESC LIMIT 1
            ) AS training_payment_status,
            (
                SELECT cb.batch_number
                FROM training_batch_workers tbw
                JOIN training_class_batches cb ON cb.id = tbw.batch_id
                WHERE tbw.workman_id = w.id
                ORDER BY tbw.created_at DESC LIMIT 1
            ) AS latest_training_batch
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

    $ids = array_column($rows, 'id');
    $workerDocs = [];
    if (!empty($ids)) {
        $idsCsv = implode(',', array_map('intval', $ids));
        $docRes = mysqli_query($conn, "SELECT workman_id, document_type, file_path FROM documents WHERE workman_id IN ($idsCsv)");
        while ($d = mysqli_fetch_assoc($docRes)) {
            $workerDocs[$d['workman_id']][] = ['type' => $d['document_type'], 'file_path' => $d['file_path']];
        }
    }
    foreach ($rows as &$r) {
        $r['documents'] = $workerDocs[$r['id']] ?? [];
    }
    unset($r);
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
              <th>S.No.</th>
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
              <tr><td colspan="10" style="text-align:center;padding:28px;color:#64748b;">No training attendance approvals pending.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $index => $r): ?>
              <?php
                $docUrl = executionTrainingDocUrl($r['training_approval_doc'] ?? '');
                $hasDoc = $docUrl !== '';
                $reviewedBy = (int)($r['execution_training_reviewed_by'] ?? 0);
                $statusApproved = strtolower((string)($r['execution_training_status'] ?? '')) === 'approved';
                $approved = $statusApproved;
              ?>
              <tr id="training-row-<?= (int)$r['id'] ?>">
                <td><?= $index + 1 ?></td>
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
      opacity: 1; pointer-events: all;
    }
    .modal-overlay.hidden {
      opacity: 0; pointer-events: none;
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
    </style>

    <div id="workmanModal" class="modal-overlay hidden" onclick="handleOverlayClick(event)">
      <div class="modal-box" style="max-width:850px; width:92%;">
        <div class="modal-header">
          <h3 class="modal-title">Worker Profile</h3>
          <button class="modal-close" onclick="closeWorkmanModal()">&times;</button>
        </div>
        <div id="viewContent" class="modal-body" style="padding:20px;"></div>
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

    function showWorkmanDetails(w) {
      const formatDate = (dateString) => {
        if (!dateString) return 'N/A';
        const d = new Date(dateString);
        if (isNaN(d)) return dateString;
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        return `${day}-${month}-${year}`;
      };

      const getDocUrl = (path) => {
        if (!path) return '';
        if (path.match(/^https?:\/\//i)) {
            return path;
        }
        const filename = path.split('/').pop();
        return `../../uploads/workers/${encodeURIComponent(filename)}`;
      };

      const renderDocLink = (label, path) => {
        if (!path) return '';
        const url = getDocUrl(path);
        return `
          <div class="doc-item" style="display:flex; align-items:center; justify-content:space-between; padding:8px 12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; font-size:12px; margin-bottom:6px; font-weight:600;">
            <i class="fas fa-file-pdf text-danger" style="font-size:15px; color:#ef4444;"></i>
            <span class="doc-label" style="flex-grow:1; margin-left:10px; font-weight:600; color:#334155;">${label}</span>
            <a href="${url}" target="_blank" class="btn btn-xs btn-outline-primary" style="padding:2px 8px; font-size:11px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:4px; border:1px solid #3b82f6; border-radius:6px; color:#3b82f6; background:transparent;"><i class="fas fa-external-link-alt"></i> View</a>
          </div>
        `;
      };

      const photoSrc = w.photo ? getDocUrl(w.photo) : 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23cbd5e1"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>';

      let statusLabel = 'Active';
      let statusStyle = 'background:#10b981; color:#fff;';
      if (w.is_blocked == 1) {
        statusLabel = `Blocked by ${w.blocked_source || 'Welfare'}`;
        statusStyle = 'background:#ef4444; color:#fff;';
      }

      let vaultHtml = '';
      const renderedCoreFiles = new Set();
      const renderAndTrack = (label, path) => {
        if (!path) return '';
        renderedCoreFiles.add(path);
        return renderDocLink(label, path);
      };

      vaultHtml += renderAndTrack('Workman Photo', w.photo);
      vaultHtml += renderAndTrack('Signature', w.signature);
      vaultHtml += renderAndTrack('Aadhaar Card', w.aadhaar_doc);
      vaultHtml += renderAndTrack('Training Approval', w.training_approval_doc);
      vaultHtml += renderAndTrack('Education Cert', w.education_doc);
      vaultHtml += renderAndTrack('Bank Passbook', w.bank_doc);
      vaultHtml += renderAndTrack('Skill Certificate', w.skill_cert_doc);
      vaultHtml += renderAndTrack('Police Verification', w.police_doc);
      vaultHtml += renderAndTrack('Medical Report', w.medical_doc);
      vaultHtml += renderAndTrack('Insurance Copy', w.insurance_doc);

      if (vaultHtml.trim() === '') {
        vaultHtml = '<div style="font-size:11px; color:#94a3b8; text-align:center; padding:10px; border:1px dashed #e2e8f0; border-radius:8px;">No core documents uploaded.</div>';
      }

      let dynamicDocsHtml = '';
      if (w.documents && w.documents.length > 0) {
        let count = 0;
        w.documents.forEach(doc => {
          if (!renderedCoreFiles.has(doc.file_path)) {
            const docLink = renderDocLink(doc.type, doc.file_path);
            if (docLink) {
              dynamicDocsHtml += docLink;
              count++;
            }
          }
        });
        if (count === 0) {
          dynamicDocsHtml = '<div style="font-size:11px; color:#94a3b8; text-align:center; padding:10px; border:1px dashed #e2e8f0; border-radius:8px;">No additional dynamic documents.</div>';
        }
      } else {
        dynamicDocsHtml = '<div style="font-size:11px; color:#94a3b8; text-align:center; padding:10px; border:1px dashed #e2e8f0; border-radius:8px;">No additional dynamic documents.</div>';
      }

      const eoStatus = (w.execution_training_status || 'pending').toLowerCase();
      const eoStatusClass = eoStatus === 'approved' ? 'status-approved' : (eoStatus === 'rejected' ? 'status-rejected' : 'status-pending');
      const eoStatusLabel = eoStatus.toUpperCase();

      const safetyStatus = (w.safety_enrollment_status || 'pending').toLowerCase();
      const safetyStatusClass = safetyStatus === 'approved' ? 'status-approved' : (safetyStatus === 'rejected' ? 'status-rejected' : 'status-pending');
      const safetyStatusLabel = safetyStatus.toUpperCase();
      
      let payStatusStr = (w.training_payment_status || '').toUpperCase();
      if (!payStatusStr) payStatusStr = 'NOT PAID';
      let payBadgeColor = payStatusStr === 'PAID' ? '#166534' : (payStatusStr === 'NOT PAID' ? '#475569' : '#92400e');

      document.getElementById('viewContent').innerHTML = `
        <style>
          .profile-container { font-family: 'Outfit', 'Inter', sans-serif; color: #1e293b; text-align: left; }
          .profile-header-card { display: flex; gap: 24px; background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); color: white; padding: 24px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); align-items: center; }
          .profile-photo { width: 100px; height: 120px; object-fit: cover; border-radius: 12px; border: 3px solid rgba(255,255,255,0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
          .profile-header-info { display: flex; flex-direction: column; gap: 4px; flex-grow: 1; }
          .profile-name { font-size: 22px; font-weight: 800; margin: 0; color: #fff; line-height: 1.2; }
          .profile-sub { font-size: 13px; color: rgba(255,255,255,0.85); margin-bottom: 6px; }
          .badge-container { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 4px; }
          .profile-badge { font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 99px; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; }
          .badge-role { background: rgba(255,255,255,0.2); color: white; }
          
          .profile-body-grid { display: grid; grid-template-columns: 1.8fr 1.2fr; gap: 20px; }
          .profile-section-card { background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 18px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
          .profile-section-title { font-size: 13px; font-weight: 800; color: #1e3a8a; border-bottom: 2px solid #eff6ff; padding-bottom: 6px; margin-top: 0; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 8px; }
          
          .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 16px; font-size: 13px; }
          .detail-row { display: flex; flex-direction: column; gap: 2px; }
          .detail-label { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; }
          .detail-val { font-weight: 600; color: #1e293b; word-break: break-all; }
          
          .approval-status-box { padding: 10px 12px; border-radius: 12px; border: 1px solid; font-size: 12px; margin-bottom: 10px; }
          .status-approved { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
          .status-pending { background: #fffbeb; border-color: #fde68a; color: #92400e; }
          .status-rejected { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
        </style>

        <div class="profile-container">
          <!-- Banner header -->
          <div class="profile-header-card">
            <img src="${photoSrc}" class="profile-photo" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; viewBox=&quot;0 0 24 24&quot; fill=&quot;%23cbd5e1&quot;><path d=&quot;M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z&quot;/></svg>'">
            <div class="profile-header-info">
              <h4 class="profile-name">${w.name}</h4>
              <div class="profile-sub">${w.gender} | DOB: ${formatDate(w.dob)} | Reg Date: ${formatDate(w.registration_date)}</div>
              <div class="badge-container">
                <span class="profile-badge badge-role"><i class="fas fa-user-shield"></i> ${w.worker_type ? w.worker_type.replace(' Pass', '') : 'Workman'}</span>
                <span class="profile-badge" style="${statusStyle}"><i class="fas fa-info-circle"></i> ${statusLabel}</span>
              </div>
            </div>
          </div>

          <!-- Body content split -->
          <div class="profile-body-grid">
            <!-- Left: Details -->
            <div>
              <!-- 1. Personal Info -->
              <div class="profile-section-card">
                <h5 class="profile-section-title"><i class="fas fa-id-card"></i> Personal Information</h5>
                <div class="details-grid">
                  <div class="detail-row">
                    <span class="detail-label">Temp ID</span>
                    <span class="detail-val text-primary" style="font-weight:700; color:#3b82f6;">${w.temp_id}</span>
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">Aadhaar Number</span>
                    <span class="detail-val">${w.aadhaar}</span>
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">Father's Name</span>
                    <span class="detail-val">${w.father_name || 'N/A'}</span>
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">Marital Status</span>
                    <span class="detail-val">${w.marital_status || 'N/A'}</span>
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">Nationality</span>
                    <span class="detail-val">${w.nationality || 'Indian'}</span>
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">Blood Group</span>
                    <span class="detail-val">${w.blood_group || 'N/A'}</span>
                  </div>
                </div>
              </div>

              <!-- 2. Address & Contact -->
              <div class="profile-section-card">
                <h5 class="profile-section-title"><i class="fas fa-map-marker-alt"></i> Contact & Address</h5>
                <div class="details-grid" style="grid-template-columns: 1fr;">
                  <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                    <div class="detail-row">
                      <span class="detail-label">Mobile Number</span>
                      <span class="detail-val">${w.mobile}</span>
                    </div>
                    <div class="detail-row">
                      <span class="detail-label">WhatsApp Number</span>
                      <span class="detail-val">${w.whatsapp_no || 'N/A'}</span>
                    </div>
                    <div class="detail-row">
                      <span class="detail-label">Email Address</span>
                      <span class="detail-val">${w.email || 'N/A'}</span>
                    </div>
                    <div class="detail-row">
                      <span class="detail-label">Emergency Contact</span>
                      <span class="detail-val">${w.emergency_contact || 'N/A'}</span>
                    </div>
                  </div>
                  <div class="detail-row" style="margin-top: 4px;">
                    <span class="detail-label">Present Address</span>
                    <span class="detail-val" style="font-weight: 500;">${w.present_address} ${w.district ? ', ' + w.district : ''} ${w.state ? ', ' + w.state : ''} ${w.pincode ? ' - ' + w.pincode : ''}</span>
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">Permanent Address</span>
                    <span class="detail-val" style="font-weight: 500;">${w.permanent_address || 'Same as Present'}</span>
                  </div>
                </div>
              </div>

              <!-- 3. Work Order & Employment -->
              <div class="profile-section-card">
                <h5 class="profile-section-title"><i class="fas fa-briefcase"></i> Work Order & Employment</h5>
                <div class="details-grid">
                  <div class="detail-row">
                    <span class="detail-label">Work Order No</span>
                    <span class="detail-val" style="color:#1e3a8a;">${w.work_order_no || 'N/A'}</span>
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">Project Name</span>
                    <span class="detail-val">${w.project_name || 'N/A'}</span>
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">Department</span>
                    <span class="detail-val">${w.department}</span>
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">Trade / Nature of Work</span>
                    <span class="detail-val">${w.nature_of_work || w.trade || 'N/A'}</span>
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">Skill Category</span>
                    <span class="detail-val">${w.skill_category}</span>
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">Experience</span>
                    <span class="detail-val">${w.experience ? w.experience + ' Years' : 'N/A'}</span>
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">PF Number / UAN</span>
                    <span class="detail-val">${w.pf_no || 'N/A'}</span>
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">ESIC Number</span>
                    <span class="detail-val">${w.esi_no || w.esic_number || 'N/A'}</span>
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">Certified Wage Rate</span>
                    <span class="detail-val">${w.certified_wage_rate || 'N/A'}</span>
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">Expected Join Date</span>
                    <span class="detail-val" style="color:#10b981;">${formatDate(w.expected_joining_date)}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right: Flow Approvals + Documents -->
            <div>
              <!-- 4. Approvals Timeline -->
              <div class="profile-section-card">
                <h5 class="profile-section-title"><i class="fas fa-list-check"></i> Workflow Status</h5>
                
                <!-- Safety Training Info -->
                <div style="font-weight:700; font-size:11px; color:#64748b; margin-bottom:4px; text-transform:uppercase;">Safety Training Status</div>
                <div style="padding:10px 12px; background:#f1f5f9; border-radius:12px; border:1px solid #cbd5e1; font-size:12px;">
                  <div><strong>Payment Option:</strong> ${(w.safety_fee_payment_option || 'N/A').replace(/_/g, ' ').toUpperCase()}</div>
                  <div style="margin-bottom:6px;"><strong>Payment Status:</strong> <strong style="color:${payBadgeColor};">${payStatusStr}</strong></div>
                  <div><strong>Batch:</strong> ${w.latest_training_batch || 'Not Scheduled'}</div>
                  <div><strong>Language:</strong> ${w.safety_language || 'N/A'}</div>
                  <div><strong>Date:</strong> ${formatDate(w.latest_training_date)}</div>
                  <div><strong>Training Result:</strong> <strong style="text-transform:uppercase; color:${w.safety_status === 'passed' ? '#166534' : (w.safety_status === 'failed' ? '#991b1b' : '#92400e')}">${w.safety_status || 'PENDING'}</strong></div>
                </div>
              </div>

              <!-- 5. Documents Vault -->
              <div class="profile-section-card">
                <h5 class="profile-section-title"><i class="fas fa-folder-open"></i> Documents Vault</h5>
                <div class="doc-vault">
                  ${vaultHtml}
                  
                  <div style="font-weight: 700; font-size: 11px; color: #475569; margin: 10px 0 4px 0; border-top: 1px solid #e2e8f0; padding-top: 8px; text-transform: uppercase;">Dynamic Documents</div>
                  ${dynamicDocsHtml}
                </div>
              </div>
            </div>
          </div>
        </div>
      `;
      
      const modal = document.getElementById('workmanModal');
      modal.classList.remove('hidden');
      modal.classList.add('show');
    }

    function closeWorkmanModal() {
      const modal = document.getElementById('workmanModal');
      modal.classList.remove('show');
      setTimeout(() => modal.classList.add('hidden'), 250);
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
