<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_user', 'welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';
$role=$_SESSION['role'];
$name=$_SESSION['name']??'Welfare Officer';

function ws($c, $q) {
    return db_count($c, $q);
}
function wr($c, $q) {
    return db_fetch_all($c, $q);
}

function renderContent(){
global $conn;
try {
// Ensure required tables exist
$reqTables = ['document_verifications','compliance','noc_requests','audit_logs'];
foreach($reqTables as $tbl){
    $conn->query("CREATE TABLE IF NOT EXISTS $tbl (
        id INT AUTO_INCREMENT PRIMARY KEY,
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
$conn->query("CREATE TABLE IF NOT EXISTS contractor_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contractor_id INT NULL,
    doc_type VARCHAR(100) NULL,
    file_path VARCHAR(255) NULL,
    original_name VARCHAR(255) NULL,
    status VARCHAR(30) DEFAULT 'pending',
    remarks TEXT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    KEY idx_contractor (contractor_id),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
// Ensure audit_logs has needed columns
foreach(['action'=>'VARCHAR(100)','module'=>'VARCHAR(100)','user_id'=>'INT DEFAULT 0'] as $col=>$def){
    $c=$conn->query("SHOW COLUMNS FROM audit_logs LIKE '$col'");
    if(!$c||$c->num_rows===0) $conn->query("ALTER TABLE audit_logs ADD COLUMN $col $def");
}
$c=$conn->query("SHOW COLUMNS FROM document_verifications LIKE 'status'");
if(!$c||$c->num_rows===0) $conn->query("ALTER TABLE document_verifications ADD COLUMN status VARCHAR(50) DEFAULT 'pending'");

$pc=ws($conn,"SELECT COUNT(*)c FROM contractor_annexure2a WHERE status IN ('submitted', 'under_review', 'pending')");
$pw=ws($conn,"SELECT COUNT(*)c FROM workmen WHERE status='pending'");
$sc=ws($conn,"SELECT COUNT(*)c FROM workmen WHERE training_status='pending'");
$pg=ws($conn,"SELECT COUNT(*)c FROM gate_pass_request_workers WHERE status='approved'");
$ta=ws($conn,"SELECT COUNT(*)c FROM gate_pass_request_workers WHERE status='approved'"); // Both point to the same "ready" pool
$aa=ws($conn,"SELECT COUNT(*)c FROM gate_passes WHERE status='active'");
$ed=ws($conn,"SELECT COUNT(*)c FROM document_verifications WHERE status='expired'");
$bw=ws($conn,"SELECT COUNT(*)c FROM workmen WHERE status IN('blocked','temp_blocked','perm_blocked')");
$cp=ws($conn,"SELECT COUNT(*)c FROM contractor_annexure3a WHERE status='pending'");
$ab=ws($conn,"SELECT COUNT(*)c FROM workmen WHERE status='acc_generated' AND biometric_status='pending'");
$pd=ws($conn,"SELECT COUNT(*)c FROM document_verifications WHERE status IN('pending','reupload_required')");
$cdp=ws($conn,"SELECT COUNT(*)c FROM contractor_documents WHERE COALESCE(status,'pending') IN('pending','reupload_required')");
$np=ws($conn,"SELECT COUNT(*)c FROM noc_requests WHERE status='pending'");
$ar=ws($conn,"SELECT COUNT(*)c FROM workmen WHERE status='acc_return_pending'");
$pa=ws($conn,"SELECT COUNT(*)c FROM workmen WHERE status='temporary_issued'");
$sf=ws($conn,"SELECT COUNT(*)c FROM workmen WHERE training_status='failed'");
$sd=ws($conn,"SELECT COUNT(*)c FROM workmen WHERE training_status IN ('pass','passed','training_passed','qualified','completed') OR safety_training_status = 1");
$re=ws($conn,"SELECT COUNT(*)c FROM gate_passes WHERE status='active' AND valid_to BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)");

$gpl=wr($conn,"SELECT gp.pass_number,gp.pass_type,gp.status,w.name wn,c.contractor_name cn FROM gate_passes gp LEFT JOIN workmen w ON gp.workman_id=w.id LEFT JOIN contractors c ON w.contractor_id=c.id WHERE gp.status='pending' ORDER BY gp.created_at DESC LIMIT 5");
$pwl=wr($conn,"SELECT w.name,w.status,c.contractor_name FROM workmen w LEFT JOIN contractors c ON w.contractor_id=c.id WHERE w.training_status='pending' ORDER BY w.created_at DESC LIMIT 5");
$pcl=wr($conn,"SELECT vendor_name as contractor_name, vendor_code, status, created_at FROM contractor_annexure2a WHERE status IN ('submitted', 'under_review', 'pending') ORDER BY created_at DESC LIMIT 5");
$al=wr($conn,"SELECT l.action,l.module,l.created_at,u.name FROM audit_logs l LEFT JOIN users u ON l.user_id=u.id ORDER BY l.created_at DESC LIMIT 8");
} catch(Throwable $e) {
    $pc=$pw=$sc=$pg=$ta=$aa=$ed=$bw=$cp=$ab=$pd=$cdp=$np=$ar=$pa=$sf=$sd=$re=0;
    $gpl=$pwl=$pcl=$al=[];
    echo '<div class="card glass" style="margin:20px 0;border-left:4px solid #ef4444"><div class="card-body"><strong style="color:#ef4444">⚠ Database Error:</strong> '.htmlspecialchars($e->getMessage()).'<br><small>Some data may not load. Please run the migration script.</small></div></div>';
}
?>

<div class="content-header">
  <div>
    <h2 class="page-title"><i class="fas fa-briefcase" style="color:#3b82f6;margin-right:8px"></i>Welfare User Operations Center</h2>
    <!-- <p class="page-subtitle">Daily Execution Desk — Verification, Pass Issuance, Compliance Checking & Worker Lifecycle</p> -->
  </div>
  <div style="display:flex;gap:8px">
    <a href="reports.php" class="btn btn-outline"><i class="fas fa-chart-bar"></i> Reports</a>
  </div>
</div>

<!-- KPI CARDS -->
<div class="wu-kpi-grid">
  <?php $cards=[
    ['Pending Contractors',$pc,'fa-building','#f59e0b','approve_contractors.php'],
    ['Worker Verifications',$pw,'fa-user-plus','#6366f1','enrollment_monitor.php'],
    ['Safety Clearances',$sc,'fa-graduation-cap','#ec4899','training_monitor.php'],
    ['Pending Pass Requests',$pg,'fa-id-card-clip','#3b82f6','pending_requests.php'],
    ['Temporary Pass Issue',$ta,'fa-clock','#10b981','pending_requests.php'],
    ['Active ACC Cards',$aa,'fa-fingerprint','#7c3aed','acc_generation.php'],
    ['Expired Documents',$ed,'fa-file-circle-xmark','#ef4444','verify_documents.php'],
    ['Blocked Workers',$bw,'fa-user-slash','#ef4444','worker_block.php'],
    ['Compliance Pending',$cp,'fa-shield-halved','#f59e0b','compliance_monitor.php'],
    ['Awaiting Biometric',$ab,'fa-hand-pointer','#7c3aed','acc_generation.php'],
  ];
  foreach($cards as list($l,$v,$i,$c,$u)):?>
  <div class="wu-kpi" onclick="location.href='<?=$u?>'" style="cursor:pointer;border-left:4px solid <?=$c?>">
    <div class="wu-kpi-icon" style="background:<?=$c?>1a;color:<?=$c?>"><i class="fas <?=$i?>"></i></div>
    <div class="wu-kpi-val"><?=$v?></div>
    <div class="wu-kpi-lbl"><?=$l?></div>
    <?php if($v>0&&in_array($i,['fa-building','fa-user-plus','fa-id-card-clip','fa-shield-halved','fa-file-circle-xmark'])):?>
    <span class="badge badge-warning" style="margin-top:4px;font-size:10px">Action</span>
    <?php endif;?>
  </div>
  <?php endforeach;?>
</div>

<!-- SAP Integration Status -->
<div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-top:16px;">
  <div class="card glass" style="border-left: 4px solid #3b82f6; cursor:pointer;" onclick="location.href='sap_logs.php'">
    <div class="card-body" style="padding:16px; display:flex; align-items:center; gap:20px;">
      <div class="stat-icon" style="background:rgba(59,130,246,0.1); color:#3b82f6; width:50px; height:50px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:24px;">
        <i class="fas fa-sync-alt fa-spin" style="animation-duration: 4s;"></i>
      </div>
      <div>
        <h5 style="margin:0; font-size:16px; font-weight:800; color:#1e293b;">SAP S/4 HANA Integration</h5>
        <p style="margin:5px 0 0 0; font-size:13px; color:#10b981; font-weight:600;">
          <i class="fas fa-circle" style="font-size:8px; vertical-align:middle; margin-right:5px;"></i> Connected & Simulated
        </p>
      </div>
      <div style="margin-left:auto;">
        <span class="badge badge-primary">View Logs</span>
      </div>
    </div>
  </div>
  
  <div class="card glass" style="border-left: 4px solid #10b981;">
    <div class="card-body" style="padding:16px; display:flex; align-items:center; gap:20px;">
      <div class="stat-icon" style="background:rgba(16,185,129,0.1); color:#10b981; width:50px; height:50px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:24px;">
        <i class="fas fa-database"></i>
      </div>
      <div>
        <h5 style="margin:0; font-size:16px; font-weight:800; color:#1e293b;">Master Data Sync</h5>
        <p style="margin:5px 0 0 0; font-size:13px; color:var(--text-muted);">
          Last Sync: <?= date('d M, H:i') ?>
        </p>
      </div>
      <div style="margin-left:auto; text-align:right;">
        <div style="font-size:18px; font-weight:800; color:#1e293b;"><?= db_count($conn, "SELECT COUNT(*) FROM sap_workers") ?></div>
        <div style="font-size:10px; color:var(--text-muted); font-weight:600; text-transform:uppercase;">Synced Workers</div>
      </div>
    </div>
  </div>
</div>

<!-- Quick Actions -->
<div class="card glass" style="margin:18px 0">
  <div class="card-header"><div class="card-title"><i class="fas fa-bolt"></i> Quick Actions</div></div>
  <div class="card-body"><div style="display:flex;gap:8px;flex-wrap:wrap">
    <a href="pending_requests.php" class="btn btn-primary"><i class="fas fa-id-card"></i> Gate Pass Verification</a>
    <a href="pending_requests.php" class="btn btn-outline"><i class="fas fa-clock"></i> Issue Temp Pass</a>
    <a href="acc_generation.php" class="btn btn-outline"><i class="fas fa-fingerprint"></i> Approve ACC</a>
    <a href="worker_block.php" class="btn btn-danger"><i class="fas fa-user-slash"></i> Block Worker</a>
    <a href="compliance_monitor.php" class="btn btn-outline"><i class="fas fa-shield-check"></i> Verify Compliance</a>
    <a href="noc_transfer.php" class="btn btn-outline"><i class="fas fa-exchange-alt"></i> NOC Transfer</a>
    <a href="verify_documents.php" class="btn btn-outline"><i class="fas fa-file-shield"></i> Verify Documents</a>
    <a href="verify_documents.php#contractor-documents" class="btn btn-outline"><i class="fas fa-building-shield"></i> Contractor Docs <?= $cdp > 0 ? '(' . $cdp . ')' : '' ?></a>
  </div></div>
</div>

<!-- 5 OPERATIONAL DESKS -->
<div class="wu-desks">

  <!-- DESK 1: Registration & Enrollment Verification -->
  <div class="wu-desk">
    <div class="wu-desk-head" style="background:linear-gradient(135deg,#667eea,#764ba2)"><i class="fas fa-id-card"></i> Registration & Enrollment</div>
    <div class="wu-desk-body">
      <a class="wu-desk-item" href="approve_contractors.php"><span><i class="fas fa-building-circle-check"></i> Contractor Verification (2A)</span><span class="wu-badge"><?=$pc?></span></a>
      <a class="wu-desk-item" href="enrollment_monitor.php"><span><i class="fas fa-users"></i> Worker Enrollment (4A)</span><span class="wu-badge"><?=$pw?></span></a>
      <a class="wu-desk-item" href="verify_documents.php"><span><i class="fas fa-file-shield"></i> Document Verification</span><span class="wu-badge"><?=$pd?></span></a>
      <a class="wu-desk-item" href="verify_documents.php#contractor-documents"><span><i class="fas fa-building-shield"></i> Contractor Uploaded Docs</span><span class="wu-badge"><?=$cdp?></span></a>
    </div>
  </div>

  <!-- DESK 2: Safety Training Coordination -->
  <div class="wu-desk">
    <div class="wu-desk-head" style="background:linear-gradient(135deg,#11998e,#38ef7d)"><i class="fas fa-user-shield"></i> Safety & Training</div>
    <div class="wu-desk-body">
      <a class="wu-desk-item" href="training_monitor.php"><span><i class="fas fa-graduation-cap"></i> Training Queue</span><span class="wu-badge"><?=$sc?></span></a>
      <a class="wu-desk-item" href="training_monitor.php?filter=passed"><span><i class="fas fa-check-circle"></i> Safety Cleared</span><span class="wu-badge" style="background:#dcfce7;color:#16a34a"><?=$sd?></span></a>
      <a class="wu-desk-item" href="training_monitor.php?filter=failed"><span><i class="fas fa-times-circle"></i> Failed — Reapply</span><span class="wu-badge" style="background:#fee2e2;color:#dc2626"><?=$sf?></span></a>
    </div>
  </div>

  <!-- DESK 3: Pass Issuance -->
  <div class="wu-desk">
    <div class="wu-desk-head" style="background:linear-gradient(135deg,#4776e6,#8e54e9)"><i class="fas fa-print"></i> Pass Issuance</div>
    <div class="wu-desk-body">
      <a class="wu-desk-item" href="pending_requests.php"><span><i class="fas fa-clipboard-check"></i> Gate Pass Verification</span><span class="wu-badge"><?=$pg?></span></a>
      <a class="wu-desk-item" href="pending_requests.php"><span><i class="fas fa-clock"></i> Temporary Pass Issue</span><span class="wu-badge" style="background:#dbeafe;color:#2563eb"><?=$ta?></span></a>
      <a class="wu-desk-item" href="acc_generation.php"><span><i class="fas fa-fingerprint"></i> ACC Generation</span><span class="wu-badge"><?=$pa?></span></a>
      <a class="wu-desk-item" href="acc_generation.php?filter=biometric"><span><i class="fas fa-hand-pointer"></i> Biometric Enrollment</span><span class="wu-badge"><?=$ab?></span></a>
      <a class="wu-desk-item" href="pass_status.php"><span><i class="fas fa-satellite-dish"></i> Pass Renewal Queue</span><span class="wu-badge"><?=$re?></span></a>
    </div>
  </div>

  <!-- DESK 4: Lifecycle Management -->
  <div class="wu-desk">
    <div class="wu-desk-head" style="background:linear-gradient(135deg,#e53935,#e35d5b)"><i class="fas fa-user-lock"></i> Lifecycle Desk</div>
    <div class="wu-desk-body">
      <a class="wu-desk-item" href="worker_block.php"><span><i class="fas fa-user-slash"></i> Block / Unblock Workers</span><span class="wu-badge" style="background:#fee2e2;color:#dc2626"><?=$bw?></span></a>
      <a class="wu-desk-item" href="blocking_control.php"><span><i class="fas fa-building-circle-xmark"></i> Block / Suspend Contractor</span><span class="wu-badge">Ctrl</span></a>
      <a class="wu-desk-item" href="noc_transfer.php"><span><i class="fas fa-exchange-alt"></i> Company Transfer / NOC</span><span class="wu-badge"><?=$np?></span></a>
      <a class="wu-desk-item" href="acc_return_queue.php"><span><i class="fas fa-undo"></i> Relieving & ACC Return</span><span class="wu-badge"><?=$ar?></span></a>
    </div>
  </div>

  <!-- DESK 5: Compliance & Attendance -->
  <div class="wu-desk">
    <div class="wu-desk-head" style="background:linear-gradient(135deg,#f093fb,#f5576c)"><i class="fas fa-chart-line"></i> Compliance & Monitoring</div>
    <div class="wu-desk-body">
      <a class="wu-desk-item" href="compliance_monitor.php"><span><i class="fas fa-shield-check"></i> ESI/PF/KLWF/CLA Audit</span><span class="wu-badge"><?=$cp?></span></a>
      <a class="wu-desk-item" href="attendance_monitor.php"><span><i class="fas fa-calendar-check"></i> Daily Attendance</span><span class="wu-badge" style="background:#dcfce7;color:#16a34a">Live</span></a>
      <a class="wu-desk-item" href="skill_productivity.php"><span><i class="fas fa-star"></i> Skill & Productivity</span><span class="wu-badge" style="background:#fef3c7;color:#d97706">Rate</span></a>
      <a class="wu-desk-item" href="reports.php"><span><i class="fas fa-chart-bar"></i> Reports & Export</span><span class="wu-badge" style="background:#e0e7ff;color:#4f46e5">All</span></a>
    </div>
  </div>
</div>

<!-- DATA TABLES -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px">

  <!-- Pending Gate Passes -->
  <div class="card glass">
    <div class="card-header"><div class="card-title"><i class="fas fa-id-card-clip"></i> Pending Gate Pass Requests</div>
      <a href="gatepass_monitor.php" class="btn btn-sm btn-primary">View All</a></div>
    <div class="card-body" style="padding:0">
      <table class="data-table"><thead><tr><th>Pass#</th><th>Worker</th><th>Contractor</th><th>Type</th><th>Action</th></tr></thead>
      <tbody>
        <?php if(empty($gpl)):?><tr><td colspan="5" style="text-align:center;padding:20px;color:#9ca3af">No pending requests</td></tr>
        <?php else:foreach($gpl as $g):?>
        <tr>
          <td style="font-size:11px"><?=htmlspecialchars($g['pass_number']??'-')?></td>
          <td style="font-size:12px;font-weight:600"><?=htmlspecialchars($g['wn']??'-')?></td>
          <td style="font-size:11px;color:#6b7280"><?=htmlspecialchars($g['cn']??'-')?></td>
          <td><span class="badge <?=$g['pass_type']==='temporary'?'badge-warning':'badge-info'?>"><?=ucfirst($g['pass_type'])?></span></td>
          <td><a href="gatepass_monitor.php" class="btn btn-sm btn-primary">Review</a></td>
        </tr>
        <?php endforeach;endif;?>
      </tbody></table>
    </div>
  </div>

  <!-- Pending Contractors -->
  <div class="card glass">
    <div class="card-header"><div class="card-title"><i class="fas fa-building"></i> Pending Contractor Verification</div>
      <a href="approve_contractors.php" class="btn btn-sm btn-primary">View All</a></div>
    <div class="card-body" style="padding:0">
      <table class="data-table"><thead><tr><th>Contractor</th><th>Vendor Code</th><th>Date</th><th>Action</th></tr></thead>
      <tbody>
        <?php if(empty($pcl)):?><tr><td colspan="4" style="text-align:center;padding:20px;color:#9ca3af">No pending contractors</td></tr>
        <?php else:foreach($pcl as $c):?>
        <tr>
          <td style="font-size:12px;font-weight:600"><?=htmlspecialchars($c['contractor_name'])?></td>
          <td style="font-size:11px;color:#6b7280"><?=htmlspecialchars($c['vendor_code']??'-')?></td>
          <td style="font-size:11px"><?=date('d M',strtotime($c['created_at']))?></td>
          <td><a href="approve_contractors.php" class="btn btn-sm btn-primary">Verify</a></td>
        </tr>
        <?php endforeach;endif;?>
      </tbody></table>
    </div>
  </div>
</div>

<!-- Active Gate Passes (NEW SECTION) -->
<div class="card glass" style="margin-top:20px">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-check-double" style="color:#10b981"></i> Issued Gate Passes (Active)</div>
    <a href="gatepass_monitor.php" class="btn btn-sm btn-outline">Monitoring Center</a>
  </div>
  <div class="card-body" style="padding:0">
    <table class="data-table">
      <thead>
        <tr>
          <th>Worker</th>
          <th>Contractor</th>
          <th>ACC / Temp ID</th>
          <th>Validity</th>
          <th>Type</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $active_passes = wr($conn, "SELECT w.*, c.contractor_name FROM workmen w JOIN contractors c ON w.contractor_id = c.id WHERE w.status IN ('temporary_issued', 'acc_generated', 'permanent_active') ORDER BY w.updated_at DESC LIMIT 8");
        if(empty($active_passes)): ?>
        <tr><td colspan="6" style="text-align:center;padding:20px;color:#9ca3af">No active passes found</td></tr>
        <?php else: foreach($active_passes as $ap): ?>
        <tr>
          <td><strong><?= htmlspecialchars($ap['name']) ?></strong></td>
          <td style="font-size:11px"><?= htmlspecialchars($ap['contractor_name']) ?></td>
          <td><code><?= $ap['acc_number'] ?: ($ap['temp_id'] ?: 'N/A') ?></code></td>
          <td><?= ($ap['valid_to'] ?? $ap['temp_valid_to']) ? date('d M Y', strtotime($ap['valid_to'] ?? $ap['temp_valid_to'])) : '—' ?></td>
          <td><span class="badge <?= $ap['acc_number'] ? 'badge-primary' : 'badge-warning' ?>"><?= $ap['acc_number'] ? 'Permanent' : 'Temporary' ?></span></td>
          <td>
            <span class="badge badge-success"><?= strtoupper(str_replace('_', ' ', $ap['status'])) ?></span>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px">

  <!-- Workers Awaiting Safety -->
  <div class="card glass">
    <div class="card-header"><div class="card-title"><i class="fas fa-graduation-cap"></i> Workers Awaiting Safety</div>
      <a href="training_monitor.php" class="btn btn-sm btn-outline">All</a></div>
    <div class="card-body" style="padding:0">
      <table class="data-table"><thead><tr><th>Worker</th><th>Contractor</th><th>Status</th></tr></thead>
      <tbody>
        <?php if(empty($pwl)):?><tr><td colspan="3" style="text-align:center;padding:20px;color:#9ca3af">None pending</td></tr>
        <?php else:foreach($pwl as $w):?>
        <tr>
          <td style="font-size:12px;font-weight:600"><?=htmlspecialchars($w['name'])?></td>
          <td style="font-size:11px;color:#6b7280"><?=htmlspecialchars($w['contractor_name']??'-')?></td>
          <td><span class="badge badge-warning">Training Pending</span></td>
        </tr>
        <?php endforeach;endif;?>
      </tbody></table>
    </div>
  </div>

  <!-- Audit Trail -->
  <div class="card glass">
    <div class="card-header"><div class="card-title"><i class="fas fa-history"></i> My Recent Actions</div></div>
    <div class="card-body" style="padding:0">
      <table class="data-table"><thead><tr><th>Action</th><th>Module</th><th>Time</th></tr></thead>
      <tbody>
        <?php foreach($al as $l):?>
        <tr>
          <td><span class="badge badge-info"><?=strtoupper(substr($l['action'],0,14))?></span></td>
          <td style="font-size:12px"><?=htmlspecialchars($l['module'])?></td>
          <td style="font-size:11px"><?=date('d M H:i',strtotime($l['created_at']))?></td>
        </tr>
        <?php endforeach;?>
      </tbody></table>
    </div>
  </div>

  <!-- SAP Sync Monitor (NEW) -->
  <div class="card glass">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-sync" style="color:#3b82f6"></i> Worker SAP Status</div>
      <a href="sap_logs.php" class="btn btn-sm btn-outline">Full Logs</a>
    </div>
    <div class="card-body" style="padding:0">
      <table class="data-table">
        <thead>
          <tr>
            <th>Worker</th>
            <th>ACC No</th>
            <th>SAP Status</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $sap_w = wr($conn, "SELECT worker_name, acc_no, sap_status FROM sap_workers ORDER BY synced_at DESC LIMIT 5");
          if(empty($sap_w)): ?>
          <tr><td colspan="3" style="text-align:center;padding:20px;color:#9ca3af">No workers synced yet</td></tr>
          <?php else: foreach($sap_w as $sw): ?>
          <tr>
            <td style="font-size:12px;font-weight:600"><?= htmlspecialchars($sw['worker_name']) ?></td>
            <td style="font-size:11px"><?= htmlspecialchars($sw['acc_no']) ?></td>
            <td><span class="badge badge-success"><?= strtoupper($sw['sap_status']) ?></span></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Notifications -->
<div class="card glass" style="margin-top:20px">
  <div class="card-header"><div class="card-title"><i class="fas fa-bell"></i> Notification Panel</div></div>
  <div class="card-body">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:10px">
      <?php $notifs=[
        [$pc>0?"$pc new contractor requests":'No new contractors',$pc>0?'#f59e0b':'#10b981','fa-building'],
        [$sd>0?"$sd workers safety cleared":'No safety completions',$sd>0?'#10b981':'#6b7280','fa-graduation-cap'],
        [$ed>0?"$ed documents expired":'All documents valid',$ed>0?'#ef4444':'#10b981','fa-file-circle-xmark'],
        [$np>0?"$np transfer requests pending":'No transfer requests',$np>0?'#6366f1':'#10b981','fa-exchange-alt'],
        [$cp>0?"$cp compliance items pending":'Compliance OK',$cp>0?'#ef4444':'#10b981','fa-shield-halved'],
        [$re>0?"$re passes expiring soon":'No expiring passes',$re>0?'#f59e0b':'#10b981','fa-clock'],
      ];
      foreach($notifs as list($m,$c,$i)):?>
      <div style="display:flex;align-items:center;gap:10px;padding:10px;border-radius:8px;border-left:3px solid <?=$c?>;background:<?=$c?>08">
        <i class="fas <?=$i?>" style="color:<?=$c?>;font-size:13px"></i>
        <span style="font-size:12px;font-weight:500"><?=$m?></span>
      </div>
      <?php endforeach;?>
    </div>
  </div>
</div>

<style>
.wu-kpi-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:14px}
.wu-kpi{background:#fff;border-radius:12px;padding:16px;box-shadow:0 2px 8px rgba(0,0,0,.06);transition:.2s}
.wu-kpi:hover{transform:translateY(-3px);box-shadow:0 6px 20px rgba(0,0,0,.1)}
.wu-kpi-icon{width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;margin-bottom:8px}
.wu-kpi-val{font-size:26px;font-weight:800;color:#1e293b;line-height:1}
.wu-kpi-lbl{font-size:11px;color:#6b7280;margin-top:4px;font-weight:600}
.wu-desks{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:16px;margin-top:0}
.wu-desk{background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08)}
.wu-desk-head{padding:13px 16px;font-weight:800;font-size:13px;color:#fff;display:flex;align-items:center;gap:10px}
.wu-desk-body{padding:10px}
.wu-desk-item{display:flex;justify-content:space-between;align-items:center;padding:10px 12px;text-decoration:none;color:#475569;border-radius:9px;transition:.2s;margin-bottom:4px;font-size:13px;font-weight:600}
.wu-desk-item:hover{background:#f1f5f9;transform:translateX(5px);color:#1e293b}
.wu-desk-item i{margin-right:6px;width:14px;text-align:center}
.wu-badge{background:#f1f5f9;color:#1e293b;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:800;min-width:22px;text-align:center}
@media(max-width:1200px){.wu-kpi-grid{grid-template-columns:repeat(auto-fit,minmax(160px,1fr))}}
</style>
<?php
}
renderLayout("Welfare User Dashboard",'renderContent',$role,$name);
?>
