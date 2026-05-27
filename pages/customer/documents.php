<?php
require_once '../../include/config.php';
require_once '../../include/auth_middleware.php';
require_once '../../include/layout.php';

require_role(['customer']);

$name = $_SESSION['customer_name'] ?? $_SESSION['name'] ?? 'Customer';
$customer_code = $_SESSION['customer_code'] ?? '';

// Fetch uploaded documents for this customer's contractors
$sql = "SELECT cd.*, v.vendor_name, v.vendor_code
        FROM contractor_documents cd
        JOIN contractors c ON cd.contractor_id = c.id
        JOIN work_orders wo ON wo.vendor_code = c.vendor_code
        JOIN sap_vendor_master v ON v.vendor_code = c.vendor_code
        WHERE wo.customer_code = ?
        ORDER BY cd.uploaded_at DESC";

$docs = db_fetch_all($conn, $sql, 's', [$customer_code]);

function renderContent() {
    global $docs;
?>
<div class="page-header">
    <div class="header-content">
        <h1><i class="fas fa-folder-open" style="color:#6366f1"></i> Contractor Documents Library</h1>
        <!-- <p>Browse and review statutory documents uploaded by your contractors.</p> -->
    </div>
</div>

<div class="card glass">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-file-pdf"></i> Contractor Document Submissions</div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table data-table" id="documents-table">
                <thead>
                    <tr>
                        <th>Contractor</th>
                        <th>Document Type</th>
                        <th>File Name</th>
                        <th>Upload Date</th>
                        <th>Verification Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($docs)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No contractor documents found.</td></tr>
                    <?php else: foreach($docs as $d): ?>
                        <?php
                        $st = strtolower($d['status'] ?? 'pending');
                        $badge = ['verified' => 'badge-success', 'rejected' => 'badge-danger', 'pending' => 'badge-warning'][$st] ?? 'badge-gray';
                        
                        $docLabels = [
                            'insurance_policy' => 'Insurance Policy',
                            'cla_license' => 'CLA License (>20 workers)',
                            'workmen_compensation' => 'Workmen Compensation Cert',
                            'pan_card' => 'PAN Card',
                            'gst_certificate' => 'GST Certificate',
                            'mou_agreement' => 'MOU / Work Order',
                            'bank_statement' => 'Bank Statement'
                        ];
                        $label = $docLabels[$d['doc_type']] ?? $d['doc_type'];
                        ?>
                        <tr>
                            <td>
                                <div style="font-weight:600"><?= htmlspecialchars($d['vendor_name']) ?></div>
                                <div class="text-muted small">Code: <?= htmlspecialchars($d['vendor_code']) ?></div>
                            </td>
                            <td><strong><?= htmlspecialchars($label) ?></strong></td>
                            <td style="font-size:12px;"><?= htmlspecialchars($d['original_name'] ?: '—') ?></td>
                            <td><?= date('d M Y, H:i', strtotime($d['uploaded_at'])) ?></td>
                            <td><span class="badge <?= $badge ?>"><?= strtoupper($st) ?></span></td>
                            <td>
                                <?php if($d['file_path']): ?>
                                    <a href="../../<?= htmlspecialchars($d['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary" style="display:inline-flex; align-items:center; gap:4px;">
                                        <i class="fas fa-eye"></i> View File
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
}

renderLayout("Contractor Documents", 'renderContent', $_SESSION['role'], $name);
?>
