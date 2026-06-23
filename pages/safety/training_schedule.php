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
    $venues = clms_safety_active_rows(db_fetch_all($conn, "SELECT id, venue_code, venue_name, COALESCE(seats, 35) seats, from_date, to_date, status FROM training_venue_masters ORDER BY venue_name ASC"));
    $instructors = clms_safety_active_rows(db_fetch_all($conn, "SELECT id, instructor_code, instructor_name, from_date, to_date, status FROM safety_instructor_masters ORDER BY instructor_name ASC"));

    $batches = db_fetch_all($conn, "
        SELECT b.*,
               COALESCE(wc.total_workers, 0) AS total_workers
        FROM training_class_batches b
        LEFT JOIN (
            SELECT batch_id, SUM(CASE WHEN ticked = 1 THEN 1 ELSE 0 END) AS total_workers
            FROM training_batch_workers
            GROUP BY batch_id
        ) wc ON wc.batch_id = b.id
        WHERE LOWER(COALESCE(b.status, '')) IN ('open', 'scheduled', 'active')
        ORDER BY b.training_date DESC, b.id DESC
        LIMIT 100
    ");

    $forceRequestId = (int)($_GET['request_id'] ?? 0);
    $selectedBatchId = (int)($_GET['batch_id'] ?? 0);

    if (!$selectedBatchId && $forceRequestId > 0) {
        $reqWorker = db_single($conn, "SELECT COALESCE(NULLIF(TRIM(w.safety_language), ''), '') as lang FROM training_requests tr JOIN workmen w ON w.id = tr.workman_id WHERE tr.id = ?", 'i', array($forceRequestId));
        if ($reqWorker && $reqWorker['lang'] !== '') {
            $reqLang = strtolower($reqWorker['lang']);
            foreach ($batches as $b) {
                if (strtolower(trim($b['language_name'])) === $reqLang) {
                    $selectedBatchId = (int)$b['id'];
                    break;
                }
            }
        }
    }

    if (!$selectedBatchId) {
        $selectedBatchId = (int)($batches[0]['id'] ?? 0);
    }

    $batch = $selectedBatchId
        ? db_single($conn, "SELECT * FROM training_class_batches WHERE id = ? LIMIT 1", 'i', array($selectedBatchId))
        : null;
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
<style>
  .selected-row { border-left: 4px solid #2563eb !important; background: #eff6ff !important; }
  .ts-selected-panel { background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); color: #fff; padding: 16px 20px; border-radius: 12px; margin-bottom: 18px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15); display: flex; flex-direction: column; gap: 12px; }
  .ts-selected-panel-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.15); padding-bottom: 10px; }
  .ts-selected-panel-title { font-size: 15px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; }
  .ts-selected-panel-count { background: #fff; color: #2563eb; border-radius: 99px; padding: 2px 8px; font-size: 11px; font-weight: 900; }
  .ts-selected-chips { display: flex; flex-wrap: wrap; gap: 8px; max-height: 120px; overflow-y: auto; }
  .ts-chip { display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25); border-radius: 99px; padding: 4px 10px; font-size: 11px; font-weight: 700; color: #fff; }
  .ts-chip-token { opacity: 0.7; font-family: monospace; font-size: 10px; }
  .ts-chip-remove { cursor: pointer; color: rgba(255,255,255,0.6); font-weight: 900; margin-left: 4px; font-size: 12px; transition: color 0.15s; }
  .ts-chip-remove:hover { color: #fff; }
  @media print { .ts-selected-panel { display: none !important; } }

  /* ── Batch Info Unified Section ── */
  .ts-batch-unified{background:#fff;border:1px solid #e2e8f0;border-radius:12px;margin-bottom:18px;box-shadow:0 1px 3px rgba(0,0,0,.04);overflow:hidden;margin-top:10px}
  .ts-selector-inner{display:flex;align-items:center;gap:14px;flex-wrap:wrap;padding:16px 20px;border-bottom:1px solid #e5e7eb;background:#f8fafc}
  .ts-selector-inner label{font-size:12px;font-weight:800;color:#475569;display:flex;flex-direction:column;gap:5px;flex:1;min-width:280px}
  .ts-selector-inner select.form-control{height:40px;border:1px solid #cbd5e1;border-radius:8px;padding:0 12px;background:#fff;font-size:13px;font-weight:600;color:#1e293b;transition:.2s}
  .ts-selector-inner select.form-control:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1);outline:none}

  /* ── Batch Summary ── */
  .ts-summary{display:grid;grid-template-columns:repeat(4,minmax(150px,1fr));gap:0}
  .ts-summary-card{padding:14px 16px;border-right:1px solid #e5e7eb;border-bottom:1px solid #e5e7eb;transition:.2s;background:#fff}
  .ts-summary-card:nth-child(4n){border-right:none}
  .ts-summary-card:nth-child(n+5){border-bottom:none}
  .ts-summary-card:hover{background:#f8fafc}
  .ts-summary-card .ts-label{font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px}
  .ts-summary-card .ts-value{font-size:15px;font-weight:800;color:#0f172a;line-height:1.2}
  .ts-summary-card.ts-slots{background:linear-gradient(135deg,#eff6ff 0%,#dbeafe 100%)}
  .ts-summary-card.ts-slots .ts-value{color:#1d4ed8;font-size:18px}
  .ts-summary-card.ts-slots .ts-sub{font-size:11px;color:#475569;font-weight:600;margin-top:3px}

  /* ── Workers Card ── */
  .ts-workers-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.04)}
  .ts-card-header{display:flex;justify-content:space-between;align-items:center;gap:14px;padding:16px 20px;border-bottom:1px solid #e5e7eb;background:#f8fafc;flex-wrap:wrap}
  .ts-card-header-left{display:flex;flex-direction:column;gap:4px}
  .ts-card-title{font-size:16px;font-weight:800;color:#0f172a;display:flex;align-items:center;gap:8px}
  .ts-card-title i{color:#2563eb;font-size:15px}
  .ts-card-desc{font-size:12px;color:#64748b;font-weight:500;max-width:600px;line-height:1.4}
  .ts-card-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
  .ts-card-actions .btn{font-size:12px;padding:8px 14px;border-radius:8px;font-weight:700;display:inline-flex;align-items:center;gap:6px;transition:.2s;cursor:pointer;text-decoration:none}
  .ts-btn-outline{background:#fff;border:1px solid #d1d5db;color:#374151}
  .ts-btn-outline:hover{background:#f3f4f6;border-color:#9ca3af}
  .ts-btn-finalize{background:linear-gradient(135deg,#2563eb,#3b82f6);border:none;color:#fff;padding:9px 20px!important;font-size:13px!important}
  .ts-btn-finalize:hover{background:linear-gradient(135deg,#1d4ed8,#2563eb);box-shadow:0 4px 14px rgba(37,99,235,.3)}

  /* ── Table ── */
  .ts-table-wrap{overflow-x:auto}
  .ts-table{width:100%;border-collapse:collapse}
  .ts-table thead{background:#f1f5f9}
  .ts-table th{padding:11px 12px;font-size:11px;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.3px;text-align:left;border-bottom:2px solid #e2e8f0;white-space:nowrap}
  .ts-table th.col-center,.ts-table td.col-center{text-align:center}
  .ts-table td{padding:10px 12px;font-size:13px;color:#1e293b;border-bottom:1px solid #f1f5f9;vertical-align:middle}
  .ts-table tbody tr{transition:.15s}
  .ts-table tbody tr:hover{background:#f8fafc}
  .ts-table .waiting-row{background:#fffbeb}
  .ts-table .waiting-row:hover{background:#fef9c3}
  .ts-table .seat-disabled-row{opacity:.55;background:#f8fafc}
  .ts-table .worker-check,.ts-table .tick-all-check{width:18px;height:18px;cursor:pointer;accent-color:#4f46e5}
  .ts-table .worker-name{font-weight:700;color:#0f172a}
  .ts-table .worker-sub{font-size:11px;color:#64748b;margin-top:1px}
  .ts-table .token-pill{display:inline-flex;min-width:58px;justify-content:center;padding:4px 10px;border-radius:999px;background:#eef2ff;color:#4338ca;font-weight:800;font-size:11px}
  .ts-table .badge{display:inline-flex;align-items:center;padding:4px 10px;border-radius:999px;font-size:11px;font-weight:800}
  .ts-table .badge-info{background:#dbeafe;color:#1d4ed8}
  .ts-table .badge-warning{background:#fef3c7;color:#92400e}
  .ts-table .badge-gray{background:#f1f5f9;color:#64748b}
  .ts-table .badge-danger{background:#fee2e2;color:#991b1b}
  .ts-empty{text-align:center;padding:40px 20px!important;color:#94a3b8;font-size:14px}

  @media(max-width:1000px){.ts-summary{grid-template-columns:repeat(2,minmax(140px,1fr))}}
  @media(max-width:640px){.ts-summary{grid-template-columns:1fr}.ts-card-header{flex-direction:column;align-items:stretch}.ts-card-actions{justify-content:flex-end}.ts-card-actions .btn{flex:1;justify-content:center}}
  @media print{.sidebar,.topbar,.ts-card-actions,.ts-selector-inner{display:none!important}.main-content{margin:0!important}.ts-batch-unified,.ts-workers-card{box-shadow:none!important;border:none!important}.ts-summary{grid-template-columns:repeat(4,1fr)}}
</style>

<!-- Unified Batch Info -->
<div class="ts-batch-unified">
  <form method="get" class="ts-selector-inner">
    <label>Select Batch
      <select class="form-control" name="batch_id" onchange="this.form.submit()">
        <?php foreach ($batches as $item): ?>
          <option value="<?= (int)$item['id'] ?>" <?= $batch && (int)$batch['id'] === (int)$item['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($item['batch_number']) ?> - <?= date('d M Y', strtotime($item['training_date'])) ?> - <?= htmlspecialchars($item['language_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
  </form>

<?php if (!$batch): ?>
  <div class="alert alert-warning" style="margin:16px;">No batch found. Create a training batch first.</div>
<?php else: ?>
  <!-- Batch Summary -->
  <div class="ts-summary">
    <div class="ts-summary-card">
      <div class="ts-label">Training Date</div>
      <div class="ts-value"><?= date('d M Y', strtotime($batch['training_date'])) ?></div>
    </div>
    <div class="ts-summary-card">
      <div class="ts-label">Language</div>
      <div class="ts-value"><?= htmlspecialchars($batch['language_name']) ?></div>
    </div>
    <div class="ts-summary-card">
      <div class="ts-label">Location</div>
      <div class="ts-value"><?= htmlspecialchars($batch['venue_name']) ?></div>
    </div>
    <div class="ts-summary-card">
      <div class="ts-label">Session</div>
      <div class="ts-value"><?= htmlspecialchars($batch['session_name']) ?></div>
    </div>
    <div class="ts-summary-card">
      <div class="ts-label">Time</div>
      <div class="ts-value"><?= htmlspecialchars(substr((string)($batch['time_from'] ?: ($batch['session_name'] === 'AN' ? '14:00' : '09:00')), 0, 5)) ?> - <?= htmlspecialchars(substr((string)($batch['time_to'] ?: ''), 0, 5) ?: '-') ?></div>
    </div>
    <div class="ts-summary-card">
      <div class="ts-label">Training Type</div>
      <div class="ts-value"><?= htmlspecialchars($batch['training_type']) ?></div>
    </div>
    <div class="ts-summary-card">
      <div class="ts-label">Trainer</div>
      <div class="ts-value"><?= htmlspecialchars($batch['instructor_name'] ?: 'Not assigned') ?></div>
    </div>
    <div class="ts-summary-card ts-slots">
      <div class="ts-label">Slots</div>
      <div class="ts-value"><b id="selectedCount">0</b> / <?= $capacity ?></div>
      <div class="ts-sub"><?= $regularCapacity ?> regular + <?= $emergencySeats ?> emergency</div>
    </div>
  </div>
<?php endif; ?>
</div>

<?php if ($batch): ?>

<!-- Workmen Selected For Training Panel -->
<div class="ts-selected-panel" id="selectedWorkersPanel" style="display:none">
  <div class="ts-selected-panel-header">
    <div class="ts-selected-panel-title">
      <i class="fas fa-user-check"></i>
      Workmen Selected For Training
      <span class="ts-selected-panel-count" id="selectedPanelCount">0</span>
    </div>
    <div style="font-size:12px;opacity:.85">Capacity: <?= (int)$capacity ?> slots | <span id="slotsRemaining"><?= (int)$capacity ?></span> remaining</div>
  </div>
  <div class="ts-selected-chips" id="selectedChipsContainer">
    <!-- chips injected by JS -->
  </div>
</div>

<!-- Workers Table -->
<form method="post" id="scheduleForm">
  <input type="hidden" name="batch_id" value="<?= (int)$batch['id'] ?>">
  <input type="hidden" name="force_request_id" value="<?= (int)$forceRequestId ?>">
  <div class="ts-workers-card">
    <div class="ts-card-header">
      <div class="ts-card-header-left">
        <div class="ts-card-title"><i class="fas fa-users-cog"></i> Assign Batch Workers</div>
        <div class="ts-card-desc">Only <?= htmlspecialchars($batch['language_name']) ?> language workers shown. First <?= $capacity ?> seats auto-ticked.</div>
      </div>
      <div class="ts-card-actions">
        <a href="reschedule_batch.php?batch_id=<?= (int)$batch['id'] ?>" class="btn ts-btn-outline"><i class="fas fa-calendar-day"></i> Reschedule Batch</a>
        <a href="training_batch_report.php?batch_id=<?= (int)$batch['id'] ?>" class="btn ts-btn-outline"><i class="fas fa-file-lines"></i> Batch Report</a>
        <button type="submit" class="btn ts-btn-finalize" name="schedule_mode" value="schedule"><i class="fas fa-check-double"></i> Finalize Batch</button>
        <button type="button" class="btn ts-btn-outline" onclick="exportScheduleCsv()"><i class="fas fa-file-excel"></i> XL</button>
        <a href="reports.php" class="btn ts-btn-outline"><i class="fas fa-chart-bar"></i> All Trainings</a>
      </div>
    </div>
    <div class="ts-table-wrap">
      <table class="ts-table" id="scheduleTable">
        <thead>
          <tr>
            <th class="col-center"><input type="checkbox" class="tick-all-check" id="tickAllCheck" title="Select / Deselect All"></th>
            <th class="col-center">S.No</th>
            <th>Enrolment Dt</th>
            <th>Aadhaar</th>
            <th>Name</th>
            <th>Contractor Code</th>
            <th>Contractor Name</th>
            <th>Language</th>
            <th class="col-center">Token</th>
            <th class="col-center">Attempt</th>
            <th class="col-center">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($workers as $idx => $worker):
            $isForcedRequest = $forceRequestId > 0 && (int)$worker['training_request_id'] === $forceRequestId;
            $autoChecked = false;
            if ($alreadyScheduled) {
                $autoChecked = ((int)$worker['ticked'] === 1) || $isForcedRequest;
            } else {
                $autoChecked = ($idx < $capacity) || $isForcedRequest;
            }
            if ($isDraft && in_array((int)$worker['training_request_id'], $selectedInDraft, true)) {
                $autoChecked = true;
            }
            $tokenPreview = '';
            if ($worker['token_number']) {
                $tokenPreview = $worker['token_number'];
            } elseif ($autoChecked) {
                $tokenPreview = (string)rand(100000, 999999);
            }
            $isBlocked = (int)$worker['attempt_no'] > 3;
            $seatLabel = ($autoChecked && $idx >= $regularCapacity) ? 'Emergency' : ($autoChecked ? 'Selected' : 'Waiting');
            $seatBadge = $seatLabel === 'Emergency' ? 'badge-warning' : ($autoChecked ? 'badge-info' : 'badge-gray');
          ?>
          <tr class="<?= $idx >= $capacity && !$autoChecked ? 'waiting-row' : '' ?>">
            <td class="col-center">
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
            <td class="col-center"><?= $idx + 1 ?></td>
            <td><?= !empty($worker['enrolment_date']) ? date('d M Y', strtotime($worker['enrolment_date'])) : (!empty($worker['requested_date']) ? date('d M Y', strtotime($worker['requested_date'])) : date('d M Y', strtotime($worker['request_created_at']))) ?></td>
            <td><?= htmlspecialchars($worker['aadhaar'] ?? '') ?></td>
            <td><span class="worker-name"><?= htmlspecialchars($worker['name'] ?? '') ?></span><div class="worker-sub"><?= htmlspecialchars($worker['temp_id'] ?? '') ?></div></td>
            <td><?= htmlspecialchars($worker['contractor_code'] ?? '') ?></td>
            <td><?= htmlspecialchars($worker['contractor_name'] ?? '') ?></td>
            <td><?= htmlspecialchars($worker['safety_language'] ?? $batch['language_name']) ?></td>
            <td class="col-center"><span class="token-pill" id="token_<?= (int)$worker['training_request_id'] ?>"><?= htmlspecialchars($tokenPreview) ?></span></td>
            <td class="col-center">
              <?php if ($isBlocked): ?>
                <span class="badge badge-danger">Max Attempt</span>
              <?php else: ?>
                <?= (int)$worker['attempt_no'] ?>
              <?php endif; ?>
            </td>
            <td class="col-center"><span class="badge <?= $seatBadge ?> row-state"><?= $seatLabel ?></span></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($workers)): ?>
            <tr><td colspan="11" class="ts-empty"><i class="fas fa-inbox" style="font-size:24px;display:block;margin-bottom:8px;color:#cbd5e1"></i>No eligible <?= htmlspecialchars($batch['language_name']) ?> language workers found for scheduling.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</form>

<script>
  const scheduleCapacity = <?= (int)$capacity ?>;
  const regularCapacity = <?= (int)$regularCapacity ?>;

  // Build a worker info map from table data for chip labels
  const workerInfoMap = {};
  document.querySelectorAll('#scheduleTable tbody tr').forEach(row => {
    const cb = row.querySelector('.worker-check');
    if (!cb) return;
    const cells = row.querySelectorAll('td');
    // cells: [0]=checkbox, [1]=sno, [2]=enroldt, [3]=aadhaar, [4]=name, [5]=code, [6]=cname, [7]=lang, [8]=token, [9]=attempt, [10]=status
    workerInfoMap[cb.value] = {
      name: cells[4]?.querySelector('.worker-name')?.textContent?.trim() || cells[4]?.textContent?.trim() || 'Worker',
      aadhaar: cells[3]?.textContent?.trim() || '',
      requestId: cb.value
    };
  });

  function refreshSelectedPanel() {
    const allChecks = Array.from(document.querySelectorAll('.worker-check'));
    const checked = allChecks.filter(i => i.checked);
    const panel = document.getElementById('selectedWorkersPanel');
    const chipsContainer = document.getElementById('selectedChipsContainer');
    const panelCount = document.getElementById('selectedPanelCount');
    const slotsRemaining = document.getElementById('slotsRemaining');

    if (panel) panel.style.display = checked.length > 0 ? '' : 'none';
    if (panelCount) panelCount.textContent = checked.length;
    if (slotsRemaining) slotsRemaining.textContent = Math.max(0, scheduleCapacity - checked.length);

    if (chipsContainer) {
      chipsContainer.innerHTML = '';
      checked.forEach((input, idx) => {
        const info = workerInfoMap[input.value] || {};
        const target = document.getElementById(input.dataset.tokenTarget);
        const tokenNum = (target && target.textContent.trim() !== '') ? target.textContent : String(Math.floor(100000 + Math.random() * 900000));
        const chip = document.createElement('div');
        chip.className = 'ts-chip';
        chip.innerHTML = `
          <span class="ts-chip-token">${tokenNum}</span>
          <span>${(info.name || 'Worker').substring(0,22)}</span>
          <span class="ts-chip-remove" title="Remove" data-req="${input.value}">&times;</span>`;
        chipsContainer.appendChild(chip);
      });
      // Attach remove listeners
      chipsContainer.querySelectorAll('.ts-chip-remove').forEach(btn => {
        btn.addEventListener('click', () => {
          const cb = document.querySelector(`.worker-check[value="${btn.dataset.req}"]`);
          if (cb && !cb.disabled) { cb.checked = false; refreshSelection(); }
        });
      });
    }

    // Move selected rows visually to top by reordering tbody
    const tbody = document.querySelector('#scheduleTable tbody');
    if (tbody) {
      const rows = Array.from(tbody.querySelectorAll('tr'));
      const selectedRows = rows.filter(r => r.querySelector('.worker-check')?.checked);
      const otherRows   = rows.filter(r => !r.querySelector('.worker-check')?.checked);
      selectedRows.forEach(r => { r.classList.add('selected-row'); tbody.prepend(r); });
      otherRows.forEach(r => { r.classList.remove('selected-row'); tbody.appendChild(r); });
    }
  }

  function refreshSelection() {
    const checks = Array.from(document.querySelectorAll('.worker-check:not([disabled])'));
    const allChecks = Array.from(document.querySelectorAll('.worker-check'));
    const checked = allChecks.filter(i => i.checked);
    const el = document.getElementById('selectedCount');
    if (el) el.textContent = checked.length;
    const isFull = checked.length >= scheduleCapacity;

    // Update tick-all checkbox state
    const tickAll = document.getElementById('tickAllCheck');
    if (tickAll) {
      tickAll.checked = checks.length > 0 && checks.every(i => i.checked);
      tickAll.indeterminate = checks.some(i => i.checked) && !tickAll.checked;
    }

    allChecks.forEach(input => {
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
      if (target && target.textContent.trim() === '') {
          target.textContent = String(Math.floor(100000 + Math.random() * 900000));
      }
      const state = input.closest('tr')?.querySelector('.row-state');
      if (state && idx >= regularCapacity) {
        state.textContent = 'Emergency';
        state.className = 'badge row-state badge-warning';
      }
    });
    refreshSelectedPanel();
  }

  // Tick All handler
  document.getElementById('tickAllCheck')?.addEventListener('change', function() {
    const checks = Array.from(document.querySelectorAll('.worker-check:not([disabled])'));
    if (this.checked) {
      let count = 0;
      checks.forEach(input => {
        if (count < scheduleCapacity) {
          input.checked = true;
          count++;
        } else {
          input.checked = false;
        }
      });
    } else {
      checks.forEach(input => input.checked = false);
    }
    refreshSelection();
  });

  document.addEventListener('change', event => {
    if (!event.target.classList.contains('worker-check')) return;
    const selected = document.querySelectorAll('.worker-check:checked').length;
    if (selected > scheduleCapacity) {
      event.target.checked = false;
      Swal.fire({ icon: 'warning', title: 'Maximum Slot Exceeded', text: `Only ${scheduleCapacity} workers can be selected for this batch.`, confirmButtonColor: '#f59e0b' });
    }
    refreshSelection();
  });

  document.getElementById('scheduleForm')?.addEventListener('submit', event => {
    const selected = document.querySelectorAll('.worker-check:checked').length;
    if (selected > scheduleCapacity) {
      event.preventDefault();
      Swal.fire({ icon: 'warning', title: 'Maximum Slot Exceeded', text: `Only ${scheduleCapacity} workers can be selected for this batch.`, confirmButtonColor: '#f59e0b' });
      return;
    }
    if (selected === 0) {
      event.preventDefault();
      Swal.fire({ icon: 'warning', title: 'No Workers Selected', text: 'Please select at least one worker to finalize the batch.', confirmButtonColor: '#f59e0b' });
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
  refreshSelectedPanel();
</script>
<?php
endif;
}

renderLayout('Training Schedule', 'renderContent', $role, $name);
?>