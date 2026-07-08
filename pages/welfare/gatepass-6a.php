<?php
require_once '../../include/auth.php';
checkAuth(['welfare_user', 'pass_user', 'welfare_admin', 'super_admin']);
include '../../include/config.php';
include '../../include/layout.php';
require_once '../../include/gate_pass_document_master.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare User';
$user_id = (int)($_SESSION['user_id'] ?? 0);

$is_internal_user = true;
$contractors_list = db_fetch_all($conn, "SELECT id, contractor_name, vendor_code FROM contractors WHERE status = 'approved' ORDER BY contractor_name ASC");

$contractorId = isset($_GET['contractor_id']) ? (int)$_GET['contractor_id'] : 0;
$contractor = null;
if ($contractorId > 0) {
    $contractor = db_single($conn, "SELECT id, contractor_name FROM contractors WHERE id = ? LIMIT 1", 'i', [$contractorId]);
}

function gatePassColumnExists($conn, $table, $column) {
    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && mysqli_num_rows($result) > 0;
}

function renderContent() {
    global $conn, $user_id, $role, $is_internal_user, $contractors_list, $contractorId, $contractor;

    $documents = clms_get_gate_pass_documents_for_form($conn);
    $roleTypeExpr = gatePassColumnExists($conn, 'workmen', 'role_type') ? "COALESCE(w.role_type, '')" : "''";
    $safetyEnrollmentExpr = gatePassColumnExists($conn, 'workmen', 'safety_enrollment_status') ? "COALESCE(w.safety_enrollment_status, 'approved')" : "'approved'";

    $workers = $contractorId ? db_fetch_all(
        $conn,
        "SELECT
            w.id, w.name, w.aadhaar, w.temp_id, w.worker_type, $roleTypeExpr AS role_type, w.trade, w.skill,
            w.training_valid_till, $safetyEnrollmentExpr AS safety_enrollment_status,
            COALESCE(w.training_status, '') AS training_status,
            COALESCE(w.safety_training_status, '') AS safety_training_status,
            w.status AS workman_status,
            w.temp_pass_status,
            (
                SELECT gpr.status
                FROM gate_pass_requests gpr
                JOIN gate_pass_request_workers gprw ON gprw.request_id = gpr.id
                WHERE gprw.workman_id = w.id
                  AND LOWER(COALESCE(gpr.status, 'pending')) IN ('draft','pending','submitted','reupload_required','issued','approved','active','under_review','processing')
                ORDER BY 
                   CASE LOWER(COALESCE(gpr.status, 'pending'))
                       WHEN 'issued' THEN 1
                       WHEN 'active' THEN 1
                       WHEN 'approved' THEN 2
                       WHEN 'processing' THEN 3
                       WHEN 'under_review' THEN 3
                       WHEN 'pending' THEN 3
                       WHEN 'submitted' THEN 3
                       WHEN 'reupload_required' THEN 4
                       WHEN 'draft' THEN 5
                       ELSE 6
                   END ASC, gpr.id DESC LIMIT 1
            ) AS gate_pass_request_status,
            (
                SELECT gpr.status
                FROM gate_pass_requests gpr
                JOIN gate_pass_request_workers gprw ON gprw.request_id = gpr.id
                WHERE gprw.workman_id = w.id
                  AND LOWER(COALESCE(gpr.status, 'pending')) IN ('draft','pending','submitted','reupload_required')
                ORDER BY gpr.id DESC LIMIT 1
            ) AS active_request_status,
            EXISTS (
                SELECT 1
                FROM gate_pass_requests gpr
                JOIN gate_pass_request_workers gprw ON gprw.request_id = gpr.id
                WHERE gprw.workman_id = w.id
                  AND LOWER(COALESCE(gpr.status, 'pending')) IN ('draft','pending','submitted','reupload_required')
            ) AS has_active_request,
            (
                SELECT gp.id
                FROM gate_passes gp
                WHERE gp.workman_id = w.id
                  AND gp.is_temporary = 1
                  AND gp.is_extended = 0
                  AND LOWER(COALESCE(gp.status, '')) IN ('active', 'approved')
                ORDER BY gp.id DESC LIMIT 1
            ) AS extendable_temp_pass_id,
            (
                SELECT gp.valid_to
                FROM gate_passes gp
                WHERE gp.workman_id = w.id
                  AND gp.is_temporary = 1
                  AND gp.is_extended = 0
                  AND LOWER(COALESCE(gp.status, '')) IN ('active', 'approved')
                ORDER BY gp.id DESC LIMIT 1
            ) AS extendable_temp_valid_to
         FROM workmen w
         WHERE w.contractor_id = ?
           AND LOWER($safetyEnrollmentExpr) = 'approved'
           AND (
                LOWER(COALESCE(w.training_status, '')) IN ('pass','passed','training_passed','qualified','completed')
                OR LOWER(COALESCE(w.safety_training_status, '')) IN ('1','training_passed','passed','pass','qualified','completed')
           )
           AND (w.training_valid_till IS NULL OR w.training_valid_till >= CURDATE())
         ORDER BY w.name ASC",
        'i',
        [$contractorId]
    ) : [];

    $requests = $contractorId ? db_fetch_all(
        $conn,
        "SELECT gpr.request_no, gpr.status, gpr.created_at, w.name AS worker_name, w.temp_id
         FROM gate_pass_requests gpr
         JOIN gate_pass_request_workers gprw ON gprw.request_id = gpr.id
         JOIN workmen w ON w.id = gprw.workman_id
         WHERE gpr.contractor_id = ?
           AND LOWER(COALESCE(gpr.status, '')) <> 'draft'
         ORDER BY gpr.id DESC
         LIMIT 10",
        'i',
        [$contractorId]
    ) : [];

    $availableWorkerCount = count(array_filter($workers, function($worker) {
        $s = trim((string)($worker['gate_pass_request_status'] ?? ''));
        return $s === '';
    }));
?>
<style>
/* ===== GATE PASS — PROFESSIONAL UI ===== */
.gp-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:12px}
.gp-title{display:flex;align-items:center;gap:12px}
.gp-title-icon{width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#2563eb,#7c3aed);display:grid;place-items:center;color:#fff;font-size:18px;flex-shrink:0;box-shadow:0 4px 14px rgba(37,99,235,.3)}
.gp-title h2{font-size:20px;font-weight:800;margin:0 0 3px;color:#2563eb;}
.gp-title p{font-size:12px;color:var(--text-muted);margin:0}

/* Steps */
.gp-steps{display:flex;align-items:center;background:var(--card-bg,#fff);border:1px solid var(--border-color);border-radius:12px;padding:14px 20px;margin-bottom:20px;overflow-x:auto;gap:0}
.gp-step{display:flex;align-items:center;gap:10px;flex-shrink:0}
.gp-step-num{width:32px;height:32px;border-radius:50%;display:grid;place-items:center;font-weight:800;font-size:13px;background:#f1f5f9;color:#94a3b8;border:2px solid #e2e8f0;transition:.3s}
.gp-step-info strong{font-size:13px;display:block;color:var(--text-muted);transition:.3s}
.gp-step-info small{font-size:11px;color:var(--text-muted)}
.gp-step.active .gp-step-num{background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;border-color:transparent;box-shadow:0 4px 12px rgba(37,99,235,.35)}
.gp-step.active .gp-step-info strong{color:#2563eb}
.gp-step.done .gp-step-num{background:#16a34a;color:#fff;border-color:transparent}
.gp-step.done .gp-step-info strong{color:#16a34a}
.gp-line{flex:1;height:2px;background:#e2e8f0;margin:0 14px;min-width:40px;border-radius:2px;transition:.3s}
.gp-line.done{background:#16a34a}

/* Panel */
.gp-panel{background:var(--card-bg,#fff);border:1px solid var(--border-color);border-radius:12px;margin-bottom:20px;overflow:hidden}
.gp-panel-hd{padding:15px 20px;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.gp-panel-hd h3{font-size:15px;font-weight:700;margin:0 0 2px;display:flex;align-items:center;gap:8px;color:#2563eb;}
.gp-panel-hd p{font-size:12px;color:var(--text-muted);margin:0}
.gp-count{display:inline-flex;align-items:center;gap:6px;padding:5px 14px;background:linear-gradient(135deg,#eff6ff,#eef2ff);color:#2563eb;border-radius:20px;font-weight:800;font-size:13px;border:1px solid rgba(37,99,235,.15);white-space:nowrap}

/* Search */
.gp-search{display:flex;gap:10px;padding:12px 20px;border-bottom:1px solid var(--border-color);background:var(--hover-bg,#f8fafc);flex-wrap:wrap}
.gp-inp-wrap{position:relative;flex:1;min-width:160px}
.gp-inp-wrap i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px;pointer-events:none}
.gp-inp-wrap input{width:100%;padding:9px 12px 9px 34px;border:1px solid var(--border-color);border-radius:8px;font-size:13px;background:var(--card-bg,#fff);color:var(--text-primary);box-sizing:border-box;transition:border .2s,box-shadow .2s}
.gp-inp-wrap input:focus{outline:none;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1)}

/* Table */
.gp-tw{overflow-x:auto}
.gp-tbl{width:100%;border-collapse:collapse;font-size:13px}
.gp-tbl thead th{padding:10px 16px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);background:var(--hover-bg,#f8fafc);border-bottom:1px solid var(--border-color)}
.gp-tbl tbody tr{border-bottom:1px solid var(--border-color);transition:background .15s}
.gp-tbl tbody tr:last-child{border-bottom:none}
.gp-tbl tbody tr:hover{background:var(--hover-bg,#f8fafc)}
.gp-tbl td{padding:12px 16px;vertical-align:middle}

.gp-worker{display:flex;align-items:center;gap:10px}
.gp-av{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#dbeafe,#ede9fe);display:grid;place-items:center;color:#2563eb;font-weight:800;font-size:14px;flex-shrink:0}
.gp-worker strong{font-size:13px;font-weight:700;display:block}
.gp-worker small{font-size:11px;color:var(--text-muted);display:block;margin-top:1px}
.gp-code{font-family:monospace;font-size:12px;color:#475569;background:#f1f5f9;padding:3px 8px;border-radius:5px;display:inline-block}
.gp-tag{display:inline-block;padding:3px 8px;background:#f8fafc;border:1px solid var(--border-color);border-radius:5px;font-size:11px;font-weight:600;color:#475569}

/* Status */
.gp-pill{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700}
.gp-pill.ok{background:#ecfdf5;color:#065f46}
.gp-pill.warn{background:#fffbeb;color:#92400e}
.gp-pill.info{background:#eff6ff;color:#1d4ed8}
.gp-pill.danger{background:#fef2f2;color:#991b1b}
.gp-pill.muted{background:#f1f5f9;color:#334155}
.gp-pill.issued{background:#dcfce7;color:#166534;border:1px solid #bbf7d0;}
.gp-dot{width:7px;height:7px;border-radius:50%;display:inline-block;flex-shrink:0}

/* Doc Upload */
.gp-sel-bar{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;padding:14px 20px;border-bottom:1px solid var(--border-color);background:linear-gradient(135deg,rgba(37,99,235,.03),rgba(124,58,237,.03))}
.gp-sel-item { background: white; border: 1px solid var(--border-color, #e5e7eb); border-radius: 8px; padding: 12px 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
.gp-sel-item label{font-size:10px;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);font-weight:700;display:block;margin-bottom:3px}
.gp-sel-item strong{font-size:14px;font-weight:700}

.gp-docs{display:flex;flex-direction:column;gap:12px;padding:20px;}
.gp-doc-row{display:flex;align-items:center;gap:14px;padding:16px;border:1px solid var(--border-color);border-radius:10px;background:var(--hover-bg,#f8fafc);flex-wrap:wrap;transition:box-shadow .2s;box-shadow:0 1px 3px rgba(0,0,0,0.02)}
.gp-doc-row:hover{box-shadow:0 4px 12px rgba(0,0,0,.05);background:var(--card-bg,#fff)}
.gp-doc-no{width:26px;height:26px;border-radius:7px;background:linear-gradient(135deg,#eff6ff,#eef2ff);color:#2563eb;display:grid;place-items:center;font-weight:800;font-size:12px;flex-shrink:0}
.gp-doc-info{flex:1;min-width:180px}
.gp-doc-info strong{font-size:13px;font-weight:700;display:block}
.gp-doc-info small{font-size:11px;color:var(--text-muted);margin-top:2px;display:block}
.gp-doc-acts{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.gp-file-badge{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;color:#94a3b8;padding:4px 10px;background:#f1f5f9;border-radius:20px;white-space:nowrap;transition:all .2s;max-width:180px;overflow:hidden;text-overflow:ellipsis}
.gp-file-badge.uploaded{background:#ecfdf5;color:#065f46}
.gate-doc-input{opacity:0;position:absolute;z-index:-1;width:0.1px;height:0.1px;overflow:hidden;left:-9999px;top:-9999px;}

.gp-footer{display:flex;align-items:center;justify-content:space-between;padding:13px 20px;border-top:1px solid var(--border-color);background:var(--hover-bg,#f8fafc);gap:10px;flex-wrap:wrap}
.gp-footer-right{display:flex;gap:10px;flex-wrap:wrap}

.gp-empty{display:flex;flex-direction:column;align-items:center;padding:40px 20px;gap:8px;color:var(--text-muted)}
.gp-empty i{font-size:38px;opacity:.25}
.gp-empty strong{font-size:14px;color:var(--text-primary)}
.gp-empty span{font-size:12px}
.hidden{display:none!important}

.blue-header {
    background: linear-gradient(to right, #1e3a8a, #2563eb) !important;
    color: white !important;
}
.blue-header h3, .blue-header p, .blue-header i, .blue-header h2, .blue-header .gp-count {
    color: white !important;
}
.blue-header .gp-count { background: rgba(255,255,255,0.2); border-color: transparent; }
.blue-header .gp-step-num { background: rgba(255,255,255,0.2); color: white; border-color: transparent; }
.blue-header .gp-step.active .gp-step-num { background: white; color: #2563eb; }
.blue-header .gp-step-info strong, .blue-header .gp-step-info small { color: rgba(255,255,255,0.9); }
.blue-header .gp-step.active .gp-step-info strong { color: white; }
.blue-header .gp-line { background: rgba(255,255,255,0.2); }
.blue-header .gp-line.done { background: #10b981; }

@media(max-width:700px){
  .gp-doc-row{flex-direction:column;align-items:flex-start}
  .gp-footer{flex-direction:column}
  .gp-footer-right{width:100%;justify-content:flex-end}
}
</style>

<!-- Header -->
<div class="gp-panel blue-header" style="padding: 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; border-radius: 12px;">
  <div class="gp-header" style="margin-bottom: 0; display: flex; align-items: center; gap: 12px; border: none; padding: 0;">
    <div class="gp-title">
      <div class="gp-title-icon" style="background: rgba(255,255,255,0.2); box-shadow: none;"><i class="fas fa-id-badge"></i></div>
      <div>
        <h2 style="margin: 0; font-size: 20px;">Gate Pass Creation Request</h2>
      </div>
    </div>
  </div>

  <!-- Step Tracker -->
  <div class="gp-steps" style="background:transparent; border:none; padding:0; margin-bottom:0;">
    <div class="gp-step active" data-step-indicator="1">
      <div class="gp-step-num">1</div>
      <div class="gp-step-info"><strong>Select Employee</strong><small>Safety-cleared only</small></div>
    </div>
    <div class="gp-line" id="gpLine1"></div>
    <div class="gp-step" data-step-indicator="2">
      <div class="gp-step-num">2</div>
      <div class="gp-step-info"><strong>Upload Documents</strong><small>Required supporting files</small></div>
    </div>
    <div class="gp-line" id="gpLine2"></div>
    <div class="gp-step" data-step-indicator="3">
      <div class="gp-step-num">3</div>
      <div class="gp-step-info"><strong>Approval Process</strong><small>Starts after submit</small></div>
    </div>
  </div>
</div>

<!-- Contractor Selection Dropdown -->
<div class="gp-panel" style="padding: 20px; margin-bottom: 20px; border-radius: 12px; background: white; border: 1px solid var(--border-color, #e5e7eb);">
  <form method="GET" action="gatepass-6a.php" id="contractorFilterForm">
    <div class="form-group" style="margin-bottom: 0; display: flex; align-items: center; gap: 15px;">
      <label class="form-label" style="font-weight: 700; margin-bottom: 0; white-space: nowrap;"><i class="fas fa-building text-primary"></i> Select Contractor:</label>
      <select name="contractor_id" class="form-control" onchange="document.getElementById('contractorFilterForm').submit()" style="max-width: 400px; padding: 8px 12px;">
        <option value="">-- Choose Contractor --</option>
        <?php foreach ($contractors_list as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $contractorId === (int)$c['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['contractor_name']) ?> (<?= htmlspecialchars($c['vendor_code']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
</div>

<?php if (!$contractorId): ?>
  <div class="alert alert-warning" style="margin-bottom:20px;"><i class="fas fa-exclamation-triangle"></i><div>Please select a contractor from the dropdown above to continue.</div></div>
<?php return; endif; ?>

<!-- Step 1: Employee Selection -->
<section id="employeeStep" class="gp-panel">
  <div class="gp-panel-hd blue-header" style="border-radius: 12px 12px 0 0;">
    <div>
      <h3><i class="fas fa-users"></i> Select Employee</h3>
      <p>Only enrollment-approved employees with completed Safety Training are listed.</p>
    </div>
    <div class="gp-count"><i class="fas fa-circle-check"></i> <?= $availableWorkerCount ?> Available</div>
  </div>

  <div class="gp-search" style="padding: 15px 20px;">
    <div class="gp-inp-wrap">
      <i class="fas fa-fingerprint"></i>
      <input type="search" id="aadhaarSearch" placeholder="Search Aadhaar No.">
    </div>
    <div class="gp-inp-wrap">
      <i class="fas fa-user"></i>
      <input type="search" id="nameSearch" placeholder="Search Employee Name">
    </div>
  </div>

  <div class="gp-tw">
    <table class="gp-tbl">
      <thead>
        <tr>
          <th style="width:46px;text-align:center;">#</th>
          <th style="width:42px;text-align:center;">Sel.</th>
          <th>Employee</th>
          <th>Aadhaar</th>
          <th>Category</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody id="employeeRows">
        <?php if (!$workers): ?>
          <tr><td colspan="7">
            <div class="gp-empty">
              <i class="fas fa-user-shield"></i>
              <strong>No eligible employees found</strong>
              <span>Enrollment approval and Safety Training completion are required.</span>
            </div>
          </td></tr>
        <?php endif; ?>
        <?php foreach ($workers as $idx => $worker):
          $category     = $worker['worker_type'] ?: ($worker['role_type'] ?: ($worker['skill'] ?: 'Worker'));
          $reqStatus    = strtolower(trim((string)($worker['gate_pass_request_status'] ?? '')));
          $hasActive    = ((int)$worker['has_active_request'] > 0);
          $activeStatus = strtolower((string)($worker['active_request_status'] ?? ''));
          $extendableTempId = (int)($worker['extendable_temp_pass_id'] ?? 0);
          $extendableTempValidTo = $worker['extendable_temp_valid_to'] ?? '';
          $workmanStatus = strtolower(trim((string)($worker['workman_status'] ?? '')));
          $tempPassStatus = (int)($worker['temp_pass_status'] ?? 0);

          if ($workmanStatus === 'temporary_issued' || $workmanStatus === 'acc_generated' || $tempPassStatus === 1 || $extendableTempId > 0) {
              $reqStatus = 'active';
              $activeStatus = ''; // Hide continue/reupload buttons
              $hasActive = true;
          }
          
          $isIssued     = in_array($reqStatus, ['issued','approved','active']);
          
          if ($reqStatus === '') $pillCls = 'ok';
          elseif ($reqStatus === 'draft') $pillCls = 'muted';
          elseif ($reqStatus === 'approved') $pillCls = 'ok';
          elseif (in_array($reqStatus, ['issued','active'])) $pillCls = 'issued';
          elseif (in_array($reqStatus, ['reupload_required','rejected'])) $pillCls = 'danger';
          else $pillCls = 'warn';

          $displayStatus = $reqStatus;
          if ($reqStatus === 'pending') $displayStatus = 'Submitted';
          elseif ($reqStatus === 'approved') $displayStatus = 'Approved';
          elseif ($reqStatus === 'issued' || $reqStatus === 'active') $displayStatus = 'Issued';
          
          $pillLbl      = $reqStatus === '' ? '<i class="fas fa-check"></i> Eligible' : ($isIssued ? '<i class="fas fa-id-badge"></i> ' . strtoupper($displayStatus) : strtoupper(str_replace('_',' ',$displayStatus)));
          $initial      = strtoupper(mb_substr($worker['name'], 0, 1));
          $workerJson   = htmlspecialchars(json_encode(['id'=>(int)$worker['id'],'name'=>$worker['name'],'aadhaar'=>$worker['aadhaar'],'category'=>$category,'temp_id'=>$worker['temp_id'], 'request_status' => $reqStatus]), ENT_QUOTES,'UTF-8');
        ?>
          <tr data-name="<?= htmlspecialchars(strtolower((string)$worker['name'])) ?>"
              data-aadhaar="<?= htmlspecialchars(strtolower((string)$worker['aadhaar'])) ?>">
            <td style="text-align:center;font-size:12px;color:var(--text-muted);"><?= $idx+1 ?></td>
            <td style="text-align:center;">
              <input type="checkbox" class="gp-chk worker-checkbox" value="<?= (int)$worker['id'] ?>" <?= $hasActive && $reqStatus !== 'reupload_required' && $reqStatus !== 'draft' ?'disabled':'' ?>>
            </td>
            <td>
              <div class="gp-worker">
                <div class="gp-av"><?= $initial ?></div>
                <div>
                  <strong><?= htmlspecialchars($worker['name']) ?></strong>
                  <small><?= htmlspecialchars($worker['temp_id'] ?: 'No Temp ID') ?></small>
                </div>
              </div>
            </td>
            <td><span class="gp-code"><?= htmlspecialchars($worker['aadhaar'] ?: '-') ?></span></td>
            <td><span class="gp-tag"><?= htmlspecialchars($category) ?></span></td>
            <td>
              <?php if ($reqStatus !== ''): ?>
                <span class="gp-pill <?= $pillCls ?>" onclick="showStatusInfo('<?= $reqStatus ?>')" style="cursor:pointer;" title="Click for details"><?= $pillLbl ?></span>
              <?php else: ?>
                <span class="gp-pill <?= $pillCls ?>"><?= $pillLbl ?></span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($isIssued || $hasActive || $extendableTempId > 0): ?>
                <div style="display:flex; gap:8px;">
                    <a class="btn btn-sm btn-outline" href="pass_status.php?worker_id=<?= (int)$worker['id'] ?>"><i class="fas fa-eye"></i> View</a>
                    
                    <?php if (!$isIssued && ($activeStatus === 'reupload_required' || $activeStatus === 'draft')): ?>
                        <button type="button" class="btn btn-sm btn-primary select-worker" data-worker='<?= $workerJson ?>'>
                          <i class="fas fa-upload"></i> <?= $activeStatus === 'draft' ? 'Continue' : 'Reupload' ?>
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($extendableTempId > 0): ?>
                        <button type="button" class="btn btn-sm btn-outline" style="border-color: #fcd34d; color: #b45309; white-space: nowrap;" onclick="openExtendModal(<?= $extendableTempId ?>, '<?= $extendableTempValidTo ?>')">
                          <i class="fas fa-calendar-plus"></i> Extend
                        </button>
                    <?php endif; ?>
                </div>
              <?php else: ?>
                <button type="button" class="btn btn-sm btn-primary select-worker" data-worker='<?= $workerJson ?>'>
                  <i class="fas fa-upload"></i> Upload
                </button>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<!-- Step 2: Document Upload -->
<section id="documentStep" class="gp-panel hidden">
  <div class="gp-panel-hd blue-header" style="border-radius: 12px 12px 0 0;">
    <div>
      <h3><i class="fas fa-file-arrow-up"></i> Upload Supporting Documents</h3>
      <p>Upload all mandatory documents before submitting the request.</p>
    </div>
    <span class="gp-pill" id="documentProgress" style="font-size:12px; background: rgba(255,255,255,0.2); color: white; border-color: transparent;">0 / <?= count($documents) ?> uploaded</span>
  </div>

  <div class="gp-sel-bar">
    <div class="gp-sel-item"><label>Employee Name</label><strong id="selectedName">-</strong></div>
    <div class="gp-sel-item"><label>Aadhaar No.</label><strong id="selectedAadhaar">-</strong></div>
    <div class="gp-sel-item"><label>Category</label><strong id="selectedCategory">-</strong></div>
    <div class="gp-sel-item"><label>Temp ID</label><strong id="selectedTempId">-</strong></div>
  </div>

  <form id="documentForm" enctype="multipart/form-data">
    <input type="hidden" name="request_id" id="requestId">
    <input type="hidden" name="workman_id" id="selectedWorkerId">
    <input type="hidden" name="action" value="submit">
    <input type="hidden" name="contractor_id" value="<?= $contractorId ?>">

    <!-- Temporary Pass Section -->
    <div style="margin: 20px 20px 0 20px; padding: 15px; border: 1px solid #fcd34d; background: #fffbeb; border-radius: 8px;">
      <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;font-size:14px;color:var(--gray-800); font-weight: bold;">
        <input type="checkbox" id="is_temporary_pass" name="is_temporary" value="1" onchange="toggleTemporaryPass(this)" style="margin-top:3px;width:18px;height:18px" />
        <span>Apply for Temporary Pass (Extremely urgent situations only)</span>
      </label>
      <div id="temporary_pass_fields" style="display:none; margin-top: 15px; border-top: 1px solid #fde68a; padding-top: 15px;">
        <p style="font-size: 13px; color: #b45309; margin-bottom: 10px;">
          <i class="fas fa-exclamation-triangle"></i> Temporary passes are strictly limited to a maximum of 7 days and require approval from the Welfare User.
        </p>
        <div class="form-group" style="margin-bottom: 15px;">
          <label style="display:block; font-size:12px; font-weight:700; color:var(--text-muted); margin-bottom:5px;">Executing Officer Declaration <span class="required" style="color:red">*</span></label>
          <input class="form-control" type="file" id="executing_officer_declaration" name="executing_officer_declaration" accept=".pdf,.jpg,.jpeg,.png" style="width:100%; padding:8px; border:1px solid var(--border-color); border-radius:6px;" />
          <small style="color:var(--text-muted); font-size:11px;">Upload the declaration duly signed by the executing officer.</small>
        </div>
        <div style="display:flex; gap:15px;">
          <div class="form-group" style="flex:1;">
            <label style="display:block; font-size:12px; font-weight:700; color:var(--text-muted); margin-bottom:5px;">Pass From Date <span class="required" style="color:red">*</span></label>
            <input class="form-control" type="date" id="temp_from_date" name="valid_from" value="<?= date('Y-m-d') ?>" style="width:100%; padding:8px; border:1px solid var(--border-color); border-radius:6px;" onchange="enforceTemporaryPassLimits()" />
          </div>
          <div class="form-group" style="flex:1;">
            <label style="display:block; font-size:12px; font-weight:700; color:var(--text-muted); margin-bottom:5px;">Pass To Date (7 Days Default)</label>
            <input class="form-control" type="date" id="temp_to_date" name="valid_to" value="<?= date('Y-m-d', strtotime('+6 days')) ?>" style="width:100%; padding:8px; border:1px solid var(--border-color); border-radius:6px; background:#f3f4f6;" readonly />
          </div>
        </div>
      </div>
    </div>

    <div class="gp-docs">
      <?php foreach ($documents as $i => $doc): ?>
        <div class="gp-doc-row" data-key="<?= htmlspecialchars($doc['key']) ?>" data-id="<?= htmlspecialchars($doc['id']) ?>" data-required="<?= $doc['required'] ? 'true' : 'false' ?>">
          <div class="gp-doc-no"><?= $i+1 ?></div>
          <div class="gp-doc-info">
            <strong>
              <?= htmlspecialchars($doc['label']) ?>
              <?php if ($doc['required']): ?>
                <span style="color:#ef4444;font-size:10px;margin-left:4px;font-weight:600;">*Required</span>
              <?php endif; ?>
            </strong>
            <small><?= htmlspecialchars($doc['hint']) ?></small>
          </div>
          <div class="gp-inp-wrap gp-doc-acts" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;justify-content:flex-end;">
            <?php if (!empty($doc['format_file_path'])): ?>
              <a href="../../<?= ltrim(htmlspecialchars($doc['format_file_path']), '/') ?>" target="_blank" class="btn btn-sm btn-outline" style="white-space:nowrap;" download>
                <i class="fas fa-download"></i> Format
              </a>
            <?php endif; ?>
            <a href="#" target="_blank" class="btn btn-sm btn-outline btn-view-uploaded hidden" style="white-space:nowrap;"><i class="fas fa-eye"></i> View</a>
            
            <label class="btn btn-sm btn-outline btn-choose-file" for="gate-doc-<?= htmlspecialchars($doc['id']) ?>" style="cursor:pointer;margin:0;white-space:nowrap;">
              <i class="fas fa-upload"></i> Choose File
            </label>
            <input class="gate-doc-input" type="file"
                   id="gate-doc-<?= htmlspecialchars($doc['id']) ?>"
                   name="<?= htmlspecialchars($doc['key']) ?>"
                   accept=".pdf,.jpg,.jpeg,.png"
                   <?= $doc['required'] ? 'required' : '' ?>>
            
            <button type="button" class="btn btn-sm btn-outline btn-view-doc hidden" style="white-space:nowrap;">
              <i class="fas fa-eye"></i> Preview
            </button>
            <span class="gp-file-badge" data-file-state style="white-space:nowrap;"><i class="fas fa-clock"></i> Pending</span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="gp-footer">
      <button type="button" class="btn btn-outline" id="backToEmployees"><i class="fas fa-arrow-left"></i> Back</button>
      <div class="gp-footer-right">
        <button type="button" class="btn btn-outline" id="saveGatePassDraft"><i class="fas fa-floppy-disk"></i> Save Draft</button>
        <button type="submit" class="btn btn-primary" id="submitGatePass"><i class="fas fa-paper-plane"></i> Submit Request</button>
      </div>
    </div>
  </form>
</section>

<!-- Recent Requests -->
<section class="gp-panel">
  <div class="gp-panel-hd blue-header" style="border-radius: 12px 12px 0 0;">
    <div>
      <h3><i class="fas fa-clock-rotate-left"></i> Recent Gate Pass Requests</h3>
      <p>Last 10 submitted requests and their current approval status.</p>
    </div>
    <a href="pass_status.php" class="btn btn-sm btn-outline" style="background: rgba(255,255,255,0.1); color: white; border-color: rgba(255,255,255,0.3);"><i class="fas fa-external-link-alt"></i> View All</a>
  </div>
  <div class="gp-tw">
    <table class="gp-tbl" id="recentRequestsTable">
      <thead>
        <tr>
          <th style="width: 40px;">#</th>
          <th>Request No.</th>
          <th>Employee</th>
          <th>Temp ID</th>
          <th>Status</th>
          <th>Submitted On</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$requests): ?>
          <tr><td colspan="6">
            <div class="gp-empty">
              <i class="fas fa-inbox"></i>
              <strong>No requests submitted yet</strong>
            </div>
          </td></tr>
        <?php endif; ?>
        <?php foreach ($requests as $idx => $req):
          $st  = strtolower($req['status'] ?? '');
          $cls = in_array($st, ['approved','active']) ? 'ok' : (in_array($st, ['rejected','reupload_required']) ? 'danger' : 'warn');
          $dot = $cls === 'ok' ? '#16a34a' : ($cls === 'danger' ? '#dc2626' : '#d97706');
        ?>
          <tr>
            <td style="color:var(--text-muted);font-size:12px;"><?= $idx + 1 ?></td>
            <td><strong class="gp-code"><?= htmlspecialchars($req['request_no']) ?></strong></td>
            <td><?= htmlspecialchars($req['worker_name']) ?></td>
            <td><span class="gp-code"><?= htmlspecialchars($req['temp_id'] ?: '-') ?></span></td>
            <td>
              <span class="gp-pill <?= $cls ?>"><span class="gp-dot" style="background:<?= $dot ?>;"></span><?= strtoupper($st) ?></span>
            </td>
            <td style="font-size:12px;color:var(--text-muted);">
              <?= !empty($req['created_at']) ? date('d M Y, h:i A', strtotime($req['created_at'])) : '-' ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<script>
const employeeStep  = document.getElementById('employeeStep');
const documentStep  = document.getElementById('documentStep');
const documentForm  = document.getElementById('documentForm');

function fireMessage(icon, title, text) {
  if (typeof Swal !== 'undefined' && Swal.fire) return Swal.fire({icon, title, text, confirmButtonColor:'#2563eb'});
  alert(title + ': ' + text);
}

function showStatusInfo(status) {
  const info = {
    'pending': { title: 'Submitted', text: 'Waiting for verification.', icon: 'info' },
    'reupload_required': { title: 'Re-upload Required', text: 'Some documents rejected.', icon: 'warning' },
    'approved': { title: 'Approved', text: 'Ready to be issued.', icon: 'success' }
  };
  const data = info[status] || { title: status.toUpperCase(), text: 'Current status: ' + status, icon: 'info' };
  if (typeof Swal !== 'undefined' && Swal.fire) Swal.fire({title: data.title, text: data.text, icon: data.icon, confirmButtonColor: '#1e3a8a'});
  else alert(data.title + ': ' + data.text);
}

function setStep(step) {
  document.querySelectorAll('[data-step-indicator]').forEach(el => {
    const v = Number(el.dataset.stepIndicator);
    el.classList.toggle('active', v === step);
    el.classList.toggle('done', v < step);
  });
}

async function selectEmployee(worker, btn) {
  const orig = btn ? btn.innerHTML : '';
  if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...'; }
  try {
    const body = new FormData();
    body.append('action', 'prepare');
    body.append('workman_id', worker.id);
    body.append('contractor_id', <?= $contractorId ?>);
    const res = await fetch('../../api/save_gate_pass_request.php', {method:'POST', body});
    const result = await res.json();
    if (!result.success) throw new Error(result.message);
    document.getElementById('requestId').value = result.data.request_id || '';
    document.getElementById('selectedWorkerId').value = worker.id;
    document.getElementById('selectedName').textContent     = worker.name     || '-';
    document.getElementById('selectedAadhaar').textContent  = worker.aadhaar  || '-';
    document.getElementById('selectedCategory').textContent = worker.category || '-';
    document.getElementById('selectedTempId').textContent   = worker.temp_id  || '-';

    // Reset previous uploads in UI
    document.querySelectorAll('.gp-doc-row').forEach(row => {
      const wrap = row.querySelector('.gp-inp-wrap');
      const isReq = row.dataset.required === 'true';
      if (wrap) {
        const viewUp = wrap.querySelector('.btn-view-uploaded');
        if (viewUp) viewUp.classList.add('hidden');
        
        const chooseBtn = wrap.querySelector('.btn-choose-file');
        if (chooseBtn) {
            chooseBtn.classList.remove('hidden');
            chooseBtn.innerHTML = '<i class="fas fa-upload"></i> Choose File';
        }
        
        const fileInp = wrap.querySelector('.gate-doc-input');
        if (fileInp) {
            fileInp.value = '';
            fileInp.disabled = false;
            if (isReq) fileInp.setAttribute('required', 'required');
            else fileInp.removeAttribute('required');
        }
        
        const viewDoc = wrap.querySelector('.btn-view-doc');
        if (viewDoc) viewDoc.classList.add('hidden');
        
        const badge = wrap.querySelector('.gp-file-badge');
        if (badge) {
            badge.innerHTML = '<i class="fas fa-clock"></i> Pending';
            badge.style.color = '';
            badge.classList.remove('hidden');
        }
      }
    });

    const docs = (result.data && result.data.uploaded_docs) || result.uploaded_docs;
    if (docs) {
      Object.entries(docs).forEach(([key, doc]) => {
        const row = document.querySelector(`.gp-doc-row[data-key="${key}"]`);
        if (row) {
          const wrap = row.querySelector('.gp-inp-wrap');
          const viewUp = wrap.querySelector('.btn-view-uploaded');
          const chooseBtn = wrap.querySelector('.btn-choose-file');
          const fileInp = wrap.querySelector('.gate-doc-input');
          const badge = wrap.querySelector('.gp-file-badge');

          if (viewUp) {
              viewUp.href = `../../uploads/documents/${doc.file_path}`;
              viewUp.classList.remove('hidden');
          }
          
          if (doc.status === 'approved') {
              if (badge) {
                  badge.innerHTML = `<i class="fas fa-check-circle"></i> Approved`;
                  badge.style.color = '#10b981';
              }
              if (chooseBtn) chooseBtn.classList.add('hidden');
              if (fileInp) {
                  fileInp.disabled = true;
                  fileInp.removeAttribute('required');
              }
          } else if (doc.status === 'rejected' || doc.status === 'reupload_required') {
              if (badge) {
                  badge.innerHTML = `<i class="fas fa-times-circle"></i> Rejected`;
                  badge.style.color = '#ef4444';
              }
              if (chooseBtn) {
                  chooseBtn.classList.remove('hidden');
                  chooseBtn.innerHTML = '<i class="fas fa-upload"></i> Reupload';
              }
              if (fileInp) {
                  fileInp.disabled = false;
                  fileInp.setAttribute('required', 'required');
              }
          } else {
              if (badge) {
                  badge.innerHTML = `<i class="fas fa-clock"></i> Pending`;
                  badge.style.color = '#f59e0b';
              }
              if (!worker.request_status || worker.request_status === 'draft') {
                  if (chooseBtn) {
                      chooseBtn.classList.remove('hidden');
                      chooseBtn.innerHTML = '<i class="fas fa-upload"></i> Change';
                  }
                  if (fileInp) {
                      fileInp.disabled = false;
                      fileInp.removeAttribute('required'); // File already exists on server
                  }
              } else {
                  if (chooseBtn) chooseBtn.classList.add('hidden');
                  if (fileInp) {
                      fileInp.disabled = true;
                      fileInp.removeAttribute('required');
                  }
              }
          }
        }
      });
    }

    employeeStep.classList.add('hidden');
    documentStep.classList.remove('hidden');
    setStep(2);
    updateDocProgress();
    window.scrollTo({top:0, behavior:'smooth'});
  } catch(err) {
    fireMessage('error', 'Gate Pass Request', err.message);
  } finally {
    if (btn) { btn.disabled = false; btn.innerHTML = orig; }
  }
}

document.querySelectorAll('.select-worker').forEach(btn => {
  btn.addEventListener('click', e => {
    const tr = e.currentTarget.closest('tr');
    const cb = tr.querySelector('.worker-checkbox');
    if (!cb || !cb.checked) {
       fireMessage('warning', 'Selection Required', 'Please tick the checkbox first before clicking Upload Document.');
       return;
    }
    selectEmployee(JSON.parse(e.currentTarget.dataset.worker), e.currentTarget);
  });
});
document.querySelectorAll('.worker-checkbox').forEach(cb => {
  cb.addEventListener('change', e => {
    document.querySelectorAll('.worker-checkbox').forEach(o => { if (o !== e.currentTarget) o.checked = false; });
  });
});

const aadhaarSearch = document.getElementById('aadhaarSearch');
const nameSearch    = document.getElementById('nameSearch');
function filterEmployees() {
  const a = aadhaarSearch ? aadhaarSearch.value.trim().toLowerCase() : '';
  const n = nameSearch ? nameSearch.value.trim().toLowerCase() : '';
  document.querySelectorAll('#employeeRows tr[data-name]').forEach(row => {
    row.classList.toggle('hidden', !row.dataset.aadhaar.includes(a) || !row.dataset.name.includes(n));
  });
}
[aadhaarSearch, nameSearch].forEach(inp => inp?.addEventListener('input', filterEmployees));
document.getElementById('backToEmployees')?.addEventListener('click', () => {
  documentStep.classList.add('hidden');
  employeeStep.classList.remove('hidden');
  setStep(1);
});

function updateDocProgress() {
  const inputs   = Array.from(document.querySelectorAll('.gate-doc-input'));
  let total = 0;
  let uploaded = 0;
  inputs.forEach(i => {
     if(i.hasAttribute('required') || i.closest('.gp-doc-row').dataset.required === 'true') {
         total++;
         const row = i.closest('.gp-doc-row');
         const state = row?.querySelector('[data-file-state]');
         const hasFile = i.files.length > 0;
         const isApproved = row.innerHTML.includes('Approved');
         const isPending = row.innerHTML.includes('Pending') && !row.innerHTML.includes('type="file"');
         const isOldPending = row.innerHTML.includes('Pending</span>');
         if (hasFile || isApproved || isOldPending) uploaded++;
     }
  });
  
  const el = document.getElementById('documentProgress');
  if (el) {
    el.textContent = `${uploaded} / ${total} uploaded`;
    el.className   = 'gp-pill ' + (uploaded > 0 && uploaded >= total ? 'ok' : 'muted');
  }
}
// Event delegation for file inputs
documentForm?.addEventListener('change', e => {
  if (e.target && e.target.classList.contains('gate-doc-input')) {
    const input = e.target;
    const row = input.closest('.gp-doc-row');
    const badge = row?.querySelector('.gp-file-badge');
    const viewDoc = row?.querySelector('.btn-view-doc');
    const file = input.files[0];
    
    if (badge) {
      const fname = file?.name || '';
      if (fname) {
          badge.innerHTML = `<i class="fas fa-check-circle"></i> ${fname.length > 20 ? fname.substring(0,20)+'…' : fname}`;
          badge.style.color = '#10b981';
          badge.classList.add('uploaded');
          badge.classList.remove('hidden');
      } else {
          badge.innerHTML = '';
          badge.style.color = '';
          badge.classList.remove('uploaded');
      }
    }
    
    if (viewDoc) {
      if (file) {
        const url = URL.createObjectURL(file);
        viewDoc.dataset.url = url;
        viewDoc.classList.remove('hidden');
      } else {
        viewDoc.classList.add('hidden');
        if (viewDoc.dataset.url) {
          URL.revokeObjectURL(viewDoc.dataset.url);
          delete viewDoc.dataset.url;
        }
      }
    }
    
    updateDocProgress();
  }
});

documentForm?.addEventListener('click', e => {
    const viewBtn = e.target.closest('.btn-view-doc');
    if (viewBtn && viewBtn.dataset.url) {
        window.open(viewBtn.dataset.url, '_blank');
    }
});

document.querySelectorAll('.download-format').forEach(btn => {
  btn.addEventListener('click', e => {
    const filePath = e.currentTarget.dataset.filepath;
    if (filePath && filePath.trim() !== '') {
      const link = document.createElement('a');
      link.href = '../../' + filePath;
      link.download = filePath.split('/').pop() || 'format';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }
  });
});

async function saveGatePass(action) {
  const btn  = action === 'save_draft' ? document.getElementById('saveGatePassDraft') : document.getElementById('submitGatePass');
  const orig = btn ? btn.innerHTML : '';
  if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…'; }
  try {
    if (!documentForm) throw new Error('Upload form not found.');
    const fd = new FormData(documentForm);
    fd.set('action', action);
    const res    = await fetch('../../api/save_gate_pass_request.php', {method:'POST', body:fd});
    const result = await res.json();
    if (!result.success) throw new Error(result.message || 'Gate Pass request could not be saved.');
    if (action === 'save_draft') {
      await fireMessage('success', 'Draft Saved', 'Documents saved. You can continue and submit later.');
      return;
    }
    setStep(3);
    await fireMessage('success', 'Gate Pass Request Submitted', 'Approval process has been initiated.');
    location.reload();
  } catch(err) {
    fireMessage('error', action === 'save_draft' ? 'Draft Save Failed' : 'Submission Failed', err.message);
  } finally {
    if (btn) { btn.disabled = false; btn.innerHTML = orig; }
  }
}
document.getElementById('saveGatePassDraft')?.addEventListener('click', () => saveGatePass('save_draft'));
documentForm?.addEventListener('submit', async e => { 
    e.preventDefault(); 
    
    const isTemp = document.getElementById('is_temporary_pass')?.checked;
    if (isTemp) {
        const fileInput = document.getElementById('executing_officer_declaration');
        if (!fileInput.files.length) {
            fireMessage('warning', 'Validation Error', 'Executing Officer Declaration is required for Temporary Pass.');
            return;
        }
        
        const fromInput = document.getElementById('temp_from_date');
        const toInput = document.getElementById('temp_to_date');
        const fromDate = new Date(fromInput.value);
        const toDate = new Date(toInput.value);
        const diffTime = Math.abs(toDate - fromDate);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        if (diffDays > 7) {
            fireMessage('warning', 'Validation Error', 'Temporary Pass validity cannot exceed 7 days.');
            return;
        }
    } else if (!documentForm.reportValidity()) {
        return;
    }
    saveGatePass('submit'); 
});

function toggleTemporaryPass(cb) {
    const fields = document.getElementById('temporary_pass_fields');
    const requiredDocs = document.querySelectorAll('.gp-doc-row[data-required="true"] .gate-doc-input');
    
    if (cb.checked) {
        fields.style.display = 'block';
        // Make standard docs non-required for temp pass
        requiredDocs.forEach(inp => inp.removeAttribute('required'));
    } else {
        fields.style.display = 'none';
        // Restore required attribute
        requiredDocs.forEach(inp => inp.setAttribute('required', 'required'));
    }
    enforceTemporaryPassLimits();
}

function enforceTemporaryPassLimits() {
    const isTemp = document.getElementById('is_temporary_pass')?.checked;
    const fromInput = document.getElementById('temp_from_date');
    const toInput = document.getElementById('temp_to_date');
    
    if (isTemp && fromInput.value) {
        const fromDate = new Date(fromInput.value);
        const toDate = new Date(fromDate);
        toDate.setDate(fromDate.getDate() + 6); // Max 7 days including start date
        
        // Format to YYYY-MM-DD
        const toStr = toDate.toISOString().split('T')[0];
        
        // Force the toDate to exactly 7 days
        toInput.value = toStr;
    }
}

document.addEventListener("DOMContentLoaded", function() {
    const initDT = function() {
        if (typeof $ !== 'undefined' && $.fn.DataTable) {
            // Only initialize DataTable if there are data rows (not just the single empty colspan row)
            if ($('#recentRequestsTable tbody td').length > 1 && !$('#recentRequestsTable tbody td[colspan]').length) {
                $('#recentRequestsTable').DataTable({
                    "pageLength": 10,
                    "lengthChange": false,
                    "searching": false,
                    "ordering": true,
                    "info": false,
                    "paging": false // They are already max 10
                });
            }
        } else {
            setTimeout(initDT, 50);
        }
    };
    initDT();
});
</script>

<!-- Extend Temp Pass Modal -->
<div id="extendModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
    <div class="modal-content" style="background:#fff; width:100%; max-width:500px; border-radius:12px; padding:24px; box-shadow:0 10px 25px rgba(0,0,0,0.1);">
        <h3 style="margin-top:0; color:#b45309;"><i class="fas fa-calendar-plus"></i> Extend Temporary Pass</h3>
        <p style="font-size:13px; color:var(--text-muted); margin-bottom:20px;">Upload GM Declaration to extend this pass continuously for 7 days.</p>
        
        <form id="extendForm" onsubmit="submitExtension(event)">
          <input type="hidden" id="extend_gate_pass_id" name="gate_pass_id" value="">
          
          <div class="form-group" style="margin-bottom:15px;">
            <label style="display:block; font-size:12px; font-weight:700; color:var(--text-muted); margin-bottom:5px;">Current Valid To <span class="required" style="color:red">*</span></label>
            <input class="form-control" type="date" id="extend_current_valid_to" style="width:100%; padding:8px; border:1px solid var(--border-color); border-radius:6px; background:#f3f4f6;" readonly />
          </div>

          <div class="form-group" style="margin-bottom: 15px;">
            <label style="display:block; font-size:12px; font-weight:700; color:var(--text-muted); margin-bottom:5px;">GM Declaration <span class="required" style="color:red">*</span></label>
            <input class="form-control" type="file" name="gm_declaration" accept=".pdf,.jpg,.jpeg,.png" style="width:100%; padding:8px; border:1px solid var(--border-color); border-radius:6px;" required />
            <small style="color:var(--text-muted); font-size:11px;">Upload the declaration duly signed by the GM.</small>
          </div>
          
          <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
            <button type="button" class="btn btn-outline" onclick="closeExtendModal()">Cancel</button>
            <button type="submit" class="btn btn-primary" id="extendBtn">Submit Extension</button>
          </div>
        </form>
    </div>
</div>

<script>
function openExtendModal(id, currentValidTo) {
    document.getElementById('extend_gate_pass_id').value = id;
    if (currentValidTo) {
        document.getElementById('extend_current_valid_to').value = currentValidTo.split(' ')[0];
    } else {
        document.getElementById('extend_current_valid_to').value = '';
    }
    document.getElementById('extendModal').style.display = 'flex';
}

function closeExtendModal() {
    document.getElementById('extendModal').style.display = 'none';
    document.getElementById('extendForm').reset();
}

async function submitExtension(e) {
    e.preventDefault();
    const btn = document.getElementById('extendBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

    const formData = new FormData(document.getElementById('extendForm'));
    
    try {
        const res = await fetch('../../api/contractor/extend_temp_pass.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            alert('Temporary Pass extended successfully!');
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to extend pass.'));
            btn.disabled = false;
            btn.innerHTML = 'Submit Extension';
        }
    } catch (err) {
        alert('A network error occurred.');
        btn.disabled = false;
        btn.innerHTML = 'Submit Extension';
    }
}
</script>
<?php
}

renderLayout("Gate Pass Creation Request", 'renderContent', $role, $name);
?>
