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
    
    // Worker + Enrollment + Training
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
      <h2 class="page-title">Worker Profile & Action Center</h2>
      <p class="page-subtitle">Welfare Department - Approve Enrollment and Safety Training</p>
    </div>

    <style>
    .info-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }
    .info-table th {
      background: var(--gray-50);
      color: var(--gray-700);
      font-weight: 600;
      text-align: left;
      padding: 10px 12px;
      font-size: 13px;
      border-bottom: 1px solid var(--gray-200);
      width: 35%;
    }
    .info-table td {
      padding: 10px 12px;
      font-size: 13px;
      color: var(--gray-800);
      border-bottom: 1px solid var(--gray-200);
    }
    </style>
    <div style="display:grid;grid-template-columns:1.25fr 0.75fr;gap:24px;">
        <!-- Details Card -->
        <div style="display:flex; flex-direction:column; gap:24px;">
            <div class="card glass">
                <div class="card-header"><div class="card-title"><i class="fas fa-user"></i> Worker Profile Details</div></div>
                <div class="card-body">
                    <?php if (!empty($data['photo'])): 
                        $photoPath = strpos($data['photo'], '/') === false && strpos($data['photo'], '\\') === false
                            ? '../../uploads/workers/' . $data['photo']
                            : '../../' . ltrim($data['photo'], '/\\');
                    ?>
                    <div style="display:flex; gap: 20px; align-items: center; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
                        <img src="<?= htmlspecialchars($photoPath) ?>" alt="Worker Photo" style="width: 100px; height: 100px; border-radius: 8px; object-fit: cover; border: 2px solid var(--gray-200); box-shadow: var(--shadow-sm);">
                        <div>
                            <h3 style="margin: 0; font-size: 18px; color: var(--gray-800);"><?= htmlspecialchars($data['name'] ?? 'Unknown Worker') ?></h3>
                            <p style="margin: 5px 0 0 0; font-size: 13px; color: var(--gray-500);"><i class="fas fa-id-card"></i> Aadhaar: <?= htmlspecialchars($data['aadhaar'] ?? 'N/A') ?> &nbsp;|&nbsp; <i class="fas fa-building"></i> Contractor: <?= htmlspecialchars($data['contractor_name'] ?? 'N/A') ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
                        <!-- Personal Info -->
                        <div>
                            <h4 style="font-size:13px;color:var(--gray-500);margin-bottom:10px;border-bottom:1px solid #eee;padding-bottom:5px;">Personal Information</h4>
                            <table class="info-table">
                                <tr><th>Name</th><td><?= htmlspecialchars($data['name'] ?? 'N/A') ?></td></tr>
                                <tr><th>Father's Name</th><td><?= htmlspecialchars($data['father_name'] ?? 'N/A') ?></td></tr>
                                <tr><th>Gender / DOB</th><td><?= htmlspecialchars($data['gender'] ?? 'N/A') ?> / <?= htmlspecialchars($data['dob'] ?? 'N/A') ?></td></tr>
                                <tr><th>Marital Status</th><td><?= htmlspecialchars($data['marital_status'] ?? 'N/A') ?></td></tr>
                                <tr><th>Nationality</th><td><?= htmlspecialchars($data['nationality'] ?? 'N/A') ?></td></tr>
                                <tr><th>Blood Group</th><td><?= htmlspecialchars($data['blood_group'] ?? 'N/A') ?></td></tr>
                                <tr><th>Religion</th><td><?= htmlspecialchars($data['region'] ?? 'N/A') ?></td></tr>
                                <tr><th>PWD Status</th><td><?= htmlspecialchars($data['pwd_status'] ?? 'N/A') ?></td></tr>
                                <tr><th>Passport No</th><td><?= htmlspecialchars($data['passport_no'] ?: 'N/A') ?></td></tr>
                                <tr><th>Driving Licence No</th><td><?= htmlspecialchars($data['driving_licence_no'] ?: 'N/A') ?></td></tr>
                                <tr><th>Email ID</th><td><?= htmlspecialchars($data['email'] ?? $data['contact_email'] ?? 'N/A') ?></td></tr>
                            </table>
                        </div>

                        <!-- Contact & Address -->
                        <div>
                            <h4 style="font-size:13px;color:var(--gray-500);margin-bottom:10px;border-bottom:1px solid #eee;padding-bottom:5px;">Contact & Address Details</h4>
                            <table class="info-table">
                                <tr><th>Mobile</th><td><?= htmlspecialchars($data['mobile'] ?? 'N/A') ?></td></tr>
                                <tr><th>WhatsApp No</th><td><?= htmlspecialchars($data['whatsapp_no'] ?? 'N/A') ?></td></tr>
                                <tr><th>Emergency Contact</th><td><?= htmlspecialchars($data['emergency_contact'] ?? 'N/A') ?></td></tr>
                                <tr><th>Present Address</th><td><?= nl2br(htmlspecialchars($data['present_address'] ?? 'N/A')) ?></td></tr>
                                <tr><th>Permanent Address</th><td><?= nl2br(htmlspecialchars($data['permanent_address'] ?? 'N/A')) ?></td></tr>
                                <tr><th>State</th><td><?= htmlspecialchars($data['state'] ?? 'N/A') ?></td></tr>
                                <tr><th>District</th><td><?= htmlspecialchars($data['district'] ?? 'N/A') ?></td></tr>
                                <tr><th>Pincode</th><td><?= htmlspecialchars($data['pincode'] ?? 'N/A') ?></td></tr>
                            </table>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:24px;">
                        <!-- Job & Employment -->
                        <div>
                            <h4 style="font-size:13px;color:var(--gray-500);margin-bottom:10px;border-bottom:1px solid #eee;padding-bottom:5px;">Employment & Job Details</h4>
                            <table class="info-table">
                                <tr><th>Contractor</th><td><?= htmlspecialchars($data['contractor_name'] ?? 'N/A') ?></td></tr>
                                <tr><th>Work Order No</th><td><code><?= htmlspecialchars($data['work_order_no'] ?? 'N/A') ?></code></td></tr>
                                <tr><th>Project Name (WBS)</th><td><?= htmlspecialchars($data['project_name'] ?? 'N/A') ?></td></tr>
                                <tr><th>Department</th><td><?= htmlspecialchars($data['department'] ?? 'N/A') ?></td></tr>
                                <tr><th>Trade</th><td><?= htmlspecialchars($data['trade'] ?? 'N/A') ?></td></tr>
                                <tr><th>Skill Category</th><td><?= htmlspecialchars($data['skill_category'] ?? 'N/A') ?></td></tr>
                                <tr><th>Nature of Work</th><td><?= htmlspecialchars($data['nature_of_work'] ?? 'N/A') ?></td></tr>
                                <tr><th>Experience (Years)</th><td><?= htmlspecialchars($data['experience'] ?? '0') ?></td></tr>
                                <tr><th>Safety Language</th><td><?= htmlspecialchars($data['safety_language'] ?? 'N/A') ?></td></tr>
                                <tr><th>Executing Officer</th><td><?= htmlspecialchars($data['executing_officer_name'] ?? 'N/A') ?> (E-Code: <?= htmlspecialchars($data['executing_officer_code'] ?? 'N/A') ?>)</td></tr>
                            </table>
                        </div>

                        <!-- Statutory & Banking -->
                        <div>
                            <h4 style="font-size:13px;color:var(--gray-500);margin-bottom:10px;border-bottom:1px solid #eee;padding-bottom:5px;">Statutory & Banking Details</h4>
                            <table class="info-table">
                                <tr><th>Aadhaar Number</th><td><code><?= htmlspecialchars($data['aadhaar'] ?? 'N/A') ?></code></td></tr>
                                <tr><th>EPF Registered</th><td><?= htmlspecialchars($data['epf_registered_worker'] ?? 'N/A') ?></td></tr>
                                <tr><th>PF No / UAN</th><td><?= htmlspecialchars($data['pf_no'] ?: 'N/A') ?> / <?= htmlspecialchars($data['uan_number'] ?: 'N/A') ?></td></tr>
                                <tr><th>ESI Registered</th><td><?= htmlspecialchars($data['esi_registered_worker'] ?? 'N/A') ?></td></tr>
                                <tr><th>ESIC Number</th><td><?= htmlspecialchars($data['esic_number'] ?: 'N/A') ?></td></tr>
                                <tr><th>Bank Account No</th><td><?= htmlspecialchars($data['bank_account'] ?: 'N/A') ?></td></tr>
                                <tr><th>Bank IFSC Code</th><td><?= htmlspecialchars($data['ifsc'] ?: 'N/A') ?></td></tr>
                                <tr><th>Certified Wage Rate</th><td>INR <?= htmlspecialchars($data['certified_wage_rate'] ?? '0.00') ?></td></tr>
                                <tr><th>Payment Option</th><td><?= htmlspecialchars(str_replace('_', ' ', strtoupper($data['safety_fee_payment_option'] ?? 'N/A'))) ?></td></tr>
                            </table>
                        </div>
                    </div>

                    <div style="margin-top:24px;">
                        <h4 style="font-size:13px;color:var(--gray-500);margin-bottom:10px;border-bottom:1px solid #eee;padding-bottom:5px;">Worker Documents (Annexure 4A / 6A)</h4>
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
                            <?php 
                            // 1. Collect docs from workmen table (Enrollment 4A)
                            $displayDocs = [
                                'Aadhaar (4A)' => $data['aadhaar_doc'] ? ['path' => "../../uploads/workers/" . $data['aadhaar_doc'], 'type' => 'enrollment'] : null,
                                'Medical (4A)' => $data['medical_doc'] ? ['path' => "../../uploads/workers/" . $data['medical_doc'], 'type' => 'enrollment'] : null,
                                'Police Verification (4A)' => $data['police_doc'] ? ['path' => "../../uploads/workers/" . $data['police_doc'], 'type' => 'enrollment'] : null,
                                'Insurance (4A)' => $data['insurance_doc'] ? ['path' => "../../uploads/workers/" . $data['insurance_doc'], 'type' => 'enrollment'] : null,
                                'Education (4A)' => ($data['educational_doc'] ?: $data['education_doc']) ? ['path' => "../../uploads/workers/" . ($data['educational_doc'] ?: $data['education_doc']), 'type' => 'enrollment'] : null,
                                'Photo' => $data['photo'] ? ['path' => "../../uploads/workers/" . $data['photo'], 'type' => 'enrollment'] : null,
                                'Signature' => $data['signature_doc'] ? ['path' => "../../uploads/workers/" . $data['signature_doc'], 'type' => 'enrollment'] : null,
                                'Bank Proof' => $data['bank_doc'] ? ['path' => "../../uploads/workers/" . $data['bank_doc'], 'type' => 'enrollment'] : null,
                                'Gate Pass Doc' => $data['gatepass_doc'] ? ['path' => "../../uploads/workers/" . $data['gatepass_doc'], 'type' => 'enrollment'] : null,
                                'Skill Certificate' => $data['skill_cert_doc'] ? ['path' => "../../uploads/workers/" . $data['skill_cert_doc'], 'type' => 'enrollment'] : null,
                                'Training Approval' => $data['training_approval_doc'] ? ['path' => "../../uploads/workers/" . $data['training_approval_doc'], 'type' => 'enrollment'] : null,
                            ];

                            // 2. Fetch docs from documents table (Gate Pass 6A)
                            $otherDocsRes = $conn->query("SELECT document_type, file_path FROM documents WHERE workman_id = $id");
                            while($otherDocsRes && $row = $otherDocsRes->fetch_assoc()) {
                                $displayDocs[$row['document_type']] = [
                                    'path' => "../../uploads/documents/" . $row['file_path'],
                                    'type' => 'gatepass'
                                ];
                            }

                            foreach($displayDocs as $label => $doc): 
                                if ($doc === null && !in_array($label, ['Aadhaar (4A)', 'Medical (4A)', 'Police Verification (4A)', 'Photo'])) continue; // Only show essential docs if missing
                            ?>
                                <div class="doc-card" style="padding:12px;border:1px solid #eee;border-radius:8px;background:#f8fafc;display:flex;flex-direction:column;gap:8px;">
                                    <div style="display:flex;align-items:center;justify-content:space-between;">
                                        <span style="font-size:11px;font-weight:600;"><?= htmlspecialchars($label) ?></span>
                                        <?php if($doc && isset($doc['type'])): ?>
                                            <span class="badge" style="font-size:9px;padding:2px 6px;<?= $doc['type']==='gatepass' ? 'background:#dcfce7;color:#166534' : 'background:#f1f5f9;color:#475569' ?>">
                                                <?= $doc['type']==='gatepass' ? '6A Upload' : '4A Upload' ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if($doc): ?>
                                        <a href="<?= htmlspecialchars($doc['path']) ?>" target="_blank" class="btn btn-sm btn-outline" style="width:100%;text-align:center;">
                                            <i class="fas fa-external-link-alt"></i> View Document
                                        </a>
                                    <?php else: ?>
                                        <div class="text-danger" style="font-size:11px;text-align:center;padding:5px;border:1px dashed #fecaca;border-radius:4px;">
                                            <i class="fas fa-exclamation-triangle"></i> Missing
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Card -->
        <div>
            <!-- Status Sidebar -->
            <div class="card glass" style="margin-bottom:24px;">
                <div class="card-header"><div class="card-title"><i class="fas fa-info-circle"></i> Workflow Status</div></div>
                <div class="card-body">
                    <div class="info-row" style="display:flex;justify-content:space-between;margin-bottom:10px;">
                        <span>Temporary ID:</span>
                        <code class="text-primary"><?= htmlspecialchars($data['temp_id'] ?: 'Pending') ?></code>
                    </div>
                    <div class="info-row" style="display:flex;justify-content:space-between;margin-bottom:10px;">
                        <span>Enrollment:</span>
                        <span class="badge <?= (strtolower($data['status']??'')==='verified'||strtolower($data['status']??'')==='approved')?'badge-success':'badge-warning' ?>">
                            <?= strtoupper($data['status'] ?: 'Pending') ?>
                        </span>
                    </div>
                    <div class="info-row" style="display:flex;justify-content:space-between;">
                        <span>Safety Training:</span>
                        <span class="badge <?= (strtolower($data['training_status']??'')==='pass'||strtolower($data['training_status']??'')==='passed')?'badge-success':'badge-warning' ?>">
                            <?= strtoupper($data['training_status'] ?: 'Pending') ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

renderLayout("Worker Details", 'renderContent', $role, $name);
?>

