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

    if (!empty($safetyApprovalRequests)) {
        $ids = array_column($safetyApprovalRequests, 'workman_id');
        $workerDocs = [];
        if (!empty($ids)) {
            $idsCsv = implode(',', array_map('intval', $ids));
            $docRes = mysqli_query($conn, "SELECT workman_id, document_type, file_path FROM documents WHERE workman_id IN ($idsCsv)");
            while ($d = mysqli_fetch_assoc($docRes)) {
                $workerDocs[$d['workman_id']][] = ['type' => $d['document_type'], 'file_path' => $d['file_path']];
            }
        }
        foreach ($safetyApprovalRequests as &$r) {
            $r['documents'] = $workerDocs[$r['workman_id']] ?? [];
        }
        unset($r);
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
            <?php if(!empty($approval['execution_training_remarks'])): ?>
              <div style="font-size:11px; margin-top:6px; color:#64748b; font-style:italic; white-space:normal; line-height:1.2;">
                <i class="fas fa-comment-dots" style="color:#94a3b8; font-size:9px;"></i> <?= htmlspecialchars($approval['execution_training_remarks']) ?>
              </div>
            <?php endif; ?>
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

<!-- Workman Details Modal (Popup Style) -->
<style>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  z-index: 9999;
  display: none;
  align-items: center;
  justify-content: center;
  padding: 20px;
}
.modal-overlay.show {
  display: flex !important;
  opacity: 1; pointer-events: all;
}
.modal-overlay.hidden {
  opacity: 0; pointer-events: none;
}
.modal-box {
  background: #fff;
  border-radius: 12px;
  max-width: 1000px;
  width: 96%;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  max-height: 90vh;
  animation: modalFadeIn 0.25s ease-out;
}
@keyframes modalFadeIn {
  from { transform: scale(0.95); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 24px;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
}
.modal-title {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}
.modal-close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #94a3b8;
  line-height: 1;
  transition: color 0.15s;
}
.modal-close:hover {
  color: #475569;
}
.modal-body {
  padding: 24px;
  overflow-y: auto;
  flex-grow: 1;
}
</style>

<div id="workmanModal" class="modal-overlay hidden" onclick="handleOverlayClick(event)">
  <div class="modal-box" style="max-width:850px; width:92%;">
    <div class="modal-header">
      <h3 class="modal-title">Worker Profile</h3>
      <button class="modal-close" onclick="closeWorkmanModal()">&times;</button>
    </div>
    <div id="viewContent" class="modal-body" style="padding:20px;"></div>
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

function showWorkmanDetails(w) {
  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const d = new Date(dateString);
    if (isNaN(d)) return dateString;
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}-${month}-${year}`;
  };

  const getDocUrl = (path) => {
    if (!path) return '';
    if (path.match(/^https?:\/\//i)) {
        return path;
    }
    const filename = path.split('/').pop();
    return `../../uploads/workers/${encodeURIComponent(filename)}`;
  };

  const renderDocLink = (label, path) => {
    if (!path) return '';
    const url = getDocUrl(path);
    return `
      <div class="doc-item" style="display:flex; align-items:center; justify-content:space-between; padding:8px 12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; font-size:12px; margin-bottom:6px; font-weight:600;">
        <i class="fas fa-file-pdf text-danger" style="font-size:15px; color:#ef4444;"></i>
        <span class="doc-label" style="flex-grow:1; margin-left:10px; font-weight:600; color:#334155;">${label}</span>
        <a href="${url}" target="_blank" class="btn btn-xs btn-outline-primary" style="padding:2px 8px; font-size:11px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:4px; border:1px solid #3b82f6; border-radius:6px; color:#3b82f6; background:transparent;"><i class="fas fa-external-link-alt"></i> View</a>
      </div>
    `;
  };

  const photoSrc = w.photo ? getDocUrl(w.photo) : 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23cbd5e1"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>';

  let statusLabel = 'Active';
  let statusStyle = 'background:#10b981; color:#fff;';
  if (w.is_blocked == 1) {
    statusLabel = `Blocked by ${w.blocked_source || 'Welfare'}`;
    statusStyle = 'background:#ef4444; color:#fff;';
  }

  let vaultHtml = '';
  const renderedCoreFiles = new Set();
  const getBasename = (p) => p ? p.split(/[\\/]/).pop().toLowerCase() : '';
  
  const renderAndTrack = (label, path) => {
    if (!path) return '';
    renderedCoreFiles.add(getBasename(path));
    return renderDocLink(label, path);
  };

  vaultHtml += renderAndTrack('Workman Photo', w.photo);
  vaultHtml += renderAndTrack('Signature', w.signature);
  vaultHtml += renderAndTrack('Aadhaar Card', w.aadhaar_doc);
  vaultHtml += renderAndTrack('Training Approval', w.training_approval_doc);
  vaultHtml += renderAndTrack('Education Cert', w.education_doc);
  vaultHtml += renderAndTrack('Bank Passbook', w.bank_doc);
  vaultHtml += renderAndTrack('Skill Certificate', w.skill_cert_doc);
  vaultHtml += renderAndTrack('Police Verification', w.police_doc);
  vaultHtml += renderAndTrack('Medical Report', w.medical_doc);
  vaultHtml += renderAndTrack('Insurance Copy', w.insurance_doc);

  if (vaultHtml.trim() === '') {
    vaultHtml = '<div style="font-size:11px; color:#94a3b8; text-align:center; padding:10px; border:1px dashed #e2e8f0; border-radius:8px;">No core documents uploaded.</div>';
  }

  let dynamicDocsHtml = '';
  if (w.documents && w.documents.length > 0) {
    let count = 0;
    w.documents.forEach(doc => {
      if (!renderedCoreFiles.has(getBasename(doc.file_path))) {
        const docLink = renderDocLink(doc.type, doc.file_path);
        if (docLink) {
          dynamicDocsHtml += docLink;
          count++;
        }
      }
    });
    if (count === 0) {
      dynamicDocsHtml = '<div style="font-size:11px; color:#94a3b8; text-align:center; padding:10px; border:1px dashed #e2e8f0; border-radius:8px;">No additional dynamic documents.</div>';
    }
  } else {
    dynamicDocsHtml = '<div style="font-size:11px; color:#94a3b8; text-align:center; padding:10px; border:1px dashed #e2e8f0; border-radius:8px;">No additional dynamic documents.</div>';
  }

  let payStatusStr = (w.training_payment_status || '').toUpperCase();
  if (!payStatusStr) payStatusStr = 'NOT PAID';
  let payBadgeColor = payStatusStr === 'PAID' ? '#166534' : (payStatusStr === 'NOT PAID' ? '#475569' : '#92400e');

  document.getElementById('viewContent').innerHTML = `
    <style>
      .profile-container { font-family: 'Outfit', 'Inter', sans-serif; color: #1e293b; text-align: left; }
      .profile-header-card { display: flex; gap: 24px; background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); color: white; padding: 24px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); align-items: center; }
      .profile-photo { width: 100px; height: 120px; object-fit: cover; border-radius: 12px; border: 3px solid rgba(255,255,255,0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
      .profile-header-info { display: flex; flex-direction: column; gap: 4px; flex-grow: 1; }
      .profile-name { font-size: 22px; font-weight: 800; margin: 0; color: #fff; line-height: 1.2; }
      .profile-sub { font-size: 13px; color: rgba(255,255,255,0.85); margin-bottom: 6px; }
      .badge-container { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 4px; }
      .profile-badge { font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 99px; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; }
      .badge-role { background: rgba(255,255,255,0.2); color: white; }
      
      .profile-body-grid { display: grid; grid-template-columns: 1.8fr 1.2fr; gap: 20px; }
      .profile-section-card { background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 18px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
      .profile-section-title { font-size: 13px; font-weight: 800; color: #1e3a8a; border-bottom: 2px solid #eff6ff; padding-bottom: 6px; margin-top: 0; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 8px; }
      
      .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 16px; font-size: 13px; }
      .detail-row { display: flex; flex-direction: column; gap: 2px; }
      .detail-label { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; }
      .detail-val { font-weight: 600; color: #1e293b; word-break: break-all; }
    </style>

    <div class="profile-container">
      <!-- Banner header -->
      <div class="profile-header-card">
        <img src="${photoSrc}" class="profile-photo" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; viewBox=&quot;0 0 24 24&quot; fill=&quot;%23cbd5e1&quot;><path d=&quot;M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z&quot;/></svg>'">
        <div class="profile-header-info">
          <h4 class="profile-name">${w.name}</h4>
          <div class="profile-sub">${w.gender} | DOB: ${formatDate(w.dob)} | Reg Date: ${formatDate(w.registration_date)}</div>
          <div class="badge-container">
            <span class="profile-badge badge-role"><i class="fas fa-user-shield"></i> ${w.worker_type ? w.worker_type.replace(' Pass', '') : 'Workman'}</span>
            <span class="profile-badge" style="${statusStyle}"><i class="fas fa-info-circle"></i> ${statusLabel}</span>
          </div>
        </div>
      </div>

      <!-- Body content split -->
      <div class="profile-body-grid">
        <!-- Left: Details -->
        <div>
          <!-- 1. Personal Info -->
          <div class="profile-section-card">
            <h5 class="profile-section-title"><i class="fas fa-id-card"></i> Personal Information</h5>
            <div class="details-grid">
              <div class="detail-row">
                <span class="detail-label">Temp ID</span>
                <span class="detail-val text-primary" style="font-weight:700; color:#3b82f6;">${w.temp_id}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Aadhaar Number</span>
                <span class="detail-val">${w.aadhaar}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Father's Name</span>
                <span class="detail-val">${w.father_name || 'N/A'}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Marital Status</span>
                <span class="detail-val">${w.marital_status || 'N/A'}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Nationality</span>
                <span class="detail-val">${w.nationality || 'Indian'}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Blood Group</span>
                <span class="detail-val">${w.blood_group || 'N/A'}</span>
              </div>
            </div>
          </div>

          <!-- 2. Address & Contact -->
          <div class="profile-section-card">
            <h5 class="profile-section-title"><i class="fas fa-map-marker-alt"></i> Contact & Address</h5>
            <div class="details-grid" style="grid-template-columns: 1fr;">
              <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                <div class="detail-row">
                  <span class="detail-label">Mobile Number</span>
                  <span class="detail-val">${w.mobile}</span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">WhatsApp Number</span>
                  <span class="detail-val">${w.whatsapp_no || 'N/A'}</span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Email Address</span>
                  <span class="detail-val">${w.email || 'N/A'}</span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Emergency Contact</span>
                  <span class="detail-val">${w.emergency_contact || 'N/A'}</span>
                </div>
              </div>
              <div class="detail-row" style="margin-top: 4px;">
                <span class="detail-label">Present Address</span>
                <span class="detail-val" style="font-weight: 500;">${w.present_address} ${w.district ? ', ' + w.district : ''} ${w.state ? ', ' + w.state : ''} ${w.pincode ? ' - ' + w.pincode : ''}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Permanent Address</span>
                <span class="detail-val" style="font-weight: 500;">${w.permanent_address || 'Same as Present'}</span>
              </div>
            </div>
          </div>

          <!-- 3. Work Order & Employment -->
          <div class="profile-section-card">
            <h5 class="profile-section-title"><i class="fas fa-briefcase"></i> Work Order & Employment</h5>
            <div class="details-grid">
              <div class="detail-row">
                <span class="detail-label">Work Order No</span>
                <span class="detail-val" style="color:#1e3a8a;">${w.work_order_no || 'N/A'}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Project Name</span>
                <span class="detail-val">${w.project_name || 'N/A'}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Department</span>
                <span class="detail-val">${w.department}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Trade / Nature of Work</span>
                <span class="detail-val">${w.nature_of_work || w.trade || 'N/A'}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Skill Category</span>
                <span class="detail-val">${w.skill_category}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Experience</span>
                <span class="detail-val">${w.experience ? w.experience + ' Years' : 'N/A'}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">PF Number / UAN</span>
                <span class="detail-val">${w.pf_no || 'N/A'}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">ESIC Number</span>
                <span class="detail-val">${w.esi_no || w.esic_number || 'N/A'}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Certified Wage Rate</span>
                <span class="detail-val">${w.certified_wage_rate || 'N/A'}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Expected Join Date</span>
                <span class="detail-val" style="color:#10b981;">${formatDate(w.expected_joining_date)}</span>
              </div>
              <div class="detail-row" style="grid-column: 1 / -1; background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0; margin-top: 5px;">
                <span class="detail-label"><i class="fas fa-comment-dots text-primary"></i> EO Remarks</span>
                <span class="detail-val" style="font-size:12px; font-style:italic;">${w.execution_training_remarks || w.request_remarks || 'No remarks provided.'}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Flow Approvals + Documents -->
        <div>
          <!-- 4. Workflow Timeline -->
          <div class="profile-section-card">
            <h5 class="profile-section-title"><i class="fas fa-list-check"></i> Workflow Status</h5>
            
            <div style="font-weight:700; font-size:11px; color:#64748b; margin-bottom:4px; text-transform:uppercase;">Safety Training Status</div>
            <div style="padding:10px 12px; background:#f1f5f9; border-radius:12px; border:1px solid #cbd5e1; font-size:12px;">
              <div><strong>Payment Option:</strong> ${(w.safety_fee_payment_option || 'N/A').replace(/_/g, ' ').toUpperCase()}</div>
              <div style="margin-bottom:6px;"><strong>Payment Status:</strong> <strong style="color:${payBadgeColor};">${payStatusStr}</strong></div>
              <div><strong>Batch:</strong> ${w.pre_batch_number || w.assigned_batch_number || w.latest_training_batch || 'Not Scheduled'}</div>
              <div><strong>Language:</strong> ${w.batch_language || w.training_booking_language || w.safety_language || 'N/A'}</div>
              <div><strong>Date:</strong> ${formatDate(w.pre_batch_date || w.latest_training_date)}</div>
              <div><strong>Training Result:</strong> <strong style="text-transform:uppercase; color:${w.safety_status === 'passed' ? '#166534' : (w.safety_status === 'failed' ? '#991b1b' : '#92400e')}">${w.safety_status || 'PENDING'}</strong></div>
            </div>
          </div>

          <!-- 5. Documents Vault -->
          <div class="profile-section-card">
            <h5 class="profile-section-title"><i class="fas fa-folder-open"></i> Documents Vault</h5>
            <div class="doc-vault">
              ${vaultHtml}
              
              <div style="font-weight: 700; font-size: 11px; color: #475569; margin: 10px 0 4px 0; border-top: 1px solid #e2e8f0; padding-top: 8px; text-transform: uppercase;">Dynamic Documents</div>
              ${dynamicDocsHtml}
            </div>
          </div>
        </div>
      </div>
    </div>
  `;
  
  const modal = document.getElementById('workmanModal');
  modal.classList.remove('hidden');
  modal.classList.add('show');
}

function closeWorkmanModal() {
  const modal = document.getElementById('workmanModal');
  modal.classList.remove('show');
  setTimeout(() => modal.classList.add('hidden'), 250);
}

function handleOverlayClick(e) {
  if (e.target.id === 'workmanModal') {
    closeWorkmanModal();
  }
}

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
  
  const alertOptions = {
    icon: rejecting ? 'warning' : 'question',
    title: rejecting ? 'Reject enrollment?' : 'Confirm schedule of safety class.',
    text: rejecting
      ? 'The enrollment will return to the contractor for correction and resubmission.'
      : 'The worker will be released for Safety training scheduling.',
    showCancelButton: true,
    confirmButtonText: rejecting ? 'Reject & Return' : 'Approve Enrollment',
    confirmButtonColor: rejecting ? '#dc2626' : '#16a34a'
  };

  if (rejecting) {
    alertOptions.input = 'textarea';
    alertOptions.inputLabel = 'Correction / rejection remarks';
    alertOptions.inputPlaceholder = 'Clearly mention what the contractor must correct.';
    alertOptions.inputValidator = value => !String(value || '').trim() ? 'Rejection remarks are required.' : undefined;
  }

  const prompt = await Swal.fire(alertOptions);
  if (!prompt.isConfirmed) return;
  await doEnrollmentAPI([workmanId], decision, rejecting ? String(prompt.value || '').trim() : '');
}

// ── Approve/Reject entire batch group ───────────────────────────────────────
async function reviewBatchGroup(workmanIds, decision, batchLabel) {
  const rejecting  = decision === 'rejected';
  const count      = workmanIds.length;
  
  const alertOptions = {
    icon: rejecting ? 'warning' : 'question',
    title: rejecting
      ? `Reject all ${count} enrollment(s) in "${batchLabel}"?`
      : `Confirm schedule of safety class.`,
    html: rejecting
      ? `All selected workers in <strong>${batchLabel}</strong> will be returned to their contractors for correction.`
      : `All <strong>${count} workers</strong> in batch <strong>${batchLabel}</strong> will be released for Safety training scheduling.`,
    showCancelButton: true,
    confirmButtonText: rejecting ? `Reject ${count} Worker(s)` : `Approve ${count} Worker(s)`,
    confirmButtonColor: rejecting ? '#dc2626' : '#16a34a',
    cancelButtonText: 'Cancel'
  };

  if (rejecting) {
    alertOptions.input = 'textarea';
    alertOptions.inputLabel = 'Correction / rejection remarks (applies to all)';
    alertOptions.inputPlaceholder = 'Clearly mention what the contractor must correct.';
    alertOptions.inputValidator = value => !String(value || '').trim() ? 'Rejection remarks are required.' : undefined;
  }

  const prompt = await Swal.fire(alertOptions);
  if (!prompt.isConfirmed) return;
  await doEnrollmentAPI(workmanIds, decision, rejecting ? String(prompt.value || '').trim() : '');
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
  
  const alertOptions = {
    icon: rejecting ? 'warning' : 'question',
    title: rejecting ? `Reject ${workmanIds.length} enrollment(s)?` : `Confirm schedule of safety class.`,
    text: rejecting
      ? 'Selected enrollments will return to the contractor for correction and resubmission.'
      : 'Selected workers will be released for Safety training scheduling.',
    showCancelButton: true,
    confirmButtonText: rejecting ? 'Reject & Return Selected' : 'Approve Selected',
    confirmButtonColor: rejecting ? '#dc2626' : '#16a34a'
  };

  if (rejecting) {
    alertOptions.input = 'textarea';
    alertOptions.inputLabel = 'Correction / rejection remarks';
    alertOptions.inputPlaceholder = 'Clearly mention what the contractor must correct.';
    alertOptions.inputValidator = value => !String(value || '').trim() ? 'Rejection remarks are required.' : undefined;
  }

  const prompt = await Swal.fire(alertOptions);
  if (!prompt.isConfirmed) return;
  await doEnrollmentAPI(workmanIds, decision, rejecting ? String(prompt.value || '').trim() : '');
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
