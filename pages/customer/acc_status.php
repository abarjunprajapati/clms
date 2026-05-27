<?php
require_once '../../include/config.php';
require_once '../../include/auth_middleware.php';
require_once '../../include/layout.php';

require_role(['customer']);

$name = $_SESSION['customer_name'] ?? $_SESSION['name'] ?? 'Customer';
$customer_code = $_SESSION['customer_code'] ?? '';

// Fetch ACC Cards for workmen under this customer's contractors
$sql = "SELECT w.id, w.name as worker_name, w.trade, w.aadhaar, w.status, w.acc_no,
               v.vendor_name
        FROM workmen w
        JOIN contractors c ON w.contractor_id = c.id
        JOIN work_orders wo ON wo.vendor_code = c.vendor_code
        JOIN sap_vendor_master v ON v.vendor_code = c.vendor_code
        WHERE wo.customer_code = ?
        ORDER BY w.id DESC";

$workmen = db_fetch_all($conn, $sql, 's', [$customer_code]);

function renderContent() {
    global $workmen;
?>
<div class="page-header">
    <div class="header-content">
        <h1><i class="fas fa-fingerprint" style="color:#6366f1"></i> ACC Card Status</h1>
        <!-- <p>Monitor permanent biometric ACC Card issuance and status across your contractors' workforce.</p> -->
    </div>
</div>

<div class="card glass">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-id-card-clip"></i> Biometric ACC Cards</div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table data-table" id="acc-table">
                <thead>
                    <tr>
                        <th>Worker Name</th>
                        <th>Contractor</th>
                        <th>Trade</th>
                        <th>Aadhaar</th>
                        <th>ACC Number</th>
                        <th>Biometric Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($workmen)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No ACC records found.</td></tr>
                    <?php else: foreach($workmen as $w): ?>
                        <?php
                        $st = strtolower($w['status']);
                        $statusText = 'PENDING';
                        $badgeClass = 'badge-warning';
                        if ($st === 'acc_generated' || $st === 'permanent_active' || $st === 'biometric_completed') {
                            $statusText = 'ACTIVE';
                            $badgeClass = 'badge-success';
                        }
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($w['worker_name']) ?></strong></td>
                            <td><?= htmlspecialchars($w['vendor_name']) ?></td>
                            <td><?= htmlspecialchars($w['trade'] ?: 'General') ?></td>
                            <td><?= htmlspecialchars($w['aadhaar']) ?></td>
                            <td><code><?= htmlspecialchars($w['acc_no'] ?: 'NOT GENERATED') ?></code></td>
                            <td><span class="badge <?= $badgeClass ?>"><?= $statusText ?></span></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
}

renderLayout("ACC Card Status", 'renderContent', $_SESSION['role'], $name);
?>
