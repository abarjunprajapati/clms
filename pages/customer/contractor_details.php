<?php
require_once '../../include/config.php';
require_once '../../include/auth_middleware.php';
require_once '../../include/layout.php';

require_role(['customer']);

$name = $_SESSION['customer_name'] ?? $_SESSION['name'] ?? 'Customer';
$customer_code = $_SESSION['customer_code'] ?? '';
$vendor_code = $_GET['vendor_code'] ?? '';

if (empty($vendor_code)) {
    $_SESSION['error'] = "Contractor code missing.";
    header("Location: dashboard.php");
    exit;
}

// 1. Fetch Contractor Basic Info
$vendor = db_single($conn, "SELECT * FROM sap_vendor_master WHERE vendor_code = ?", 's', [$vendor_code]);
if (!$vendor) {
    $_SESSION['error'] = "Contractor not found in SAP.";
    header("Location: dashboard.php");
    exit;
}

// 2. Security Validation & Fetch Linked Work Orders for this Customer & Contractor
// This ensures the customer can only see contractors mapped to them
$work_orders = db_fetch_all($conn, "
    SELECT wo.*, a.id as annexure_id, a.status as annexure_status
    FROM work_orders wo
    LEFT JOIN contractor_annexure3a a ON a.work_order_no = wo.work_order_no AND a.customer_code = wo.customer_code
    WHERE wo.customer_code = ? AND wo.vendor_code = ?
", 'ss', [$customer_code, $vendor_code]);

if (empty($work_orders)) {
    $_SESSION['error'] = "Access denied or no work orders found for this contractor.";
    header("Location: dashboard.php");
    exit;
}

function renderContent() {
    global $vendor, $work_orders, $vendor_code;
?>
<div class="page-header">
    <div class="header-content">
        <h1><i class="fas fa-building" style="color:#6366f1"></i> Contractor Profile: <?= htmlspecialchars($vendor['vendor_name']) ?></h1>
        <p>Detailed view of contractor compliance, work orders, and statutory submissions.</p>
    </div>
    <div class="header-actions">
        <a href="javascript:history.back()" class="btn btn-outline" style="background:white; border: 1px solid #ddd; color: #4a5568;">
            <i class="fas fa-arrow-left me-1"></i> Go Back
        </a>
    </div>
</div>

<div class="grid" style="display:grid; grid-template-columns: 1fr 2fr; gap:20px;">
    <!-- Profile Card -->
    <div class="card glass">
        <div class="card-header"><div class="card-title">Basic Information</div></div>
        <div class="card-body">
            <div class="mb-3">
                <label class="text-muted small fw-bold">VENDOR CODE</label>
                <div class="fw-bold"><?= $vendor['vendor_code'] ?></div>
            </div>
            <div class="mb-3">
                <label class="text-muted small fw-bold">GST NUMBER</label>
                <div class="fw-bold"><?= $vendor['gst_no'] ?: 'N/A' ?></div>
            </div>
            <div class="mb-3">
                <label class="text-muted small fw-bold">PF REGISTRATION</label>
                <div class="fw-bold"><?= $vendor['pf_no'] ?: 'N/A' ?></div>
            </div>
            <div class="mb-3">
                <label class="text-muted small fw-bold">ESI REGISTRATION</label>
                <div class="fw-bold"><?= $vendor['esi_no'] ?: 'N/A' ?></div>
            </div>
            <div class="mb-3">
                <label class="text-muted small fw-bold">ADDRESS</label>
                <div class="small"><?= nl2br(htmlspecialchars($vendor['address'] ?? 'N/A')) ?></div>
            </div>
        </div>
    </div>

    <!-- Work Orders Card -->
    <div class="card glass">
        <div class="card-header"><div class="card-title">Linked Work Orders & Customer Registration</div></div>
        <div class="card-body" style="padding:0;">
            <table class="table data-table">
                <thead>
                    <tr>
                        <th>WO Number</th>
                        <th>Project / Dept</th>
                        <th>Customer Registration</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($work_orders)): ?>
                        <tr><td colspan="4" class="text-center py-4">No work orders linked.</td></tr>
                    <?php else: foreach($work_orders as $wo): ?>
                        <tr>
                            <td><strong><?= $wo['work_order_no'] ?></strong></td>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($wo['project_name']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($wo['department']) ?></div>
                            </td>
                            <td>
                                <?php if ($wo['annexure_id']): ?>
                                    <span class="badge badge-<?= $wo['annexure_status'] === 'approved' ? 'success' : ($wo['annexure_status'] === 'rejected' ? 'danger' : 'warning') ?>">
                                        <?= strtoupper($wo['annexure_status']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge" style="background:#edf2f7; color:#718096;">NOT SUBMITTED</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($wo['annexure_id']): ?>
                                    <a href="annexure-3a.php?edit_id=<?= $wo['annexure_id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View Form
                                    </a>
                                <?php else: ?>
                                    <a href="annexure-3a.php?wo=<?= urlencode($wo['work_order_no']) ?>" class="btn btn-sm btn-outline">
                                        Create Form
                                    </a>
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

renderLayout("Contractor Details", 'renderContent', $_SESSION['role'], $name);
?>
