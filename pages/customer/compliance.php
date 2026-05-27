<?php
require_once '../../include/config.php';
require_once '../../include/auth_middleware.php';
require_once '../../include/layout.php';

require_role(['customer']);

$name = $_SESSION['customer_name'] ?? $_SESSION['name'] ?? 'Customer';
$customer_code = $_SESSION['customer_code'] ?? '';

// Fetch compliance submissions for this customer's contractors
$sql = "SELECT comp.*, v.vendor_name, v.vendor_code
        FROM compliance comp
        JOIN contractors c ON comp.contractor_id = c.id
        JOIN work_orders wo ON wo.vendor_code = c.vendor_code
        JOIN sap_vendor_master v ON v.vendor_code = c.vendor_code
        WHERE wo.customer_code = ?
        ORDER BY comp.uploaded_at DESC";

$history = db_fetch_all($conn, $sql, 's', [$customer_code]);

function renderContent() {
    global $history;
?>
<div class="page-header">
    <div class="header-content">
        <h1><i class="fas fa-shield-check" style="color:#6366f1"></i> Statutory Compliance Monitor</h1>
        <!-- <p>Track monthly EPF, ESI, and KLWF compliance filings submitted by your contractors.</p> -->
    </div>
</div>

<div class="card glass">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-history"></i> Contractor Filing History</div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table data-table" id="compliance-table">
                <thead>
                    <tr>
                        <th>Contractor</th>
                        <th>Month/Year</th>
                        <th>ESI Amount</th>
                        <th>PF Amount</th>
                        <th>KLWF Amount</th>
                        <th>Validation</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No statutory compliance filings found.</td></tr>
                    <?php else: foreach($history as $row): ?>
                        <?php
                        $status = strtolower($row['status'] ?? 'pending');
                        $badge = ['verified' => 'badge-success', 'rejected' => 'badge-danger', 'pending' => 'badge-warning'][$status] ?? 'badge-gray';
                        $vBadge = ($row['validation_status'] ?? '') === 'passed' ? 'badge-success' : (($row['validation_status'] ?? '') === 'mismatch' ? 'badge-danger' : 'badge-warning');
                        ?>
                        <tr>
                            <td>
                                <div style="font-weight:600"><?= htmlspecialchars($row['vendor_name']) ?></div>
                                <div class="text-muted small">Code: <?= htmlspecialchars($row['vendor_code']) ?></div>
                            </td>
                            <td><strong><?= htmlspecialchars($row['month_year'] ?: (($row['month'] ?? '') . ' ' . ($row['year'] ?? ''))) ?></strong></td>
                            <td><?= (float)$row['esi_amount'] > 0 ? '₹ ' . number_format((float)$row['esi_amount'], 2) : '<span class="text-muted">-</span>' ?></td>
                            <td><?= (float)$row['pf_amount'] > 0 ? '₹ ' . number_format((float)$row['pf_amount'], 2) : '<span class="text-muted">-</span>' ?></td>
                            <td><?= (float)$row['klwf_amount'] > 0 ? '₹ ' . number_format((float)$row['klwf_amount'], 2) : '<span class="text-muted">-</span>' ?></td>
                            <td>
                                <span class="badge <?= $vBadge ?>"><?= htmlspecialchars($row['validation_status'] ?? 'pending') ?></span>
                                <?php if (!empty($row['validation_errors'])): ?>
                                    <div style="font-size:11px;color:var(--danger);white-space:pre-line;max-width:260px;"><?= htmlspecialchars($row['validation_errors']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?= $badge ?>"><?= strtoupper($status) ?></span></td>
                            <td>
                                <?php if($row['challan_file']): ?>
                                    <a href="../../<?= htmlspecialchars($row['challan_file']) ?>" target="_blank" class="btn btn-sm btn-outline-primary" style="display:inline-flex; align-items:center; gap:4px;">
                                        <i class="fas fa-file-pdf"></i> View Challan
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

renderLayout("Compliance Monitor", 'renderContent', $_SESSION['role'], $name);
?>
