<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';
require_once __DIR__ . '/../../include/safety_training_control.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Safety Officer';
clms_safety_ensure_control_schema($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $batchId = (int)($_POST['batch_id'] ?? 0);
        $forceRequestId = (int)($_POST['force_request_id'] ?? 0);
        if (($_POST['batch_action'] ?? '') === 'reschedule') {
            $result = clms_safety_reschedule_batch($conn, $batchId, $_POST, (int)($_SESSION['user_id'] ?? 0));
            $_SESSION['success'] = 'Batch ' . $result['batch_number'] . ' rescheduled to ' . date('d M Y', strtotime($result['training_date'])) . ' (' . $result['session_name'] . ').';
            header('Location: training_schedule.php?batch_id=' . $batchId);
            exit;
        }
        $selected = $_POST['selected_requests'] ?? array();
        $mode = ($_POST['schedule_mode'] ?? 'schedule') === 'draft' ? 'draft' : 'schedule';
        if ($mode === 'draft') {
            $result = clms_safety_save_batch_selection($conn, $batchId, $selected, (int)($_SESSION['user_id'] ?? 0), $forceRequestId);
            $_SESSION['success'] = 'Draft saved for batch ' . $result['batch_number'] . '. Selected ' . $result['selected'] . ' worker(s).';
        } else {
            $result = clms_safety_schedule_batch($conn, $batchId, $selected, (int)($_SESSION['user_id'] ?? 0), $forceRequestId);
            $_SESSION['success'] = 'Batch ' . $result['batch_number'] . ' scheduled. Token generated for ' . $result['scheduled'] . ' worker(s).';
        }
        $returnUrl = 'training_schedule.php?batch_id=' . $batchId;
        if (!empty($_POST['force_request_id'])) $returnUrl .= '&request_id=' . (int)$_POST['force_request_id'];
        header('Location: ' . $returnUrl);
        exit;
    } catch (Throwable $e) {
        $_SESSION['error'] = $e->getMessage();
        $returnUrl = 'training_schedule.php' . (!empty($_POST['batch_id']) ? '?batch_id=' . (int)$_POST['batch_id'] : '');
        if (!empty($_POST['force_request_id'])) $returnUrl .= (strpos($returnUrl, '?') === false ? '?' : '&') . 'request_id=' . (int)$_POST['force_request_id'];
        header('Location: ' . $returnUrl);
        exit;
    }
}

function renderContent() {
    global $conn;
    $venues = clms_safety_active_rows(db_fetch_all($conn, "SELECT id, venue_code, venue_name, COALESCE(seats, 35) seats, status FROM training_venue_masters ORDER BY venue_name ASC"));
    $instructors = clms_safety_active_rows(db_fetch_all($conn, "SELECT id, instructor_code, instructor_name, status FROM safety_instructor_masters ORDER BY instructor_name ASC"));

    $batches = db_fetch_all($conn, "
        SELECT b.*,
               COALESCE(wc.total_workers, 0) AS total_workers
        FROM training_class_batches b
        LEFT JOIN (
            SELECT batch_id, SUM(CASE WHEN ticked = 1 THEN 1 ELSE 0 END) AS total_workers
            FROM training_batch_workers
            GROUP BY batch_id
        ) wc ON wc.batch_id = b.id
        ORDER BY b.training_date DESC, b.id DESC
        LIMIT 100
    ");

    $selectedBatchId = (int)($_GET['batch_id'] ?? ($batches[0]['id'] ?? 0));
    $batch = $selectedBatchId
        ? db_single($conn, "SELECT * FROM training_class_batches WHERE id = ? LIMIT 1", 'i', array($selectedBatchId))
        : null;
    $forceRequestId = (int)($_GET['request_id'] ?? 0);
    $workers = $batch ? clms_safety_batch_candidates($conn, (int)$batch['id'], $forceRequestId) : array();
    $alreadyScheduled = false;
    foreach ($workers as $worker) {
        if ((int)($worker['ticked'] ?? 0) === 1) {
            $alreadyScheduled = true;
            break;
        }
    }
    $capacityInfo = $batch ? clms_safety_batch_capacity_summary($conn, $batch) : array('total' => 0, 'regular' => 0, 'emergency' => 0);
    $capacity = (int)$capacityInfo['total'];
    $emergencySeats = (int)$capacityInfo['emergency'];
    $regularCapacity = (int)$capacityInfo['regular'];
?>
<div class="content-header schedule-header">
  <div>
    <h2 class="page-title"><i class="fas fa-calendar-alt"></i> Training Schedule</h2>
    <p class="page-subtitle">Assign language-matching workers in enrolment-date order. The first available seats are auto-ticked.</p>
  </div>
  <div class="schedule-actions">
    <a href="training_class_master.php" class="btn btn-outline"><i class="fas fa-calendar-plus"></i> Create Batch</a>
    <a href="training_requests.php" class="btn btn-outline"><i class="fas fa-user-plus"></i> Find/Add Requests</a>
    <a href="reports.php" class="btn btn-outline"><i class="fas fa-list"></i> All Trainings</a>
  </div>
</div>

<section class="card glass selector-card">
  <div class="card-body">
    <form method="get" class="batch-select-form">
      <label>Batch No
        <select class="form-control" name="batch_id" onchange="this.form.submit()">
          <?php foreach ($batches as $item): ?>
            <option value="<?= (int)$item['id'] ?>" <?= $batch && (int)$batch['id'] === (int)$item['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($item['batch_number']) ?> - <?= date('d M Y', strtotime($item['training_date'])) ?> - <?= htmlspecialchars($item['language_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <a class="btn btn-outline" href="training_batch_report.php<?= $batch ? '?batch_id=' . (int)$batch['id'] : '' ?>"><i class="fas fa-file-lines"></i> Batch Report</a>
    </form>
  </div>
</section>

<?php if (!$batch): ?>
  <div class="alert alert-warning">No batch found. Create a training batch first.</div>
<?php else: ?>
<section class="batch-summary">
  <div class="summary-card"><span>Training Dt</span><strong><?= date('d M Y', strtotime($batch['training_date'])) ?></strong></div>
  <div class="summary-card"><span>Language</span><strong><?= htmlspecialchars($batch['language_name']) ?></strong></div>
  <div class="summary-card"><span>Location</span><strong><?= htmlspecialchars($batch['venue_name']) ?></strong></div>
  <div class="summary-card"><span>Session</span><strong><?= htmlspecialchars($batch['session_name']) ?></strong></div>
  <div class="summary-card"><span>Time</span><strong><?= htmlspecialchars(substr((string)($batch['time_from'] ?: ($batch['session_name'] === 'AN' ? '14:00' : '09:00')), 0, 5)) ?> - <?= htmlspecialchars(substr((string)($batch['time_to'] ?: ''), 0, 5) ?: '-') ?></strong></div>
  <div class="summary-card"><span>Training Type</span><strong><?= htmlspecialchars($batch['training_type']) ?></strong></div>
  <div class="summary-card"><span>Trainer</span><strong><?= htmlspecialchars($batch['instructor_name'] ?: 'Not assigned') ?></strong></div>
  <div class="summary-card capacity"><span>Slots</span><strong><b id="selectedCount">0</b> / <?= $capacity ?></strong><small><?= $regularCapacity ?> regular + <?= $emergencySeats ?> emergency</small></div>
</section>

<section class="card glass reschedule-card">
  <div class="card-header schedule-table-head">
    <div>
      <div class="card-title"><i class="fas fa-calendar-day"></i> Reschedule This Batch</div>
      <p>Use this when today's training cannot be conducted. Assigned workers, tokens and attempts remain with the same batch.</p>
    </div>
  </div>
  <div class="card-body">
    <form method="post" class="reschedule-form" onsubmit="return confirm('Reschedule this batch and all assigned workers?');">
      <input type="hidden" name="batch_action" value="reschedule">
      <input type="hidden" name="batch_id" value="<?= (int)$batch['id'] ?>">
      <label>New Date
        <input class="form-control" type="date" name="reschedule_date" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($batch['training_date']) ?>" required>
      </label>
      <label>Location
        <select class="form-control" name="reschedule_venue_id" required>
          <?php foreach ($venues as $venue): ?>
            <option value="<?= (int)$venue['id'] ?>" <?= (int)$venue['id'] === (int)($batch['venue_id'] ?? 0) ? 'selected' : '' ?>>
              <?= htmlspecialchars(($venue['venue_code'] ? $venue['venue_code'] . ' - ' : '') . $venue['venue_name']) ?> (<?= (int)$venue['seats'] ?> regular)
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Session
        <select class="form-control" name="reschedule_session_name">
          <option value="FN" <?= ($batch['session_name'] ?? '') === 'FN' ? 'selected' : '' ?>>FN</option>
          <option value="AN" <?= ($batch['session_name'] ?? '') === 'AN' ? 'selected' : '' ?>>AN</option>
        </select>
      </label>
      <label>Time From
        <input class="form-control" type="time" name="reschedule_time_from" value="<?= htmlspecialchars(substr((string)($batch['time_from'] ?: ''), 0, 5)) ?>">
      </label>
      <label>Time To
        <input class="form-control" type="time" name="reschedule_time_to" value="<?= htmlspecialchars(substr((string)($batch['time_to'] ?: ''), 0, 5)) ?>">
      </label>
      <label>Trainer
        <select class="form-control" name="reschedule_instructor_id">
          <option value="">Not assigned</option>
          <?php foreach ($instructors as $instructor): ?>
            <option value="<?= (int)$instructor['id'] ?>" <?= (int)$instructor['id'] === (int)($batch['instructor_id'] ?? 0) ? 'selected' : '' ?>>
              <?= htmlspecialchars(($instructor['instructor_code'] ? $instructor['instructor_code'] . ' - ' : '') . $instructor['instructor_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <div class="reschedule-actions">
        <button type="submit" class="btn btn-warning"><i class="fas fa-calendar-day"></i> Reschedule Batch</button>
      </div>
    </form>
  </div>
</section>

<form method="post" id="scheduleForm">
  <input type="hidden" name="batch_id" value="<?= (int)$batch['id'] ?>">
  <input type="hidden" name="force_request_id" value="<?= (int)$forceRequestId ?>">
  <section class="card glass workers-card">
    <div class="card-header schedule-table-head">
      <div>
        <div class="card-title"><i class="fas fa-users"></i> Assign Batch Workers</div>
        <p>Only <?= htmlspecialchars($batch['language_name']) ?> language workers are shown, sorted by enrolment date. The first <?= $capacity ?> rows are auto-ticked; selecting row <?= $capacity + 1 ?> without unticking another row is blocked.</p>
      </div>
      <div class="table-actions">
        <button type="button" class="btn btn-sm btn-outline" onclick="exportScheduleCsv()"><i class="fas fa-file-excel"></i> XL</button>
        <button type="submit" class="btn btn-sm btn-outline" name="schedule_mode" value="draft"><i class="fas fa-file"></i> Save Draft</button>
        <button type="submit" class="btn btn-sm btn-primary" name="schedule_mode" value="schedule"><i class="fas fa-check"></i> Schedule</button>
      </div>
    </div>
    <div class="card-body" style="padding:0">
      <table class="data-table schedule-table" id="scheduleTable">
        <thead>
          <tr>
            <th>Tick</th>
            <th>S.No</th>
            <th>Enrolment Dt</th>
            <th>Aadhaar</th>
            <th>Name</th>
            <th>Contractor Code</th>
            <th>Contractor Name</th>
            <th>Language</th>
            <th>Token</th>
            <th>Attempt</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($workers as $idx => $worker):
            $isForcedRequest = $forceRequestId > 0 && (int)$worker['training_request_id'] === $forceRequestId;
            $autoChecked = $alreadyScheduled ? (((int)$worker['ticked'] === 1) || $isForcedRequest) : (($idx < $capacity) || $isForcedRequest);
            $isBlocked = (int)$worker['attempt_no'] > 3;
            $tokenPreview = $worker['token_number'] ?: ($autoChecked ? str_pad((string)($idx + 1), 6, '0', STR_PAD_LEFT) : '');
            $seatLabel = ($autoChecked && $idx >= $regularCapacity) ? 'Emergency' : ($autoChecked ? 'Selected' : 'Waiting');
            $seatBadge = $seatLabel === 'Emergency' ? 'badge-warning' : ($autoChecked ? 'badge-info' : 'badge-gray');
          ?>
          <tr class="<?= $idx >= $capacity && !$autoChecked ? 'waiting-row' : '' ?>">
            <td>
              <input
                type="checkbox"
                class="worker-check"
                name="selected_requests[]"
                value="<?= (int)$worker['training_request_id'] ?>"
                data-token-target="token_<?= (int)$worker['training_request_id'] ?>"
                <?= $isBlocked ? 'data-max-attempt="1"' : '' ?>
                <?= $autoChecked && !$isBlocked ? 'checked' : '' ?>
                <?= $isBlocked ? 'disabled' : '' ?>
              >
            </td>
            <td><?= $idx + 1 ?></td>
            <td><?= !empty($worker['enrolment_date']) ? date('d M Y', strtotime($worker['enrolment_date'])) : (!empty($worker['requested_date']) ? date('d M Y', strtotime($worker['requested_date'])) : date('d M Y', strtotime($worker['request_created_at']))) ?></td>
            <td><?= htmlspecialchars($worker['aadhaar'] ?? '') ?></td>
            <td><strong><?= htmlspecialchars($worker['name'] ?? '') ?></strong><div class="muted"><?= htmlspecialchars($worker['temp_id'] ?? '') ?></div></td>
            <td><?= htmlspecialchars($worker['contractor_code'] ?? '') ?></td>
            <td><?= htmlspecialchars($worker['contractor_name'] ?? '') ?></td>
            <td><?= htmlspecialchars($worker['safety_language'] ?? $batch['language_name']) ?></td>
            <td><span class="token-pill" id="token_<?= (int)$worker['training_request_id'] ?>"><?= htmlspecialchars($tokenPreview) ?></span></td>
            <td>
              <?php if ($isBlocked): ?>
                <span class="badge badge-danger">Max Attempt</span>
              <?php else: ?>
                <?= (int)$worker['attempt_no'] ?>
              <?php endif; ?>
            </td>
            <td><span class="badge <?= $seatBadge ?> row-state"><?= $seatLabel ?></span></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($workers)): ?>
            <tr><td colspan="11" style="text-align:center;padding:34px;color:var(--text-muted)">No eligible <?= htmlspecialchars($batch['language_name']) ?> language workers found for scheduling.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</form>
<?php endif; ?>

<style>
  .schedule-header{display:flex;justify-content:space-between;align-items:flex-end;gap:14px;margin-bottom:16px}
  .schedule-actions,.table-actions{display:flex;gap:8px;flex-wrap:wrap}
  .selector-card{margin-bottom:14px}
  .batch-select-form{display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap}
  .batch-select-form label{display:flex;flex-direction:column;gap:5px;font-size:12px;font-weight:800;color:#475569;min-width:320px;flex:1}
  .form-control{height:38px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px;background:#fff}
  .batch-summary{display:grid;grid-template-columns:repeat(4,minmax(160px,1fr));gap:10px;margin-bottom:16px}
  .summary-card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:12px;min-height:70px}
  .summary-card span{display:block;font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;margin-bottom:6px}
  .summary-card strong{font-size:14px;color:#111827;line-height:1.25}
  .summary-card.capacity{border-color:#bfdbfe;background:#eff6ff}
  .summary-card.capacity strong{color:#1d4ed8}
  .summary-card.capacity small{display:block;margin-top:4px;color:#475569;font-size:11px;font-weight:700}
  .reschedule-card{margin-bottom:16px;border-color:#fde68a}
  .reschedule-card .card-header{background:#fffbeb}
  .reschedule-form{display:grid;grid-template-columns:repeat(3,minmax(170px,1fr));gap:12px;align-items:end}
  .reschedule-form label{display:flex;flex-direction:column;gap:5px;font-size:12px;font-weight:800;color:#475569}
  .reschedule-actions{display:flex;justify-content:flex-end}
  .schedule-table-head{align-items:flex-start}
  .schedule-table-head p{margin:4px 0 0;font-size:12px;color:#64748b}
  .workers-card{overflow:hidden}
  .muted{font-size:11px;color:#64748b;margin-top:2px}
  .token-pill{display:inline-flex;min-width:58px;justify-content:center;padding:3px 8px;border-radius:999px;background:#f1f5f9;color:#334155;font-weight:800;font-size:11px}
  .waiting-row{background:#fffaf0}
  .seat-disabled-row{opacity:.62;background:#f8fafc}
  .worker-check{width:18px;height:18px;cursor:pointer}
  @media(max-width:1000px){.batch-summary{grid-template-columns:repeat(2,minmax(160px,1fr))}.schedule-header{flex-direction:column;align-items:stretch}.reschedule-form{grid-template-columns:repeat(2,minmax(170px,1fr))}}
  @media(max-width:640px){.batch-summary{grid-template-columns:1fr}.batch-select-form label{min-width:0}.schedule-actions .btn,.table-actions .btn{flex:1}.reschedule-form{grid-template-columns:1fr}.reschedule-actions .btn{width:100%}}
  @media print{.sidebar,.topbar,.selector-card,.schedule-actions,.table-actions{display:none!important}.main-content{margin:0!important}.card{box-shadow:none!important}.batch-summary{grid-template-columns:repeat(4,1fr)}}
</style>
<script>
  const scheduleCapacity = <?= (int)$capacity ?>;
  const regularCapacity = <?= (int)$regularCapacity ?>;
  function refreshSelection() {
    const checks = Array.from(document.querySelectorAll('.worker-check'));
    const checked = checks.filter(input => input.checked);
    const selectedCount = document.getElementById('selectedCount');
    if (selectedCount) selectedCount.textContent = checked.length;
    const isFull = checked.length >= scheduleCapacity;

    checks.forEach(input => {
      const row = input.closest('tr');
      const state = row ? row.querySelector('.row-state') : null;
      if (row) row.classList.toggle('seat-disabled-row', isFull && !input.checked && !input.dataset.maxAttempt);
      if (state) {
        state.textContent = input.checked ? 'Selected' : 'Waiting';
        state.className = 'badge row-state ' + (input.checked ? 'badge-info' : 'badge-gray');
      }
      const target = document.getElementById(input.dataset.tokenTarget);
      if (target && !input.checked) target.textContent = '';
    });
    checked.forEach((input, idx) => {
      const target = document.getElementById(input.dataset.tokenTarget);
      if (target) target.textContent = String(idx + 1).padStart(6, '0');
      const state = input.closest('tr')?.querySelector('.row-state');
      if (state && idx >= regularCapacity) {
        state.textContent = 'Emergency';
        state.className = 'badge row-state badge-warning';
      }
    });
  }

  document.addEventListener('change', event => {
    if (!event.target.classList.contains('worker-check')) return;
    const selected = document.querySelectorAll('.worker-check:checked').length;
    if (selected > scheduleCapacity) {
      event.target.checked = false;
      const message = 'Maximum Slot Exceeded';
      if (window.Swal) {
        Swal.fire({ icon: 'warning', title: message, text: `Only ${scheduleCapacity} workers can be selected for this batch.` });
      } else {
        alert(message);
      }
    }
    refreshSelection();
  });

  document.getElementById('scheduleForm')?.addEventListener('submit', event => {
    const selected = document.querySelectorAll('.worker-check:checked').length;
    if (selected > scheduleCapacity) {
      event.preventDefault();
      alert('Maximum Slot Exceeded');
      return;
    }
    if (selected === 0) {
      event.preventDefault();
      alert('Please select workers to schedule.');
    }
  });

  function exportScheduleCsv() {
    const table = document.getElementById('scheduleTable');
    if (!table) return;
    const rows = Array.from(table.querySelectorAll('tr')).map(row =>
      Array.from(row.children).slice(1).map(cell => `"${cell.innerText.replace(/"/g, '""').trim()}"`).join(',')
    );
    const blob = new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'training-schedule.csv';
    a.click();
    URL.revokeObjectURL(url);
  }

  refreshSelection();
</script>
<?php
}

renderLayout('Training Schedule', 'renderContent', $role, $name);
?>
