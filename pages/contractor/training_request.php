<?php
require_once '../../include/auth.php';
checkAuth(['contractor', 'customer']);
include '../../include/config.php';
include '../../include/customer_portal_context.php';
include '../../include/layout.php';
require_once __DIR__ . '/../../include/training_flow.php';
require_once __DIR__ . '/../../include/payment_flow.php';
require_once __DIR__ . '/../../include/training_type_master.php';

// One-time fix to repair statuses that were wrongly set to pending
@mysqli_query($conn, "UPDATE training_requests SET status='pending_safety' WHERE status='pending' AND source='contractor_re_enroll'");

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';
$user_id = $_SESSION['user_id'];
clms_get_portal_contractor($conn);

function contractorTrainingTableExists($conn, $table) {
    $safeTable = mysqli_real_escape_string($conn, $table);
    $res = mysqli_query($conn, "SHOW TABLES LIKE '$safeTable'");
    return $res && mysqli_num_rows($res) > 0;
}

function contractorTrainingColumnExists($conn, $table, $column) {
    if (!contractorTrainingTableExists($conn, $table)) return false;
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$safeColumn'");
    return $res && mysqli_num_rows($res) > 0;
}

function repairPrematureTrainingConfirmations($conn, $contractorId) {
    if (!$contractorId ||
        !contractorTrainingTableExists($conn, 'audit_logs') ||
        !contractorTrainingTableExists($conn, 'training_session_workers') ||
        !contractorTrainingColumnExists($conn, 'training_requests', 'scheduled_session_id') ||
        !contractorTrainingColumnExists($conn, 'training_requests', 'contractor_confirmed') ||
        !contractorTrainingColumnExists($conn, 'training_requests', 'contractor_remarks')) {
        return;
    }

    $affected = db_fetch_all(
        $conn,
        "SELECT tr.id, tr.scheduled_session_id
         FROM training_requests tr
         WHERE tr.contractor_id = ?
           AND tr.status = 'contractor_confirmed'
           AND COALESCE(tr.contractor_confirmed, 0) = 1
           AND TRIM(COALESCE(tr.contractor_remarks, '')) = ''
           AND NOT EXISTS (
               SELECT 1
               FROM audit_logs al
               WHERE al.action = 'training_confirmed'
                 AND al.module = 'training_requests'
                 AND al.details LIKE CONCAT('%Request ID ', tr.id, ' confirmed by contractor%')
           )
           AND NOT EXISTS (
               SELECT 1
               FROM training_session_workers tsw
               WHERE tsw.training_request_id = tr.id
                 AND (
                     LOWER(COALESCE(tsw.attendance_status, 'pending')) NOT IN ('pending', '')
                     OR LOWER(COALESCE(tsw.result, 'pending')) NOT IN ('pending', '')
                 )
           )",
        'i',
        [(int)$contractorId]
    );
    if (!$affected) return;

    $requestIds = array_map(function($row) { return (int)$row['id']; }, $affected);
    $sessionIds = array_values(array_unique(array_filter(array_map(function($row) { return (int)($row['scheduled_session_id'] ?? 0); }, $affected))));
    $ids = implode(',', $requestIds);

    mysqli_begin_transaction($conn);
    try {
        mysqli_query($conn, "DELETE FROM training_session_workers WHERE training_request_id IN ($ids)");
        mysqli_query($conn, "UPDATE training_requests SET status = 'scheduled', contractor_confirmed = 0, updated_at = NOW() WHERE id IN ($ids)");
        foreach ($sessionIds as $sessionId) {
            db_execute(
                $conn,
                "UPDATE training_schedule
                 SET enrolled_count = (
                     SELECT COUNT(*)
                     FROM training_session_workers tsw
                     JOIN training_requests tr ON tr.id = tsw.training_request_id
                     WHERE tsw.session_id = ? AND tr.status = 'contractor_confirmed'
                 )
                 WHERE id = ?",
                'ii',
                [$sessionId, $sessionId]
            );
        }
        mysqli_commit($conn);
    } catch (Throwable $e) {
        mysqli_rollback($conn);
    }
}

function renderContent() {
    global $conn, $user_id;
    clms_ensure_payment_flow($conn);
    $trainingTypes = clms_get_training_type_rows($conn, true);

    $contractor = db_single($conn, "SELECT id, contractor_name FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    $c_id = $contractor['id'] ?? null;
    $paymentRequests = $c_id ? db_fetch_all(
        $conn,
        "SELECT * FROM training_payment_requests WHERE contractor_id = ? ORDER BY id DESC",
        'i',
        [(int)$c_id]
    ) : [];

    $paymentWorkersMap = [];
    if ($c_id && !empty($paymentRequests)) {
        $pwRows = db_fetch_all($conn,
            "SELECT pw.payment_request_id, w.name, w.temp_id
             FROM training_payment_request_workers pw
             JOIN workmen w ON pw.workman_id = w.id
             JOIN training_payment_requests pr ON pr.id = pw.payment_request_id
             WHERE pr.contractor_id = ?",
            'i', [(int)$c_id]
        );
        foreach ($pwRows as $row) {
            $paymentWorkersMap[$row['payment_request_id']][] = htmlspecialchars($row['name']) . ($row['temp_id'] ? " (" . htmlspecialchars($row['temp_id']) . ")" : "");
        }
    }

    // Eligible workers (pending training)
    $eligible_workers = $c_id ? db_fetch_all($conn,
        "SELECT id, name, trade, skill, temp_id, executing_officer_code, executing_officer_name FROM workmen
         WHERE contractor_id = ?
           AND COALESCE(execution_training_status, 'pending') = 'approved'
           AND COALESCE(execution_training_reviewed_by, 0) > 0
           AND training_status IN ('pending','training_pending','training_failed','fail','failed')
           AND NOT EXISTS (
               SELECT 1 FROM training_requests tr
               WHERE tr.workman_id = workmen.id
                 AND tr.status IN ('welfare_pending','pending','scheduled','contractor_confirmed','passed')
           )
         ORDER BY name",
        'i', [$c_id]) : [];

    $eo_pending_workers = $c_id ? db_fetch_all($conn,
        "SELECT id, name, trade, temp_id, executing_officer_code, executing_officer_name, COALESCE(execution_training_status, 'pending_eo') AS execution_training_status, training_approval_doc
         FROM workmen
         WHERE contractor_id = ?
           AND COALESCE(status, '') <> 'draft'
           AND COALESCE(execution_training_status, 'pending_eo') IN ('pending_eo','pending','rejected')
           AND training_status IN ('pending','training_pending','training_failed','fail','failed')
         ORDER BY created_at DESC",
        'i', [$c_id]) : [];

    $resultSelect = "NULL AS latest_result, NULL AS latest_total_score, NULL AS latest_valid_till, NULL AS latest_result_remarks";
    $resultJoin = "";
    if (
        contractorTrainingTableExists($conn, 'training_session_workers') &&
        contractorTrainingColumnExists($conn, 'training_session_workers', 'training_request_id')
    ) {
        $sessionResultExpr = contractorTrainingColumnExists($conn, 'training_session_workers', 'result') ? 'sr.result' : 'NULL';
        $sessionScoreExpr = contractorTrainingColumnExists($conn, 'training_session_workers', 'total_score') ? 'sr.total_score' : 'NULL';
        $sessionValidExpr = contractorTrainingColumnExists($conn, 'training_session_workers', 'valid_till') ? 'sr.valid_till' : 'NULL';
        $sessionRemarksExpr = contractorTrainingColumnExists($conn, 'training_session_workers', 'remarks') ? 'sr.remarks' : 'NULL';
        $resultSelect = "$sessionResultExpr AS latest_result, $sessionScoreExpr AS latest_total_score, $sessionValidExpr AS latest_valid_till, $sessionRemarksExpr AS latest_result_remarks";
        // Join on workman_id picking the LATEST session result for that worker
        // This ensures that even after a fail & re-request, the fail result still shows on the latest row
        $resultJoin = "
            LEFT JOIN (
                SELECT sw1.*
                FROM training_session_workers sw1
                INNER JOIN (
                    SELECT training_request_id, MAX(id) AS max_id
                    FROM training_session_workers
                    GROUP BY training_request_id
                ) sw2 ON sw2.max_id = sw1.id
            ) sr ON sr.training_request_id = tr.id
        ";
    } elseif (contractorTrainingTableExists($conn, 'training_results')) {
        $latestResultExpr = contractorTrainingColumnExists($conn, 'training_results', 'result') ? 'lr.result' : 'NULL';
        $latestScoreExpr = contractorTrainingColumnExists($conn, 'training_results', 'total_score') ? 'lr.total_score' : 'NULL';
        $latestValidExpr = contractorTrainingColumnExists($conn, 'training_results', 'valid_till') ? 'lr.valid_till' : 'NULL';
        $latestRemarksExpr = contractorTrainingColumnExists($conn, 'training_results', 'remarks') ? 'lr.remarks' : 'NULL';
        $resultSelect = "$latestResultExpr AS latest_result, $latestScoreExpr AS latest_total_score, $latestValidExpr AS latest_valid_till, $latestRemarksExpr AS latest_result_remarks";
        $resultJoin = "
            LEFT JOIN (
                SELECT tr1.*
                FROM training_results tr1
                INNER JOIN (
                    SELECT workman_id, MAX(id) AS max_id
                    FROM training_results
                    GROUP BY workman_id
                ) tr2 ON tr2.max_id = tr1.id
            ) lr ON lr.workman_id = tr.workman_id
        ";
    }

    $workerTrainingValidExpr = contractorTrainingColumnExists($conn, 'workmen', 'training_valid_till') ? 'w.training_valid_till' : 'NULL';

    // All training requests for this contractor with full details
    $my_requests = $c_id ? db_fetch_all($conn,
        "SELECT tr.*, w.name as worker_name, w.trade as worker_trade, w.temp_id AS worker_temp_id, w.work_order_source,
                $workerTrainingValidExpr AS training_valid_till,
                COALESCE(w.execution_training_status, 'pending') AS execution_training_status,
                COALESCE(w.execution_training_reviewed_by, 0) AS execution_training_reviewed_by,
                pr.status AS payment_status,
                pr.payment_ref,
                pr.payment_token,
                $resultSelect
         FROM training_requests tr
         JOIN workmen w ON tr.workman_id = w.id
         LEFT JOIN (
             SELECT pw1.workman_id, MAX(pw1.payment_request_id) AS max_pay_id
             FROM training_payment_request_workers pw1
             GROUP BY pw1.workman_id
         ) pw ON pw.workman_id = tr.workman_id
         LEFT JOIN training_payment_requests pr ON pr.id = pw.max_pay_id
         $resultJoin
         WHERE tr.contractor_id = ?
           AND tr.id = (
               SELECT tr2.id
               FROM training_requests tr2
               WHERE tr2.workman_id = tr.workman_id
                 AND tr2.contractor_id = tr.contractor_id
               ORDER BY tr2.updated_at DESC, tr2.id DESC
               LIMIT 1
           )
         ORDER BY tr.created_at DESC",
        'i', [$c_id]) : [];

    // Build per-workman attempt history (all past requests, ordered oldest→newest)
    $attemptHistoryMap = [];
    if ($c_id) {
        $allRequests = db_fetch_all($conn,
            "SELECT tr.workman_id, tr.id, tr.batch_number, tr.scheduled_date, tr.status,
                    tr.scheduled_session_id
             FROM training_requests tr
             WHERE tr.contractor_id = ?
             ORDER BY tr.id ASC",
            'i', [$c_id]);
        foreach ($allRequests as $ar) {
            $wid = (int)$ar['workman_id'];
            $attemptHistoryMap[$wid][] = $ar;
        }
    }
    ?>

    <div class="content-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:20px;">
      <div>
        <h2 class="page-title"><i class="fas fa-graduation-cap" style="color:#8b5cf6;margin-right:10px;"></i> Safety Training Request</h2>
      </div>
      <div style="display:flex; gap:10px; align-items:center;">
        <a class="btn btn-outline" href="training_payments.php">
          <i class="fas fa-history"></i> Safety Payments History
        </a>
      </div>
    </div>

    <?php if (!$c_id): ?>
    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i><div>Complete <a href="annexure-2a.php">Contractor Registration</a> first.</div></div>
    <?php return; endif; ?>

    <!-- Confirm Modal -->
    <div id="confirmModal" class="modal-backdrop hidden">
      <div class="modal-content glass" style="max-width:500px; padding:0;">
        <div class="modal-header" style="padding:20px; border-bottom:1px solid rgba(255,255,255,0.1);">
          <h3><i class="fas fa-calendar-check" style="color:#8b5cf6;"></i> Confirm Training Schedule</h3>
          <button class="btn-close" onclick="closeConfirmModal()">&times;</button>
        </div>
        <div class="modal-body" style="padding:24px;">
          <div id="scheduleInfoBox" style="background:rgba(139,92,246,0.1); border:1px solid rgba(139,92,246,0.3); border-radius:12px; padding:16px; margin-bottom:20px;">
            <!-- populated via JS -->
          </div>
          <div class="form-group">
            <label class="form-label">Your Remarks (optional)</label>
            <textarea class="form-control" id="contractorRemarks" rows="3" placeholder="Any specific requirements, concerns or acknowledgement..."></textarea>
          </div>
          <div style="margin-top:20px; display:flex; gap:12px; justify-content:flex-end;">
            <button class="btn btn-outline" onclick="closeConfirmModal()">Cancel</button>
            <button class="btn btn-primary" id="confirmTrainingBtn" onclick="submitConfirmation()">
              <i class="fas fa-check-circle"></i> Confirm Attendance
            </button>
          </div>
          <input type="hidden" id="confirmRequestId" value="">
        </div>
      </div>
    </div>

    <!-- Re-request Modal -->
    <div id="reRequestModal" class="modal-backdrop hidden">
      <div class="modal-content glass" style="max-width:520px; padding:0;">
        <div class="modal-header" style="padding:20px; border-bottom:1px solid rgba(255,255,255,0.1);">
          <h3><i class="fas fa-redo" style="color:#f59e0b;"></i> Re-Training Request</h3>
          <button class="btn-close" onclick="closeReRequestModal()">&times;</button>
        </div>
        <div class="modal-body" style="padding:24px;">
          <div id="reRequestWorkerInfo" style="background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.3); border-radius:12px; padding:14px; margin-bottom:18px; font-size:13px;"></div>
          <div class="form-group">
            <label class="form-label required">Training Type</label>
            <select class="form-control" id="reReqTrainingType">
              <?php foreach ($trainingTypes as $tt): ?>
                <option value="<?= htmlspecialchars($tt['type_name'], ENT_QUOTES) ?>"><?= htmlspecialchars($tt['type_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
            <div class="form-group">
              <label class="form-label required">Preferred Date</label>
              <input type="date" class="form-control" id="reReqDate" min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
            </div>
            <div class="form-group">
              <label class="form-label required">Preferred Shift</label>
              <div style="display:flex; gap:10px; margin-top:6px;">
                <label class="shift-option" style="flex:1;">
                  <input type="radio" name="reReqShift" value="morning" checked>
                  <div class="shift-card">
                    <i class="fas fa-sun" style="color:#f59e0b;"></i>
                    <strong>Morning</strong>
                    <small>FN</small>
                  </div>
                </label>
                <label class="shift-option" style="flex:1;">
                  <input type="radio" name="reReqShift" value="evening">
                  <div class="shift-card">
                    <i class="fas fa-moon" style="color:#818cf8;"></i>
                    <strong>Evening</strong>
                    <small>AN</small>
                  </div>
                </label>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Remarks (optional)</label>
            <textarea class="form-control" id="reReqRemarks" rows="2" placeholder="Any specific notes for re-training..."></textarea>
          </div>
          <div style="margin-top:20px; display:flex; gap:12px; justify-content:flex-end;">
            <button class="btn btn-outline" onclick="closeReRequestModal()">Cancel</button>
            <button class="btn btn-warning" id="submitReReqBtn" onclick="submitReRequest()">
              <i class="fas fa-redo"></i> Submit Re-Training Request
            </button>
          </div>
          <input type="hidden" id="reReqWorkmanId" value="">
          <input type="hidden" id="reReqWorkerName" value="">
        </div>
      </div>
    </div>

    <div>
      <!-- Training Requests History -->
      <div class="card glass">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-history"></i> Training Request Status</div>
          <span class="badge badge-gray"><?= count($my_requests) ?> Records</span>
        </div>
        <div class="card-body" style="padding:0;">
          <?php if (empty($my_requests)): ?>
          <div style="padding:40px; text-align:center; color:var(--text-muted);">
            <i class="fas fa-clipboard-list" style="font-size:40px;opacity:.2;display:block;margin-bottom:12px;"></i>
            <p>No training requests submitted yet.</p>
          </div>
          <?php else: ?>
          <table class="data-table">
            <thead>
              <tr>
                <th>S.No.</th>
                <th>Worker</th>
                <th>Training Type</th>
                <th>Payment</th>
                <th>Preferred</th>
                <th>Scheduled By Safety</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
            <?php 
              $sno = 1;
              foreach ($my_requests as $r): 
            ?>
            <?php
              $st = $r['status'] ?? 'pending';
              $executionApproved = strtolower((string)($r['execution_training_status'] ?? 'pending')) === 'approved' && (int)($r['execution_training_reviewed_by'] ?? 0) > 0;
              $latestResult = strtolower((string)($r['latest_result'] ?? ''));
              $viewStatus = $st;
              // Always respect the actual result from training_session_workers first
              if (in_array($latestResult, ['pass', 'passed'], true)) {
                  $viewStatus = 'passed';
              } elseif (in_array($latestResult, ['fail', 'failed'], true)) {
                  $viewStatus = 'failed';
              } elseif (in_array($st, ['passed', 'failed', 'completed', 'absent'], true)) {
                  $viewStatus = $st;
              }
              if ($executionApproved && in_array($viewStatus, ['pending', 'welfare_pending'], true) && empty($r['scheduled_date'])) {
                  $viewStatus = 'welfare_pending';
              }
              // 'failed' should ALWAYS show as failed — never hide behind exec_pending
              $displayStatus = (!$executionApproved && in_array($viewStatus, ['pending','correction_required'], true)) ? 'exec_pending' : $viewStatus;
              $validTill = $r['latest_valid_till'] ?: ($r['training_valid_till'] ?? '');
              $sc = [
                'exec_pending'          => 'badge-gray',
                'welfare_pending'       => 'badge-warning',
                'welfare_rejected'      => 'badge-danger',
                'pending'              => 'badge-warning',
                'pending_safety'       => 'badge-warning',
                'pending_eo'           => 'badge-gray',
                'scheduled'            => 'badge-info',
                'contractor_confirmed' => 'badge-primary',
                'passed'               => 'badge-success',
                'completed'            => 'badge-success',
                'failed'               => 'badge-danger',
                'absent'               => 'badge-danger',
                'rejected'             => 'badge-danger',
              ];
            ?>
            <tr style="<?= $st === 'scheduled' ? 'background:rgba(99,102,241,0.06);' : '' ?>">
              <td><?= $sno++ ?></td>
              <td>
                <div style="font-weight:600;"><?= htmlspecialchars($r['worker_name'] ?? '—') ?></div>
                <div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($r['worker_trade'] ?? '') ?><?= !empty($r['worker_temp_id']) ? ' | ' . htmlspecialchars($r['worker_temp_id']) : '' ?></div>
                <div style="font-size:10px;color:var(--text-muted);">Req #<?= (int)$r['id'] ?></div>
              </td>
              <td><?= htmlspecialchars($r['training_type'] ?? '—') ?></td>
              <td>
                <?php 
                  $wos = strtoupper(trim((string)($r['work_order_source'] ?? '')));
                  if ($wos !== 'PWO'): 
                ?>
                  <span class="badge badge-gray" style="white-space:nowrap; background:#f1f5f9; color:#475569; padding:4px 8px; border-radius:999px; font-weight:700;">N/A</span>
                <?php elseif ($r['payment_status'] === 'paid'): ?>
                  <span class="badge badge-success"><i class="fas fa-check-circle"></i> Paid</span>
                  <div style="font-size:10px;color:var(--text-muted);margin-top:2px;">Ref: <?= htmlspecialchars($r['payment_ref']) ?></div>
                <?php elseif (in_array($r['payment_status'], ['pending', 'link_sent', 'gateway_created', 'submitted'], true)): ?>
                  <span class="badge badge-warning" style="background:#fef3c7; color:#92400e; padding:4px 8px; border-radius:999px; font-weight:700;"><i class="fas fa-clock"></i> UNPAID</span>
                  <div style="margin-top:4px;">
                    <a class="btn btn-sm btn-primary" style="padding: 2px 6px; font-size: 10px; line-height: 1.2;" href="payment.php?token=<?= urlencode($r['payment_token']) ?>">
                      Pay Fee
                    </a>
                  </div>
                <?php else: ?>
                  <?php if (($r['source'] ?? '') === 'contractor_re_enroll'): ?>
                    <span class="badge badge-gray" style="white-space:nowrap; background:#f1f5f9; color:#475569; padding:4px 8px; border-radius:999px; font-weight:700;">N/A (Retraining)</span>
                  <?php else: ?>
                    <span class="badge badge-gray" style="white-space:nowrap; background:#f1f5f9; color:#475569; padding:4px 8px; border-radius:999px; font-weight:700;">NOT GENERATED</span>
                  <?php endif; ?>
                <?php endif; ?>
              </td>
              <td>
                <?= $r['preferred_date'] ? date('d M Y', strtotime($r['preferred_date'])) : '—' ?>
                <?php if ($r['preferred_shift']): ?>
                <span class="badge badge-gray" style="font-size:10px;"><?= ucfirst($r['preferred_shift']) ?></span>
                <?php endif; ?>
              </td>
              <?php
                // Determine attempt number for this workman
                $wAttempts = $attemptHistoryMap[(int)$r['workman_id']] ?? [];
                $attemptNumber = 0;
                foreach ($wAttempts as $atIdx => $at) {
                    if ((int)$at['id'] === (int)$r['id']) {
                        $attemptNumber = $atIdx + 1;
                        break;
                    }
                }
                $totalAttempts = count($wAttempts);
                // Previous batches (all except current)
                $prevBatches = array_filter($wAttempts, function($at) use ($r) {
                    return (int)$at['id'] !== (int)$r['id'] && !empty($at['batch_number']);
                });
              ?>
              <td>
                <?php if ($r['scheduled_date']): ?>
                  <?php if ($totalAttempts > 1): ?>
                  <div style="font-size:10px; font-weight:700; color:#f59e0b; margin-bottom:3px;">
                    <i class="fas fa-layer-group"></i>
                    Attempt <?= $attemptNumber ?> of <?= $totalAttempts ?>
                  </div>
                  <?php endif; ?>
                  <div style="font-weight:700;color:var(--primary);"><?= htmlspecialchars($r['batch_number'] ?: 'Batch Pending') ?></div>
                  <div style="font-size:11px;margin-top:2px;"><strong><?= date('d M Y', strtotime($r['scheduled_date'])) ?></strong><?= !empty($r['scheduled_time']) ? ' | ' . htmlspecialchars($r['scheduled_time']) : '' ?></div>
                  <div style="font-size:11px;margin-top:2px;">
                    <i class="fas <?= $r['scheduled_shift'] === 'morning' ? 'fa-sun' : 'fa-moon' ?>" style="color:<?= $r['scheduled_shift'] === 'morning' ? '#f59e0b' : '#818cf8' ?>;"></i>
                    <?= ucfirst($r['scheduled_shift']) ?> • <?= htmlspecialchars($r['scheduled_venue']) ?>
                  </div>
                  <?php if (!empty($r['instructor'])): ?>
                  <div style="font-size:11px;color:var(--text-muted);margin-top:2px;"><i class="fas fa-user-tie"></i> <?= htmlspecialchars($r['instructor']) ?></div>
                  <?php endif; ?>
                  <?php if ($r['safety_remarks']): ?>
                  <div style="font-size:11px; color:var(--text-muted); margin-top:3px;"><i class="fas fa-comment-alt"></i> <?= htmlspecialchars($r['safety_remarks']) ?></div>
                  <?php endif; ?>
                  <?php if (!empty($prevBatches)): ?>
                  <div style="margin-top:5px; border-top:1px dashed rgba(255,255,255,0.15); padding-top:4px;">
                    <div style="font-size:10px; color:var(--text-muted); font-weight:600;">Previous attempts:</div>
                    <?php foreach (array_values($prevBatches) as $pbIdx => $pb): ?>
                    <div style="font-size:10px; color:var(--text-muted);">
                      Attempt <?= $pbIdx + 1 ?>:
                      <?php if (!empty($pb['batch_number'])): ?>
                        <span style="color:var(--primary);font-weight:600;"><?= htmlspecialchars($pb['batch_number']) ?></span>
                      <?php else: ?>
                        <em>No batch</em>
                      <?php endif; ?>
                      <span style="color:<?= in_array(strtolower($pb['status']), ['passed','completed']) ? '#10b981' : '#ef4444' ?>;">• <?= ucfirst($pb['status']) ?></span>
                    </div>
                    <?php endforeach; ?>
                  </div>
                  <?php endif; ?>
                <?php else: ?>
                  <?php if ($totalAttempts > 1): ?>
                  <div style="font-size:10px; font-weight:700; color:#f59e0b; margin-bottom:3px;">
                    <i class="fas fa-layer-group"></i> Attempt <?= $attemptNumber ?> of <?= $totalAttempts ?>
                  </div>
                  <?php endif; ?>
                  <span style="color:var(--text-muted); font-size:12px;">Awaiting schedule…</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge <?= $sc[$displayStatus] ?? 'badge-gray' ?>">
                  <?php
                    if ($displayStatus === 'exec_pending') {
                        echo 'EXEC APPROVAL PENDING';
                    } elseif ($viewStatus === 'welfare_pending') {
                        echo 'READY FOR SAFETY SCHEDULING';
                    } elseif ($viewStatus === 'welfare_rejected') {
                        echo 'WELFARE REJECTED';
                    } elseif ($viewStatus === 'pending_safety' && ($r['source'] ?? '') === 'contractor_re_enroll') {
                        echo 'RE-TRAINING BATCH APPROVAL PENDING';
                    } elseif ($viewStatus === 'pending_safety') {
                        echo 'SAFETY ENROLLMENT PENDING';
                    } else {
                        echo strtoupper(str_replace('_', ' ', $viewStatus));
                    }
                  ?>
                </span>
                <?php if (in_array($viewStatus, ['passed','completed','failed'], true) || in_array($latestResult, ['pass','passed','fail','failed'], true)): ?>
                  <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">
                    <?php if ($latestResult !== ''): ?>
                      Result: <strong><?= htmlspecialchars(strtoupper(str_replace('ed', '', $latestResult))) ?></strong>
                    <?php endif; ?>
                    <?php if ($r['latest_total_score'] !== null && $r['latest_total_score'] !== ''): ?>
                      | Marks: <?= (int)$r['latest_total_score'] ?>
                    <?php endif; ?>
                    <?php if (!empty($validTill) && in_array($viewStatus, ['passed','completed'], true)): ?>
                      | Valid till <?= date('d M Y', strtotime($validTill)) ?>
                    <?php endif; ?>
                  </div>
                  <?php if (!empty($r['conduct_remarks']) || !empty($r['latest_result_remarks'])): ?>
                    <div style="font-size:10px;color:var(--text-muted);margin-top:2px;">
                      <i class="fas fa-comment-alt"></i> <?= htmlspecialchars($r['conduct_remarks'] ?: $r['latest_result_remarks']) ?>
                    </div>
                  <?php endif; ?>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($st === 'scheduled' || $st === 'contractor_confirmed'): ?>
                <span style="font-size:11px; color:var(--primary);"><i class="fas fa-calendar-check"></i> Scheduled</span>
                <?php elseif ($viewStatus === 'completed' || $viewStatus === 'passed'): ?>
                <span style="font-size:11px; color:var(--success);"><i class="fas fa-trophy"></i> Passed</span>
                <?php elseif ($viewStatus === 'welfare_pending'): ?>
                <div style="display:flex; flex-direction:column; gap:4px;">
                  <span style="font-size:11px; color:var(--warning);"><i class="fas fa-calendar-plus"></i> Awaiting safety schedule</span>
                  <button class="btn btn-sm btn-danger" style="padding: 2px 5px; font-size:10px;" onclick="cancelTrainingRequest(<?= $r['id'] ?>)">Cancel Request</button>
                </div>
                <?php elseif ($st === 'welfare_rejected'): ?>
                <a class="btn btn-sm btn-outline" href="enrolment-4a.php?type=workmen" title="Open worker enrolment for correction">
                  <i class="fas fa-edit"></i> Correct &amp; re-submit
                </a>
                <?php elseif ($displayStatus === 'exec_pending'): ?>
                <div style="display:flex; flex-direction:column; gap:4px;">
                  <span style="font-size:11px; color:var(--text-muted);"><i class="fas fa-user-clock"></i> EO approval pending</span>
                  <button class="btn btn-sm btn-danger" style="padding: 2px 5px; font-size:10px;" onclick="cancelTrainingRequest(<?= $r['id'] ?>)">Cancel Request</button>
                </div>
                <?php elseif ($st === 'pending'): ?>
                <div style="display:flex; flex-direction:column; gap:4px;">
                  <span style="font-size:11px; color:var(--warning);"><i class="fas fa-hourglass-half"></i> Awaiting action</span>
                  <button class="btn btn-sm btn-danger" style="padding: 2px 5px; font-size:10px;" onclick="cancelTrainingRequest(<?= $r['id'] ?>)">Cancel Request</button>
                </div>
                <?php elseif ($viewStatus === 'failed' || $viewStatus === 'rejected' || $viewStatus === 'correction_required'): ?>
                <a class="btn btn-sm btn-warning" href="enrolment-4a.php?type=workmen&re_enroll_worker=<?= (int)$r['workman_id'] ?>" title="Submit re-training request">
                  <i class="fas fa-redo"></i> Re-request
                </a>
                <?php else: ?>
                <span style="font-size:11px; color:var(--text-muted);">—</span>
                <?php endif; ?>
              </td>
            </tr>

            <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php if (!empty($eo_pending_workers)): ?>
    <div class="card glass" style="margin-top:20px;">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-user-check"></i> Executing Officer Approval Status</div>
        <span class="badge badge-warning"><?= count($eo_pending_workers) ?> Pending/Rejected</span>
      </div>
      <div class="card-body" style="padding:0;">
        <table class="data-table">
          <thead>
            <tr>
              <th>S.No.</th>
              <th>Worker</th>
              <th>Executing Officer</th>
              <th>Attachment</th>
              <th>Status</th>
              <th>Next Step</th>
            </tr>
          </thead>
          <tbody>
            <?php 
              $eoSno = 1;
              foreach ($eo_pending_workers as $w):
              $eoStatus = strtolower((string)($w['execution_training_status'] ?? 'pending_eo'));
              $hasDoc = trim((string)($w['training_approval_doc'] ?? '')) !== '';
            ?>
            <tr>
              <td><?= $eoSno++ ?></td>
              <td>
                <strong><?= htmlspecialchars($w['name'] ?? '') ?></strong><br>
                <small><?= htmlspecialchars($w['trade'] ?? '') ?> <?= !empty($w['temp_id']) ? ' | ' . htmlspecialchars($w['temp_id']) : '' ?></small>
              </td>
              <td>
                <code><?= htmlspecialchars($w['executing_officer_code'] ?? '-') ?></code><br>
                <small><?= htmlspecialchars($w['executing_officer_name'] ?? '') ?></small>
              </td>
              <td>
                <?php if ($hasDoc): ?>
                  <a class="btn btn-sm btn-outline" target="_blank" href="../../uploads/workers/<?= htmlspecialchars($w['training_approval_doc']) ?>">
                    <i class="fas fa-file-pdf"></i> View
                  </a>
                <?php else: ?>
                  <span class="badge badge-gray">No upload</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge <?= $eoStatus === 'rejected' ? 'badge-danger' : 'badge-warning' ?>">
                  <?= $eoStatus === 'rejected' ? 'EO REJECTED' : 'EO APPROVAL PENDING' ?>
                </span>
              </td>
              <td style="font-size:12px;color:var(--text-muted);">
                <?= $eoStatus === 'rejected'
                    ? 'Update the worker details and submit the corrected document or E-Code for review.'
                    : ($hasDoc ? 'The request is available for review and scheduling by the responsible team.' : 'The request is pending online review by the Executing Officer.') ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- Info Box -->
    <div class="alert alert-info" style="margin-top:20px;">
      <i class="fas fa-info-circle"></i>
      <div>
        <strong>Training Flow:</strong>
        Submit request (choose Morning/Evening) →
        Executing Officer approval/document validation →
        Safety Dept. schedules with date, shift &amp; venue →
        Safety conducts training →
        Result recorded (Pass/Fail) →
        Pass required for Gate Pass.
      </div>
    </div>

    <style>
    .form-group { margin-bottom:14px; }
    .form-label { display:block; font-size:13px; font-weight:600; margin-bottom:5px; }
    .form-label.required::after { content:' *'; color:#ef4444; }
    .form-control { width:100%; padding:9px 13px; border-radius:8px; border:1.5px solid var(--border-color); background:var(--input-bg, rgba(255,255,255,.04)); color:var(--text-primary); font-size:13px; transition:.2s; box-sizing:border-box; }
    .form-control:focus { outline:none; border-color:#8b5cf6; box-shadow:0 0 0 3px rgba(139,92,246,.12); }
    select[multiple] { padding:4px; }
    select[multiple] option { padding:6px 8px; border-radius:4px; }
    select[multiple] option:checked { background:rgba(139,92,246,.15); color:var(--text-primary); }
    .empty-state { text-align:center; color:var(--text-muted); }

    /* Shift Selector */
    .shift-option { cursor:pointer; display:block; }
    .shift-option input[type="radio"] { display:none; }
    .shift-card { display:flex; flex-direction:column; align-items:center; gap:4px; padding:14px 8px; border-radius:12px; border:2px solid var(--border-color); transition:.2s; text-align:center; }
    .shift-card i { font-size:22px; }
    .shift-card strong { font-size:13px; }
    .shift-card small { font-size:11px; color:var(--text-muted); }
    .shift-option input:checked + .shift-card { border-color:#8b5cf6; background:rgba(139,92,246,.12); }
    .shift-option:hover .shift-card { border-color:rgba(139,92,246,.5); }

    /* Modal */
    .modal-backdrop { position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.7); backdrop-filter:blur(5px); display:flex; align-items:center; justify-content:center; z-index:1100; }
    .modal-content { width:90%; border-radius:16px; border:1px solid rgba(255,255,255,0.15); box-shadow:0 25px 50px -12px rgba(0,0,0,0.5); }
    .hidden { display:none; }
    .modal-header { display:flex; justify-content:space-between; align-items:center; }
    .btn-close { background:none; border:none; font-size:28px; color:var(--text-muted); cursor:pointer; }

    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.6} }

    .toast-msg { position:fixed; bottom:30px; right:30px; z-index:9999; padding:14px 20px; border-radius:12px; display:flex; align-items:center; gap:10px; font-size:14px; font-weight:600; animation:slideUp .3s ease; box-shadow:0 8px 30px rgba(0,0,0,.2); }
    .toast-success { background:#10b981; color:white; }
    .toast-error   { background:#ef4444; color:white; }
    @keyframes slideUp { from{transform:translateY(30px);opacity:0;} to{transform:translateY(0);opacity:1;} }
    </style>

    <script>
    const contractorTrainingTypes = <?= json_encode(array_values(array_map(function($row) { return $row['type_name']; }, $trainingTypes)), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?? '[]' ?>;

    async function cancelTrainingRequest(requestId) {
        if (typeof Swal === 'undefined') {
            if (!confirm('Are you sure you want to cancel this training request? The workman will be notified via email.')) return;
            let reason = prompt('Please provide a reason for cancellation:');
            if (!reason) {
                alert('Reason is required to cancel.');
                return;
            }
            executeCancelRequest(requestId, reason);
            return;
        }

        const { value: reason, isConfirmed } = await Swal.fire({
            title: 'Cancel Training Request?',
            text: 'Provide a reason for cancellation. This will notify the workman via email.',
            input: 'text',
            inputPlaceholder: 'Enter cancellation reason...',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Cancel',
            confirmButtonColor: '#dc2626',
            inputValidator: (value) => {
                if (!value) return 'Reason is required to cancel!';
            }
        });

        if (isConfirmed) {
            executeCancelRequest(requestId, reason);
        }
    }

    async function executeCancelRequest(requestId, reason) {
        try {
            const fd = new FormData();
            fd.append('request_id', requestId);
            fd.append('reason', reason);
            
            let token = window.CLMS_CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            if (token) {
                fd.append('csrf_token', token);
            }

            const res = await fetch('../../api/contractor/cancel_training_request.php', { 
                method: 'POST', 
                body: fd,
                headers: { 'X-CSRF-TOKEN': token }
            });
            const textResponse = await res.text();
            
            try {
                const data = JSON.parse(textResponse);
                if (data.success) {
                    if (typeof Swal !== 'undefined') {
                        await Swal.fire('Cancelled!', data.message, 'success');
                    } else {
                        showToast(data.message, 'success');
                    }
                    setTimeout(() => location.reload(), typeof Swal !== 'undefined' ? 0 : 1500);
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', data.error || 'Failed to cancel', 'error');
                    } else {
                        showToast('Error: ' + (data.error || 'Failed to cancel'), 'error');
                    }
                }
            } catch (e) {
                console.error("Invalid JSON. Server returned:", textResponse);
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', 'Server returned invalid data. Check console.', 'error');
                } else {
                    showToast('Server returned invalid data. Check console.', 'error');
                }
            }
        } catch (e) {
            console.error("Fetch failed:", e);
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'Network error. Please try again.', 'error');
            } else {
                showToast('Network error. Please try again.', 'error');
            }
        }
    }

    function showToast(msg, type='success') {
      let t = document.createElement('div');
      t.className = 'toast-msg toast-' + type;
      t.innerHTML = `<i class="fas fa-${type==='success'?'check-circle':'exclamation-circle'}"></i> ${msg}`;
      document.body.appendChild(t);
      setTimeout(() => t.remove(), 3500);
    }

    // Submit new training request
    document.getElementById('trainingForm')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = document.getElementById('submitTrainingBtn');
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

      const form = e.target;
      const selectedWorkers = Array.from(document.getElementById('workerSelect').selectedOptions).map(o => o.value);
      if (selectedWorkers.length === 0) {
        showToast('Please select at least one worker.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Training Request';
        return;
      }

      const data = {
        workman_ids:     selectedWorkers,
        training_type:   form.querySelector('[name="training_type"]').value,
        preferred_shift: form.querySelector('[name="preferred_shift"]:checked')?.value || 'morning',
        preferred_date:  form.querySelector('[name="preferred_date"]').value,
        remarks:         form.querySelector('[name="remarks"]').value
      };

      try {
        const res = await fetch('../../api/submit_training_request.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify(data)
        });
        const result = await res.json();
        if (result.success) {
          showToast('Training request submitted successfully!', 'success');
          setTimeout(() => location.reload(), 1800);
        } else {
          showToast('Error: ' + (result.message || 'Submission failed'), 'error');
        }
      } catch(err) {
        showToast('Network error — please try again.', 'error');
      }

      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Training Request';
    });

    function openReRequestModal(workmanId, workerName, trainingType) {
      document.getElementById('reReqWorkmanId').value = workmanId;
      document.getElementById('reReqWorkerName').value = workerName;
      // Set default training type
      const ttSelect = document.getElementById('reReqTrainingType');
      if (trainingType) {
        for (let i = 0; i < ttSelect.options.length; i++) {
          if (ttSelect.options[i].value === trainingType) {
            ttSelect.selectedIndex = i;
            break;
          }
        }
      }
      // Reset fields
      document.getElementById('reReqDate').value = '';
      document.getElementById('reReqRemarks').value = '';
      document.querySelector('input[name="reReqShift"][value="morning"]').checked = true;
      // Show worker info
      document.getElementById('reRequestWorkerInfo').innerHTML =
        `<i class="fas fa-user-hard-hat" style="color:#f59e0b;"></i>
         <strong style="margin-left:6px;">${escapeHtml(workerName)}</strong>
         <span style="margin-left:8px; font-size:11px; color:var(--text-muted);">— Re-training after failed attempt</span>`;
      document.getElementById('reRequestModal').classList.remove('hidden');
    }

    function closeReRequestModal() {
      document.getElementById('reRequestModal').classList.add('hidden');
    }

    async function submitReRequest() {
      const workmanId = parseInt(document.getElementById('reReqWorkmanId').value);
      const trainingType = document.getElementById('reReqTrainingType').value;
      const preferredDate = document.getElementById('reReqDate').value;
      const preferredShift = document.querySelector('input[name="reReqShift"]:checked')?.value || 'morning';
      const remarks = document.getElementById('reReqRemarks').value.trim() || 'Re-training requested after failed training.';

      if (!workmanId) { showToast('Invalid worker.', 'error'); return; }
      if (!preferredDate) { showToast('Please select a preferred date.', 'error'); return; }

      const btn = document.getElementById('submitReReqBtn');
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

      try {
        const res = await fetch('../../api/submit_training_request.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            workman_ids:     [workmanId],
            training_type:   trainingType,
            preferred_shift: preferredShift,
            preferred_date:  preferredDate,
            remarks:         remarks,
            source:          'retraining_from_status_page'
          })
        });
        const result = await res.json();
        if (result.success) {
          showToast(result.message || 'Re-training request submitted successfully!', 'success');
          closeReRequestModal();
          setTimeout(() => location.reload(), 1600);
        } else {
          showToast('Error: ' + (result.message || result.error || 'Submission failed'), 'error');
          btn.disabled = false;
          btn.innerHTML = '<i class="fas fa-redo"></i> Submit Re-Training Request';
        }
      } catch (err) {
        showToast('Network error. Please try again.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-redo"></i> Submit Re-Training Request';
      }
    }

    // Keep legacy function alias (for any other callers)
    async function reRequestTraining(workmanId) {
      openReRequestModal(workmanId, '', contractorTrainingTypes[0] || 'Safety Induction');
    }

    // Confirm training modal
    function escapeHtml(value) {
      return String(value ?? '').replace(/[&<>"']/g, ch => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
      }[ch]));
    }

    function openConfirmModal(data) {
      document.getElementById('confirmRequestId').value = data.id;
      document.getElementById('contractorRemarks').value = '';
      const shiftLabel = data.shift === 'morning' ? '☀️ Morning (8 AM – 12 PM)' : '🌙 Evening (2 PM – 6 PM)';
      document.getElementById('scheduleInfoBox').innerHTML = `
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <div><div style="font-size:11px; font-weight:700; opacity:.6; text-transform:uppercase;">Worker</div><div style="font-weight:600;">${escapeHtml(data.worker)}</div><small>${escapeHtml([data.trade, data.temp_id].filter(Boolean).join(' | '))}</small></div>
          <div><div style="font-size:11px; font-weight:700; opacity:.6; text-transform:uppercase;">Training Type</div><div style="font-weight:600;">${escapeHtml(data.training_type || '—')}</div></div>
          <div><div style="font-size:11px; font-weight:700; opacity:.6; text-transform:uppercase;">Batch Number</div><div style="font-weight:600;">${escapeHtml(data.batch_number || '—')}</div></div>
          <div><div style="font-size:11px; font-weight:700; opacity:.6; text-transform:uppercase;">Date</div><div style="font-weight:600;">${escapeHtml(data.date)}</div></div>
          <div><div style="font-size:11px; font-weight:700; opacity:.6; text-transform:uppercase;">Shift</div><div style="font-weight:600;">${shiftLabel}</div></div>
          <div><div style="font-size:11px; font-weight:700; opacity:.6; text-transform:uppercase;">Venue</div><div style="font-weight:600;">${escapeHtml(data.venue)}</div></div>
          ${data.time ? `<div><div style="font-size:11px; font-weight:700; opacity:.6; text-transform:uppercase;">Time</div><div style="font-weight:600;">${escapeHtml(data.time)}</div></div>` : ''}
          ${data.instructor ? `<div><div style="font-size:11px; font-weight:700; opacity:.6; text-transform:uppercase;">Instructor</div><div style="font-weight:600;">${escapeHtml(data.instructor)}</div></div>` : ''}
          ${data.remarks ? `<div style="grid-column:span 2;"><div style="font-size:11px; font-weight:700; opacity:.6; text-transform:uppercase;">Safety Remarks</div><div style="font-weight:500; color:var(--text-muted);">${escapeHtml(data.remarks)}</div></div>` : ''}
        </div>
      `;
      document.getElementById('confirmModal').classList.remove('hidden');
    }

    function closeConfirmModal() {
      document.getElementById('confirmModal').classList.add('hidden');
    }

    async function submitConfirmation() {
      const btn = document.getElementById('confirmTrainingBtn');
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Confirming...';

      const reqId   = document.getElementById('confirmRequestId').value;
      const remarks = document.getElementById('contractorRemarks').value;

      try {
        const res = await fetch('../../api/contractor/confirm_training.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ request_id: parseInt(reqId), contractor_remarks: remarks })
        });
        const result = await res.json();
        if (result.success) {
          showToast('Training confirmed! Safety team notified.', 'success');
          closeConfirmModal();
          setTimeout(() => location.reload(), 1800);
        } else {
          showToast('Error: ' + (result.error || 'Failed to confirm'), 'error');
          btn.disabled = false;
          btn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Attendance';
        }
      } catch (err) {
        showToast('Network error. Please try again.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Attendance';
      }
    }
    </script>
    <?php
}

renderLayout("Training Request", 'renderContent', $role, $name);
?>
