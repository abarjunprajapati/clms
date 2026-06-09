<?php
require_once '../../include/config.php';
require_once '../../include/auth_middleware.php';
require_once '../../include/layout.php';

require_role(['customer']);

$name = $_SESSION['customer_name'] ?? $_SESSION['name'] ?? 'Customer';
$customer_code = $_SESSION['customer_code'] ?? '';
$annexure_id = $_GET['id'] ?? '';

if (empty($annexure_id)) {
    $_SESSION['error'] = "Invalid Annexure ID";
    header("Location: dashboard.php");
    exit;
}

// 1. Fetch Annexure Data with Joins
$sql = "SELECT a.*, c.customer_name, v.vendor_name, v.gst_no, v.pf_no, v.esi_no, v.address as vendor_address,
               wo.project_name, wo.department
        FROM contractor_annexure3a a
        JOIN sap_customer_master c ON c.customer_code = a.customer_code
        JOIN sap_vendor_master v ON v.vendor_code = a.vendor_code
        LEFT JOIN work_orders wo ON wo.work_order_no = a.work_order_no AND wo.customer_code = a.customer_code
        WHERE a.id = ? AND a.customer_code = ?";

$data = db_single($conn, $sql, 'is', [$annexure_id, $customer_code]);

if (!$data) {
    $_SESSION['error'] = "Access Denied: Annexure not found or not mapped to you.";
    header("Location: dashboard.php");
    exit;
}

function renderContent() {
    global $data;
?>
<style>
    .form-view-container { max-width: 1000px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    .form-header { text-align: center; border-bottom: 2px solid #edf2f7; margin-bottom: 30px; padding-bottom: 20px; }
    .form-section { margin-bottom: 30px; border: 1px solid #edf2f7; border-radius: 8px; padding: 20px; }
    .form-section-title { font-size: 14px; font-weight: 800; color: #4a5568; text-transform: uppercase; margin-bottom: 20px; border-bottom: 1px solid #f7fafc; padding-bottom: 10px; display: flex; align-items: center; gap: 10px; }
    .data-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 15px; }
    .data-item label { display: block; font-size: 11px; font-weight: 700; color: #a0aec0; text-transform: uppercase; margin-bottom: 4px; }
    .data-item div { font-size: 14px; font-weight: 600; color: #2d3748; }
    .badge-status { font-size: 12px; padding: 6px 12px; border-radius: 20px; font-weight: 700; }
</style>

<div class="page-header no-print">
    <div class="header-content">
        <h1><i class="fas fa-file-invoice" style="color:#6366f1"></i> Customer Registration: Submission Review</h1>
        <p>Viewing submitted statutory compliance form for Work Order: <strong><?= $data['work_order_no'] ?></strong></p>
    </div>
    <div class="header-actions">
        <button class="btn btn-outline" onclick="window.print()"><i class="fas fa-print"></i> Print / PDF</button>
    </div>
</div>

<div class="form-view-container">
    <div class="form-header">
        <h2 style="margin:0; font-weight:800; color:#1a365d;">CUSTOMER REGISTRATION</h2>
        <div style="font-size:13px; color:#718096; margin-top:5px;">Statutory Compliance & Labour Onboarding Form</div>
        <div style="margin-top:15px;">
            <span class="badge-status" style="background:#ebf4ff; color:#3182ce;">ID: <?= $data['id'] ?></span>
            <span class="badge-status" style="background:<?= $data['status'] === 'approved' ? '#f0fff4; color:#38a169;' : '#fff5f5; color:#e53e3e;' ?>">
                STATUS: <?= strtoupper($data['status']) ?>
            </span>
        </div>
    </div>

    <!-- 1. CUSTOMER & WORK DETAILS -->
    <div class="form-section">
        <div class="form-section-title"><i class="fas fa-handshake"></i> Customer & Work Details</div>
        <div class="data-row">
            <div class="data-item">
                <label>Customer Name</label>
                <div><?= htmlspecialchars($data['customer_name']) ?></div>
            </div>
            <div class="data-item">
                <label>Customer Code</label>
                <div><?= $data['customer_code'] ?></div>
            </div>
            <div class="data-item">
                <label>Work Order No</label>
                <div><?= $data['work_order_no'] ?></div>
            </div>
        </div>
        <div class="data-row">
            <div class="data-item">
                <label>Project Name</label>
                <div><?= htmlspecialchars($data['project_name']) ?></div>
            </div>
            <div class="data-item">
                <label>Department</label>
                <div><?= htmlspecialchars($data['department']) ?></div>
            </div>
            <div class="data-item">
                <label>Submission Date</label>
                <div><?= date('d M Y, H:i', strtotime($data['created_at'])) ?></div>
            </div>
        </div>
    </div>

    <!-- 2. CONTRACTOR DETAILS -->
    <div class="form-section">
        <div class="form-section-title"><i class="fas fa-user-tie"></i> Contractor Information</div>
        <div class="data-row">
            <div class="data-item" style="grid-column: span 2;">
                <label>Contractor Name</label>
                <div><?= htmlspecialchars($data['vendor_name']) ?></div>
            </div>
            <div class="data-item">
                <label>Vendor Code</label>
                <div><?= $data['vendor_code'] ?></div>
            </div>
        </div>
        <div class="data-row">
            <div class="data-item">
                <label>GST Number</label>
                <div><?= $data['gst_no'] ?: 'N/A' ?></div>
            </div>
            <div class="data-item">
                <label>PF Code</label>
                <div><?= $data['pf_no'] ?: 'N/A' ?></div>
            </div>
            <div class="data-item">
                <label>ESI Code</label>
                <div><?= $data['esi_no'] ?: 'N/A' ?></div>
            </div>
        </div>
    </div>

    <!-- 3. MANPOWER & LICENSE -->
    <div class="form-section">
        <div class="form-section-title"><i class="fas fa-users"></i> Manpower & Statutory Details</div>
        <div class="data-row">
            <div class="data-item">
                <label>Total Workers</label>
                <div style="font-size:18px; color:#3182ce;"><?= $data['total_workers'] ?></div>
            </div>
            <div class="data-item">
                <label>Skilled / Semi / Unskilled</label>
                <div><?= $data['skilled_workers'] ?> / <?= $data['semi_skilled_workers'] ?> / <?= $data['unskilled_workers'] ?></div>
            </div>
            <div class="data-item">
                <label>Labour License No</label>
                <div><?= $data['labour_license_no'] ?: 'N/A' ?></div>
            </div>
        </div>
        <div class="data-row">
            <div class="data-item" style="grid-column: span 3;">
                <label>Wage Declaration</label>
                <div style="font-weight:400; font-size:13px; font-style:italic;"><?= nl2br(htmlspecialchars($data['wage_declaration'])) ?></div>
            </div>
        </div>
    </div>

    <!-- 4. ATTACHMENTS (NON-DOWNLOADABLE IN VIEW) -->
    <div class="form-section no-print">
        <div class="form-section-title"><i class="fas fa-paperclip"></i> Verification Documents</div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <?php 
            $files = [
                'Labour License' => $data['labour_license_file'],
                'Insurance' => $data['insurance_file'],
                'EPF Reg' => $data['epf_file'],
                'ESI Reg' => $data['esi_file'],
                'Agreement' => $data['agreement_file']
            ];
            foreach($files as $lbl => $path): if($path):
            ?>
                <div style="padding:10px; border:1px solid #edf2f7; border-radius:8px; display:flex; align-items:center; gap:8px;">
                    <i class="fas fa-file-pdf" style="color:#e53e3e;"></i>
                    <span style="font-size:12px; font-weight:700;"><?= $lbl ?></span>
                    <a href="../../<?= $path ?>" target="_blank" style="font-size:10px; color:#3182ce; text-decoration:none;">View</a>
                </div>
            <?php endif; endforeach; ?>
        </div>
    </div>
</div>
<?php
}

renderLayout("View Customer Registration", 'renderContent', $_SESSION['role'], $name);
?>
