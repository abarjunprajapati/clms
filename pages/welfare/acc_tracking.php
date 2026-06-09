<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Admin';

function renderContent() {
    global $conn;
    $workers = db_fetch_all($conn, "SELECT w.*, c.contractor_name, pgp.pass_no as perm_pass_no, pgp.issued_at 
                                    FROM workmen w 
                                    JOIN contractors c ON w.contractor_id = c.id 
                                    LEFT JOIN permanent_gate_passes pgp ON w.id = pgp.worker_id 
                                    WHERE w.acc_number IS NOT NULL OR pgp.id IS NOT NULL 
                                    ORDER BY w.created_at DESC");
    ?>
    <div class="content-header">
      <h2 class="page-title">ACC & Permanent Pass Tracking</h2>
      <!-- <p class="page-subtitle">Monitor ACC number generation, SAP S/4 HANA sync, and permanent pass issuance.</p> -->
    </div>

    <div class="card glass">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-id-card-clip"></i> Permanent Pass Issuance Log</div>
      </div>
      <div class="card-body">
        <table class="data-table">
          <thead>
            <tr>
              <th>Workman Name</th>
              <th>Contractor</th>
              <th>ACC Number</th>
              <th>Permanent Pass No</th>
              <th>SAP Sync Status</th>
              <th>Issued Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($workers as $w): ?>
            <tr>
              <td>
                <strong><?= htmlspecialchars($w['name']) ?></strong>
                <div style="font-size:11px;color:var(--gray-500)">ID: <?= htmlspecialchars($w['temp_id']) ?></div>
              </td>
              <td><?= htmlspecialchars($w['contractor_name']) ?></td>
              <td><code><?= htmlspecialchars($w['acc_number'] ?: 'Processing...') ?></code></td>
              <td><?= htmlspecialchars($w['perm_pass_no'] ?: 'Pending') ?></td>
              <td>
                <?php if($w['acc_number']): ?>
                <span class="badge badge-success"><i class="fas fa-check"></i> Synced to SAP</span>
                <?php else: ?>
                <span class="badge badge-warning"><i class="fas fa-sync fa-spin"></i> Pending Sync</span>
                <?php endif; ?>
              </td>
              <td><?= $w['issued_at'] ? date('d M Y', strtotime($w['issued_at'])) : 'N/A' ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
}

renderLayout("ACC & Permanent Pass Tracking", 'renderContent', $role, $name);

