<?php
require_once '../../include/auth.php';
checkAuth(['contractor', 'customer']);
include '../../include/config.php';
include '../../include/customer_portal_context.php';
include '../../include/layout.php';
require_once '../../include/gate_pass_document_master.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';
$user_id = (int)($_SESSION['user_id'] ?? 0);
clms_get_portal_contractor($conn);

function gatePassColumnExists($conn, $table, $column) {
    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && mysqli_num_rows($result) > 0;
}

function renderContent() {
    global $conn, $user_id;

    $contractor = db_single($conn, "SELECT id, contractor_name FROM contractors WHERE user_id = ? ORDER BY id DESC LIMIT 1", 'i', [$user_id]);
    $contractorId = (int)($contractor['id'] ?? 0);
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
            (
                SELECT gpr.status
                FROM gate_pass_request_workers gprw
                JOIN gate_pass_requests gpr ON gpr.id = gprw.request_id
                WHERE gprw.workman_id = w.id
                  AND LOWER(COALESCE(gpr.status, 'pending')) IN ('draft','pending','submitted','reupload_required')
                ORDER BY gpr.id DESC LIMIT 1
            ) AS gate_pass_request_status
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
.gp-title h2{font-size:20px;font-weight:800;margin:0 0 3px}
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
.gp-panel-hd h3{font-size:15px;font-weight:700;margin:0 0 2px;display:flex;align-items:center;gap:8px}
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
.gp-dot{width:7px;height:7px;border-radius:50%;display:inline-block;flex-shrink:0}

/* Doc Upload */
.gp-sel-bar{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;padding:14px 20px;border-bottom:1px solid var(--border-color);background:linear-gradient(135deg,rgba(37,99,235,.03),rgba(124,58,237,.03))}
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
.gate-doc-input{display:none}

.gp-remarks{padding:0 20px 14px}
.gp-remarks label{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);margin:12px 0 6px}
.gp-remarks textarea{width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:8px;font-size:13px;background:var(--card-bg,#fff);color:var(--text-primary);resize:vertical;min-height:54px;box-sizing:border-box;transition:border .2s,box-shadow .2s}
.gp-remarks textarea:focus{outline:none;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1)}

.gp-footer{display:flex;align-items:center;justify-content:space-between;padding:13px 20px;border-top:1px solid var(--border-color);background:var(--hover-bg,#f8fafc);gap:10px;flex-wrap:wrap}
.gp-footer-right{display:flex;gap:10px;flex-wrap:wrap}

.gp-empty{display:flex;flex-direction:column;align-items:center;padding:40px 20px;gap:8px;color:var(--text-muted)}
.gp-empty i{font-size:38px;opacity:.25}
.gp-empty strong{font-size:14px;color:var(--text-primary)}
.gp-empty span{font-size:12px}
.hidden{display:none!important}

@media(max-width:700px){
  .gp-doc-row{flex-direction:column;align-items:flex-start}
  .gp-footer{flex-direction:column}
  .gp-footer-right{width:100%;justify-content:flex-end}
}
</style>

<!-- Header -->
<div class="gp-header">
  <div class="gp-title">
    <div class="gp-title-icon"><i class="fas fa-id-badge"></i></div>
    <div>
      <h2>Gate Pass Creation Request</h2>
      <p>Select an eligible employee, upload documents, and submit for approval.</p>
    </div>
  </div>
  <a href="pass_status.php" class="btn btn-outline"><i class="fas fa-list-check"></i> Request Tracker</a>
</div>

<?php if (!$contractorId): ?>
  <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i><div>Complete contractor registration first.</div></div>
<?php return; endif; ?>

<!-- Step Tracker -->
<div class="gp-steps">
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

<!-- Step 1: Employee Selection -->
<section id="employeeStep" class="gp-panel">
  <div class="gp-panel-hd">
    <div>
      <h3><i class="fas fa-users" style="color:#2563eb;"></i> Select Employee</h3>
      <p>Only enrollment-approved employees with completed Safety Training are listed.</p>
    </div>
    <div class="gp-count"><i class="fas fa-circle-check"></i> <?= $availableWorkerCount ?> Available</div>
  </div>

  <div class="gp-search">
    <div class="gp-inp-wrap">
      <i class="fas fa-fingerprint"></i>
      <input type="search" id="aadhaarSearch" placeholder="Search Aadhaar No.">
    </div>
    <div class="gp-inp-wrap">
      <i class="fas fa-user"></i>
      <input type="search" id="nameSearch" placeholder="Search Employee Name">
    </div>
    <button type="button" class="btn btn-primary" id="searchEmployees"><i class="fas fa-search"></i> Search</button>
    <button type="button" class="btn btn-outline" id="resetSearch"><i class="fas fa-rotate-left"></i> Reset</button>
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
          $isDraft      = $reqStatus === 'draft';
          $hasActive    = $reqStatus !== '' && !$isDraft;
          $pillCls      = $reqStatus === '' ? 'ok' : ($isDraft ? 'muted' : 'warn');
          $pillLbl      = $reqStatus === '' ? '<i class="fas fa-check"></i> Eligible' : strtoupper(str_replace('_',' ',$reqStatus));
          $initial      = strtoupper(mb_substr($worker['name'], 0, 1));
          $workerJson   = htmlspecialchars(json_encode(['id'=>(int)$worker['id'],'name'=>$worker['name'],'aadhaar'=>$worker['aadhaar'],'category'=>$category,'temp_id'=>$worker['temp_id']]), ENT_QUOTES,'UTF-8');
        ?>
          <tr data-name="<?= htmlspecialchars(strtolower((string)$worker['name'])) ?>"
              data-aadhaar="<?= htmlspecialchars(strtolower((string)$worker['aadhaar'])) ?>">
            <td style="text-align:center;font-size:12px;color:var(--text-muted);"><?= $idx+1 ?></td>
            <td style="text-align:center;">
              <input type="checkbox" class="gp-chk worker-checkbox" value="<?= (int)$worker['id'] ?>" <?= $hasActive?'disabled':'' ?>>
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
            <td><span class="gp-pill <?= $pillCls ?>"><?= $pillLbl ?></span></td>
            <td>
              <?php if ($hasActive): ?>
                <a class="btn btn-sm btn-outline" href="pass_status.php"><i class="fas fa-eye"></i> View</a>
              <?php else: ?>
                <button type="button" class="btn btn-sm btn-primary select-worker" data-worker='<?= $workerJson ?>'>
                  <i class="fas fa-<?= $isDraft?'edit':'arrow-right' ?>"></i>
                  <?= $isDraft ? 'Continue Draft' : 'Select' ?>
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
  <div class="gp-panel-hd">
    <div>
      <h3><i class="fas fa-file-arrow-up" style="color:#7c3aed;"></i> Upload Supporting Documents</h3>
      <p>Upload all mandatory documents before submitting the request.</p>
    </div>
    <span class="gp-pill muted" id="documentProgress" style="font-size:12px;">0 / <?= count($documents) ?> uploaded</span>
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

    <div class="gp-docs">
      <?php foreach ($documents as $i => $doc): ?>
        <div class="gp-doc-row">
          <div class="gp-doc-no"><?= $i+1 ?></div>
          <div class="gp-doc-info">
            <strong>
              <?= htmlspecialchars($doc['label']) ?>
              <?php if ($doc['required']): ?>
                <span style="color:#ef4444;font-size:10px;margin-left:4px;font-weight:600;">*Required</span>
              <?php else: ?>
                <span style="color:#94a3b8;font-size:10px;margin-left:4px;">Optional</span>
              <?php endif; ?>
            </strong>
            <small><?= htmlspecialchars($doc['hint']) ?></small>
          </div>
          <div class="gp-doc-acts">
            <?php if (!empty($doc['format_file_path'])): ?>
              <button type="button" class="btn btn-sm btn-outline download-format" data-filepath="<?= htmlspecialchars($doc['format_file_path']) ?>">
                <i class="fas fa-download"></i> Format
              </button>
            <?php endif; ?>
            <label class="btn btn-sm btn-outline" for="gate-doc-<?= htmlspecialchars($doc['id']) ?>" style="cursor:pointer;margin:0;">
              <i class="fas fa-upload"></i> Choose File
            </label>
            <input class="gate-doc-input" type="file"
                   id="gate-doc-<?= htmlspecialchars($doc['id']) ?>"
                   name="<?= htmlspecialchars($doc['key']) ?>"
                   accept=".pdf,.jpg,.jpeg,.png"
                   <?= $doc['required'] ? 'required' : '' ?>>
            <button type="button" class="btn btn-sm btn-outline btn-view-doc hidden" title="View Document">
              <i class="fas fa-eye"></i> View
            </button>
            <span class="gp-file-badge" data-file-state><i class="fas fa-clock"></i> Pending</span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Remarks removed as per request -->

    <div class="gp-footer">
      <button type="button" class="btn btn-outline" id="backToEmployees"><i class="fas fa-arrow-left"></i> Back</button>
      <div class="gp-footer-right">
        <button type="button" class="btn btn-outline" id="saveGatePassDraft"><i class="fas fa-floppy-disk"></i> Save Draft</button>
        <button type="submit" class="btn btn-primary" id="submitGatePass"><i class="fas fa-paper-plane"></i> Submit Gate Pass Request</button>
      </div>
    </div>
  </form>
</section>

<!-- Recent Requests -->
<section class="gp-panel">
  <div class="gp-panel-hd">
    <div>
      <h3><i class="fas fa-clock-rotate-left" style="color:#0ea5e9;"></i> Recent Gate Pass Requests</h3>
      <p>Last 10 submitted requests and their current approval status.</p>
    </div>
    <a href="pass_status.php" class="btn btn-sm btn-outline"><i class="fas fa-external-link-alt"></i> View All</a>
  </div>
  <div class="gp-tw">
    <table class="gp-tbl">
      <thead>
        <tr>
          <th>Request No.</th>
          <th>Employee</th>
          <th>Temp ID</th>
          <th>Status</th>
          <th>Submitted On</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$requests): ?>
          <tr><td colspan="5">
            <div class="gp-empty">
              <i class="fas fa-inbox"></i>
              <strong>No requests submitted yet</strong>
              <span>Your submitted gate pass requests will appear here.</span>
            </div>
          </td></tr>
        <?php endif; ?>
        <?php foreach ($requests as $req):
          $st  = strtolower($req['status'] ?? '');
          $cls = in_array($st, ['approved','active']) ? 'ok' : (in_array($st, ['rejected','reupload_required']) ? 'danger' : 'warn');
          $dot = $cls === 'ok' ? '#16a34a' : ($cls === 'danger' ? '#dc2626' : '#d97706');
        ?>
          <tr>
            <td><strong class="gp-code"><?= htmlspecialchars($req['request_no']) ?></strong></td>
            <td><?= htmlspecialchars($req['worker_name']) ?></td>
            <td><span class="gp-code"><?= htmlspecialchars($req['temp_id'] ?: '-') ?></span></td>
            <td>
              <span class="gp-pill <?= $cls ?>">
                <span class="gp-dot" style="background:<?= $dot ?>;"></span>
                <?= htmlspecialchars(strtoupper(str_replace('_',' ',$req['status']))) ?>
              </span>
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
const aadhaarSearch = document.getElementById('aadhaarSearch');
const nameSearch    = document.getElementById('nameSearch');

function fireMessage(icon, title, text) {
  if (typeof Swal !== 'undefined' && Swal.fire)
    return Swal.fire({icon, title, text, confirmButtonColor:'#2563eb'});
  alert(title + ': ' + text);
  return Promise.resolve();
}

function setStep(step) {
  document.querySelectorAll('[data-step-indicator]').forEach(el => {
    const v = Number(el.dataset.stepIndicator);
    el.classList.toggle('active', v === step);
    el.classList.toggle('done', v < step);
  });
  const l1 = document.getElementById('gpLine1');
  const l2 = document.getElementById('gpLine2');
  if (l1) l1.classList.toggle('done', step > 1);
  if (l2) l2.classList.toggle('done', step > 2);
}

async function selectEmployee(worker, btn) {
  const orig = btn ? btn.innerHTML : '';
  if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; }
  try {
    const body = new FormData();
    body.append('action', 'prepare');
    body.append('workman_id', worker.id);
    const res    = await fetch('../../api/save_gate_pass_request.php', {method:'POST', body});
    const result = await res.json();
    if (!result.success) throw new Error(result.message || 'Unable to prepare Gate Pass request.');
    const reqId = (result.data && result.data.request_id) || result.request_id || '';
    document.getElementById('requestId').value        = reqId;
    document.getElementById('selectedWorkerId').value = worker.id;
    document.getElementById('selectedName').textContent     = worker.name     || '-';
    document.getElementById('selectedAadhaar').textContent  = worker.aadhaar  || '-';
    document.getElementById('selectedCategory').textContent = worker.category || '-';
    document.getElementById('selectedTempId').textContent   = worker.temp_id  || '-';
    employeeStep.classList.add('hidden');
    documentStep.classList.remove('hidden');
    setStep(2);
    window.scrollTo({top:0, behavior:'smooth'});
  } catch(err) {
    fireMessage('error', 'Gate Pass Request', err.message);
  } finally {
    if (btn) { btn.disabled = false; btn.innerHTML = orig; }
  }
}

document.querySelectorAll('.select-worker').forEach(btn => {
  btn.addEventListener('click', e => selectEmployee(JSON.parse(e.currentTarget.dataset.worker), e.currentTarget));
});
document.querySelectorAll('.worker-checkbox').forEach(cb => {
  cb.addEventListener('change', e => {
    document.querySelectorAll('.worker-checkbox').forEach(o => { if (o !== e.currentTarget) o.checked = false; });
    const btn = e.currentTarget.closest('tr')?.querySelector('.select-worker');
    if (btn) btn.click();
  });
});

function filterEmployees() {
  const a = aadhaarSearch.value.trim().toLowerCase();
  const n = nameSearch.value.trim().toLowerCase();
  document.querySelectorAll('#employeeRows tr[data-name]').forEach(row => {
    row.classList.toggle('hidden', !row.dataset.aadhaar.includes(a) || !row.dataset.name.includes(n));
  });
}
document.getElementById('searchEmployees')?.addEventListener('click', filterEmployees);
[aadhaarSearch, nameSearch].forEach(inp => inp?.addEventListener('input', filterEmployees));
document.getElementById('resetSearch')?.addEventListener('click', () => { aadhaarSearch.value=''; nameSearch.value=''; filterEmployees(); });
document.getElementById('backToEmployees')?.addEventListener('click', () => {
  documentStep.classList.add('hidden');
  employeeStep.classList.remove('hidden');
  setStep(1);
});

function updateDocProgress() {
  const inputs   = Array.from(document.querySelectorAll('.gate-doc-input'));
  const uploaded = inputs.filter(i => i.files.length > 0).length;
  const el = document.getElementById('documentProgress');
  if (el) {
    el.textContent = `${uploaded} / ${inputs.length} uploaded`;
    el.className   = 'gp-pill ' + (uploaded > 0 && uploaded === inputs.length ? 'ok' : 'muted');
  }
}

document.querySelectorAll('.gate-doc-input').forEach(input => {
  input.addEventListener('change', () => {
    const row = input.closest('.gp-doc-row');
    const state = row?.querySelector('[data-file-state]');
    const viewBtn = row?.querySelector('.btn-view-doc');
    const file = input.files[0];
    
    if (state) {
      const fname = file?.name || '';
      state.innerHTML = fname
        ? `<i class="fas fa-check-circle"></i> ${fname.length > 20 ? fname.substring(0,20)+'…' : fname}`
        : '<i class="fas fa-clock"></i> Pending';
      state.classList.toggle('uploaded', Boolean(file));
    }
    
    if (viewBtn) {
      if (file) {
        const url = URL.createObjectURL(file);
        viewBtn.dataset.url = url;
        viewBtn.classList.remove('hidden');
      } else {
        viewBtn.classList.add('hidden');
        if (viewBtn.dataset.url) {
          URL.revokeObjectURL(viewBtn.dataset.url);
          delete viewBtn.dataset.url;
        }
      }
    }
    
    updateDocProgress();
  });
});

document.querySelectorAll('.btn-view-doc').forEach(btn => {
  btn.addEventListener('click', () => {
    if (btn.dataset.url) {
      window.open(btn.dataset.url, '_blank');
    }
  });
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
documentForm?.addEventListener('submit', async e => { e.preventDefault(); if (!documentForm.reportValidity()) return; saveGatePass('submit'); });
</script>
<?php
}

renderLayout("Gate Pass Creation Request", 'renderContent', $role, $name);
