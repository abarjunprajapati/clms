<?php
require_once '../../include/auth.php';
checkAuth(['contractor', 'super_admin']);
include '../../include/config.php';
include '../../include/layout.php';
$role = $_SESSION['role']; $name = $_SESSION['name'] ?? 'Contractor'; $user_id = $_SESSION['user_id'];

function renderContent() {
    global $conn, $user_id;
    $contractor = db_single($conn, "SELECT vendor_code FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    $vendor_code = $contractor['vendor_code'] ?? ($_SESSION['username'] ?? '');
    $pos = db_fetch_all($conn, "SELECT * FROM sap_po_master WHERE vendor_code = ? ORDER BY document_date DESC", 's', [$vendor_code]);
?>
<div class="content-header">
  <div><h2 class="page-title"><i class="fas fa-file-invoice-dollar" style="color:#6366f1;margin-right:10px;"></i> Purchase Order Details</h2>
  <p class="page-subtitle">SAP S/4 HANA Purchase Orders linked to your Vendor Code: <strong><?= htmlspecialchars($vendor_code) ?></strong></p></div>
  <div><span class="badge badge-info" style="font-size:14px;padding:8px 16px;"><?= count($pos) ?> PO Records</span></div>
</div>
<div class="card glass">
  <div class="card-body">
    <table class="data-table" style="width:100%">
      <thead><tr><th>S.No</th><th>PO Number</th><th>Type</th><th>Purchasing Group</th><th>Work Description</th><th>Currency</th><th>Total Value</th><th>Doc Date</th><th>MSME</th><th>Tender Type</th><th>Release</th></tr></thead>
      <tbody>
        <?php foreach($pos as $i => $po): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><strong><?= $po['po_number'] ?></strong></td>
          <td><?= $po['po_type'] ?></td>
          <td><?= $po['purchasing_group'] ?></td>
          <td style="max-width:250px"><?= $po['header_text'] ?></td>
          <td><?= $po['currency'] ?></td>
          <td>₹<?= number_format($po['total_value'],2) ?></td>
          <td><?= $po['document_date'] ?></td>
          <td><?= $po['msme_type_text'] ?></td>
          <td><?= $po['tender_type_text'] ?></td>
          <td><span class="badge <?= $po['release_status']==='R'?'badge-success':'badge-warning' ?>"><?= $po['release_status']==='R'?'Released':'Blocked' ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php } renderLayout('PO Details', 'renderContent', $role, $name); ?>
