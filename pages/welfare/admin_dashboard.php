<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';
$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Admin';

function ws($c, $q) {
    return db_count($c, $q);
}
function wr($c, $q) {
    return db_fetch_all($c, $q);
}

function renderContent(){
global $conn;
try {
// Ensure required tables exist (safe on repeated calls)
$reqTables = ['document_verifications','compliance','noc_requests','audit_logs'];
foreach($reqTables as $tbl){
    $conn->query("CREATE TABLE IF NOT EXISTS $tbl (
        id INT AUTO_INCREMENT PRIMARY KEY,
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
// Add type column to compliance if missing
$chk=$conn->query("SHOW COLUMNS FROM compliance LIKE 'type'");
if($chk && $chk->num_rows===0) $conn->query("ALTER TABLE compliance ADD COLUMN type VARCHAR(50) AFTER id");
// Ensure noc_requests has needed columns
$chk2=$conn->query("SHOW COLUMNS FROM noc_requests LIKE 'status'");
if(!$chk2 || $chk2->num_rows===0) $conn->query("ALTER TABLE noc_requests ADD COLUMN status VARCHAR(50) DEFAULT 'pending'");
// Ensure audit_logs has needed columns
foreach(['action'=>'VARCHAR(100)','module'=>'VARCHAR(100)','user_id'=>'INT DEFAULT 0'] as $col=>$def){
    $c=$conn->query("SHOW COLUMNS FROM audit_logs LIKE '$col'");
    if(!$c||$c->num_rows===0) $conn->query("ALTER TABLE audit_logs ADD COLUMN $col $def");
}
// Ensure document_verifications has needed columns
$c=$conn->query("SHOW COLUMNS FROM document_verifications LIKE 'status'");
if(!$c||$c->num_rows===0) $conn->query("ALTER TABLE document_verifications ADD COLUMN status VARCHAR(50) DEFAULT 'pending'");
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

// KPIs
$tc=ws($conn,"SELECT COUNT(*)c FROM contractors");
$tw=ws($conn,"SELECT COUNT(*)c FROM workmen");
$acc=ws($conn,"SELECT COUNT(*)c FROM gate_passes WHERE pass_type='permanent' AND status='active'");
$tmp=ws($conn,"SELECT COUNT(*)c FROM gate_passes WHERE pass_type='temporary' AND status='active'");
$pa=ws($conn,"SELECT COUNT(*)c FROM contractors WHERE status='pending'");
$ed=ws($conn,"SELECT COUNT(*)c FROM document_verifications WHERE status='expired'");
$bw=ws($conn,"SELECT COUNT(*)c FROM workmen WHERE status IN('blocked','temp_blocked','perm_blocked')");
$sc=ws($conn,"SELECT COUNT(*)c FROM contractors WHERE status IN('blocked','suspended')");
$cp=ws($conn,"SELECT COUNT(*)c FROM compliance WHERE status='pending'");
$sp=ws($conn,"SELECT COUNT(*)c FROM workmen WHERE training_status='pending'");
$aw=ws($conn,"SELECT COUNT(*)c FROM workmen WHERE status='active'");
$te=ws($conn,"SELECT COUNT(*)c FROM gate_passes WHERE pass_type='temporary' AND valid_to<CURDATE()");
$pp=ws($conn,"SELECT COUNT(*)c FROM gate_passes WHERE status='pending'");
$tu=ws($conn,"SELECT COUNT(*)c FROM users");
$np=ws($conn,"SELECT COUNT(*)c FROM noc_requests WHERE status='pending'");
$cdp=ws($conn,"SELECT COUNT(*)c FROM contractor_documents WHERE COALESCE(status,'pending') IN('pending','reupload_required')");

// Data
$ubr=wr($conn,"SELECT role,COUNT(*)cnt FROM users GROUP BY role ORDER BY cnt DESC LIMIT 10");
$rc=wr($conn,"SELECT contractor_name,vendor_code,status,created_at FROM contractors ORDER BY created_at DESC LIMIT 6");
$al=wr($conn,"SELECT l.action,l.module,l.created_at,u.name FROM audit_logs l LEFT JOIN users u ON l.user_id=u.id ORDER BY l.created_at DESC LIMIT 10");
$dp=wr($conn,"SELECT DATE(created_at)d,COUNT(*)c FROM gate_passes WHERE created_at>=DATE_SUB(CURDATE(),INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY d");
} catch(Throwable $e) {
    $tc=$tw=$acc=$tmp=$pa=$ed=$bw=$sc=$cp=$sp=$aw=$te=$pp=$tu=$np=$cdp=0;
    $ubr=$rc=$al=$dp=[];
    echo '<div class="card glass" style="margin:20px 0;border-left:4px solid #ef4444"><div class="card-body"><strong style="color:#ef4444">⚠ Database Error:</strong> '.htmlspecialchars($e->getMessage()).'<br><small>Some data may not load. Please run the migration script.</small></div></div>';
}
?>
<div class="content-header">
  <div>
    <h2 class="page-title"><i class="fas fa-user-tie" style="color:#6366f1;margin-right:8px"></i>Welfare Admin Control Center</h2>
    <!-- <p class="page-subtitle">System Supervision — Users, Pass Policy, Compliance, Workflow & Integration Control</p> -->
  </div>
  <div style="display:flex;gap:8px">
    <a href="reports.php" class="btn btn-outline"><i class="fas fa-file-export"></i> Reports</a>
    <a href="compliance_monitor.php" class="btn btn-primary"><i class="fas fa-shield-check"></i> Compliance</a>
  </div>
</div>

<!-- ROW 1: 10 KPI CARDS -->
<div class="wa-kpi-grid">
  <?php
  $cards = [
    ['Total Contractors',$tc,'fa-building','#3b82f6','entity_directory.php'],
    ['Total Workers',$tw,'fa-users','#10b981','enrollment_monitor.php'],
    ['Active ACC Cards',$acc,'fa-fingerprint','#7c3aed','acc_tracking.php'],
    ['Temporary Passes',$tmp,'fa-clock','#f59e0b','temp_pass_control.php'],
    ['Pending Approvals',$pa+$pp,'fa-hourglass-half','#6366f1','pending_requests.php'],
    ['Expired Documents',$ed,'fa-file-circle-xmark','#ef4444','verify_documents.php'],
    ['Blocked Workers',$bw,'fa-user-slash','#ef4444','worker_block.php'],
    ['Suspended Contractors',$sc,'fa-building-circle-xmark','#dc2626','blocking_control.php'],
    ['Compliance Pending',$cp,'fa-shield-halved','#f59e0b','compliance_monitor.php'],
    ['Safety Pending',$sp,'fa-graduation-cap','#ec4899','training_monitor.php'],
  ];
  foreach($cards as list($lbl,$val,$ico,$col,$url)):?>
  <div class="wa-kpi" onclick="location.href='<?=$url?>'" style="cursor:pointer;border-left:4px solid <?=$col?>">
    <div class="wa-kpi-icon" style="background:<?=$col?>1a;color:<?=$col?>"><i class="fas <?=$ico?>"></i></div>
    <div class="wa-kpi-val"><?=$val?></div>
    <div class="wa-kpi-lbl"><?=$lbl?></div>
    <?php if($val>0 && in_array($ico,['fa-hourglass-half','fa-shield-halved','fa-file-circle-xmark'])):?>
    <span class="badge badge-warning" style="margin-top:4px;font-size:10px">Action</span>
    <?php endif;?>
  </div>
  <?php endforeach;?>
</div>

<!-- ROW 2: Quick Actions -->
<div class="card glass" style="margin:20px 0">
  <div class="card-header"><div class="card-title"><i class="fas fa-bolt"></i> Quick Actions</div></div>
  <div class="card-body">
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <a href="../admin/create_user.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Create User</a>
      <a href="pass_limits.php" class="btn btn-primary"><i class="fas fa-sliders-h"></i> Configure Pass Limits</a>
      <a href="approve_contractors.php" class="btn btn-outline"><i class="fas fa-check-circle"></i> Monitor Contractors</a>
      <a href="verify_documents.php#contractor-documents" class="btn btn-outline"><i class="fas fa-building-shield"></i> Contractor Docs <?= $cdp > 0 ? '(' . $cdp . ')' : '' ?></a>
      <a href="sap_logs.php" class="btn btn-outline"><i class="fas fa-sync"></i> SAP Logs</a>
      <a href="compliance_monitor.php" class="btn btn-outline"><i class="fas fa-shield-check"></i> Compliance Review</a>
      <a href="worker_block.php" class="btn btn-danger"><i class="fas fa-user-slash"></i> Blocking Control</a>
      <a href="../admin/audit_logs.php" class="btn btn-outline"><i class="fas fa-history"></i> Audit Logs</a>
      <a href="sap_monitor.php" class="btn btn-outline"><i class="fas fa-sync"></i> SAP Monitor</a>
    </div>
  </div>
</div>

<!-- ROW 3: User Mgmt + Pass Categories + Workflow -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:20px">

  <!-- User Management -->
  <div class="card glass">
    <div class="card-header"><div class="card-title"><i class="fas fa-users-cog"></i> User Management</div>
      <a href="../admin/users.php" class="btn btn-sm btn-outline">Manage</a></div>
    <div class="card-body">
      <div style="text-align:center;margin-bottom:12px">
        <div style="font-size:32px;font-weight:800;color:#6366f1"><?=$tu?></div>
        <div style="font-size:11px;color:#6b7280">Total System Users</div>
      </div>
      <?php foreach($ubr as $u):?>
      <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f1f5f9;font-size:12px">
        <span style="font-weight:600"><?=ucwords(str_replace('_',' ',$u['role']))?></span>
        <span style="font-weight:800;color:#6366f1"><?=$u['cnt']?></span>
      </div>
      <?php endforeach;?>
      <div style="margin-top:10px;display:flex;gap:6px">
        <a href="../admin/create_user.php" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Add</a>
        <a href="../admin/users.php" class="btn btn-sm btn-outline"><i class="fas fa-users-cog"></i> Manage</a>
        <a href="../admin/audit_logs.php" class="btn btn-sm btn-outline"><i class="fas fa-history"></i> Audit</a>
      </div>
    </div>
  </div>

  <!-- Pass Category / Limit Config -->
  <div class="card glass">
    <div class="card-header"><div class="card-title"><i class="fas fa-sliders-h"></i> Pass Limit Config (5A)</div>
      <a href="pass_limits.php" class="btn btn-sm btn-outline">Edit</a></div>
    <div class="card-body">
      <?php $cats=[
        ['Contractor Pass',2,'#3b82f6','fa-hard-hat'],
        ['Representative',2,'#6366f1','fa-user-tie'],
        ['Supervisor (1:50)',1,'#10b981','fa-user-shield'],
        ['Workman Pass','∞','#f59e0b','fa-users'],
        ['Temporary (30d)','30d','#ec4899','fa-clock'],
        ['Permanent ACC','∞','#7c3aed','fa-fingerprint'],
        ['Visitor Pass',5,'#ef4444','fa-id-badge'],
      ];
      foreach($cats as list($l,$v,$c,$i)):?>
      <div style="display:flex;align-items:center;gap:8px;padding:7px 0;border-bottom:1px solid #f1f5f9">
        <div style="width:26px;height:26px;border-radius:6px;background:<?=$c?>1a;color:<?=$c?>;display:flex;align-items:center;justify-content:center;font-size:10px"><i class="fas <?=$i?>"></i></div>
        <div style="flex:1;font-size:12px;font-weight:600"><?=$l?></div>
        <span style="font-size:12px;font-weight:800;color:<?=$c?>"><?=$v?></span>
      </div>
      <?php endforeach;?>
      <a href="pass_limits.php" class="btn btn-primary" style="width:100%;margin-top:10px;text-align:center"><i class="fas fa-edit"></i> Manage Limits</a>
    </div>
  </div>

  <!-- Workflow Control -->
  <div class="card glass">
    <div class="card-header"><div class="card-title"><i class="fas fa-sitemap"></i> Workflow Control</div></div>
    <div class="card-body">
      <?php $wf=[
        ['Contractor Approval','Multi-level','#f59e0b','fa-building','approve_contractors.php'],
        ['Worker Enrollment','Auto-verify','#6366f1','fa-user-plus','enrollment_monitor.php'],
        ['Safety Training','Batch assign','#10b981','fa-graduation-cap','training_monitor.php'],
        ['Gate Pass Flow','Checklist','#3b82f6','fa-id-card-clip','gatepass_monitor.php'],
        ['ACC Approval','SAP+Bio','#7c3aed','fa-fingerprint','acc_tracking.php'],
        ['Renewal Flow','Auto-notify','#ec4899','fa-redo','pass_validity.php'],
        ['Blocking Rules','Escalation','#ef4444','fa-user-slash','worker_block.php'],
      ];
      foreach($wf as list($l,$t,$c,$i,$url)):?>
      <a href="<?=$url?>" style="display:flex;align-items:center;gap:8px;padding:7px 0;border-bottom:1px solid #f1f5f9;text-decoration:none;color:inherit">
        <div style="width:26px;height:26px;border-radius:6px;background:<?=$c?>1a;color:<?=$c?>;display:flex;align-items:center;justify-content:center;font-size:10px"><i class="fas <?=$i?>"></i></div>
        <div style="flex:1;font-size:12px;font-weight:600"><?=$l?></div>
        <span style="font-size:10px;background:#f1f5f9;padding:2px 6px;border-radius:4px;font-weight:600"><?=$t?></span>
      </a>
      <?php endforeach;?>
    </div>
  </div>
</div>

<!-- ROW 4: Document Master + Compliance + Contractor Monitor -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:20px">

  <!-- Document Master -->
  <div class="card glass">
    <div class="card-header"><div class="card-title"><i class="fas fa-file-shield"></i> Document Master</div>
      <a href="verify_documents.php#contractor-documents" class="btn btn-sm btn-outline">Verify</a></div>
    <div class="card-body">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;padding:10px;border-radius:8px;background:#f8fafc;border:1px solid #e2e8f0">
        <span style="font-size:12px;font-weight:800;color:#334155"><i class="fas fa-building-shield"></i> Contractor Docs Pending</span>
        <a href="verify_documents.php#contractor-documents" class="badge badge-warning" style="text-decoration:none;font-size:11px"><?= $cdp ?></a>
      </div>
      <div style="font-size:11px;font-weight:700;color:#6b7280;margin-bottom:8px;text-transform:uppercase">Contractor Documents</div>
      <?php foreach(['GST Certificate','PF Registration','ESI Registration','Work Order','PAN Card'] as $d):?>
      <div style="display:flex;justify-content:space-between;padding:4px 0;font-size:12px"><span><?=$d?></span><span class="badge badge-success" style="font-size:9px">Mandatory</span></div>
      <?php endforeach;?>
      <div style="font-size:11px;font-weight:700;color:#6b7280;margin:10px 0 8px;text-transform:uppercase">Worker Documents</div>
      <?php foreach(['Aadhaar Card','Photo','Medical Fitness','Insurance','Police Clearance','Training Certificate'] as $d):?>
      <div style="display:flex;justify-content:space-between;padding:4px 0;font-size:12px"><span><?=$d?></span><span class="badge badge-success" style="font-size:9px">Mandatory</span></div>
      <?php endforeach;?>
    </div>
  </div>

  <!-- Compliance Monitor -->
  <div class="card glass">
    <div class="card-header"><div class="card-title"><i class="fas fa-shield-check"></i> Compliance Status</div>
      <a href="compliance_monitor.php" class="btn btn-sm btn-outline">Full</a></div>
    <div class="card-body">
      <?php $ci=[
        ['ESI Challan','esi','#ef4444'],['PF Challan','pf','#f59e0b'],
        ['KLWF','klwf','#6366f1'],['Insurance','insurance','#3b82f6'],
        ['CLA License','cla','#7c3aed'],['Muster Roll','muster_roll','#ec4899'],
      ];
      foreach($ci as list($l,$t,$c)):
        $v=ws($conn,"SELECT COUNT(*)c FROM compliance WHERE type='{$t}' AND status='pending'");?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f1f5f9">
        <span style="font-size:13px;font-weight:600"><?=$l?></span>
        <span style="font-weight:800;color:<?=$v>0?$c:'#10b981'?>"><?=$v>0?$v.' Pending':'✓ OK'?></span>
      </div>
      <?php endforeach;?>
      <div style="margin-top:10px;display:flex;gap:6px">
        <a href="compliance_monitor.php" class="btn btn-sm btn-danger" style="flex:1;text-align:center"><i class="fas fa-bell"></i> Notice</a>
        <a href="worker_block.php" class="btn btn-sm btn-outline" style="flex:1;text-align:center"><i class="fas fa-ban"></i> Block</a>
      </div>
    </div>
  </div>

  <!-- Contractor Monitor -->
  <div class="card glass">
    <div class="card-header"><div class="card-title"><i class="fas fa-building"></i> Contractor Monitor</div>
      <a href="approve_contractors.php" class="btn btn-sm btn-outline">All</a></div>
    <div class="card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
        <div style="text-align:center;padding:12px;border-radius:8px;background:#f0fdf4">
          <div style="font-size:22px;font-weight:800;color:#10b981"><?=ws($conn,"SELECT COUNT(*)c FROM contractors WHERE status='approved'")?></div>
          <div style="font-size:10px;color:#6b7280">Active</div>
        </div>
        <div style="text-align:center;padding:12px;border-radius:8px;background:#fef2f2">
          <div style="font-size:22px;font-weight:800;color:#ef4444"><?=$sc?></div>
          <div style="font-size:10px;color:#6b7280">Suspended</div>
        </div>
      </div>
      <?php foreach($rc as $c):
        $sb=['approved'=>'badge-success','rejected'=>'badge-danger','pending'=>'badge-warning'][$c['status']]??'badge-warning';?>
      <div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid #f1f5f9">
        <div style="flex:1;font-size:12px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?=htmlspecialchars($c['contractor_name'])?></div>
        <span class="badge <?=$sb?>" style="font-size:9px"><?=ucfirst($c['status'])?></span>
      </div>
      <?php endforeach;?>
    </div>
  </div>
</div>

<!-- ROW 5: Blocking + Integration + Notifications -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:20px">

  <!-- Blocking Control -->
  <div class="card glass">
    <div class="card-header"><div class="card-title"><i class="fas fa-user-slash"></i> Blocking Control</div>
      <a href="worker_block.php" class="btn btn-sm btn-danger">Manage</a></div>
    <div class="card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px">
        <div style="padding:10px;border-radius:8px;background:#fef2f2;text-align:center">
          <div style="font-size:20px;font-weight:800;color:#ef4444"><?=$bw?></div>
          <div style="font-size:10px">Blocked Workers</div>
        </div>
        <div style="padding:10px;border-radius:8px;background:#fff7ed;text-align:center">
          <div style="font-size:20px;font-weight:800;color:#f59e0b"><?=$sc?></div>
          <div style="font-size:10px">Suspended Firms</div>
        </div>
      </div>
      <?php foreach(['Safety Violation','Discipline Issue','Expired Documents','Fake Documents','Blacklisting'] as $r):?>
      <div style="display:flex;align-items:center;gap:6px;padding:5px 0;font-size:12px;border-bottom:1px solid #f1f5f9">
        <i class="fas fa-exclamation-triangle" style="color:#ef4444;font-size:10px"></i> <?=$r?>
      </div>
      <?php endforeach;?>
    </div>
  </div>

  <!-- Integration Monitor -->
  <div class="card glass">
    <div class="card-header"><div class="card-title"><i class="fas fa-sync"></i> Integration Monitor</div>
      <a href="sap_monitor.php" class="btn btn-sm btn-outline">View</a></div>
    <div class="card-body">
      <?php $integrations=[
        ['SAP S/4 HANA Sync','success','#10b981','fa-sync'],
        ['Attendance System','success','#10b981','fa-calendar-check'],
        ['Biometric System','success','#10b981','fa-fingerprint'],
        ['SMS Gateway','active','#3b82f6','fa-sms'],
        ['Email Service','active','#3b82f6','fa-envelope'],
      ];
      foreach($integrations as list($l,$s,$c,$i)):?>
      <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f1f5f9">
        <div style="width:8px;height:8px;border-radius:50%;background:<?=$c?>"></div>
        <i class="fas <?=$i?>" style="color:<?=$c?>;font-size:12px;width:16px"></i>
        <span style="flex:1;font-size:12px;font-weight:600"><?=$l?></span>
        <span style="font-size:10px;color:<?=$c?>;font-weight:700"><?=ucfirst($s)?></span>
      </div>
      <?php endforeach;?>
    </div>
  </div>

  <!-- Notifications -->
  <div class="card glass">
    <div class="card-header"><div class="card-title"><i class="fas fa-bell"></i> Notification Center</div></div>
    <div class="card-body">
      <?php $notifs=[
        [$pa>0?"$pa new contractor registrations":'No new registrations',$pa>0?'#f59e0b':'#10b981','fa-building','approve_contractors.php'],
        [$cp>0?"$cp compliance items expired":'Compliance up to date',$cp>0?'#ef4444':'#10b981','fa-shield-halved','compliance_monitor.php'],
        [$bw>0?"$bw workers blocked":'No blocked workers',$bw>0?'#ef4444':'#10b981','fa-user-slash','worker_block.php'],
        [$te>0?"$te passes expired":'All passes valid',$te>0?'#f59e0b':'#10b981','fa-clock','temp_pass_control.php'],
        [$sp>0?"$sp safety trainings pending":'Safety queue clear',$sp>0?'#6366f1':'#10b981','fa-graduation-cap','training_monitor.php'],
      ];
      foreach($notifs as list($msg,$c,$i,$url)):?>
      <a href="<?=$url?>" style="display:flex;align-items:center;gap:10px;padding:8px;border-radius:8px;margin-bottom:6px;border-left:3px solid <?=$c?>;background:<?=$c?>08;text-decoration:none;color:inherit">
        <i class="fas <?=$i?>" style="color:<?=$c?>;font-size:12px"></i>
        <span style="font-size:12px;font-weight:500"><?=$msg?></span>
      </a>
      <?php endforeach;?>
    </div>
  </div>
</div>

<!-- ROW 6: Audit Trail -->
<div class="card glass" style="margin-bottom:20px">
  <div class="card-header"><div class="card-title"><i class="fas fa-history"></i> System Audit Trail</div>
    <a href="../admin/audit_logs.php" class="btn btn-sm btn-outline">Full Log</a></div>
  <div class="card-body" style="padding:0">
    <table class="data-table">
      <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Module</th></tr></thead>
      <tbody>
        <?php foreach($al as $l):?>
        <tr>
          <td style="font-size:11px;white-space:nowrap"><?=date('d M H:i',strtotime($l['created_at']))?></td>
          <td style="font-size:12px"><?=htmlspecialchars($l['name']??'System')?></td>
          <td><span class="badge badge-info"><?=strtoupper($l['action'])?></span></td>
          <td style="font-size:12px"><?=htmlspecialchars($l['module'])?></td>
        </tr>
        <?php endforeach;?>
      </tbody>
    </table>
  </div>
</div>

<style>
.wa-kpi-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:0}
.wa-kpi{background:#fff;border-radius:12px;padding:16px;box-shadow:0 2px 8px rgba(0,0,0,.06);transition:.2s}
.wa-kpi:hover{transform:translateY(-3px);box-shadow:0 6px 20px rgba(0,0,0,.1)}
.wa-kpi-icon{width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;margin-bottom:8px}
.wa-kpi-val{font-size:26px;font-weight:800;color:#1e293b;line-height:1}
.wa-kpi-lbl{font-size:11px;color:#6b7280;margin-top:4px;font-weight:600}
@media(max-width:1200px){.wa-kpi-grid{grid-template-columns:repeat(auto-fit,minmax(160px,1fr))}}
</style>
<?php
}
renderLayout("Welfare Admin Dashboard",'renderContent',$role,$name);
?>
