<?php
require_once '../../include/auth.php';
checkAuth(['contractor']);
include '../../include/config.php';
include '../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';
$user_id = $_SESSION['user_id'];

function renderContent() {
    global $conn, $user_id;

    $contractor = db_single($conn, "SELECT id, vendor_code FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    $vendor_code = $contractor['vendor_code'] ?? '';

    // Fetch Sales Orders for this vendor
    $sos = db_fetch_all($conn, "
        SELECT * FROM sap_sales_order_master 
        WHERE vendor_code = ?
    ", 's', [$vendor_code]);

    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-shopping-cart"></i> Sales Orders</h2>
        <p class="page-subtitle">SAP Integrated Sales Orders for Vendor: <?= htmlspecialchars($vendor_code) ?></p>
      </div>
    </div>

    <div class="card glass">
      <div class="card-header">
        <div class="card-title">Customer Service / Sales Orders</div>
      </div>
      <div class="card-body p-0">
        <table class="data-table">
          <thead>
            <tr>
              <th>Sales Order No</th>
              <th>Date</th>
              <th>Amount</th>
              <th>Currency</th>
              <th>Description</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($sos as $s): ?>
            <tr>
              <td><b class="text-primary"><?= htmlspecialchars($s['sales_order_number']) ?></b></td>
              <td><?= !empty($s['doc_date']) ? date('d M Y', strtotime($s['doc_date'])) : 'N/A' ?></td>
              <td style="font-weight:700;"><?= number_format($s['amount'], 2) ?></td>
              <td><?= htmlspecialchars($s['currency']) ?></td>
              <td><?= htmlspecialchars($s['description']) ?></td>
              <td><span class="badge badge-success">Released</span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($sos)): ?>
              <tr><td colspan="6" class="text-center py-4">No Sales Order records found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
}

renderLayout('Sales Orders', 'renderContent', $role, $name);
?>
