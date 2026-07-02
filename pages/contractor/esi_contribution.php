<?php
// pages/contractor/esi_contribution.php
require_once '../../include/auth.php';
checkAuth(['contractor', 'welfare_user', 'welfare_admin', 'welfare', 'admin', 'super_admin']);
include '../../include/config.php';
include '../../include/layout.php';
include '../../include/compliance_schema.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';
$user_id = $_SESSION['user_id'];

ensureComplianceSchema($conn);

function certificateRadio($key) {
    $safe = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
    return '<div class="certificate-radio">'
        . '<label><input type="radio" name="answers[' . $safe . ']" value="YES" required onchange="toggleCertificateDetails(\'' . $safe . '\')"> YES</label>'
        . '<label><input type="radio" name="answers[' . $safe . ']" value="NO" required onchange="toggleCertificateDetails(\'' . $safe . '\')"> NO</label>'
        . '</div>';
}

function certificateDetail($key, $html) {
    return '<div class="compliance-item-details" data-detail-for="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '">' . $html . '</div>';
}
function renderContent() {
    global $conn, $user_id, $role;

    $contractorList = [];
    if ($role === 'contractor') {
        $contractor = db_single($conn, "SELECT * FROM contractors WHERE user_id = ?", 'i', [$user_id]);
        $c_id = $contractor['id'] ?? null;
    } else {
        $c_id = isset($_GET['contractor_id']) ? intval($_GET['contractor_id']) : null;
        $contractorList = db_fetch_all($conn, "SELECT id, vendor_code, contractor_name FROM contractors WHERE status = 'approved' ORDER BY contractor_name ASC");
    }

    if (!$c_id && $role === 'contractor') {
        echo '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i><div>Complete contractor registration first.</div></div>';
        return;
    }

    $contractorDetails = [];
    $latestAnnexure2a = [];
    $certificateHistory = [];
    if ($c_id) {
        $contractorDetails = db_single($conn, "SELECT * FROM contractors WHERE id = ?", 'i', [$c_id]) ?: [];
        $latestAnnexure2a = db_single($conn, "SELECT * FROM annexure2a WHERE contractor_id = ? ORDER BY updated_at DESC, id DESC LIMIT 1", 'i', [$c_id]) ?: [];
        $certificateHistory = db_fetch_all($conn, "SELECT * FROM compliance_certificates WHERE contractor_id = ? ORDER BY created_at DESC, id DESC", 'i', [$c_id]);
    }

    // Fetch ESI submissions from compliance table
    $history = [];
    if ($c_id) {
        $history = db_fetch_all($conn, "
            SELECT c.*, ce.challan_date, ce.employer_contribution, ce.employee_contribution 
            FROM compliance c
            LEFT JOIN compliance_esi ce ON ce.compliance_id = c.id
            WHERE c.contractor_id = ? AND c.type = 'esi'
            ORDER BY c.month_year DESC
        ", 'i', [$c_id]);
    }

    $months = [
        '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr', '05' => 'May', '06' => 'Jun',
        '07' => 'Jul', '08' => 'Aug', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec'
    ];
    ?>
    <style>
        .esi-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .flow-warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fffbeb 100%);
            border: 1px solid #f59e0b;
            border-left: 5px solid #d97706;
            color: #b45309;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 4px 6px -1px rgba(217, 119, 6, 0.05);
        }
        .form-grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap:14px; }
        .span-2 { grid-column: span 2; }
        .error-list {
            margin: 8px 0 0 0;
            padding-left: 20px;
            font-size: 11px;
            color: #ef4444;
            text-align: left;
        }
        .error-list li {
            margin-bottom: 3px;
        }
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
        .modal-overlay.show { display: flex !important; }
        .modal-box {
            background: #fff;
            border-radius: 14px;
            max-width: 650px;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .modal-header {
            padding: 16px 24px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-title { margin: 0; font-size: 16px; font-weight: 700; color: #0f172a; }
        .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #94a3b8; line-height: 1; }
        .modal-body { padding: 24px; max-height: 75vh; overflow-y: auto; }
        .modal-footer { padding: 14px 24px; border-top: 1px solid #e2e8f0; background: #f8fafc; display: flex; justify-content: flex-end; gap: 10px; }
        .details-expander {
            cursor: pointer;
            color: #2563eb;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-top: 4px;
        }
        .details-expander:hover { text-decoration: underline; }
        .hidden-details { display: none; margin-top: 8px; border-top: 1px dashed #e2e8f0; padding-top: 8px; }
        .compliance-section {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            margin-bottom: 20px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .compliance-section-title {
            background: #f8fafc;
            padding: 14px 20px;
            font-size: 13px;
            font-weight: 800;
            color: #1e3a8a;
            border-bottom: 1px solid #e2e8f0;
            text-transform: uppercase;
        }
        .compliance-item {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .compliance-item:last-child { border-bottom: none; }
        .compliance-item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }
        .compliance-item-question {
            display: flex;
            gap: 10px;
            font-size: 13px;
            color: #334155;
            font-weight: 600;
            line-height: 1.5;
            flex: 1;
        }
        .item-letter {
            font-weight: 700;
            color: #64748b;
            min-width: 20px;
        }
        .compliance-item-controls {
            flex-shrink: 0;
            width: 140px;
        }
        .certificate-radio {
            display: flex;
            gap: 8px;
        }
        .certificate-radio label {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            height: 36px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #f8fafc;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        .certificate-radio label:hover {
            background: #f1f5f9;
        }
        .certificate-radio label:has(input:checked) {
            border-color: #2563eb;
            background: #eff6ff;
            color: #1d4ed8;
            box-shadow: 0 0 0 2px rgba(37,99,235,0.1);
        }
        .certificate-radio input[type="radio"] {
            display: none;
        }
        .compliance-item-details {
            background: #f8fafc;
            border-radius: 8px;
            padding: 14px;
            border: 1px dashed #cbd5e1;
            display: none;
        }
        .compliance-item-details.show {
            display: block;
            animation: fadeInDown 0.3s ease;
        }
        .certificate-error { border-color:#ef4444 !important; }
        .details-grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
        .details-grid-3 { display:grid; grid-template-columns: repeat(3, 1fr); gap:12px; }
        @media (max-width: 768px) {
            .compliance-item-header { flex-direction: column; }
            .compliance-item-controls { width: 100%; }
            .details-grid-2, .details-grid-3 { grid-template-columns: 1fr; }
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <!-- Prominent Muster Roll Sign & Upload Warning -->
    <div class="flow-warning">
        <i class="fas fa-exclamation-circle" style="font-size:20px; color:#d97706;"></i>
        <div>When muster roll is downloaded, sign &amp; upload.</div>
    </div>

    <div class="card glass" style="margin-bottom: 24px;">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <div class="card-title">ESI Contribution</div>
            <div style="display:flex; gap:10px;">
                <button class="btn btn-outline" style="border-radius:20px; border:1px solid #94a3b8;" onclick="openUploadModal()"><i class="fas fa-plus"></i> Add</button>
                <button class="btn btn-outline" style="border-radius:20px; border:1px solid #94a3b8;" onclick="toggleHistory()"><i class="fas fa-history"></i> View History</button>
            </div>
        </div>
        <div class="card-body">
            <div style="display:flex; gap:16px; align-items:center; margin-bottom: 20px;">
                <label class="form-label" style="margin:0; font-weight:bold;">Select Wage Month</label>
                <select class="form-control" id="wage_month_m" style="width:120px;">
                    <?php 
                    $months_list = ['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Aug','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dec'];
                    $cm_s = date('m');
                    foreach($months_list as $k=>$v) echo "<option value='$k' ".($cm_s==$k?'selected':'').">$v</option>";
                    ?>
                </select>
                <select class="form-control" id="wage_month_y" style="width:100px;">
                    <?php
                    $cy_s = date('Y');
                    for($i=2020; $i<=2030; $i++) echo "<option value='$i' ".($cy_s==$i?'selected':'').">$i</option>";
                    ?>
                </select>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Sl No</th>
                        <th>Wage Month</th>
                        <th style="text-align:center;">Download Monthly Contribution of ESI</th>
                        <th style="text-align:center;">Upload ESI Return</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1.</td>
                        <td><span id="esi_display_month"><?= date('M Y') ?></span></td>
                        <td style="text-align:center;">
                            <button class="btn btn-outline" style="border:1px solid #94a3b8; color:#334155; padding:6px 14px;" onclick="downloadESITemplate()">Download Monthly Contribution of ESI</button>
                        </td>
                        <td style="text-align:center;">
                            <button class="btn btn-outline" style="border:1px solid #94a3b8; color:#334155; padding:6px 14px;" onclick="openUploadModal()">Upload ESI Challan</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>


    <?php
        $certVendorCode = $contractorDetails['vendor_code'] ?? '';
        $certContractorName = $contractorDetails['contractor_name'] ?? ($contractorDetails['vendor_name'] ?? $name);
        $certProject = $latestAnnexure2a['project_name'] ?? ($contractorDetails['work_awarding_department'] ?? '');
        $certWorkOrder = $contractorDetails['po_number'] ?? ($contractorDetails['pwo_number'] ?? '');
        $certTotalWorkmen = $latestAnnexure2a['workers_proposed_to_be_engaged'] ?? ($contractorDetails['workers_proposed_to_be_engaged'] ?? '');
        $certEpfCode = $latestAnnexure2a['epf_code'] ?? ($contractorDetails['epf_code'] ?? '');
        $certEsicCode = $latestAnnexure2a['esic_code'] ?? ($contractorDetails['esi_code'] ?? '');
        $certPolicyNo = $latestAnnexure2a['ecp_number'] ?? ($contractorDetails['ecp_number'] ?? '');
        $certPolicyFrom = $latestAnnexure2a['ecp_valid_from'] ?? ($contractorDetails['ecp_valid_from'] ?? '');
        $certPolicyTo = $latestAnnexure2a['ecp_valid_to'] ?? ($contractorDetails['ecp_valid_to'] ?? '');
        $certLicenseNo = $latestAnnexure2a['license_no'] ?? ($contractorDetails['license_no'] ?? '');
    ?>
    <div class="card glass" style="margin-bottom:24px;">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
            <div>
                <div class="card-title">Certificate of Compliance from Contractor</div>
                <div style="font-size:12px;color:#64748b;margin-top:3px;">FOR COMPLIANCE OF PROVISIONS OF VARIOUS LABOUR ENACTMENTS</div>
            </div>
            <div style="display:flex; gap:10px;">
                <button class="btn btn-outline" style="border-radius:20px; border:1px solid #94a3b8;" onclick="openCertificateModal()"><i class="fas fa-file-signature"></i> Add Certificate</button>
                <button class="btn btn-outline" style="border-radius:20px; border:1px solid #94a3b8;" onclick="toggleCertificateHistory()"><i class="fas fa-history"></i> View Certificate History</button>
            </div>
        </div>
        <div class="card-body" id="certificateHistorySection" style="display:none; padding:0;">
            <table class="data-table">
                <thead><tr><th>Sl No</th><th>Date</th><th>Period of Work</th><th>Total Number of Workmen</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if (empty($certificateHistory)): ?>
                        <tr><td colspan="5" style="text-align:center; padding:24px; color:#64748b;">No Certificate of Compliance submissions found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($certificateHistory as $i => $cert): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars(date('d M Y', strtotime($cert['certificate_date']))) ?></td>
                                <td><?= htmlspecialchars(date('d M Y', strtotime($cert['period_from'])) . ' to ' . date('d M Y', strtotime($cert['period_to']))) ?></td>
                                <td><?= htmlspecialchars((string)$cert['total_workmen']) ?></td>
                                <td><span class="badge badge-info"><?= htmlspecialchars(strtoupper($cert['status'] ?? 'submitted')) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="certificateModal" class="modal-overlay" onclick="handleCertificateOverlayClick(event)">
        <div class="modal-box" style="max-width:1100px;">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-file-signature" style="color:#1e3a8a;margin-right:6px"></i> Certificate of Compliance from Contractor</h3>
                <button class="modal-close" onclick="closeCertificateModal()">&times;</button>
            </div>
            <form id="certificateComplianceForm">
                <div class="modal-body">
                    <div style="text-align:center;font-weight:800;color:#0f172a;margin-bottom:4px;">CERTIFICATE OF COMPLIANCE FROM CONTRACTOR</div>
                    <div style="text-align:center;font-weight:700;color:#334155;margin-bottom:18px;font-size:12px;">(FOR COMPLIANCE OF PROVISIONS OF VARIOUS LABOUR ENACTMENTS)</div>
                    <div class="form-grid-2" style="margin-bottom:18px;">
                        <div class="form-group"><label class="form-label required">VENDOR CODE</label><input class="form-control" name="vendor_code" value="<?= htmlspecialchars($certVendorCode) ?>" required></div>
                        <div class="form-group"><label class="form-label required">PROJECT</label><input class="form-control" name="project" value="<?= htmlspecialchars($certProject) ?>" required></div>
                        <div class="form-group span-2"><label class="form-label required">NAME OF CONTRACTOR</label><input class="form-control" name="contractor_name" value="<?= htmlspecialchars($certContractorName) ?>" required></div>
                        <div class="form-group"><label class="form-label required">WORK ORDER NO.</label><input class="form-control" name="work_order_no" value="<?= htmlspecialchars($certWorkOrder) ?>" required></div>
                        <div class="form-group"><label class="form-label required">DATE</label><input type="date" class="form-control" name="certificate_date" value="<?= date('Y-m-d') ?>" required></div>
                        <div class="form-group"><label class="form-label required">PERIOD OF WORK FROM</label><input type="date" class="form-control" name="period_from" required></div>
                        <div class="form-group"><label class="form-label required">TO</label><input type="date" class="form-control" name="period_to" required></div>
                        <div class="form-group span-2"><label class="form-label required">TOTAL NUMBER OF WORKMEN</label><input type="number" min="0" step="1" class="form-control" name="total_workmen" value="<?= htmlspecialchars((string)$certTotalWorkmen) ?>" required></div>
                    </div>

<?php
function complianceItem($letter, $question, $key, $detailsHtml = '') {
    $radioHtml = certificateRadio($key);
    $detailWrap = $detailsHtml ? certificateDetail($key, $detailsHtml) : '';
    
    // For standard remarks if no custom details HTML provided
    if (!$detailsHtml && in_array($key, [
        'pf_contribution_due', 'esic_contribution_due', 'gratuity_maternity',
        'wages_paid_time', 'wage_slip_issued', 'bank_digital_payment', 'wages_by_7th', 'attendance_records', 'bonus_paid',
        'ppe_provided', 'safety_training', 'accident_register', 'welfare_facilities', 'annual_medical', 'inter_state_migrant', 'migrant_requirements',
        'appointment_letters', 'service_records', 'industrial_dispute', 'klwf_deposit'
    ])) {
        $detailWrap = certificateDetail($key, '<div class="form-group mb-0" style="margin-bottom:0;"><label class="form-label" style="font-size:11px;">Remarks</label><input class="form-control form-control-sm" name="details['.$key.'][remarks]" placeholder="Any remarks..."></div>');
    }

    return '
    <div class="compliance-item">
        <div class="compliance-item-header">
            <div class="compliance-item-question">
                <span class="item-letter">'.$letter.')</span>
                <span>'.$question.'</span>
            </div>
            <div class="compliance-item-controls">
                '.$radioHtml.'
            </div>
        </div>
        '.$detailWrap.'
    </div>';
}
?>

                    <div class="compliance-section">
                        <div class="compliance-section-title">1. CODE ON SOCIAL SECURITY, 2020</div>
                        <?= complianceItem('a', 'Whether establishment is registered under EPFO (Auto generated) If YES EPF Code, If NO Reason', 'epfo_registered', '<div class="details-grid-2"><div class="form-group mb-0" style="margin-bottom:0;"><label class="form-label required" style="font-size:11px;">EPF Code (If YES)</label><input class="form-control form-control-sm" name="details[epfo_registered][epf_code]" placeholder="EPF Code" value="' . htmlspecialchars($certEpfCode) . '"></div><div class="form-group mb-0" style="margin-bottom:0;"><label class="form-label required" style="font-size:11px;">Reason (If NO)</label><input class="form-control form-control-sm" name="details[epfo_registered][reason]" placeholder="Reason"></div></div>') ?>
                        <?= complianceItem('b', 'PF contributions remitted within prescribed due dates', 'pf_contribution_due') ?>
                        <?= complianceItem('c', 'Whether establishment is registered under ESIC (Auto generated) If YES ESIC Code, If NO Reason', 'esic_registered', '<div class="details-grid-2"><div class="form-group mb-0" style="margin-bottom:0;"><label class="form-label required" style="font-size:11px;">ESIC Code (If YES)</label><input class="form-control form-control-sm" name="details[esic_registered][esic_code]" placeholder="ESIC Code" value="' . htmlspecialchars($certEsicCode) . '"></div><div class="form-group mb-0" style="margin-bottom:0;"><label class="form-label required" style="font-size:11px;">Reason (If NO)</label><input class="form-control form-control-sm" name="details[esic_registered][reason]" placeholder="Reason"></div></div>') ?>
                        <?= complianceItem('d', 'ESIC contributions remitted within prescribed due dates', 'esic_contribution_due') ?>
                        <?= complianceItem('e', 'Whether Emp.Comp. Policy exist (In case not covered under ESIC) If YES, Policy No, If NO Reason Policy Valid from to', 'emp_comp_policy', '<div class="form-group" style="margin-bottom:12px;"><label class="form-label required" style="font-size:11px;">Policy No (If YES)</label><input class="form-control form-control-sm" name="details[emp_comp_policy][policy_no]" placeholder="Policy No" value="' . htmlspecialchars($certPolicyNo) . '"></div><div class="details-grid-3"><div class="form-group mb-0" style="margin-bottom:0;"><label class="form-label required" style="font-size:11px;">Valid From (If YES)</label><input type="date" class="form-control form-control-sm" name="details[emp_comp_policy][valid_from]" value="' . htmlspecialchars($certPolicyFrom) . '"></div><div class="form-group mb-0" style="margin-bottom:0;"><label class="form-label required" style="font-size:11px;">Valid To (If YES)</label><input type="date" class="form-control form-control-sm" name="details[emp_comp_policy][valid_to]" value="' . htmlspecialchars($certPolicyTo) . '"></div><div class="form-group mb-0" style="margin-bottom:0;"><label class="form-label required" style="font-size:11px;">Reason (If NO)</label><input class="form-control form-control-sm" name="details[emp_comp_policy][reason]" placeholder="Reason"></div></div>') ?>
                        <?= complianceItem('h', 'Whether Payment of Gratuity/Maternity (Wherever applicable) processed', 'gratuity_maternity') ?>
                    </div>

                    <div class="compliance-section">
                        <div class="compliance-section-title">2. CODE ON WAGES, 2019</div>
                        <?= complianceItem('a', 'Whether wages paid within time limit as notified by Central/State Govt. as per Skilled/Semi Skilled/Unskilled', 'wages_paid_time') ?>
                        <?= complianceItem('b', 'Whether Wage Slip issued to all workers', 'wage_slip_issued') ?>
                        <?= complianceItem('c', 'Whether payment made through Bank/Digital mode', 'bank_digital_payment') ?>
                        <?= complianceItem('d', 'Payment of wages by 7th of each month', 'wages_by_7th') ?>
                        <?= complianceItem('e', 'Attendance records maintained', 'attendance_records') ?>
                        <?= complianceItem('f', 'Whether statutory Bonus paid to all workers', 'bonus_paid') ?>
                    </div>

                    <div class="compliance-section">
                        <div class="compliance-section-title">3. OCCUPATIONAL SAFETY, HEALTH &amp; WORKING CONDITIONS CODE, 2020</div>
                        <?= complianceItem('a', 'Valid Labour License obtained (If Number of workers 50 and above) If YES License No.', 'labour_license', '<div class="details-grid-2"><div class="form-group mb-0" style="margin-bottom:0;"><label class="form-label required" style="font-size:11px;">License No. (If YES)</label><input class="form-control form-control-sm" name="details[labour_license][license_no]" placeholder="License No." value="' . htmlspecialchars($certLicenseNo) . '"></div><div class="form-group mb-0" style="margin-bottom:0;"><label class="form-label" style="font-size:11px;">Remarks</label><input class="form-control form-control-sm" name="details[labour_license][remarks]" placeholder="Remarks"></div></div>') ?>
                        <?= complianceItem('b', 'PPE provided to all workers', 'ppe_provided') ?>
                        <?= complianceItem('c', 'Safety Training/Toobox Talk conducted regularly', 'safety_training') ?>
                        <?= complianceItem('d', 'Accident Register maintained', 'accident_register') ?>
                        <?= complianceItem('e', 'Whether compliance relating to drinking water, washing facilities, toilets, rest rooms, First Aid, canteen facilities (If Number of workers 100 and above) provided', 'welfare_facilities') ?>
                        <?= complianceItem('f', 'Whether Annual Medical Checkup carried out for workers of 40 yrs age and above', 'annual_medical') ?>
                        <?= complianceItem('f', 'Whether any Inter State Migrant engaged', 'inter_state_migrant') ?>
                        <?= complianceItem('g', 'If Yes, Whether statutory requirements for Migrant workers provided (Annual Travel Allowances)', 'migrant_requirements') ?>
                    </div>

                    <div class="compliance-section">
                        <div class="compliance-section-title">4. INDUSTRIAL RELATIONS CODE, 2020</div>
                        <?= complianceItem('a', 'Appointment Letters issued to all workers', 'appointment_letters') ?>
                        <?= complianceItem('b', 'Service records maintained properly', 'service_records') ?>
                        <?= complianceItem('c', 'Any illegal strike/lockout/Industrial Dispute pending', 'industrial_dispute') ?>
                    </div>

                    <div class="compliance-section">
                        <div class="compliance-section-title">5. KERALA LABOUR WELFARE FUND ACT, 1975</div>
                        <?= complianceItem('a', 'KLWF Registration No: (If no, reason)', 'klwf_registration', '<div class="details-grid-2"><div class="form-group mb-0" style="margin-bottom:0;"><label class="form-label required" style="font-size:11px;">Registration No. (If YES)</label><input class="form-control form-control-sm" name="details[klwf_registration][registration_no]" placeholder="KLWF Registration No."></div><div class="form-group mb-0" style="margin-bottom:0;"><label class="form-label required" style="font-size:11px;">Reason (If NO)</label><input class="form-control form-control-sm" name="details[klwf_registration][reason]" placeholder="Reason"></div></div>') ?>
                        <?= complianceItem('b', 'Deposit of deductions paid on or before 10th January and 10th July of every year', 'klwf_deposit') ?>
                    </div>

                    <div class="form-check" style="margin-top:16px;">
                        <input class="form-check-input" type="checkbox" name="declaration_accepted" id="declaration_accepted" required>
                        <label class="form-check-label" for="declaration_accepted">I hereby certify that the above information provided is correct. That in the event of default of any or all the above compliances, I will be liable &amp; responsible for the same at my own risk &amp; cost.</label>
                    </div>
                    <div id="certificateErrorBox" class="alert alert-danger" style="display:none; margin-top:14px;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeCertificateModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="certificateSubmitBtn">Submit Certificate</button>
                </div>
            </form>
        </div>
    </div>
    <!-- History Section -->
    <div class="card glass" id="historySection" style="display:none;">
        <div class="card-header"><div class="card-title">History</div></div>
        <div class="card-body" style="padding:0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Sl No</th>
                        <th>Wage Month</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                        <tr><td colspan="2" style="text-align:center; padding:30px; color:#64748b;">No ESI contribution submissions found.</td></tr>
                    <?php else: ?>
                        <?php 
                        $idx = 1;
                        foreach ($history as $row): 
                            $my = explode('-', $row['month_year']);
                            $monthText = isset($months[$my[1] ?? '']) ? ($months[$my[1]] . ' ' . $my[0]) : $row['month_year'];
                        ?>
                            <tr>
                                <td><?= $idx++ ?></td>
                                <td><?= htmlspecialchars($monthText) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Notes Section -->
    <div style="margin-top:24px; padding:16px; background:rgba(255,255,255,0.03); border:1px solid var(--border-color); border-radius:10px; font-size:13px; color:var(--text-muted); line-height:2;">
        <div><strong>Notes:</strong></div>
        <div>① Link compliance → ESI number</div>
        <div>② Name → Full Name</div>
        <div>③ No of days = No of days for which "Total days" from muster roll against this employee for the month</div>
        <div>④ Total monthly wage = Total days × Rate from muster roll</div>
    </div>

    <!-- Upload Challan Modal -->
    <div id="uploadChallanModal" class="modal-overlay" onclick="handleOverlayClick(event)">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-file-invoice" style="color:#ef4444;margin-right:6px"></i> Upload ESI Contribution Challan</h3>
                <button class="modal-close" onclick="closeUploadModal()">&times;</button>
            </div>
            <form id="esiContributionForm">
                <input type="hidden" name="type" value="esi">
                <div class="modal-body">
                    <div class="form-grid-2">
                        <div class="form-group span-2">
                            <label class="form-label required">Contribution Month</label>
                            <input type="month" class="form-control" name="contribution_month" id="modal_wage_month" required onchange="fetchMonthDetails(this.value)">
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Challan Number</label>
                            <input type="text" class="form-control" name="challan_no" required placeholder="ESI Challan Ref No.">
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Challan Date</label>
                            <input type="date" class="form-control" name="challan_date" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label required">No. of Employees</label>
                            <input type="number" class="form-control" name="employees_count" id="esi_employees" required readonly style="background:#f1f5f9; cursor:not-allowed;">
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Total Gross Wages (₹)</label>
                            <input type="number" class="form-control" name="gross_wages" id="esi_gross" required readonly style="background:#f1f5f9; cursor:not-allowed;">
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Employer Contribution (3.25% - ₹)</label>
                            <input type="number" class="form-control" name="employer_contribution" id="esi_employer" required readonly style="background:#f1f5f9; cursor:not-allowed;">
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Employee Contribution (0.75% - ₹)</label>
                            <input type="number" class="form-control" name="employee_contribution" id="esi_employee" required readonly style="background:#f1f5f9; cursor:not-allowed;">
                        </div>
                        <div class="form-group span-2">
                            <label class="form-label required">Total Contribution (4% - ₹)</label>
                            <input type="number" class="form-control" name="total_contribution" id="esi_total" required readonly style="background:#f1f5f9; cursor:not-allowed; font-weight:700; color:#10b981;">
                        </div>
                        <div class="form-group span-2">
                            <label class="form-label required">Upload Monthly Contribution Details PDF (From ESI Portal)</label>
                            <input type="file" class="form-control" name="challan_file" accept=".pdf" required>
                            <small class="form-hint">Upload the PDF downloaded from the ESI site after payment.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeUploadModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="esiSubmitBtn">Submit Challan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleHistory() {
            const sec = document.getElementById('historySection');
            sec.style.display = sec.style.display === 'none' ? 'block' : 'none';
        }

        function toggleDetails(expander) {
            const container = expander.nextElementSibling;
            const icon = expander.querySelector('i');
            if (container.style.display === 'block') {
                container.style.display = 'none';
                icon.className = 'fas fa-chevron-circle-down';
            } else {
                container.style.display = 'block';
                icon.className = 'fas fa-chevron-circle-up';
            }
        }

        // Update display month label when dropdowns change
        function updateDisplayMonth() {
            const m = document.getElementById('wage_month_m');
            const y = document.getElementById('wage_month_y');
            if (m && y) {
                const months = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                const mLabel = months[parseInt(m.value)] || '';
                const el = document.getElementById('esi_display_month');
                if (el) el.textContent = mLabel + ' ' + y.value;
            }
        }
        document.getElementById('wage_month_m')?.addEventListener('change', updateDisplayMonth);
        document.getElementById('wage_month_y')?.addEventListener('change', updateDisplayMonth);

        function downloadESITemplate() {
            const m = document.getElementById('wage_month_m').value;
            const y = document.getElementById('wage_month_y').value;
            const monthVal = y + '-' + m;
            <?php if ($role !== 'contractor'): ?>
            window.location.href = `../../api/contractor/download_esi_contribution.php?month_year=${monthVal}`;
            <?php else: ?>
            window.location.href = `../../api/contractor/download_esi_contribution.php?month_year=${monthVal}`;
            <?php endif; ?>
        }

        function openUploadModal() {
            const m = document.getElementById('wage_month_m').value;
            const y = document.getElementById('wage_month_y').value;
            const monthVal = y + '-' + m;
            document.getElementById('modal_wage_month').value = monthVal;
            document.getElementById('uploadChallanModal').classList.add('show');
            fetchMonthDetails(monthVal);
        }

        function closeUploadModal() {
            document.getElementById('uploadChallanModal').classList.remove('show');
        }

        function handleOverlayClick(e) {
            if (e.target.id === 'uploadChallanModal') closeUploadModal();
        }
        function toggleCertificateHistory() {
            const sec = document.getElementById('certificateHistorySection');
            if (sec) sec.style.display = sec.style.display === 'none' ? 'block' : 'none';
        }

        function openCertificateModal() {
            document.getElementById('certificateModal').classList.add('show');
        }

        function closeCertificateModal() {
            document.getElementById('certificateModal').classList.remove('show');
        }

        function handleCertificateOverlayClick(e) {
            if (e.target.id === 'certificateModal') closeCertificateModal();
        }

        function certificateAnswer(key) {
            return document.querySelector(`input[name="answers[${key}]"]:checked`)?.value || '';
        }

        function setCertificateRequired(selector, required) {
            document.querySelectorAll(selector).forEach(el => {
                el.required = required;
                if (!required) el.classList.remove('certificate-error');
                
                // Also hide/show the parent form-group
                const formGroup = el.closest('.form-group');
                if (formGroup) {
                    formGroup.style.display = required ? 'block' : 'none';
                }
            });
        }

        function toggleCertificateDetails(key) {
            const wrap = document.querySelector(`[data-detail-for="${key}"]`);
            if (wrap) wrap.classList.add('show');
            const answer = certificateAnswer(key);

            if (key === 'epfo_registered') {
                setCertificateRequired('input[name="details[epfo_registered][epf_code]"]', answer === 'YES');
                setCertificateRequired('input[name="details[epfo_registered][reason]"]', answer === 'NO');
            }
            if (key === 'esic_registered') {
                setCertificateRequired('input[name="details[esic_registered][esic_code]"]', answer === 'YES');
                setCertificateRequired('input[name="details[esic_registered][reason]"]', answer === 'NO');
            }
            if (key === 'emp_comp_policy') {
                setCertificateRequired('input[name="details[emp_comp_policy][policy_no]"]', answer === 'YES');
                setCertificateRequired('input[name="details[emp_comp_policy][valid_from]"]', answer === 'YES');
                setCertificateRequired('input[name="details[emp_comp_policy][valid_to]"]', answer === 'YES');
                setCertificateRequired('input[name="details[emp_comp_policy][reason]"]', answer === 'NO');
            }
            if (key === 'labour_license') {
                setCertificateRequired('input[name="details[labour_license][license_no]"]', answer === 'YES');
            }
            if (key === 'klwf_registration') {
                setCertificateRequired('input[name="details[klwf_registration][registration_no]"]', answer === 'YES');
                setCertificateRequired('input[name="details[klwf_registration][reason]"]', answer === 'NO');
            }
        }

        document.querySelectorAll('#certificateComplianceForm input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', () => {
                const match = radio.name.match(/^answers\[(.+)\]$/);
                if (match) toggleCertificateDetails(match[1]);
            });
        });

        document.getElementById('certificateComplianceForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const errorBox = document.getElementById('certificateErrorBox');
            errorBox.style.display = 'none';
            errorBox.innerHTML = '';

            ['epfo_registered', 'esic_registered', 'emp_comp_policy', 'labour_license', 'klwf_registration'].forEach(toggleCertificateDetails);
            if (!this.checkValidity()) {
                this.reportValidity();
                return;
            }

            const btn = document.getElementById('certificateSubmitBtn');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

            try {
                const res = await fetch('../../api/contractor/save_compliance_certificate.php', {
                    method: 'POST',
                    body: new FormData(this)
                });
                const result = await res.json();
                if (result.success) {
                    Swal.fire({ icon: 'success', title: 'Submitted', text: result.message, confirmButtonColor: '#1e3a8a' }).then(() => location.reload());
                } else {
                    const errors = Array.isArray(result.errors) ? result.errors.map(e => e.message).join('<br>') : (result.message || 'Validation failed.');
                    errorBox.innerHTML = errors;
                    errorBox.style.display = 'block';
                    Swal.fire({ icon: 'error', title: 'Validation failed', html: errors, confirmButtonColor: '#ef4444' });
                }
            } catch (err) {
                Swal.fire('Error', 'Network or server error occurred.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });

        async function fetchMonthDetails(monthVal) {
            if (!monthVal) return;
            const contractorId = <?= json_encode($c_id) ?>;
            try {
                const response = await fetch('../../api/compliance/validate.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': window.CLMS_CSRF_TOKEN || ''
                    },
                    body: JSON.stringify({ contractor_id: contractorId, month_year: monthVal })
                });
                const result = await response.json();
                if (result.success && result.data) {
                    const data = result.data;
                    const wageTotal = parseFloat(data.wage_total) || 0;
                    const workerCount = parseInt(data.worker_count) || 0;

                    document.getElementById('esi_employees').value = workerCount;
                    document.getElementById('esi_gross').value = wageTotal.toFixed(2);
                    document.getElementById('esi_employer').value = (wageTotal * 0.0325).toFixed(2);
                    document.getElementById('esi_employee').value = (wageTotal * 0.0075).toFixed(2);
                    document.getElementById('esi_total').value = (wageTotal * 0.04).toFixed(2);
                }
            } catch (err) {
                console.error('Error fetching details:', err);
            }
        }

        document.getElementById('esiContributionForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('esiSubmitBtn');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing & Parsing PDF...';

            const fd = new FormData(this);
            try {
                const res = await fetch('../../api/contractor/save_compliance.php', {
                    method: 'POST',
                    body: fd
                });
                const result = await res.json();
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Submitted!',
                        text: 'ESI Contribution Challan uploaded and verified successfully.',
                        confirmButtonColor: '#1e3a8a'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Mismatch / Error',
                        text: result.message || 'Validation failed.',
                        confirmButtonColor: '#ef4444'
                    });
                }
            } catch (err) {
                Swal.fire('Error', 'Network or server error occurred.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    </script>
    <?php
}

renderLayout("ESI Contribution", 'renderContent', $role, $name);
?>
