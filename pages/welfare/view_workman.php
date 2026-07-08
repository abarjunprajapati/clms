<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin', 'welfare_user', 'pass_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Admin';

function renderContent() {
    global $conn;

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if (!$id) {
        echo "<div class='alert alert-danger'>Invalid Request: Workman ID missing.</div>";
        return;
    }

    $query = "
    SELECT w.*, c.contractor_name, tr.training_type, tr.status as request_status
    FROM workmen w
    LEFT JOIN contractors c ON w.contractor_id = c.id
    LEFT JOIN training_requests tr ON w.id = tr.workman_id
    WHERE w.id = ?
    ";

    $data = db_single($conn, $query, 'i', [$id]);

    if (!$data) {
        echo "<div class='alert alert-danger'>Worker not found.</div>";
        return;
    }
    ?>
    <div class="content-header">
      <h2 class="page-title">Worker Profile &amp; Action Center</h2>
      <p class="page-subtitle">Welfare Department — Worker Enrollment &amp; Training Details</p>
    </div>

    <style>
    .profile-container { font-family: 'Outfit', 'Inter', sans-serif; color: #1e293b; }
    .profile-header-card {
        display: flex; gap: 24px;
        background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
        color: white; padding: 24px; border-radius: 16px;
        margin-bottom: 24px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.15);
        align-items: center;
    }
    .profile-photo {
        width: 100px; height: 120px; object-fit: cover;
        border-radius: 12px; border: 3px solid rgba(255,255,255,0.8);
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
    }
    .profile-photo-placeholder {
        width: 100px; height: 120px; border-radius: 12px;
        border: 3px solid rgba(255,255,255,0.4);
        background: rgba(255,255,255,0.15);
        display: flex; align-items: center; justify-content: center;
        font-size: 40px; color: rgba(255,255,255,0.7);
        flex-shrink: 0;
    }
    .profile-header-info { display: flex; flex-direction: column; gap: 6px; flex-grow: 1; }
    .profile-name { font-size: 22px; font-weight: 800; margin: 0; color: #fff; line-height: 1.2; }
    .profile-sub { font-size: 13px; color: rgba(255,255,255,0.85); margin: 0; }
    .badge-container { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 4px; }
    .profile-badge { font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 99px; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; }
    .badge-role   { background: rgba(255,255,255,0.2); color: white; }
    .badge-active { background: #10b981; color: #fff; }
    .badge-pending{ background: #f59e0b; color: #fff; }
    .badge-rejected{ background: #ef4444; color: #fff; }

    .profile-body-grid { display: grid; grid-template-columns: 1.8fr 1fr; gap: 20px; align-items: start; }

    .profile-section-card {
        background: white; border: 1px solid #e2e8f0;
        border-radius: 14px; margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden;
    }
    .profile-section-title {
        font-size: 11px; font-weight: 800; color: #fff;
        background: #1e3a8a; padding: 9px 16px;
        text-transform: uppercase; letter-spacing: 0.6px;
        display: flex; align-items: center; gap: 8px;
    }
    .section-body { padding: 16px 18px; }

    .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 18px; }
    .details-grid-3col { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px 18px; }
    .details-grid-1col { display: grid; grid-template-columns: 1fr; gap: 12px; }
    .detail-row { display: flex; flex-direction: column; gap: 3px; }
    .detail-label { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; }
    .detail-val   { font-size: 13px; font-weight: 600; color: #1e293b; word-break: break-word; }

    .doc-vault-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; }
    .doc-vault-card { display: flex; flex-direction: column; gap: 7px; padding: 10px 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; }
    .doc-vault-label { font-size: 11px; font-weight: 600; color: #334155; flex-grow: 1; }
    .doc-type-tag { font-size: 9px; font-weight: 700; padding: 2px 6px; border-radius: 99px; display: inline-block; }
    .tag-4a { background: #f1f5f9; color: #475569; }
    .tag-6a { background: #dcfce7; color: #166534; }

    .status-box { padding: 12px 14px; border-radius: 10px; border: 1px solid; font-size: 12px; margin-bottom: 12px; }
    .status-approved { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
    .status-pending  { background: #fffbeb; border-color: #fde68a; color: #92400e; }
    .status-rejected { background: #fef2f2; border-color: #fecaca; color: #991b1b; }

    @media(max-width: 900px) {
        .profile-body-grid  { grid-template-columns: 1fr; }
        .details-grid       { grid-template-columns: 1fr; }
        .details-grid-3col  { grid-template-columns: 1fr 1fr; }
    }
    </style>

    <div class="profile-container">

      <!-- ── HEADER BANNER ── -->
      <div class="profile-header-card">
        <?php
          $photoSrc = '';
          if (!empty($data['photo'])) {
              $photoSrc = (strpos($data['photo'], '/') === false && strpos($data['photo'], '\\') === false)
                  ? '../../uploads/workers/' . $data['photo']
                  : '../../' . ltrim($data['photo'], '/\\');
          }
        ?>
        <?php if ($photoSrc): ?>
          <img src="<?= htmlspecialchars($photoSrc) ?>" class="profile-photo" alt="Worker Photo"
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
          <div class="profile-photo-placeholder" style="display:none;"><i class="fas fa-user"></i></div>
        <?php else: ?>
          <div class="profile-photo-placeholder"><i class="fas fa-user"></i></div>
        <?php endif; ?>

        <div class="profile-header-info">
          <h3 class="profile-name"><?= htmlspecialchars($data['name'] ?? 'Unknown Worker') ?></h3>
          <p class="profile-sub">
            <?= htmlspecialchars($data['gender'] ?? 'N/A') ?> &nbsp;|&nbsp;
            DOB: <?= htmlspecialchars($data['dob'] ?? 'N/A') ?> &nbsp;|&nbsp;
            Reg: <?= htmlspecialchars($data['registration_date'] ?? 'N/A') ?>
          </p>
          <div class="badge-container">
            <span class="profile-badge badge-role"><i class="fas fa-user-shield"></i> <?= htmlspecialchars($data['pass_type'] ?? 'Workman') ?></span>
            <span class="profile-badge badge-role"><i class="fas fa-building"></i> <?= htmlspecialchars($data['contractor_name'] ?? 'N/A') ?></span>
            <?php
              $st = strtolower($data['status'] ?? '');
              $stClass = in_array($st, ['verified','approved']) ? 'badge-active' : (($st === 'rejected') ? 'badge-rejected' : 'badge-pending');
            ?>
            <span class="profile-badge <?= $stClass ?>">
              <i class="fas fa-circle"></i> <?= strtoupper($data['status'] ?? 'Pending') ?>
            </span>
          </div>
        </div>
      </div>

      <!-- ── BODY GRID ── -->
      <div class="profile-body-grid">

        <!-- LEFT COLUMN -->
        <div>

          <!-- BASIC INFO -->
          <div class="profile-section-card">
            <div class="profile-section-title"><i class="fas fa-id-card"></i> Basic Info</div>
            <div class="section-body">
              <div class="details-grid">
                <div class="detail-row">
                  <span class="detail-label">Temp ID</span>
                  <span class="detail-val" style="color:#2563eb;font-size:14px;font-weight:800;"><?= htmlspecialchars($data['temp_id'] ?: 'PENDING') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Pass Type</span>
                  <span class="detail-val"><?= htmlspecialchars($data['pass_type'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Aadhaar Number</span>
                  <span class="detail-val"><?= htmlspecialchars($data['aadhaar'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Father's Name</span>
                  <span class="detail-val"><?= htmlspecialchars($data['father_name'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Marital Status</span>
                  <span class="detail-val"><?= htmlspecialchars($data['marital_status'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Nationality</span>
                  <span class="detail-val"><?= htmlspecialchars($data['nationality'] ?? 'Indian') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Blood Group</span>
                  <span class="detail-val"><?= htmlspecialchars($data['blood_group'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Religion</span>
                  <span class="detail-val"><?= htmlspecialchars($data['region'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">PWD Status</span>
                  <span class="detail-val"><?= htmlspecialchars($data['pwd_status'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Identification Mark</span>
                  <span class="detail-val"><?= htmlspecialchars($data['identification_mark'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Passport No</span>
                  <span class="detail-val"><?= htmlspecialchars($data['passport_no'] ?: 'N/A') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Driving Licence No</span>
                  <span class="detail-val"><?= htmlspecialchars($data['driving_licence_no'] ?: 'N/A') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Email ID</span>
                  <span class="detail-val"><?= htmlspecialchars($data['email'] ?? $data['contact_email'] ?? 'N/A') ?></span>
                </div>
              </div>
            </div>
          </div>

          <!-- ADDRESS / CONTACT -->
          <div class="profile-section-card">
            <div class="profile-section-title"><i class="fas fa-map-marker-alt"></i> Address / Contact</div>
            <div class="section-body">
              <div class="details-grid" style="margin-bottom:12px;">
                <div class="detail-row">
                  <span class="detail-label">Mobile Number</span>
                  <span class="detail-val"><?= htmlspecialchars($data['mobile'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">WhatsApp Number</span>
                  <span class="detail-val"><?= htmlspecialchars($data['whatsapp_no'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Emergency Contact</span>
                  <span class="detail-val"><?= htmlspecialchars($data['emergency_contact'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">State</span>
                  <span class="detail-val"><?= htmlspecialchars($data['state'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">District</span>
                  <span class="detail-val"><?= htmlspecialchars($data['district'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Pin Code</span>
                  <span class="detail-val"><?= htmlspecialchars($data['pincode'] ?? 'N/A') ?></span>
                </div>
              </div>
              <div class="details-grid-1col">
                <div class="detail-row">
                  <span class="detail-label">Present Address</span>
                  <span class="detail-val"><?= nl2br(htmlspecialchars($data['present_address'] ?? 'N/A')) ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Permanent Address</span>
                  <span class="detail-val"><?= nl2br(htmlspecialchars($data['permanent_address'] ?? 'N/A')) ?></span>
                </div>
              </div>
            </div>
          </div>

          <!-- WORK / COMPLIANCE -->
          <div class="profile-section-card">
            <div class="profile-section-title"><i class="fas fa-briefcase"></i> Work / Compliance</div>
            <div class="section-body">
              <div class="details-grid">
                <div class="detail-row">
                  <span class="detail-label">Contractor</span>
                  <span class="detail-val"><?= htmlspecialchars($data['contractor_name'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Work Order No</span>
                  <span class="detail-val" style="color:#1e3a8a;"><?= htmlspecialchars($data['work_order_no'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Project Name (WBS)</span>
                  <span class="detail-val"><?= htmlspecialchars($data['project_name'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Department</span>
                  <span class="detail-val"><?= htmlspecialchars($data['department'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Trade / Nature of Work</span>
                  <span class="detail-val"><?= htmlspecialchars($data['trade'] ?? $data['nature_of_work'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Skill Category</span>
                  <span class="detail-val"><?= htmlspecialchars($data['skill_category'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Experience</span>
                  <span class="detail-val"><?= htmlspecialchars($data['experience'] ?? '0') ?> Years</span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Safety Language</span>
                  <span class="detail-val"><?= htmlspecialchars($data['safety_language'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">EPF Registered</span>
                  <span class="detail-val"><?= htmlspecialchars($data['epf_registered_worker'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">PF No / UAN</span>
                  <span class="detail-val"><?= htmlspecialchars($data['pf_no'] ?: 'N/A') ?> / <?= htmlspecialchars($data['uan_number'] ?: 'N/A') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">ESI Registered</span>
                  <span class="detail-val"><?= htmlspecialchars($data['esi_registered_worker'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">ESIC Number</span>
                  <span class="detail-val"><?= htmlspecialchars($data['esic_number'] ?: $data['esi_no'] ?: 'N/A') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Bank Account No</span>
                  <span class="detail-val"><?= htmlspecialchars($data['bank_account'] ?: 'N/A') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Bank IFSC Code</span>
                  <span class="detail-val"><?= htmlspecialchars($data['ifsc'] ?: 'N/A') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Certified Wage Rate</span>
                  <span class="detail-val">INR <?= htmlspecialchars($data['certified_wage_rate'] ?? '0.00') ?></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Executing Officer</span>
                  <span class="detail-val"><?= htmlspecialchars($data['executing_officer_name'] ?? 'N/A') ?> (<?= htmlspecialchars($data['executing_officer_code'] ?? 'N/A') ?>)</span>
                </div>
              </div>
            </div>
          </div>

          <!-- DOCUMENTS VAULT -->
          <div class="profile-section-card">
            <div class="profile-section-title"><i class="fas fa-folder-open"></i> Documents Vault (Annexure 4A / 6A)</div>
            <div class="section-body">
              <div class="doc-vault-grid">
                <?php
                $displayDocs = [
                  'Aadhaar (4A)'             => ['path' => $data['aadhaar_doc']       ? '../../uploads/workers/' . $data['aadhaar_doc']       : null, 'type' => '4a'],
                  'Medical (4A)'             => ['path' => $data['medical_doc']        ? '../../uploads/workers/' . $data['medical_doc']        : null, 'type' => '4a'],
                  'Police Verify (4A)'       => ['path' => $data['police_doc']         ? '../../uploads/workers/' . $data['police_doc']         : null, 'type' => '4a'],
                  'Insurance (4A)'           => ['path' => $data['insurance_doc']      ? '../../uploads/workers/' . $data['insurance_doc']      : null, 'type' => '4a'],
                  'Education (4A)'           => ['path' => ($data['educational_doc'] ?: $data['education_doc']) ? '../../uploads/workers/' . ($data['educational_doc'] ?: $data['education_doc']) : null, 'type' => '4a'],
                  'Worker Photo'             => ['path' => $data['photo']              ? '../../uploads/workers/' . $data['photo']              : null, 'type' => '4a'],
                  'Signature'                => ['path' => $data['signature_doc']      ? '../../uploads/workers/' . $data['signature_doc']      : null, 'type' => '4a'],
                  'Bank Proof'               => ['path' => $data['bank_doc']           ? '../../uploads/workers/' . $data['bank_doc']           : null, 'type' => '4a'],
                  'Gate Pass Doc'            => ['path' => $data['gatepass_doc']       ? '../../uploads/workers/' . $data['gatepass_doc']       : null, 'type' => '4a'],
                  'Skill Certificate'        => ['path' => $data['skill_cert_doc']     ? '../../uploads/workers/' . $data['skill_cert_doc']     : null, 'type' => '4a'],
                  'Training Approval'        => ['path' => $data['training_approval_doc'] ? '../../uploads/workers/' . $data['training_approval_doc'] : null, 'type' => '4a'],
                ];
                $otherDocsRes = $conn->query("SELECT document_type, file_path FROM documents WHERE workman_id = $id");
                while ($otherDocsRes && $row = $otherDocsRes->fetch_assoc()) {
                    $displayDocs[$row['document_type']] = ['path' => '../../uploads/documents/' . $row['file_path'], 'type' => '6a'];
                }
                foreach ($displayDocs as $label => $doc):
                    if (!$doc['path'] && !in_array($label, ['Aadhaar (4A)', 'Medical (4A)', 'Police Verify (4A)', 'Worker Photo'])) continue;
                ?>
                  <div class="doc-vault-card">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:4px;">
                      <span class="doc-vault-label"><?= htmlspecialchars($label) ?></span>
                      <span class="doc-type-tag tag-<?= $doc['type'] ?>"><?= strtoupper($doc['type']) ?></span>
                    </div>
                    <?php if ($doc['path']): ?>
                      <a href="<?= htmlspecialchars($doc['path']) ?>" target="_blank"
                         class="btn btn-sm btn-outline" style="text-align:center;font-size:11px;padding:4px 8px;margin-top:auto;">
                        <i class="fas fa-external-link-alt"></i> View
                      </a>
                    <?php else: ?>
                      <div style="font-size:11px;color:#dc2626;text-align:center;padding:4px 6px;border:1px dashed #fecaca;border-radius:6px;margin-top:auto;">
                        <i class="fas fa-exclamation-triangle"></i> Missing
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

        </div><!-- end left col -->

        <!-- RIGHT COLUMN -->
        <div>
          <div class="profile-section-card" style="position:sticky;top:20px;">
            <div class="profile-section-title"><i class="fas fa-tasks"></i> Workflow Status</div>
            <div class="section-body">

              <div style="margin-bottom:16px;">
                <div class="detail-label" style="margin-bottom:4px;">Temporary ID</div>
                <div style="font-size:20px;font-weight:800;color:#2563eb;letter-spacing:1px;"><?= htmlspecialchars($data['temp_id'] ?: 'PENDING') ?></div>
              </div>

              <div class="detail-label" style="margin-bottom:6px;">1. Enrollment Status</div>
              <?php
                $eStatus = strtolower($data['status'] ?? 'pending');
                $eClass  = in_array($eStatus, ['verified','approved']) ? 'status-approved' : (($eStatus === 'rejected') ? 'status-rejected' : 'status-pending');
              ?>
              <div class="status-box <?= $eClass ?>" style="margin-bottom:16px;">
                <div style="font-weight:700;font-size:13px;"><?= strtoupper($data['status'] ?? 'PENDING') ?></div>
              </div>

              <div class="detail-label" style="margin-bottom:6px;">2. Safety Training</div>
              <?php
                $tStatus = strtolower($data['training_status'] ?? 'pending');
                $tClass  = in_array($tStatus, ['pass','passed','approved']) ? 'status-approved' : (($tStatus === 'failed') ? 'status-rejected' : 'status-pending');
              ?>
              <div class="status-box <?= $tClass ?>" style="margin-bottom:6px;">
                <div style="font-weight:700;font-size:13px;"><?= strtoupper($data['training_status'] ?? 'PENDING') ?></div>
                <?php if (!empty($data['request_status'])): ?>
                  <div style="margin-top:4px;font-size:11px;opacity:0.85;">Training Request: <?= strtoupper($data['request_status']) ?></div>
                <?php endif; ?>
              </div>

              <div style="margin-top:20px;padding-top:14px;border-top:1px solid #e2e8f0;">
                <a href="enrolled_workers.php" class="btn btn-outline"
                   style="width:100%;text-align:center;display:block;font-size:13px;">
                  <i class="fas fa-arrow-left"></i> Back to Enrolled Workers
                </a>
              </div>
            </div>
          </div>
        </div><!-- end right col -->

      </div><!-- end body grid -->
    </div><!-- end profile-container -->
    <?php
}

renderLayout("Worker Details", 'renderContent', $role, $name);
?>
