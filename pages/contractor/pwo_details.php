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

    // Fetch PWOs linked to this contractor's POs
    $pwos = db_fetch_all($conn, "
        SELECT p.*, po.po_type 
        FROM sap_pwo_master p
        JOIN sap_po_master po ON p.po_number = po.po_number
        WHERE po.vendor_code = ?
    ", 's', [$vendor_code]);

    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-ship"></i> PWO Details (SAP Integrated)</h2>
        <p class="page-subtitle">Project Work Order details fetched from SAP S/4 HANA for Vendor: <?= htmlspecialchars($vendor_code) ?></p>
      </div>
    </div>

    <div class="card glass">
      <div class="card-header">
        <div class="card-title">Active PWOs & Vessels</div>
      </div>
      <div class="card-body p-0">
        <table class="data-table">
          <thead>
            <tr>
              <th>PWO Number</th>
              <th>PO Number</th>
              <th>Vessel / Project</th>
              <th>Description</th>
              <th>Completion Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pwos as $p): 
                $is_expired = strtotime($p['completion_date'] ?? '') < time();
            ?>
            <tr>
              <td><b class="text-primary"><?= htmlspecialchars($p['pwo_number']) ?></b></td>
              <td><code><?= htmlspecialchars($p['po_number']) ?></code></td>
              <td>
                <div style="font-weight:700;"><?= htmlspecialchars($p['vessel'] ?? 'N/A') ?></div>
                <div style="font-size:11px; color:var(--gray-500);"><?= htmlspecialchars($p['project'] ?? 'General') ?></div>
              </td>
              <td><?= htmlspecialchars($p['description']) ?></td>
              <td><?= !empty($p['completion_date']) ? date('d M Y', strtotime($p['completion_date'])) : 'N/A' ?></td>
              <td>
                <?php if ($is_expired): ?>
                  <span class="badge badge-danger">Expired</span>
                <?php else: ?>
                  <span class="badge badge-success">Active</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($pwos)): ?>
              <tr><td colspan="6" class="text-center py-4">No PWO records found for your vendor code.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="alert alert-info mt-4">
      <i class="fas fa-info-circle"></i>
      <div>These details are read-only and synced from SAP. If any information is incorrect, please contact the Purchase Department.</div>
    </div>
    <?php
}

renderLayout('PWO Details', 'renderContent', $role, $name);
?>
