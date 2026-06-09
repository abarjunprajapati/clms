<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    
    // 1. Live Stats from SAP Attendance
    $todayAttendance = db_count($conn, "SELECT COUNT(DISTINCT acc_no) c FROM sap_attendance WHERE attendance_date = CURDATE() AND in_time IS NOT NULL");
    $liveInside = db_count($conn, "SELECT COUNT(*) c FROM sap_attendance WHERE attendance_date = CURDATE() AND in_time IS NOT NULL AND out_time IS NULL");
    $totalWorkers = db_count($conn, "SELECT COUNT(*) c FROM workmen WHERE status NOT IN ('blocked','inactive')");
    $ratio = ($totalWorkers > 0) ? round(($todayAttendance / $totalWorkers) * 100) : 0;
    
    // 2. SAP Sync Status Stats
    $syncPending = db_count($conn, "SELECT COUNT(*) c FROM sap_attendance WHERE sap_sync_status = 'PENDING'");
    $syncFailed = db_count($conn, "SELECT COUNT(*) c FROM sap_attendance WHERE sap_sync_status = 'FAILED'");
    
    // 3. Weekly trends using SAP data
    $weekly = db_fetch_all($conn, "SELECT attendance_date as dt, COUNT(DISTINCT acc_no) as cnt FROM sap_attendance WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY attendance_date ORDER BY dt");
    
    // 4. Contractor-wise Distribution (Top 5)
    $contractorDist = db_fetch_all($conn, "SELECT contractor_name, COUNT(*) as cnt FROM sap_attendance WHERE attendance_date = CURDATE() GROUP BY contractor_name ORDER BY cnt DESC LIMIT 5");
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-chart-line" style="color:#6366f1;margin-right:10px;"></i> Attendance Analytics</h2>
        <!-- <p class="page-subtitle">Centralized oversight of SAP sync, gate flow, and workforce productivity.</p> -->
      </div>
      <div>
        <a href="../demo-punch-machine.php" target="_blank" class="btn btn-primary" style="background:#6366f1; border:none; padding:10px 20px; border-radius:8px; font-weight:600; text-decoration:none; display:inline-block;">
            <i class="fas fa-fingerprint" style="margin-right:8px"></i> Simulate Biometric Punch
        </a>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px;">
      <div class="card glass" style="padding:18px;text-align:center;">
          <div style="font-size:28px;font-weight:800;color:#6366f1;"><?= $todayAttendance ?></div>
          <div style="font-size:12px;opacity:0.6;text-transform:uppercase;letter-spacing:1px">Present Today</div>
      </div>
      <div class="card glass" style="padding:18px;text-align:center;">
          <div style="font-size:28px;font-weight:800;color:#10b981;"><?= $liveInside ?></div>
          <div style="font-size:12px;opacity:0.6;text-transform:uppercase;letter-spacing:1px">Live Inside Gate</div>
      </div>
      <div class="card glass" style="padding:18px;text-align:center;">
          <div style="font-size:28px;font-weight:800;color:#f59e0b;"><?= $syncPending ?></div>
          <div style="font-size:12px;opacity:0.6;text-transform:uppercase;letter-spacing:1px">SAP Sync Pending</div>
      </div>
      <div class="card glass" style="padding:18px;text-align:center;">
          <div style="font-size:28px;font-weight:800;color:#ef4444;"><?= $syncFailed ?></div>
          <div style="font-size:12px;opacity:0.6;text-transform:uppercase;letter-spacing:1px">Sync Failures</div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px;">
      <!-- Weekly Trend -->
      <div class="card glass">
        <div class="card-header"><div class="card-title"><i class="fas fa-chart-bar"></i> 7-Day Attendance Volume</div></div>
        <div class="card-body" style="height:220px;display:flex;align-items:flex-end;gap:12px;justify-content:space-around;padding:0 20px 40px 20px;">
          <?php
          $maxVal = 1;
          foreach($weekly as $w) if($w['cnt'] > $maxVal) $maxVal = $w['cnt'];
          foreach($weekly as $w):
            $pct = ($w['cnt'] / $maxVal) * 100;
          ?>
          <div style="text-align:center;flex:1;">
            <div style="font-size:11px;font-weight:600;margin-bottom:4px;"><?= $w['cnt'] ?></div>
            <div style="width:100%;height:<?= max(10,$pct) ?>%;background:linear-gradient(to top,#6366f1,#818cf8);border-radius:6px 6px 2px 2px;min-height:10px;box-shadow: 0 4px 12px rgba(99,102,241,0.2)"></div>
            <div style="font-size:10px;margin-top:8px;opacity:0.6;"><?= date('D', strtotime($w['dt'])) ?></div>
          </div>
          <?php endforeach; ?>
          <?php if(empty($weekly)): ?>
          <div style="text-align:center;width:100%;opacity:0.5;padding:40px;">No historical SAP data found.</div>
          <?php endif; ?>
        </div>
      </div>

      <!-- SAP Sync Health -->
      <div class="card glass">
        <div class="card-header"><div class="card-title"><i class="fas fa-sync"></i> SAP Integration Health</div></div>
        <div class="card-body">
            <div style="margin-bottom:20px">
                <div style="display:flex; justify-content:space-between; margin-bottom:8px">
                    <span style="font-size:13px">Sync Success Rate</span>
                    <span style="font-weight:bold; color:#10b981"><?= $todayAttendance > 0 ? round((($todayAttendance - $syncFailed) / $todayAttendance) * 100) : 100 ?>%</span>
                </div>
                <div style="height:8px; background:rgba(255,255,255,0.05); border-radius:4px overflow:hidden">
                    <div style="width:<?= $todayAttendance > 0 ? round((($todayAttendance - $syncFailed) / $todayAttendance) * 100) : 100 ?>%; height:100%; background:#10b981; border-radius:4px"></div>
                </div>
            </div>
            
            <div style="display:flex; flex-direction:column; gap:12px">
                <div style="display:flex; justify-content:space-between; align-items:center; padding:10px; background:rgba(255,255,255,0.03); border-radius:8px">
                    <span style="font-size:12px"><i class="fas fa-check-circle" style="color:#10b981; margin-right:8px"></i>Synced Records</span>
                    <span class="badge badge-success"><?= $todayAttendance - $syncPending - $syncFailed ?></span>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:10px; background:rgba(255,255,255,0.03); border-radius:8px">
                    <span style="font-size:12px"><i class="fas fa-clock" style="color:#f59e0b; margin-right:8px"></i>Queued for SAP</span>
                    <span class="badge badge-warning"><?= $syncPending ?></span>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:10px; background:rgba(255,255,255,0.03); border-radius:8px">
                    <span style="font-size:12px"><i class="fas fa-times-circle" style="color:#ef4444; margin-right:8px"></i>Sync Failures</span>
                    <span class="badge badge-danger"><?= $syncFailed ?></span>
                </div>
            </div>
        </div>
      </div>
    </div>

    <!-- Contractor Distribution -->
    <div class="card glass">
      <div class="card-header"><div class="card-title"><i class="fas fa-users-cog"></i> Top Contractor Attendance (Today)</div></div>
      <div class="card-body" style="padding:0;">
        <table class="data-table">
          <thead><tr><th>Contractor</th><th>Punches Recorded</th><th>Percentage</th><th>Live Status</th></tr></thead>
          <tbody>
          <?php foreach($contractorDist as $c): 
            $perc = round(($c['cnt'] / ($todayAttendance ?: 1)) * 100);
            ?>
          <tr>
            <td><strong><?= htmlspecialchars($c['contractor_name']) ?></strong></td>
            <td><?= $c['cnt'] ?></td>
            <td>
                <div style="display:flex; align-items:center; gap:10px">
                    <div style="flex:1; height:4px; background:rgba(255,255,255,0.05); border-radius:2px">
                        <div style="width:<?= $perc ?>%; height:100%; background:#6366f1; border-radius:2px"></div>
                    </div>
                    <span><?= $perc ?>%</span>
                </div>
            </td>
            <td><span class="badge badge-success">ACTIVE</span></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
}

renderLayout("Attendance Dashboard", 'renderContent', $_SESSION['role'], $_SESSION['name']);
