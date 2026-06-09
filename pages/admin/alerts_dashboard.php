<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    
    // Live alert queries with priorities
    $criticalAlerts = [];
    $highAlerts = [];
    $mediumAlerts = [];
    $lowAlerts = [];
    
    // CRITICAL: Failed SAP syncs
    $failedSAP = db_fetch_all($conn, "SELECT * FROM sap_sync_queue WHERE sync_status='failed' ORDER BY created_at DESC LIMIT 5");
    foreach($failedSAP as $f) {
        $criticalAlerts[] = ['title'=>'SAP Sync Failed','detail'=>$f['entity_type'].' #'.$f['entity_id'].' - '.$f['action'],'time'=>$f['created_at'],'icon'=>'fa-sync','color'=>'#ef4444'];
    }
    
    // HIGH: Expired gate passes
    $expiredPasses = db_count($conn, "SELECT COUNT(*) c FROM gate_passes WHERE valid_to < CURDATE() AND status='active'");
    if($expiredPasses > 0) {
        $highAlerts[] = ['title'=>'Expired Active Passes','detail'=>"$expiredPasses passes are past validity but still active",'time'=>date('Y-m-d H:i:s'),'icon'=>'fa-id-card','color'=>'#f59e0b'];
    }
    
    // HIGH: Blocked contractors with active workers
    $blockedActive = db_fetch_all($conn, "SELECT c.contractor_name, COUNT(w.id) as wc FROM contractors c JOIN workmen w ON w.contractor_id = c.id WHERE (c.is_blocked=1 OR c.status='blocked') AND w.status NOT IN ('blocked','inactive') GROUP BY c.id HAVING wc > 0 LIMIT 5");
    foreach($blockedActive as $ba) {
        $highAlerts[] = ['title'=>'Blocked Contractor has Active Workers','detail'=>$ba['contractor_name'].' still has '.$ba['wc'].' active workers','time'=>date('Y-m-d H:i:s'),'icon'=>'fa-exclamation-triangle','color'=>'#f59e0b'];
    }
    
    // MEDIUM: Pending approvals > 3 days
    $staleApps = db_count($conn, "SELECT COUNT(*) c FROM applications WHERE current_status NOT IN ('approved','rejected','completed','acc_generated','permanent_issued') AND updated_at < DATE_SUB(NOW(), INTERVAL 3 DAY)");
    if($staleApps > 0) {
        $mediumAlerts[] = ['title'=>'Stale Applications','detail'=>"$staleApps applications pending for 3+ days",'time'=>date('Y-m-d H:i:s'),'icon'=>'fa-clock','color'=>'#3b82f6'];
    }
    
    // LOW: Training reminders
    $failedTraining = db_count($conn, "SELECT COUNT(*) c FROM training_results WHERE result='fail'");
    if($failedTraining > 0) {
        $lowAlerts[] = ['title'=>'Failed Training Results','detail'=>"$failedTraining workers have failed safety training",'time'=>date('Y-m-d H:i:s'),'icon'=>'fa-graduation-cap','color'=>'#64748b'];
    }
    
    $allAlerts = [
        'CRITICAL' => $criticalAlerts,
        'HIGH' => $highAlerts,
        'MEDIUM' => $mediumAlerts,
        'LOW' => $lowAlerts,
    ];
    $priorityColors = ['CRITICAL'=>'#ef4444','HIGH'=>'#f59e0b','MEDIUM'=>'#3b82f6','LOW'=>'#64748b'];
    $totalAlerts = count($criticalAlerts) + count($highAlerts) + count($mediumAlerts) + count($lowAlerts);
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-bell" style="color:#f59e0b;margin-right:10px;"></i> Alerts & Monitoring Dashboard</h2>
        <p class="page-subtitle">Priority-based alerts: Critical → High → Medium → Low.</p>
      </div>
      <div>
        <span class="badge badge-<?= $totalAlerts > 0 ? 'danger' : 'success' ?>" style="font-size:14px;padding:8px 16px;">
          <?= $totalAlerts ?> Active Alerts
        </span>
      </div>
    </div>

    <?php foreach($allAlerts as $priority => $items):
      $color = $priorityColors[$priority];
      if(empty($items)) continue;
    ?>
    <div class="card glass" style="margin-bottom:20px;border-left:4px solid <?= $color ?>;">
      <div class="card-header">
        <div class="card-title" style="font-size:14px;">
          <span style="display:inline-flex;align-items:center;gap:8px;">
            <span style="width:10px;height:10px;background:<?= $color ?>;border-radius:50%;<?= $priority=='CRITICAL'?'animation:pulse 2s infinite;':'' ?>"></span>
            <?= $priority ?> (<?= count($items) ?>)
          </span>
        </div>
      </div>
      <div class="card-body">
        <?php foreach($items as $alert): ?>
        <div style="display:flex;align-items:center;gap:14px;padding:12px;border-bottom:1px solid rgba(0,0,0,0.04);">
          <i class="fas <?= $alert['icon'] ?>" style="font-size:18px;color:<?= $alert['color'] ?>;width:24px;text-align:center;"></i>
          <div style="flex:1;">
            <div style="font-weight:600;font-size:14px;"><?= htmlspecialchars($alert['title']) ?></div>
            <div style="font-size:12px;opacity:0.6;"><?= htmlspecialchars($alert['detail']) ?></div>
          </div>
          <small style="opacity:0.4;"><?= date('H:i', strtotime($alert['time'])) ?></small>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>

    <?php if($totalAlerts == 0): ?>
    <div class="card glass" style="padding:60px;text-align:center;">
      <i class="fas fa-check-circle" style="font-size:48px;color:#10b981;margin-bottom:16px;"></i>
      <h3 style="font-weight:700;margin-bottom:8px;">All Clear!</h3>
      <p style="opacity:0.6;">No active alerts. System is operating normally.</p>
    </div>
    <?php endif; ?>

    <style>@keyframes pulse{0%{box-shadow:0 0 0 0 rgba(239,68,68,0.7)}70%{box-shadow:0 0 0 10px rgba(239,68,68,0)}}</style>
    <?php
}

renderLayout("Alert Dashboard", 'renderContent', $_SESSION['role'], $_SESSION['name']);
