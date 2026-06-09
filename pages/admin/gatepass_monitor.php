<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    
    // Summary Stats
    $activePasses = db_count($conn, "SELECT COUNT(*) c FROM gate_passes WHERE status='active'");
    $expired = db_count($conn, "SELECT COUNT(*) c FROM gate_passes WHERE valid_to < CURDATE() AND status='active'");
    $issuedToday = db_count($conn, "SELECT COUNT(*) c FROM gate_passes WHERE DATE(created_at) = CURDATE()");
    $pending = db_count($conn, "SELECT COUNT(*) c FROM gate_pass_requests WHERE status='pending'");
    
    $passes = db_fetch_all($conn, "SELECT g.*, w.name as workman_name, c.contractor_name 
                                   FROM gate_passes g 
                                   JOIN workmen w ON g.workman_id = w.id 
                                   JOIN contractors c ON w.contractor_id = c.id
                                   ORDER BY g.created_at DESC LIMIT 100");
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-id-card-clip" style="color:#0284c7;margin-right:10px;"></i> Gate Pass Monitoring</h2>
        <!-- <p class="page-subtitle">Track pass issuance, validity status, and real-time active pass counts.</p> -->
      </div>
    </div>

    <!-- Stats Row -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
      <div class="card glass" style="text-align:center;padding:16px;">
        <div style="font-size:24px;font-weight:800;color:#6366f1;"><?= $activePasses ?></div>
        <div style="font-size:11px;opacity:0.6;">Active Passes</div>
      </div>
      <div class="card glass" style="text-align:center;padding:16px;">
        <div style="font-size:24px;font-weight:800;color:#ef4444;"><?= $expired ?></div>
        <div style="font-size:11px;opacity:0.6;">Expired (Still Active)</div>
      </div>
      <div class="card glass" style="text-align:center;padding:16px;">
        <div style="font-size:24px;font-weight:800;color:#10b981;"><?= $issuedToday ?></div>
        <div style="font-size:11px;opacity:0.6;">Issued Today</div>
      </div>
      <div class="card glass" style="text-align:center;padding:16px;">
        <div style="font-size:24px;font-weight:800;color:#f59e0b;"><?= $pending ?></div>
        <div style="font-size:11px;opacity:0.6;">Pending Requests</div>
      </div>
    </div>

    <div class="card glass">
      <div class="card-header">
        <div class="card-title">Live Pass Registry</div>
      </div>
      <div class="card-body" style="padding:0;">
        <table class="data-table">
          <thead>
            <tr>
              <th>Pass #</th>
              <th>Workman</th>
              <th>Contractor</th>
              <th>Type</th>
              <th>Validity</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($passes as $p): 
                $isExpired = (strtotime($p['valid_to']) < time() && $p['status'] == 'active');
                $stCls = ($p['status'] == 'active') ? ($isExpired ? 'badge-danger' : 'badge-success') : 'badge-outline';
            ?>
            <tr>
              <td><code style="font-weight:700;"><?= htmlspecialchars($p['pass_number']) ?></code></td>
              <td><strong><?= htmlspecialchars($p['workman_name']) ?></strong></td>
              <td><small><?= htmlspecialchars($p['contractor_name']) ?></small></td>
              <td><span class="badge badge-outline"><?= strtoupper($p['pass_type']) ?></span></td>
              <td>
                <div style="font-size:11px;">
                  From: <?= date('d M Y', strtotime($p['valid_from'])) ?><br>
                  To: <span style="<?= $isExpired ? 'color:red;font-weight:bold;' : '' ?>"><?= date('d M Y', strtotime($p['valid_to'])) ?></span>
                </div>
              </td>
              <td>
                <span class="badge <?= $stCls ?>">
                    <?= $isExpired ? 'EXPIRED' : strtoupper($p['status']) ?>
                </span>
              </td>
              <td>
                <button class="btn btn-sm btn-outline" onclick="viewPassDetails('<?= $p['pass_number'] ?>')"><i class="fas fa-search"></i> Details</button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <script>
    function viewPassDetails(passNo) {
        alert('Detailed access log for Pass #' + passNo + ' would be shown here (Integration with biometric punch logs).');
    }
    </script>
    <?php
}

renderLayout("Gate Pass Monitor", 'renderContent', $_SESSION['role'], $_SESSION['name']);
