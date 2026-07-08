<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/training_flow.php';
require_once __DIR__ . '/../../include/training_venue_master.php';
require_once __DIR__ . '/../../include/training_type_master.php';
require_once __DIR__ . '/../../include/safety_training_control.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Safety Officer';

function safetyApprovalTableExists($conn, $table) {
    $table = mysqli_real_escape_string($conn, $table);
    $res = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return $res && mysqli_num_rows($res) > 0;
}

function safetyApprovalColumnExists($conn, $table, $column) {
    if (!safetyApprovalTableExists($conn, $table)) return false;
    $safeTable = str_replace('`', '``', $table);
    $column = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$column'");
    return $res && mysqli_num_rows($res) > 0;
}

function renderContent() {
    global $conn;
    clms_safety_ensure_control_schema($conn);

    $hasRequests = safetyApprovalTableExists($conn, 'training_requests');
    $hasWorkmen  = safetyApprovalTableExists($conn, 'workmen');

    // ── Seed training_requests queue ────────────────────────────────────────
    // 1) Standard seed: EO-approved workers who have a paid payment record
    if ($hasRequests && $hasWorkmen) {
        clms_training_seed_approved_queue($conn);
    }

    // 2) Supplemental seed: EO-approved workers WITHOUT a payment record
    //    (free workers – SO/PO with zero fee, or EO-approved via online route)
    //    This ensures they still appear in the Safety inbox even if no payment
    //    record exists.
    if ($hasRequests && $hasWorkmen) {
        @mysqli_query($conn, "
            INSERT INTO training_requests
                (workman_id, contractor_id, training_type, requested_date, preferred_date,
                 preferred_shift, remarks, source, requested_by, status, created_at, updated_at)
            SELECT
                w.id,
                w.contractor_id,
                'Safety Induction',
                CURDATE(),
                NULL,
                'morning',
                'Auto-created for Safety Department approval after Executing Officer approval (free/direct route).',
                CASE WHEN COALESCE(w.training_approval_doc, '') <> '' THEN 'attached_doc' ELSE 'execution' END,
                COALESCE(w.execution_training_reviewed_by, 0),
                'pending_safety',
                NOW(),
                NOW()
            FROM workmen w
            WHERE COALESCE(w.execution_training_status, '') = 'approved'
              AND COALESCE(w.contractor_id, 0) > 0
              AND LOWER(TRIM(COALESCE(w.training_status, 'pending')))
                  IN ('', 'pending', 'training_pending', 'training_failed', 'fail', 'failed')
              AND LOWER(COALESCE(w.safety_enrollment_status, 'pending')) <> 'approved'
              AND NOT EXISTS (
                  SELECT 1 FROM training_requests tr
                  WHERE tr.workman_id = w.id
                    AND tr.status IN (
                        'pending_safety', 'welfare_pending', 'pending',
                        'scheduled', 'contractor_confirmed', 'passed'
                    )
              )
        ");
    }

    $safetyApprovalRequests = [];
    if ($hasRequests && $hasWorkmen) {
        $approvalContractorNameParts = [];
        foreach (['contractor_name', 'vendor_name', 'name'] as $column) {
            if (safetyApprovalColumnExists($conn, 'contractors', $column)) {
                $approvalContractorNameParts[] = "c.`$column`";
            }
        }
        $approvalContractorNameParts[] = "CONCAT('Contractor #', w.contractor_id)";
        $approvalContractorNameExpr = "COALESCE(" . implode(', ', $approvalContractorNameParts) . ")";

        // ── Batch matching strategy ──────────────────────────────────────────
        // At the pending_safety stage, workers are NOT yet in training_batch_workers.
        // Instead, we match each worker to the EARLIEST upcoming batch whose
        // language_name matches the worker's safety_language (case-insensitive).
        // This groups workers under their planned batch for one-click approval.
        $hasBatches = safetyApprovalTableExists($conn, 'training_class_batches');

        $batchJoin   = '';
        $batchSelect = "NULL AS pre_batch_number, NULL AS pre_batch_date, NULL AS batch_language, NULL AS pre_batch_label";

        if ($hasBatches) {
            // LEFT JOIN to find the best matching batch:
            // 1. If mapping exists in training_batch_workers, use that batch (if active).
            // 2. If tr.batch_number is set, match the batch with that batch_number (if active).
            // 3. Otherwise, match by worker's safety_language to the earliest upcoming batch of that language.
            $batchJoin = "
            LEFT JOIN training_class_batches tcb ON tcb.id = COALESCE(
                (
                    SELECT tbw.batch_id 
                    FROM training_batch_workers tbw 
                    JOIN training_class_batches b ON b.id = tbw.batch_id
                    WHERE tbw.training_request_id = tr.id 
                      AND tbw.ticked = 1 
                      AND LOWER(COALESCE(b.status, '')) IN ('open', 'scheduled', 'active')
                    LIMIT 1
                ),
                (
                    SELECT tb_asg.id 
                    FROM training_class_batches tb_asg 
                    WHERE tb_asg.batch_number = tr.batch_number 
                      AND LOWER(COALESCE(tb_asg.status, '')) IN ('open', 'scheduled', 'active')
                    LIMIT 1
                ),
                (
                    SELECT tb2.id
                    FROM training_class_batches tb2
                    WHERE LOWER(TRIM(tb2.language_name)) = LOWER(TRIM(COALESCE(NULLIF(w.training_booking_language, ''), w.safety_language, '')))
                      AND tb2.training_date >= CURDATE()
                      AND LOWER(COALESCE(tb2.status, '')) IN ('open', 'scheduled', 'active')
                    ORDER BY tb2.training_date ASC, tb2.id ASC
                    LIMIT 1
                )
            )";
            $batchSelect = "tcb.batch_number AS pre_batch_number,
                   tcb.training_date AS pre_batch_date,
                   tcb.language_name AS batch_language,
                   CONCAT(tcb.batch_number, ' — ', DATE_FORMAT(tcb.training_date, '%d %b %Y'), ' (', tcb.language_name, ')') AS pre_batch_label";
        }

        $batchOrderExpr = $hasBatches
            ? "COALESCE(tcb.batch_number, 'ZZZZ')"
            : "'ZZZZ'";

        $safetyApprovalRequests = db_fetch_all($conn, "
            SELECT tr.id AS request_id, tr.status AS request_status, tr.source, tr.remarks AS request_remarks,
                   tr.batch_number AS assigned_batch_number,
                   $batchSelect,
                   w.*, w.id AS workman_id, w.name AS worker_name,
                   COALESCE(w.safety_enrollment_status, 'pending') AS safety_enrollment_status,
                   $approvalContractorNameExpr AS contractor_name
            FROM training_requests tr
            JOIN workmen w ON w.id = tr.workman_id
            LEFT JOIN contractors c ON c.id = w.contractor_id
            $batchJoin
            WHERE LOWER(COALESCE(tr.status, '')) IN ('pending_safety', 'welfare_pending')
              AND LOWER(COALESCE(w.execution_training_status, '')) = 'approved'
              AND LOWER(COALESCE(w.safety_enrollment_status, 'pending')) <> 'approved'
              AND tr.id = (
                  SELECT tr2.id
                  FROM training_requests tr2
                  WHERE tr2.workman_id = tr.workman_id
                  ORDER BY tr2.id DESC
                  LIMIT 1
              )
            ORDER BY $batchOrderExpr ASC,
                     COALESCE(LOWER(NULLIF(w.training_booking_language, '')), LOWER(w.safety_language), 'zzz') ASC,
                     COALESCE(tr.updated_at, tr.created_at) ASC, tr.id ASC
        ");

    }


    // ── Group by Language → Batch ─────────────────────────────────────────────
    // Effective batch number: prefer pre-assigned batch (from training_class_batches JOIN),
    // fall back to batch_number stored on the training_request row itself.
    $groups = []; // [ lang_key => [ 'language'=>..., 'batches'=> [ batchKey => [...] ] ] ]
    foreach ($safetyApprovalRequests as $req) {
        $effectiveLang = !empty($req['training_booking_language']) ? $req['training_booking_language'] : ($req['safety_language'] ?? '');
        $lang    = trim((string)($req['batch_language'] ?? $effectiveLang ?? ''));
        $langKey = $lang !== '' ? strtolower($lang) : '__no_language__';

        // Use pre-assigned active batch (from training_class_batches)
        $bn = trim((string)($req['pre_batch_number'] ?? ''));
        $bnKey = $bn !== '' ? $bn : '__no_batch__';

        $batchLabel = trim((string)($req['pre_batch_label'] ?? ''));
        if ($batchLabel === '') $batchLabel = 'No Batch Assigned';

        if (!isset($groups[$langKey])) {
            $groups[$langKey] = [
                'language'     => $lang !== '' ? $lang : 'No Language Specified',
                'language_raw' => $lang,
                'batches'      => [],
                'all_workers'  => [],
            ];
        }
        if (!isset($groups[$langKey]['batches'][$bnKey])) {
            $groups[$langKey]['batches'][$bnKey] = [
                'batch_number' => $bn !== '' ? $bn : null,
                'label'        => $batchLabel,
                'workers'      => [],
            ];
        }
        $groups[$langKey]['batches'][$bnKey]['workers'][] = $req;
        $groups[$langKey]['all_workers'][] = $req;
    }

    $safetyApprovalPending = count($safetyApprovalRequests);
    $batchCount = 0;
    foreach ($groups as $langGroup) {
        foreach ($langGroup['batches'] as $bk => $b) {
            if ($bk !== '__no_batch__') $batchCount++;
        }
    }
?>

<style>
/* ── Page layout ── */
.ea-page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px}
.ea-page-header h2{font-size:20px;font-weight:800;color:#0f172a;display:flex;align-items:center;gap:10px}
.ea-stats{display:flex;gap:12px;flex-wrap:wrap}
.ea-stat-pill{background:#f1f5f9;border:1px solid #e2e8f0;border-radius:10px;padding:6px 14px;font-size:12px;font-weight:700;color:#475569;display:flex;align-items:center;gap:6px}
.ea-stat-pill .val{font-size:16px;font-weight:900;color:#2563eb}

/* ── Batch group card ── */
.batch-group{background:#fff;border:1px solid #e2e8f0;border-radius:14px;margin-bottom:20px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.04)}
.batch-group-header{display:flex;align-items:center;justify-content:space-between;padding:13px 18px;background:linear-gradient(135deg,#1e3a8a 0%,#2563eb 100%);flex-wrap:wrap;gap:10px}
.batch-group-header.no-batch{background:linear-gradient(135deg,#475569 0%,#64748b 100%)}
.batch-group-title{display:flex;align-items:center;gap:10px}
.batch-group-title h3{color:#fff;font-size:15px;font-weight:800;margin:0}
.batch-group-title .worker-count{background:rgba(255,255,255,.2);color:#fff;border-radius:999px;padding:2px 10px;font-size:11px;font-weight:900}
.batch-group-actions{display:flex;gap:8px;flex-wrap:wrap}
.btn-batch-approve{background:#10b981;color:#fff;border:none;padding:7px 16px;border-radius:8px;font-size:12px;font-weight:800;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:.15s}
.btn-batch-approve:hover{background:#059669}
.btn-batch-reject{background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.35);padding:7px 14px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:.15s}
.btn-batch-reject:hover{background:rgba(239,68,68,.8);border-color:transparent}
.batch-select-all-wrap{display:flex;align-items:center;gap:8px;color:rgba(255,255,255,.85);font-size:12px;font-weight:700;cursor:pointer}
.batch-select-all-wrap input{transform:scale(1.2);cursor:pointer;accent-color:#fff}

/* ── Worker table ── */
.ea-table{width:100%;border-collapse:collapse}
.ea-table thead{background:#f8fafc}
.ea-table th{padding:9px 12px;font-size:11px;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.3px;text-align:left;border-bottom:2px solid #e2e8f0;white-space:nowrap}
.ea-table th.col-center,.ea-table td.col-center{text-align:center}
.ea-table td{padding:10px 12px;font-size:13px;color:#1e293b;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.ea-table tbody tr:hover{background:#f8fafc}
.ea-table .worker-name{font-weight:700;color:#0f172a}
.ea-table .text-muted{font-size:11px;color:#64748b;margin-top:2px}
.ea-table .badge{display:inline-flex;align-items:center;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:800}
.badge-success{background:#dcfce7;color:#166534}
.badge-warning{background:#fef3c7;color:#92400e}
.badge-info{background:#dbeafe;color:#1d4ed8}

/* ── Row actions ── */
.ea-row-actions{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
.btn-row{display:inline-flex;align-items:center;gap:5px;padding:5px 11px;border-radius:7px;font-size:11px;font-weight:800;cursor:pointer;border:none;transition:.15s}
.btn-row-approve{background:#dcfce7;color:#166534}
.btn-row-approve:hover{background:#bbf7d0}
.btn-row-reject{background:#fee2e2;color:#991b1b}
.btn-row-reject:hover{background:#fecaca}

/* ── Floating batch panel ── */
.batch-action-panel{position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1e293b;color:#fff;
    padding:12px 24px;border-radius:12px;display:flex;align-items:center;gap:16px;
    box-shadow:0 10px 25px -5px rgba(0,0,0,.3);z-index:10000;transition:all .3s ease}
.batch-action-panel.hidden{display:none!important}

/* ── Empty state ── */
.ea-empty{padding:50px 20px;text-align:center;color:#94a3b8}
.ea-empty i{font-size:40px;display:block;margin-bottom:12px;color:#cbd5e1}
.ea-empty h4{font-size:16px;font-weight:700;color:#64748b;margin-bottom:6px}
</style>

<!-- Page header -->
<div class="ea-page-header">
  <div>
    <h2><i class="fas fa-user-check" style="color:#6366f1"></i> Enrollment Approval — Batch Wise</h2>
    <div style="font-size:12px;color:#64748b;margin-top:4px">EO-approved enrollments grouped by training batch. Approve or reject an entire batch at once.</div>
  </div>
  <div class="ea-stats">
    <div class="ea-stat-pill"><span class="val"><?= $batchCount ?></span> Batches</div>
    <div class="ea-stat-pill"><span class="val"><?= $safetyApprovalPending ?></span> Pending</div>
    <a class="btn btn-outline" href="dashboard.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
  </div>
</div>

<?php if (empty($groups)): ?>
<div class="batch-group">
  <div class="ea-empty">
    <i class="fas fa-check-circle" style="color:#10b981"></i>
    <h4>All Clear!</h4>
    <p>No enrollment is waiting for Safety Department approval.</p>
  </div>
</div>
<?php else: ?>

<?php foreach ($groups as $langKey => $langGroup): 
    $languageName = htmlspecialchars($langGroup['language']);
    $langWorkerCount = count($langGroup['all_workers']);
    $langWorkerIds = array_column($langGroup['all_workers'], 'workman_id');
    $langWorkerIdsJson = htmlspecialchars(json_encode(array_values(array_map('intval', $langWorkerIds))), ENT_QUOTES);
    $langGroupId = 'lang-group-' . preg_replace('/[^a-zA-Z0-9]/', '_', $langKey);
?>
<div class="language-section" style="margin-bottom: 40px;">
  <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom: 16px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;">
    <h3 style="margin:0; font-size: 18px; font-weight: 800; color: #1e293b;">
      <i class="fas fa-language" style="color:#6366f1; margin-right:8px;"></i>
      Language: <?= $languageName ?>
      <span style="font-size:12px; background:#e0e7ff; color:#4338ca; padding:3px 8px; border-radius:99px; margin-left:8px; vertical-align:middle;"><?= $langWorkerCount ?> pending</span>
    </h3>
    <div style="display:flex; gap:8px;">
      <button class="btn btn-sm" style="background:#10b981; color:#fff; border:none; font-weight:700;" 
              onclick="reviewBatchGroup(<?= $langWorkerIdsJson ?>, 'approved', 'Language: <?= addslashes($languageName) ?>')">
        <i class="fas fa-check-double"></i> Approve All <?= $languageName ?>
      </button>
      <button class="btn btn-sm btn-outline-danger" style="font-weight:700;" 
              onclick="reviewBatchGroup(<?= $langWorkerIdsJson ?>, 'rejected', 'Language: <?= addslashes($languageName) ?>')">
        <i class="fas fa-times"></i> Reject All <?= $languageName ?>
      </button>
    </div>
  </div>

  <?php foreach ($langGroup['batches'] as $groupKey => $group):
      $batchWorkers  = $group['workers'];
      $batchNumber   = $group['batch_number'];
      $batchLabel    = htmlspecialchars($group['label']);
      $workerCount   = count($batchWorkers);
      $isNoBatch     = ($batchNumber === null);
      $workerIds     = array_column($batchWorkers, 'workman_id');
      $workerIdsJson = htmlspecialchars(json_encode(array_values(array_map('intval', $workerIds))), ENT_QUOTES);
      $groupId       = 'batch-group-' . preg_replace('/[^a-zA-Z0-9]/', '_', $langKey . '_' . $groupKey);
  ?>
  <div class="batch-group" id="<?= $groupId ?>" style="margin-bottom: 20px;">

    <!-- Batch group header -->
    <div class="batch-group-header <?= $isNoBatch ? 'no-batch' : '' ?>">
      <div class="batch-group-title">
        <label class="batch-select-all-wrap" title="Select / deselect all in this batch">
          <input type="checkbox" class="batch-select-all" data-group="<?= $groupId ?>" onchange="toggleBatchAll(this)">
          Select All
        </label>
        <div>
          <h3 style="font-size:15px; margin:0; color:#fff;">
            <?php if ($isNoBatch): ?>
              <i class="fas fa-layer-group"></i> No Batch Assigned
            <?php else: ?>
              <i class="fas fa-th-list"></i> Batch: <?= $batchLabel ?>
            <?php endif; ?>
          </h3>
          <div style="font-size:11px;color:rgba(255,255,255,.75);margin-top:2px">
            <?= $workerCount ?> worker<?= $workerCount > 1 ? 's' : '' ?> pending approval
          </div>
        </div>
        <span class="worker-count"><?= $workerCount ?></span>
      </div>
      <div class="batch-group-actions">
        <button class="btn-batch-approve"
                onclick="reviewBatchGroup(<?= $workerIdsJson ?>, 'approved', '<?= addslashes($group['label']) ?>')"
                title="Approve all <?= $workerCount ?> workers in this batch">
          <i class="fas fa-check"></i> Approve Batch
        </button>
        <button class="btn-batch-reject"
                onclick="reviewBatchGroup(<?= $workerIdsJson ?>, 'rejected', '<?= addslashes($group['label']) ?>')"
                title="Reject all <?= $workerCount ?> workers in this batch">
          <i class="fas fa-times"></i> Reject Batch
        </button>
      </div>
    </div>

    <!-- Workers table -->
    <div style="overflow-x:auto">
      <table class="ea-table">
        <thead>
          <tr>
            <th class="col-center" style="width:40px"><i class="fas fa-check" style="font-size:10px"></i></th>
            <th>Worker</th>
            <th>Contractor / Work</th>
            <th>Language</th>
            <th>Executing Officer</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($batchWorkers as $approval): ?>
        <tr id="safety-approval-row-<?= (int)$approval['workman_id'] ?>">
          <td class="col-center">
            <input type="checkbox"
                   class="worker-checkbox group-checkbox-<?= $groupId ?>"
                   value="<?= (int)$approval['workman_id'] ?>"
                   style="transform:scale(1.15);cursor:pointer;accent-color:#2563eb"
                   onchange="onWorkerCheckChange()">
          </td>
          <td>
            <div class="worker-name"><?= htmlspecialchars($approval['worker_name'] ?? '') ?></div>
            <div class="text-muted"><code><?= htmlspecialchars($approval['temp_id'] ?: ('W-' . $approval['workman_id'])) ?></code></div>
            <div class="text-muted">Aadhaar: <?= htmlspecialchars($approval['aadhaar'] ?? '-') ?></div>
          </td>
          <td>
            <div style="font-weight:700"><?= htmlspecialchars($approval['contractor_name'] ?? 'N/A') ?></div>
            <div class="text-muted"><?= htmlspecialchars($approval['department'] ?? '-') ?> / <?= htmlspecialchars($approval['nature_of_work'] ?? '-') ?></div>
          </td>
          <td>
            <span class="badge badge-info"><?= htmlspecialchars(!empty($approval['training_booking_language']) ? $approval['training_booking_language'] : ($approval['safety_language'] ?? '-')) ?></span>
          </td>
          <td>
            <code><?= htmlspecialchars($approval['executing_officer_code'] ?? '-') ?></code>
            <div class="text-muted"><?= htmlspecialchars($approval['executing_officer_name'] ?? '') ?></div>
            <span class="badge badge-success" style="margin-top:4px">EO Approved</span>
          </td>
          <td>
            <span class="badge badge-warning">Safety Pending</span>
            <div class="text-muted" style="margin-top:4px"><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string)($approval['source'] ?? 'enrolment')))) ?></div>
            <?php if (!empty($approval['training_approval_doc'])): ?>
              <a class="btn btn-row btn-row-view" style="margin-top:5px" target="_blank"
                 href="../../uploads/workers/<?= rawurlencode(basename((string)$approval['training_approval_doc'])) ?>">
                 <i class="fas fa-file-pdf"></i> Doc
              </a>
            <?php endif; ?>
          </td>
          <td>
            <div class="ea-row-actions">
              <button type="button" class="btn-view btn-view-workman"
                      data-workman='<?= htmlspecialchars(json_encode($approval), ENT_QUOTES, 'UTF-8') ?>'>
                <i class="fas fa-eye"></i> View
              </button>
              <button class="btn btn-row btn-row-approve" type="button"
                      onclick="reviewSafetyEnrollment(<?= (int)$approval['workman_id'] ?>, 'approved')">
                <i class="fas fa-check"></i> Approve
              </button>
              <button class="btn btn-row btn-row-reject" type="button"
                      onclick="reviewSafetyEnrollment(<?= (int)$approval['workman_id'] ?>, 'rejected')">
                <i class="fas fa-times"></i> Reject
              </button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endforeach; ?>

<?php endif; ?>

<!-- Floating multi-select panel -->
<div id="batchActionPanel" class="batch-action-panel hidden">
  <span style="font-size:13px;font-weight:700"><i class="fas fa-tasks" style="color:#6366f1;margin-right:6px"></i> <span id="selectedCountText">0</span> workers selected</span>
  <div style="height:20px;width:1px;background:#475569"></div>
  <button class="btn-batch-approve" onclick="reviewSafetyEnrollmentBatch('approved')"><i class="fas fa-check"></i> Approve Selected</button>
  <button class="btn-batch-reject" style="border:1px solid rgba(255,255,255,.3)" onclick="reviewSafetyEnrollmentBatch('rejected')"><i class="fas fa-times"></i> Reject Selected</button>
  <button class="btn-row btn-row-view" onclick="clearAllSelections()" style="border:1px solid rgba(255,255,255,.2);color:#fff;background:transparent;font-size:11px">Clear</button>
</div>

<!-- Workman Details Modal -->
<style>
.modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,.65);backdrop-filter:blur(4px);z-index:9999;display:none;align-items:center;justify-content:center;padding:20px}
.modal-overlay.show{display:flex!important}
.modal-box{background:#f8fafc;border-radius:16px;max-width:1060px;width:97%;box-shadow:0 25px 50px -12px rgba(0,0,0,.3);overflow:hidden;display:flex;flex-direction:column;max-height:92vh;animation:modalFadeIn .25s ease-out}
@keyframes modalFadeIn{from{transform:scale(.95);opacity:0}to{transform:scale(1);opacity:1}}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:16px 24px;border-bottom:1px solid #e2e8f0;background:#fff}
.modal-title{margin:0;font-size:16px;font-weight:700;color:#0f172a}
.modal-close{background:none;border:none;font-size:24px;cursor:pointer;color:#94a3b8;line-height:1;transition:color .15s}
.modal-close:hover{color:#475569}
.modal-body{padding:20px 24px;overflow-y:auto;flex-grow:1}
/* Profile Header Banner */
.wm-profile-banner{display:flex;gap:20px;align-items:center;background:linear-gradient(135deg,#1e3a8a 0%,#2563eb 100%);color:#fff;padding:20px 24px;border-radius:12px;margin-bottom:20px;box-shadow:0 8px 15px -3px rgba(0,0,0,0.15)}
.wm-photo{width:80px;height:96px;object-fit:cover;border-radius:10px;border:3px solid rgba(255,255,255,0.8);flex-shrink:0}
.wm-photo-placeholder{width:80px;height:96px;border-radius:10px;border:3px solid rgba(255,255,255,0.3);background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;font-size:32px;color:rgba(255,255,255,0.7);flex-shrink:0}
.wm-banner-name{font-size:20px;font-weight:800;margin:0 0 4px;color:#fff}
.wm-banner-sub{font-size:12px;color:rgba(255,255,255,.85);margin:0 0 8px}
.wm-badge{font-size:10px;font-weight:700;padding:3px 10px;border-radius:99px;text-transform:uppercase;letter-spacing:.5px;display:inline-block;background:rgba(255,255,255,.2);color:#fff;margin-right:6px}
/* Section cards */
.wm-section{background:#fff;border:1px solid #e2e8f0;border-radius:12px;margin-bottom:16px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.wm-section-title{font-size:10px;font-weight:800;color:#fff;background:#1e3a8a;padding:8px 14px;text-transform:uppercase;letter-spacing:.6px;display:flex;align-items:center;gap:8px}
.wm-section-body{padding:14px 16px}
.wm-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px 18px}
.wm-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px 14px}
.wm-row{display:flex;flex-direction:column;gap:2px}
.wm-label{font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.3px}
.wm-val{font-size:12px;font-weight:600;color:#1e293b;word-break:break-word}
/* Doc cards */
.wm-docs-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px}
.wm-doc-card{padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;display:flex;flex-direction:column;gap:6px}
.wm-doc-label{font-size:11px;font-weight:600;color:#334155}
/* 2-col layout */
.wm-body-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
</style>

<div id="workmanModal" class="modal-overlay" onclick="handleOverlayClick(event)">
  <div class="modal-box">
    <div class="modal-header">
      <h3 class="modal-title"><i class="fas fa-id-card" style="color:#1e3a8a;margin-right:6px"></i> Workman Enrollment Profile</h3>
      <button class="modal-close" onclick="closeWorkmanModal()">&times;</button>
    </div>
    <div class="modal-body">

      <!-- Header Banner -->
      <div class="wm-profile-banner">
        <div class="wm-photo-placeholder" id="wm-photo-wrap"><i class="fas fa-user"></i></div>
        <div style="flex-grow:1">
          <h4 class="wm-banner-name" id="wm-name">-</h4>
          <p class="wm-banner-sub" id="wm-banner-sub">-</p>
          <div>
            <span class="wm-badge" id="wm-pass-badge">Workman</span>
            <span class="wm-badge" id="wm-status-badge">-</span>
          </div>
        </div>
      </div>

      <!-- 2-col Body -->
      <div class="wm-body-grid">
        <!-- LEFT -->
        <div>
          <!-- Basic Info -->
          <div class="wm-section">
            <div class="wm-section-title"><i class="fas fa-id-card"></i> Basic Info</div>
            <div class="wm-section-body">
              <div class="wm-grid">
                <div class="wm-row"><span class="wm-label">Father's Name</span><span class="wm-val" id="wm-father">-</span></div>
                <div class="wm-row"><span class="wm-label">Gender / DOB</span><span class="wm-val" id="wm-gender-dob">-</span></div>
                <div class="wm-row"><span class="wm-label">Marital Status</span><span class="wm-val" id="wm-marital">-</span></div>
                <div class="wm-row"><span class="wm-label">Nationality</span><span class="wm-val" id="wm-nationality">-</span></div>
                <div class="wm-row"><span class="wm-label">Blood Group</span><span class="wm-val" id="wm-blood">-</span></div>
                <div class="wm-row"><span class="wm-label">Religion</span><span class="wm-val" id="wm-religion">-</span></div>
                <div class="wm-row"><span class="wm-label">PWD Status</span><span class="wm-val" id="wm-pwd">-</span></div>
                <div class="wm-row"><span class="wm-label">Passport No</span><span class="wm-val" id="wm-passport">-</span></div>
                <div class="wm-row"><span class="wm-label">Driving Licence</span><span class="wm-val" id="wm-dl">-</span></div>
                <div class="wm-row"><span class="wm-label">Email ID</span><span class="wm-val" id="wm-email">-</span></div>
              </div>
            </div>
          </div>
          <!-- Address / Contact -->
          <div class="wm-section">
            <div class="wm-section-title"><i class="fas fa-map-marker-alt"></i> Address / Contact</div>
            <div class="wm-section-body">
              <div class="wm-grid" style="margin-bottom:10px">
                <div class="wm-row"><span class="wm-label">Mobile Number</span><span class="wm-val" id="wm-mobile">-</span></div>
                <div class="wm-row"><span class="wm-label">WhatsApp</span><span class="wm-val" id="wm-whatsapp">-</span></div>
                <div class="wm-row"><span class="wm-label">Emergency Contact</span><span class="wm-val" id="wm-emergency">-</span></div>
                <div class="wm-row"><span class="wm-label">Location</span><span class="wm-val" id="wm-location">-</span></div>
              </div>
              <div style="display:grid;gap:8px">
                <div class="wm-row"><span class="wm-label">Present Address</span><span class="wm-val" id="wm-present-addr">-</span></div>
                <div class="wm-row"><span class="wm-label">Permanent Address</span><span class="wm-val" id="wm-permanent-addr">-</span></div>
              </div>
            </div>
          </div>
        </div>
        <!-- RIGHT -->
        <div>
          <!-- Employment -->
          <div class="wm-section">
            <div class="wm-section-title"><i class="fas fa-briefcase"></i> Work / Employment</div>
            <div class="wm-section-body">
              <div class="wm-grid">
                <div class="wm-row"><span class="wm-label">Contractor</span><span class="wm-val" id="wm-contractor">-</span></div>
                <div class="wm-row"><span class="wm-label">Work Order No</span><span class="wm-val" id="wm-wo-no">-</span></div>
                <div class="wm-row"><span class="wm-label">Project (WBS)</span><span class="wm-val" id="wm-project">-</span></div>
                <div class="wm-row"><span class="wm-label">Department</span><span class="wm-val" id="wm-dept">-</span></div>
                <div class="wm-row"><span class="wm-label">Trade</span><span class="wm-val" id="wm-trade">-</span></div>
                <div class="wm-row"><span class="wm-label">Skill Category</span><span class="wm-val" id="wm-skill-cat">-</span></div>
                <div class="wm-row"><span class="wm-label">Nature of Work</span><span class="wm-val" id="wm-nature-work">-</span></div>
                <div class="wm-row"><span class="wm-label">Experience</span><span class="wm-val" id="wm-experience">-</span></div>
                <div class="wm-row"><span class="wm-label">Safety Language</span><span class="wm-val" id="wm-safety-lang">-</span></div>
                <div class="wm-row"><span class="wm-label">Executing Officer</span><span class="wm-val" id="wm-exec-officer">-</span></div>
              </div>
            </div>
          </div>
          <!-- Statutory -->
          <div class="wm-section">
            <div class="wm-section-title"><i class="fas fa-university"></i> Statutory &amp; Banking</div>
            <div class="wm-section-body">
              <div class="wm-grid">
                <div class="wm-row"><span class="wm-label">Aadhaar Number</span><span class="wm-val" id="wm-aadhaar">-</span></div>
                <div class="wm-row"><span class="wm-label">EPF Registered</span><span class="wm-val" id="wm-epf">-</span></div>
                <div class="wm-row"><span class="wm-label">PF No / UAN</span><span class="wm-val" id="wm-pf-uan">-</span></div>
                <div class="wm-row"><span class="wm-label">ESI Registered</span><span class="wm-val" id="wm-esi">-</span></div>
                <div class="wm-row"><span class="wm-label">ESIC Number</span><span class="wm-val" id="wm-esic">-</span></div>
                <div class="wm-row"><span class="wm-label">Bank Account No</span><span class="wm-val" id="wm-bank-acc">-</span></div>
                <div class="wm-row"><span class="wm-label">Bank IFSC Code</span><span class="wm-val" id="wm-bank-ifsc">-</span></div>
                <div class="wm-row"><span class="wm-label">Certified Wage Rate</span><span class="wm-val" id="wm-wage-rate">-</span></div>
                <div class="wm-row"><span class="wm-label">Payment Option</span><span class="wm-val" id="wm-pay-option">-</span></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Documents -->
      <div class="wm-section" style="margin-bottom:0">
        <div class="wm-section-title"><i class="fas fa-folder-open"></i> Documents Vault (Annexure 4A / 6A)</div>
        <div class="wm-section-body">
          <div class="wm-docs-grid" id="wm-docs-container"></div>
        </div>
      </div>

    </div>
    <div style="padding:14px 24px;border-top:1px solid #e2e8f0;background:#f8fafc;display:flex;justify-content:flex-end">
      <button class="btn btn-outline" onclick="closeWorkmanModal()">Close</button>
    </div>
  </div>
</div>

<script>
// ── View workman modal ───────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.btn-view-workman').forEach(btn => {
    btn.addEventListener('click', function() {
      try { showWorkmanDetails(JSON.parse(this.getAttribute('data-workman'))); }
      catch(e) { console.error('Error parsing workman data:', e); }
    });
  });
});

function showWorkmanDetails(data) {
  // Populate banner header
  document.getElementById('wm-name').textContent       = data.name || 'N/A';
  const bannerSub = document.getElementById('wm-banner-sub');
  if (bannerSub) bannerSub.textContent = (data.gender || 'N/A') + ' | DOB: ' + (data.dob || 'N/A') + ' | Aadhaar: ' + (data.aadhaar || 'N/A');
  const passB = document.getElementById('wm-pass-badge');
  if (passB) passB.textContent = data.pass_type || 'Workman';
  const statusB = document.getElementById('wm-status-badge');
  if (statusB) statusB.textContent = data.work_order_no || data.contractor_name || '';

  // Photo
  const photoWrap = document.getElementById('wm-photo-wrap');
  if (photoWrap && data.photo) {
    photoWrap.innerHTML = `<img src="../../uploads/workers/${encodeURIComponent(data.photo)}" class="wm-photo" onerror="this.parentNode.innerHTML='<i class=\\'fas fa-user\\'></i>'">`;
  }

  document.getElementById('wm-father').textContent     = data.father_name || 'N/A';
  document.getElementById('wm-gender-dob').textContent = (data.gender || 'N/A') + ' / ' + (data.dob || 'N/A');
  document.getElementById('wm-marital').textContent    = data.marital_status || 'N/A';
  document.getElementById('wm-nationality').textContent= data.nationality || 'N/A';
  document.getElementById('wm-blood').textContent      = data.blood_group || 'N/A';
  document.getElementById('wm-religion').textContent   = data.region || 'N/A';
  document.getElementById('wm-pwd').textContent        = data.pwd_status || 'N/A';
  document.getElementById('wm-passport').textContent   = data.passport_no || 'N/A';
  document.getElementById('wm-dl').textContent         = data.driving_licence_no || 'N/A';
  document.getElementById('wm-email').textContent      = data.email || data.contact_email || 'N/A';
  document.getElementById('wm-mobile').textContent     = data.mobile || 'N/A';
  document.getElementById('wm-whatsapp').textContent   = data.whatsapp_no || 'N/A';
  document.getElementById('wm-emergency').textContent  = data.emergency_contact || 'N/A';
  document.getElementById('wm-present-addr').innerHTML = (data.present_address || 'N/A').replace(/\n/g, '<br>');
  document.getElementById('wm-permanent-addr').innerHTML = (data.permanent_address || 'N/A').replace(/\n/g, '<br>');
  document.getElementById('wm-location').textContent   = [data.district, data.state, data.pincode].filter(Boolean).join(', ');
  document.getElementById('wm-contractor').textContent = data.contractor_name || 'N/A';
  document.getElementById('wm-wo-no').textContent      = data.work_order_no || 'N/A';
  document.getElementById('wm-project').textContent    = data.project_name || 'N/A';
  document.getElementById('wm-dept').textContent       = data.department || 'N/A';
  document.getElementById('wm-trade').textContent      = data.trade || 'N/A';
  document.getElementById('wm-skill-cat').textContent  = data.skill_category || 'N/A';
  document.getElementById('wm-nature-work').textContent= data.nature_of_work || 'N/A';
  document.getElementById('wm-experience').textContent = (data.experience || '0') + ' Years';
  document.getElementById('wm-safety-lang').textContent= (data.training_booking_language || data.safety_language || 'N/A');
  document.getElementById('wm-exec-officer').textContent= (data.executing_officer_name || 'N/A') + ' (E-Code: ' + (data.executing_officer_code || 'N/A') + ')';
  document.getElementById('wm-aadhaar').textContent    = data.aadhaar || 'N/A';
  document.getElementById('wm-epf').textContent        = data.epf_registered_worker || 'N/A';
  document.getElementById('wm-pf-uan').textContent     = (data.pf_no || 'N/A') + ' / ' + (data.uan_number || 'N/A');
  document.getElementById('wm-esi').textContent        = data.esi_registered_worker || 'N/A';
  document.getElementById('wm-esic').textContent       = data.esic_number || data.esi_no || 'N/A';
  document.getElementById('wm-bank-acc').textContent   = data.bank_account || 'N/A';
  document.getElementById('wm-bank-ifsc').textContent  = data.ifsc || 'N/A';
  document.getElementById('wm-wage-rate').textContent  = 'INR ' + (data.certified_wage_rate || '0.00');
  document.getElementById('wm-pay-option').textContent = (data.safety_fee_payment_option || 'N/A').replace(/_/g, ' ').toUpperCase();

  const docContainer = document.getElementById('wm-docs-container');
  docContainer.innerHTML = '';
  const docsList = [
    { key: 'aadhaar_doc', label: 'Aadhaar (4A)' },
    { key: 'medical_doc', label: 'Medical (4A)' },
    { key: 'police_doc', label: 'Police Verification (4A)' },
    { key: 'insurance_doc', label: 'Insurance (4A)' },
    { key: 'education_doc', label: 'Education (4A)' },
    { key: 'educational_doc', label: 'Education (Alt)' },
    { key: 'photo', label: 'Worker Photo' },
    { key: 'signature_doc', label: 'Worker Signature' },
    { key: 'bank_doc', label: 'Bank Proof' },
    { key: 'gatepass_doc', label: 'Gate Pass Doc' },
    { key: 'skill_cert_doc', label: 'Skill Certificate' },
    { key: 'training_approval_doc', label: 'Training Approval Doc' }
  ];
  let docCount = 0;
  docsList.forEach(doc => {
    const file = data[doc.key];
    if (file) {
      docCount++;
      const card = document.createElement('div');
      card.className = 'wm-doc-card';
      card.innerHTML = `<span class="wm-doc-label">${doc.label}</span>
        <a href="../../uploads/workers/${encodeURIComponent(file)}" target="_blank"
           class="btn btn-sm btn-outline" style="text-align:center;width:100%;margin-top:auto;font-size:11px;padding:4px 6px;">
          <i class="fas fa-external-link-alt"></i> View</a>`;
      docContainer.appendChild(card);
    }
  });
  if (docCount === 0) {
    docContainer.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:15px;color:#64748b;font-size:12px;">No uploaded documents found.</div>';
  }
  document.getElementById('workmanModal').classList.add('show');
}

function closeWorkmanModal() { document.getElementById('workmanModal').classList.remove('show'); }
function handleOverlayClick(e) { if (e.target.id === 'workmanModal') closeWorkmanModal(); }

// ── Checkbox logic ───────────────────────────────────────────────────────────
function toggleBatchAll(selectAllCb) {
  const groupId = selectAllCb.dataset.group;
  document.querySelectorAll('.group-checkbox-' + groupId).forEach(cb => {
    cb.checked = selectAllCb.checked;
  });
  onWorkerCheckChange();
}

function onWorkerCheckChange() {
  const allChecked   = document.querySelectorAll('.worker-checkbox:checked');
  const allCheckboxes= document.querySelectorAll('.worker-checkbox');
  const count        = allChecked.length;
  const panel        = document.getElementById('batchActionPanel');
  const countText    = document.getElementById('selectedCountText');
  if (countText) countText.textContent = count;
  if (panel) panel.classList.toggle('hidden', count === 0);

  // Sync per-batch select-all checkboxes
  document.querySelectorAll('.batch-select-all').forEach(bsa => {
    const groupId = bsa.dataset.group;
    const groupCbs = document.querySelectorAll('.group-checkbox-' + groupId);
    const groupChecked = document.querySelectorAll('.group-checkbox-' + groupId + ':checked');
    bsa.checked = groupCbs.length > 0 && groupChecked.length === groupCbs.length;
    bsa.indeterminate = groupChecked.length > 0 && groupChecked.length < groupCbs.length;
  });
}

function clearAllSelections() {
  document.querySelectorAll('.worker-checkbox').forEach(cb => cb.checked = false);
  document.querySelectorAll('.batch-select-all').forEach(cb => { cb.checked = false; cb.indeterminate = false; });
  onWorkerCheckChange();
}

// ── Single worker approval ──────────────────────────────────────────────────
async function reviewSafetyEnrollment(workmanId, decision) {
  const rejecting = decision === 'rejected';
  const prompt = await Swal.fire({
    icon: rejecting ? 'warning' : 'question',
    title: rejecting ? 'Reject enrollment?' : 'Confirm schedule of safety class.',
    text: rejecting
      ? 'The enrollment will return to the contractor for correction and resubmission.'
      : 'The worker will be released for Safety training scheduling.',
    input: 'textarea',
    inputLabel: rejecting ? 'Correction / rejection remarks' : 'Approval remarks (optional)',
    inputPlaceholder: rejecting ? 'Clearly mention what the contractor must correct.' : 'Enter remarks if required',
    showCancelButton: true,
    confirmButtonText: rejecting ? 'Reject & Return' : 'Approve Enrollment',
    confirmButtonColor: rejecting ? '#dc2626' : '#16a34a',
    inputValidator: value => rejecting && !String(value || '').trim() ? 'Rejection remarks are required.' : undefined
  });
  if (!prompt.isConfirmed) return;
  await doEnrollmentAPI([workmanId], decision, String(prompt.value || '').trim());
}

// ── Approve/Reject entire batch group ───────────────────────────────────────
async function reviewBatchGroup(workmanIds, decision, batchLabel) {
  const rejecting  = decision === 'rejected';
  const count      = workmanIds.length;
  const prompt = await Swal.fire({
    icon: rejecting ? 'warning' : 'question',
    title: rejecting
      ? `Reject all ${count} enrollment(s) in "${batchLabel}"?`
      : `Confirm schedule of safety class.`,
    html: rejecting
      ? `All selected workers in <strong>${batchLabel}</strong> will be returned to their contractors for correction.`
      : `All <strong>${count} workers</strong> in batch <strong>${batchLabel}</strong> will be released for Safety training scheduling.`,
    input: 'textarea',
    inputLabel: rejecting ? 'Correction / rejection remarks (applies to all)' : 'Approval remarks (optional)',
    inputPlaceholder: rejecting ? 'Clearly mention what the contractor must correct.' : 'Enter remarks if required',
    showCancelButton: true,
    confirmButtonText: rejecting ? `Reject ${count} Worker(s)` : `Approve ${count} Worker(s)`,
    confirmButtonColor: rejecting ? '#dc2626' : '#16a34a',
    cancelButtonText: 'Cancel',
    inputValidator: value => rejecting && !String(value || '').trim() ? 'Rejection remarks are required.' : undefined
  });
  if (!prompt.isConfirmed) return;
  await doEnrollmentAPI(workmanIds, decision, String(prompt.value || '').trim());
}

// ── Multi-select floating panel ──────────────────────────────────────────────
async function reviewSafetyEnrollmentBatch(decision) {
  const selectedBoxes = document.querySelectorAll('.worker-checkbox:checked');
  const workmanIds    = Array.from(selectedBoxes).map(cb => parseInt(cb.value));
  if (workmanIds.length === 0) {
    Swal.fire('No Selection', 'Please select at least one worker.', 'warning');
    return;
  }
  const rejecting = decision === 'rejected';
  const prompt = await Swal.fire({
    icon: rejecting ? 'warning' : 'question',
    title: rejecting ? `Reject ${workmanIds.length} enrollment(s)?` : `Confirm schedule of safety class.`,
    text: rejecting
      ? 'Selected enrollments will return to the contractor for correction and resubmission.'
      : 'Selected workers will be released for Safety training scheduling.',
    input: 'textarea',
    inputLabel: rejecting ? 'Correction / rejection remarks' : 'Approval remarks (optional)',
    inputPlaceholder: rejecting ? 'Clearly mention what the contractor must correct.' : 'Enter remarks if required',
    showCancelButton: true,
    confirmButtonText: rejecting ? 'Reject & Return Selected' : 'Approve Selected',
    confirmButtonColor: rejecting ? '#dc2626' : '#16a34a',
    inputValidator: value => rejecting && !String(value || '').trim() ? 'Rejection remarks are required.' : undefined
  });
  if (!prompt.isConfirmed) return;
  await doEnrollmentAPI(workmanIds, decision, String(prompt.value || '').trim());
}

// ── Shared API call ───────────────────────────────────────────────────────────
async function doEnrollmentAPI(workmanIds, decision, remarks) {
  Swal.fire({
    title: 'Processing...',
    text: 'Please wait while we update selected enrollments.',
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading()
  });
  try {
    const response = await fetch('../../api/safety/review_enrollment.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CLMS_CSRF_TOKEN || '' },
      body: JSON.stringify({ workman_ids: workmanIds, decision, remarks })
    });
    const result = await response.json();
    if (!response.ok || !result.success) throw new Error(result.message || 'Unable to update enrollment approvals.');
    await Swal.fire({
      icon: 'success',
      title: decision === 'approved' ? 'Approved!' : 'Rejected!',
      text: result.message,
      confirmButtonColor: '#1e3a8a'
    });
    location.reload();
  } catch (error) {
    Swal.fire('Action Failed', error.message || 'Server response could not be processed.', 'error');
  }
}
</script>
<?php
}

renderLayout('Safety Department Enrollment Approval — Batch Wise', 'renderContent', $role, $name);
?>
