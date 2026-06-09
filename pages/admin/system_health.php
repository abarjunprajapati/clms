<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    
    // DB health
    $dbVersion = db_single($conn, "SELECT VERSION() as v");
    $dbSize = db_single($conn, "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb FROM information_schema.TABLES WHERE table_schema = 'new_clms'");
    $dbTables = db_count($conn, "SELECT COUNT(*) c FROM information_schema.TABLES WHERE table_schema = 'new_clms'");
    
    // PHP info
    $phpVer = phpversion();
    $maxUpload = ini_get('upload_max_filesize');
    $memLimit = ini_get('memory_limit');
    $maxExec = ini_get('max_execution_time');
    
    // Error logs
    $errCheck = clms_db_query($conn, "SHOW TABLES LIKE 'system_error_logs'");
    $errors = [];
    $critErrors = 0;
    if($errCheck && clms_db_num_rows($errCheck) > 0) {
        $errors = db_fetch_all($conn, "SELECT * FROM system_error_logs WHERE resolved=0 ORDER BY created_at DESC LIMIT 15");
        $critErrors = db_count($conn, "SELECT COUNT(*) c FROM system_error_logs WHERE severity='critical' AND resolved=0");
    }
    
    // Lockdown status
    $lockdownCheck = clms_db_query($conn, "SHOW TABLES LIKE 'system_settings'");
    $lockdown = '0';
    if($lockdownCheck && clms_db_num_rows($lockdownCheck) > 0) {
        $ls = db_single($conn, "SELECT setting_value FROM system_settings WHERE setting_key='system_lockdown'");
        $lockdown = $ls['setting_value'] ?? '0';
    }
    
    // Super Admin activity
    $actCheck = clms_db_query($conn, "SHOW TABLES LIKE 'super_admin_activity_logs'");
    $recentActivity = [];
    if($actCheck && clms_db_num_rows($actCheck) > 0) {
        $recentActivity = db_fetch_all($conn, "SELECT sa.*, u.name as admin_name FROM super_admin_activity_logs sa LEFT JOIN users u ON sa.admin_id = u.id ORDER BY sa.created_at DESC LIMIT 10");
    }
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-heartbeat" style="color:#10b981;margin-right:10px;"></i> System Health Monitor</h2>
        <!-- <p class="page-subtitle">Database, PHP environment, error tracking, and Super Admin activity log.</p> -->
      </div>
      <div>
        <?php if($lockdown == '1'): ?>
        <span class="badge badge-danger" style="font-size:14px;padding:8px 16px;animation:pulse 2s infinite;"><i class="fas fa-lock"></i> LOCKDOWN ACTIVE</span>
        <?php else: ?>
        <span class="badge badge-success" style="font-size:14px;padding:8px 16px;"><i class="fas fa-check-circle"></i> System Operational</span>
        <?php endif; ?>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:20px;margin-bottom:20px;">
      <!-- DB Health -->
      <div class="card glass">
        <div class="card-header"><div class="card-title"><i class="fas fa-database" style="color:#6366f1;"></i> Database Health</div></div>
        <div class="card-body">
          <div class="health-row"><span>MariaDB/MySQL Version</span><span class="badge badge-info"><?= htmlspecialchars($dbVersion['v'] ?? '-') ?></span></div>
          <div class="health-row"><span>Database Size</span><span class="badge badge-info"><?= $dbSize['size_mb'] ?? '0' ?> MB</span></div>
          <div class="health-row"><span>Total Tables</span><span class="badge badge-info"><?= $dbTables ?></span></div>
          <div class="health-row"><span>Connection Status</span><span class="badge badge-success">Connected</span></div>
        </div>
      </div>

      <!-- PHP Health -->
      <div class="card glass">
        <div class="card-header"><div class="card-title"><i class="fab fa-php" style="color:#8b5cf6;font-size:20px;"></i> PHP Environment</div></div>
        <div class="card-body">
          <div class="health-row"><span>PHP Version</span><span class="badge badge-info"><?= $phpVer ?></span></div>
          <div class="health-row"><span>Max Upload Size</span><span class="badge badge-info"><?= $maxUpload ?></span></div>
          <div class="health-row"><span>Memory Limit</span><span class="badge badge-info"><?= $memLimit ?></span></div>
          <div class="health-row"><span>Max Execution Time</span><span class="badge badge-info"><?= $maxExec ?>s</span></div>
        </div>
      </div>
    </div>

    <!-- Super Admin Activity -->
    <?php if(!empty($recentActivity)): ?>
    <div class="card glass" style="margin-bottom:20px;">
      <div class="card-header"><div class="card-title"><i class="fas fa-user-secret" style="color:#dc2626;"></i> Super Admin Activity Log</div></div>
      <div class="card-body" style="padding:0;">
        <table class="data-table">
          <thead><tr><th>Admin</th><th>Action</th><th>Module</th><th>Severity</th><th>Time</th></tr></thead>
          <tbody>
          <?php foreach($recentActivity as $a):
            $sevCls = ['info'=>'badge-info','warning'=>'badge-warning','critical'=>'badge-danger','emergency'=>'badge-danger'];
          ?>
          <tr>
            <td><strong><?= htmlspecialchars($a['admin_name'] ?? 'Admin') ?></strong></td>
            <td><?= htmlspecialchars($a['action_type']) ?></td>
            <td><span class="badge badge-outline"><?= htmlspecialchars($a['target_module'] ?? '-') ?></span></td>
            <td><span class="badge <?= $sevCls[$a['severity']]??'badge-info' ?>"><?= strtoupper($a['severity']) ?></span></td>
            <td><small><?= date('d M, H:i', strtotime($a['created_at'])) ?></small></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- Error Logs -->
    <?php if(!empty($errors)): ?>
    <div class="card glass">
      <div class="card-header"><div class="card-title"><i class="fas fa-bug" style="color:#ef4444;"></i> Unresolved System Errors (<?= $critErrors ?> Critical)</div></div>
      <div class="card-body" style="padding:0;">
        <table class="data-table">
          <thead><tr><th>Severity</th><th>Message</th><th>Source</th><th>Time</th></tr></thead>
          <tbody>
          <?php foreach($errors as $e): ?>
          <tr>
            <td><span class="badge badge-<?= $e['severity']=='critical'?'danger':'warning' ?>"><?= strtoupper($e['severity']) ?></span></td>
            <td><small><?= htmlspecialchars(substr($e['message'],0,100)) ?></small></td>
            <td><code style="font-size:11px;"><?= htmlspecialchars($e['source'] ?? '-') ?></code></td>
            <td><small><?= date('d M, H:i', strtotime($e['created_at'])) ?></small></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <style>
    .health-row { display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid rgba(0,0,0,0.04); }
    .health-row:last-child { border-bottom:none; }
    @keyframes pulse{0%{box-shadow:0 0 0 0 rgba(239,68,68,0.7)}70%{box-shadow:0 0 0 10px rgba(239,68,68,0)}}
    </style>
    <?php
}

renderLayout("System Health", 'renderContent', $_SESSION['role'], $_SESSION['name']);
