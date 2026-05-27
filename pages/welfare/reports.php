<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['pass_user', 'super_admin', 'welfare_admin', 'welfare_user']);

include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = get_normalized_role();
$name = $_SESSION['name'] ?? 'Officer';

function renderContent() {
    global $conn;
    
    // Aggregated stats for reports
    $total_passes = db_count($conn, "SELECT COUNT(*) FROM workmen WHERE status IN ('temporary_issued', 'acc_generated', 'permanent_active')");
    $perm_active  = db_count($conn, "SELECT COUNT(*) FROM workmen WHERE status = 'permanent_active'");
    $temp_active  = db_count($conn, "SELECT COUNT(*) FROM workmen WHERE status = 'temporary_issued'");
    $expiring_7   = db_count($conn, "SELECT COUNT(*) FROM workmen WHERE status = 'temporary_issued' AND valid_to BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
    
    // Contractor-wise distribution
    $contractor_stats = db_fetch_all($conn, "SELECT c.contractor_name, 
                                            COUNT(w.id) as total,
                                            SUM(CASE WHEN w.status='permanent_active' THEN 1 ELSE 0 END) as permanent,
                                            SUM(CASE WHEN w.status='temporary_issued' THEN 1 ELSE 0 END) as temporary
                                            FROM contractors c
                                            LEFT JOIN workmen w ON c.id = w.contractor_id
                                            GROUP BY c.id
                                            ORDER BY total DESC");
    ?>
    <div class="content-header">
      <h2 class="page-title">Pass Issuance Reports</h2>
      <!-- <p class="page-subtitle">Detailed analytics and statistics for gate passes and workforce enrollment.</p> -->
    </div>

    <div class="grid grid-4 mb-4">
      <div class="card glass">
        <div class="card-body">
          <div style="font-size:11px; opacity:0.6; text-transform:uppercase;">Total Active Passes</div>
          <div style="font-size:28px; font-weight:700; color:var(--primary)"><?= $total_passes ?></div>
        </div>
      </div>
      <div class="card glass">
        <div class="card-body">
          <div style="font-size:11px; opacity:0.6; text-transform:uppercase;">Permanent (ACC)</div>
          <div style="font-size:28px; font-weight:700; color:var(--success)"><?= $perm_active ?></div>
        </div>
      </div>
      <div class="card glass">
        <div class="card-body">
          <div style="font-size:11px; opacity:0.6; text-transform:uppercase;">Temporary Passes</div>
          <div style="font-size:28px; font-weight:700; color:var(--info)"><?= $temp_active ?></div>
        </div>
      </div>
      <div class="card glass">
        <div class="card-body">
          <div style="font-size:11px; opacity:0.6; text-transform:uppercase;">Expiring in 7 Days</div>
          <div style="font-size:28px; font-weight:700; color:var(--warning)"><?= $expiring_7 ?></div>
        </div>
      </div>
    </div>

    <div class="card glass">
      <div class="card-header">
        <div class="card-title">Contractor-wise Pass Distribution</div>
      </div>
      <div class="card-body" style="padding:0">
        <table class="data-table">
          <thead>
            <tr>
              <th>Contractor Name</th>
              <th>Total Issued</th>
              <th>Permanent (ACC)</th>
              <th>Temporary</th>
              <th>Utilization %</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($contractor_stats as $cs): 
                $util = $cs['total'] > 0 ? round(($cs['permanent'] / $cs['total']) * 100, 1) : 0;
            ?>
            <tr>
              <td><strong><?= htmlspecialchars($cs['contractor_name']) ?></strong></td>
              <td><?= $cs['total'] ?></td>
              <td><span class="text-success"><?= $cs['permanent'] ?></span></td>
              <td><span class="text-info"><?= $cs['temporary'] ?></span></td>
              <td>
                <div style="display:flex; align-items:center; gap:8px;">
                  <div style="flex:1; height:8px; background:rgba(255,255,255,0.05); border-radius:4px; overflow:hidden;">
                    <div style="width:<?= $util ?>%; height:100%; background:var(--success);"></div>
                  </div>
                  <span style="font-size:12px;"><?= $util ?>%</span>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
}

renderLayout("Reports", 'renderContent', $role, $name);

