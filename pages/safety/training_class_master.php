<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';
require_once __DIR__ . '/../../include/safety_training_control.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Safety Officer';
clms_safety_ensure_control_schema($conn);

if (isset($_GET['action']) && $_GET['action'] === 'get_reschedule_history') {
    $batch_id = (int)$_GET['batch_id'];
    $history = db_fetch_all($conn, "SELECT * FROM batch_reschedule_history WHERE batch_id = ? ORDER BY rescheduled_at DESC", 'i', [$batch_id]);
    header('Content-Type: application/json');
    echo json_encode($history);
    exit;
}

// AJAX endpoint: toggle batch status
if (isset($_GET['action']) && $_GET['action'] === 'toggle_batch_status') {
    header('Content-Type: application/json');
    $batchId = (int)($_POST['batch_id'] ?? 0);
    $status   = ($_POST['status'] ?? '') === 'active' ? 'active' : 'inactive';
    try {
        clms_safety_set_batch_status($conn, $batchId, $status);
        echo json_encode(['success' => true, 'message' => 'Batch status set to ' . $status . '.']);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $saveMode = ($_POST['save_mode'] ?? '') === 'draft' ? 'draft' : 'schedule';

    // Save as Draft — do NOT create a batch, just store in session
    if ($saveMode === 'draft') {
        $_SESSION['success'] = 'Draft saved. The form data has been retained. Click "Submit Batch" when you are ready to create the batch.';
        $_SESSION['batch_draft'] = $_POST;
        header('Location: training_class_master.php');
        exit;
    }

    // If they actually submit, clear the draft
    unset($_SESSION['batch_draft']);

    // Submit Batch — create the actual batch record
    try {
        $result = clms_safety_create_batch($conn, $_POST, (int)($_SESSION['user_id'] ?? 0));
        // Redirect with batch number in query string so JS can show popup + scroll
        header('Location: training_class_master.php?batch_created=' . urlencode($result['batch_number']) . '#recent-batches');
        exit;
    } catch (Throwable $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header('Location: training_class_master.php');
    exit;
}

function renderContent() {
    global $conn;
    $venues = clms_safety_active_rows(db_fetch_all($conn, "SELECT id, venue_code, venue_name, COALESCE(seats, 35) seats, from_date, to_date, status FROM training_venue_masters ORDER BY venue_name ASC"));
    $languages = clms_safety_active_rows(db_fetch_all($conn, "SELECT id, language_name, from_date, to_date, status FROM training_language_masters ORDER BY sort_order ASC, language_name ASC"));
    $types = clms_safety_active_rows(clms_get_training_type_rows($conn, false));
    $instructors = clms_safety_active_rows(db_fetch_all($conn, "SELECT id, instructor_code, instructor_name, from_date, to_date, status FROM safety_instructor_masters ORDER BY instructor_name ASC"));
    $batches = db_fetch_all($conn, "SELECT b.*, COALESCE(wc.total_workers, 0) total_workers FROM training_class_batches b LEFT JOIN (SELECT batch_id, COUNT(*) total_workers FROM training_batch_workers WHERE ticked = 1 GROUP BY batch_id) wc ON wc.batch_id = b.id ORDER BY b.created_at DESC, b.id DESC LIMIT 20");
    $prefillRequestId = (int)($_GET['request_id'] ?? 0);
    $prefillRequest = $prefillRequestId ? db_single($conn, "
        SELECT tr.id, tr.training_type, w.name AS worker_name, w.safety_language, w.temp_id
        FROM training_requests tr
        JOIN workmen w ON w.id = tr.workman_id
        WHERE tr.id = ? LIMIT 1
    ", 'i', array($prefillRequestId)) : null;
    $prefillLanguage = strtolower(trim((string)($prefillRequest['safety_language'] ?? '')));
    $prefillType = strtolower(trim((string)($prefillRequest['training_type'] ?? 'Safety Induction')));

    // Batch created success (from query param — avoids session/hash issues)
    $batchCreated = trim((string)($_GET['batch_created'] ?? ''));
?>
<div class="content-header"><div><h2 class="page-title"><i class="fas fa-calendar-plus"></i> Training Class Master</h2><p class="page-subtitle">Create batch token, auto-tick workers by language/date order and selected location seats.</p></div></div>
<?php if ($prefillRequest): ?>
<div class="alert alert-info">
  <i class="fas fa-info-circle"></i>
  <div>Assigning batch from request #<?= (int)$prefillRequest['id'] ?> for <?= htmlspecialchars($prefillRequest['worker_name']) ?>. To add this worker into an existing same-training batch, use <strong>Assign Workers</strong> on the batch below.</div>
</div>
<?php endif; ?>
<?php
  $draft = $_SESSION['batch_draft'] ?? [];
  $draftVenueId = (int)($draft['venue_id'] ?? 0);
  $draftLanguageId = (int)($draft['language_id'] ?? 0);
  $draftTypeId = (int)($draft['training_type_id'] ?? 0);
  $draftInstructorId = (int)($draft['instructor_id'] ?? 0);
  $draftSession = $draft['session_name'] ?? 'FN';
?>
<section class="card glass">
  <div class="card-header"><div class="card-title">Schedule Batch</div></div>
  <div class="card-body">
    <form method="post" class="class-form">
      <input type="hidden" name="source_request_id" value="<?= (int)$prefillRequestId ?>">
      <label>Training Date<input class="form-control" type="date" name="training_date" id="trainingDate" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($draft['training_date'] ?? date('Y-m-d')) ?>" required></label>
      <label>Training Location<select class="form-control" name="venue_id" id="venueSelect" onchange="updateSeats()" required><option value="">Select</option><?php foreach($venues as $v): ?><option value="<?= (int)$v['id'] ?>" data-seats="<?= (int)$v['seats'] ?>" <?= $draftVenueId === (int)$v['id'] ? 'selected' : '' ?>><?= htmlspecialchars(($v['venue_code'] ? $v['venue_code'].' - ' : '').$v['venue_name']) ?></option><?php endforeach; ?></select></label>
      <label>Slots<input class="form-control" id="slotBox" value="" readonly></label>
      <label>Emergency Seats<input class="form-control" type="number" name="emergency_seats" id="emergencySeats" min="0" value="<?= htmlspecialchars($draft['emergency_seats'] ?? '5') ?>" oninput="updateSeats()"></label>
      <label>Language<select class="form-control" name="language_id" id="languageSelect" onchange="syncExistingBatch()" required><option value="">Select</option><?php foreach($languages as $l): ?><option value="<?= (int)$l['id'] ?>" <?= ($draftLanguageId === (int)$l['id'] || strtolower(trim($l['language_name'])) === $prefillLanguage) ? 'selected' : '' ?>><?= htmlspecialchars($l['language_name']) ?></option><?php endforeach; ?></select></label>
      <label>Session<select class="form-control" name="session_name" id="sessionSelect"><option value="FN" <?= $draftSession === 'FN' ? 'selected' : '' ?>>FN</option><option value="AN" <?= $draftSession === 'AN' ? 'selected' : '' ?>>AN</option></select></label>
      <label>Time From<input class="form-control" type="time" name="time_from" id="timeFrom" value="<?= htmlspecialchars($draft['time_from'] ?? '') ?>"></label>
      <label>Time To<input class="form-control" type="time" name="time_to" id="timeTo" value="<?= htmlspecialchars($draft['time_to'] ?? '') ?>"></label>
      <label>Training Type<select class="form-control" name="training_type_id" id="trainingTypeSelect" onchange="syncExistingBatch()" required><option value="">Select</option><?php foreach($types as $t): ?><option value="<?= (int)$t['id'] ?>" data-type-name="<?= htmlspecialchars($t['type_name']) ?>" <?= ($draftTypeId === (int)$t['id'] || strtolower(trim($t['type_name'])) === $prefillType) ? 'selected' : '' ?>><?= htmlspecialchars($t['type_name']) ?></option><?php endforeach; ?></select></label>
      <label>Trainer<select class="form-control" name="instructor_id" id="trainerSelect"><option value="">Auto / Not assigned</option><?php foreach($instructors as $i): ?><option value="<?= (int)$i['id'] ?>" <?= $draftInstructorId === (int)$i['id'] ? 'selected' : '' ?>><?= htmlspecialchars(($i['instructor_code'] ? $i['instructor_code'].' - ' : '').$i['instructor_name']) ?></option><?php endforeach; ?></select></label>
      <div id="existingBatchHint" class="existing-batch-hint" style="display:none"></div>
      <div class="actions">
        <button class="btn btn-outline" name="save_mode" value="draft"><i class="fas fa-file"></i> Save as Draft</button>
        <button class="btn btn-primary" name="save_mode" value="schedule"><i class="fas fa-check"></i> Submit Batch</button>
      </div>
    </form>
  </div>
</section>

<section id="recent-batches" class="card glass" style="margin-top:18px">
  <div class="card-header"><div class="card-title">Recent Batches</div><a href="training_batch_report.php" class="btn btn-sm btn-primary">Reports</a></div>
  <div class="card-body" style="padding:0">
    <table class="data-table" id="batchesTable"><thead><tr><th>Batch No</th><th>Date</th><th>Location</th><th>Language</th><th>Workers</th><th>Status</th><th>Action</th></tr></thead><tbody>
    <?php foreach($batches as $b):
      $capacity = max(1, (int)$b['capacity']);
      $emg = max(0, (int)($b['emergency_seats'] ?? 0));
      $regular = max(0, $capacity - $emg);
      $assigned = (int)$b['total_workers'];
      $available = max(0, $capacity - $assigned);
      $fillPct = min(100, max(0, round(($assigned / $capacity) * 100)));
      $active = in_array(strtolower((string)$b['status']), array('open', 'scheduled', 'active'), true) && $b['training_date'] >= date('Y-m-d');
      $assignUrl = 'training_schedule.php?batch_id=' . (int)$b['id'] . ($prefillRequestId ? '&request_id=' . (int)$prefillRequestId : '');
      $sessionLabel = htmlspecialchars($b['session_name'] ?? 'FN');
      $sessionBadgeClass = ($b['session_name'] ?? 'FN') === 'AN' ? 'session-badge-an' : 'session-badge-fn';
    ?>
    <tr data-batch-id="<?= (int)$b['id'] ?>">
      <td><strong><?= htmlspecialchars($b['batch_number']) ?></strong><div style="font-size:11px;color:var(--text-muted)">Token: <?= htmlspecialchars($b['batch_token']) ?></div></td>
      <td><?= date('d M Y', strtotime($b['training_date'])) ?> <span class="session-badge <?= $sessionBadgeClass ?>"><?= $sessionLabel ?></span></td>
      <td><?= htmlspecialchars($b['venue_name']) ?></td>
      <td><?= htmlspecialchars($b['language_name']) ?></td>
      <td>
        <div class="worker-capacity">
          <div class="worker-capacity-top"><strong><?= $assigned ?></strong><span>of <?= $capacity ?> assigned</span><em><?= $available ?> open</em></div>
          <div class="worker-capacity-bar"><span style="width:<?= $fillPct ?>%"></span></div>
          <div class="worker-capacity-split"><span><?= $regular ?> regular</span><span><?= $emg ?> emergency</span></div>
        </div>
      </td>
      <td><span class="badge <?= $active ? 'badge-success' : 'badge-gray' ?>"><?= $active ? 'Active' : 'Inactive' ?></span></td>
      <td>
        <div class="row-actions">
          <?php if ($active): ?><a class="btn btn-sm btn-primary" href="<?= htmlspecialchars($assignUrl) ?>">Assign Workers</a><?php endif; ?>
          <button type="button" class="btn btn-sm btn-primary" onclick='viewBatchDetails(<?= json_encode($b) ?>)'><i class="fas fa-eye"></i> View</button>
          <a class="btn btn-sm btn-outline" href="training_batch_report.php?batch_id=<?= (int)$b['id'] ?>">Report</a>
          <button type="button" class="btn btn-sm btn-outline" onclick="viewRescheduleHistory(<?= (int)$b['id'] ?>, '<?= htmlspecialchars($b['batch_number']) ?>')">History</button>
          <a class="btn btn-sm btn-warning" href="reschedule_batch.php?batch_id=<?= (int)$b['id'] ?>" title="Reschedule this batch"><i class="fas fa-calendar-day"></i> Reschedule</a>

        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody></table>
  </div>
</section>
<style>
.class-form{display:grid;grid-template-columns:repeat(3,minmax(180px,1fr));gap:12px}
.class-form label{display:flex;flex-direction:column;gap:5px;font-size:12px;font-weight:800;color:#475569}
.form-control{height:38px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px}
.actions{grid-column:1/-1;display:flex;gap:10px;justify-content:flex-end}
.row-actions{display:flex;gap:8px;flex-wrap:wrap}
.existing-batch-hint{grid-column:1/-1;border:1px solid #bfdbfe;background:#eff6ff;border-radius:8px;padding:12px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.existing-batch-hint strong{color:#1e40af}
.existing-batch-hint span{font-size:12px;color:#475569}
.worker-capacity{min-width:155px}
.worker-capacity-top{display:flex;align-items:baseline;gap:5px;white-space:nowrap}
.worker-capacity-top strong{font-size:16px;color:#0f172a}
.worker-capacity-top span{font-size:11px;color:#475569;font-weight:700}
.worker-capacity-top em{margin-left:auto;font-style:normal;font-size:10px;font-weight:800;color:#166534;background:#dcfce7;border-radius:999px;padding:2px 7px}
.worker-capacity-bar{height:6px;background:#e5e7eb;border-radius:999px;overflow:hidden;margin:6px 0}
.worker-capacity-bar span{display:block;height:100%;background:#2563eb;border-radius:999px}
.worker-capacity-split{display:flex;gap:6px;flex-wrap:wrap}
.worker-capacity-split span{font-size:10px;font-weight:800;color:#475569;background:#f8fafc;border:1px solid #e2e8f0;border-radius:999px;padding:2px 7px}
/* Session badges */
.session-badge{display:inline-flex;align-items:center;justify-content:center;font-size:10px;font-weight:900;border-radius:6px;padding:2px 7px;margin-left:4px;letter-spacing:.5px}
.session-badge-fn{background:#dbeafe;color:#1d4ed8}
.session-badge-an{background:#fde68a;color:#92400e}
@media(max-width:900px){.class-form{grid-template-columns:1fr}.actions{justify-content:stretch;flex-direction:column}}
</style>
<script>
const existingBatches = <?= json_encode(array_map(function($b) {
  return [
    'id' => (int)$b['id'],
    'batch_number' => (string)$b['batch_number'],
    'training_type' => (string)$b['training_type'],
    'training_date' => (string)$b['training_date'],
    'venue_id' => (string)($b['venue_id'] ?? ''),
    'venue_name' => (string)($b['venue_name'] ?? ''),
    'capacity' => (int)($b['capacity'] ?? 0),
    'total_workers' => (int)($b['total_workers'] ?? 0),
    'emergency_seats' => (int)($b['emergency_seats'] ?? 0),
    'language_id' => (string)($b['language_id'] ?? ''),
    'language_name' => (string)($b['language_name'] ?? ''),
    'session_name' => (string)($b['session_name'] ?? 'FN'),
    'time_from' => substr((string)($b['time_from'] ?? ''), 0, 5),
    'time_to' => substr((string)($b['time_to'] ?? ''), 0, 5),
    'instructor_id' => (string)($b['instructor_id'] ?? ''),
    'instructor_name' => (string)($b['instructor_name'] ?? ''),
    'status' => (string)($b['status'] ?? ''),
  ];
}, array_values(array_filter($batches, function($b) {
  return in_array(strtolower((string)($b['status'] ?? '')), array('open', 'scheduled', 'active'), true)
    && (string)($b['training_date'] ?? '') >= date('Y-m-d');
}))), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

// ── (c/d) Show success popup for batch creation and scroll to recent batches ──
(function() {
  const params = new URLSearchParams(window.location.search);
  const batchCreated = params.get('batch_created');
  if (batchCreated) {
    // Clean the URL immediately so refresh won't re-show the popup
    const cleanUrl = window.location.pathname + (window.location.hash || '');
    history.replaceState(null, '', cleanUrl);

    window.addEventListener('DOMContentLoaded', () => {
      Swal.fire({
        icon: 'success',
        title: 'Batch Created!',
        html: `Batch with Batch No <strong>${batchCreated}</strong> is created successfully.`,
        confirmButtonColor: '#1e3a8a',
        confirmButtonText: 'OK'
      }).then(() => {
        const el = document.getElementById('recent-batches');
        if (el) el.scrollIntoView({ behavior: 'smooth' });
      });
      // Also scroll immediately
      const el = document.getElementById('recent-batches');
      if (el) el.scrollIntoView({ behavior: 'smooth' });
    });
  }
})();

function updateSeats(){
  var s=document.getElementById('venueSelect');
  var o=s.options[s.selectedIndex];
  var total=o&&o.dataset.seats?parseInt(o.dataset.seats,10):0;
  var emergency=Math.max(0,parseInt(document.getElementById('emergencySeats').value||'0',10));
  if(total&&emergency>total){emergency=total;document.getElementById('emergencySeats').value=emergency;}
  document.getElementById('slotBox').value=total?String(total):'';
}

function norm(value) {
  return String(value || '').trim().toLowerCase();
}

function setValue(id, value) {
  const el = document.getElementById(id);
  if (el && value !== undefined && value !== null) el.value = value;
}

function syncExistingBatch() {
  const typeSelect = document.getElementById('trainingTypeSelect');
  const selectedOption = typeSelect.options[typeSelect.selectedIndex];
  const typeName = selectedOption ? (selectedOption.dataset.typeName || selectedOption.textContent) : '';
  const languageSelect = document.getElementById('languageSelect');
  const selectedLanguageOption = languageSelect.options[languageSelect.selectedIndex];
  const languageId = languageSelect.value;
  const languageName = selectedLanguageOption ? selectedLanguageOption.textContent : '';
  const hint = document.getElementById('existingBatchHint');
  const matches = existingBatches.filter(batch => {
    const typeMatches = norm(batch.training_type) === norm(typeName);
    const languageMatches = !languageId || String(batch.language_id) === String(languageId) || norm(batch.language_name) === norm(languageName);
    return typeMatches && languageMatches;
  });
  const batch = matches.find(item => item.total_workers < item.capacity) || matches[0];

  if (!batch) {
    hint.style.display = 'none';
    return;
  }

  setValue('trainingDate', batch.training_date);
  setValue('venueSelect', batch.venue_id);
  setValue('emergencySeats', batch.emergency_seats);
  setValue('languageSelect', batch.language_id);
  setValue('sessionSelect', batch.session_name || 'FN');
  setValue('timeFrom', batch.time_from || '');
  setValue('timeTo', batch.time_to || '');
  setValue('trainerSelect', batch.instructor_id || '');
  updateSeats();

  const available = Math.max(0, batch.capacity - batch.total_workers);
  hint.innerHTML = `
    <div>
      <strong>Existing batch found: ${batch.batch_number}</strong><br>
      <span>${batch.training_date} | ${batch.venue_name} | ${batch.language_name} | ${batch.total_workers}/${batch.capacity} assigned, ${available} total seats available.</span>
    </div>
    <a class="btn btn-sm btn-primary" href="training_schedule.php?batch_id=${batch.id}<?= $prefillRequestId ? '&request_id=' . (int)$prefillRequestId : '' ?>">Add Workers to Existing Batch</a>
  `;
  hint.style.display = 'flex';
}

updateSeats();
syncExistingBatch();

async function viewRescheduleHistory(batchId, batchNumber) {
  try {
    const res = await fetch('training_class_master.php?action=get_reschedule_history&batch_id=' + batchId);
    const data = await res.json();
    if (!data || data.length === 0) {
      Swal.fire({
        icon: 'info',
        title: 'No History',
        text: 'Reschedule history not found for Batch ' + batchNumber
      });
      return;
    }
    
    let html = '<div style="max-height: 400px; overflow-y: auto;"><table class="table" style="width:100%;font-size:12px;border-collapse:collapse;margin-top:10px;">';
    html += '<thead><tr style="background:#f1f5f9;"><th style="padding:8px;border-bottom:2px solid #cbd5e1;text-align:left;">From Details</th><th style="padding:8px;border-bottom:2px solid #cbd5e1;text-align:left;">To Details</th><th style="padding:8px;border-bottom:2px solid #cbd5e1;text-align:left;">Rescheduled At</th></tr></thead><tbody>';
    data.forEach(h => {
        const formattedAt = new Date(h.rescheduled_at).toLocaleString();
        html += `<tr>
          <td style="padding:8px;border-bottom:1px solid #e2e8f0;text-align:left;line-height:1.4;">Date: <strong>${h.original_date}</strong><br><small style="color:#64748b;">Loc: ${h.original_venue_name || 'N/A'}</small><br><small style="color:#64748b;">Session: ${h.original_session || 'FN'}</small></td>
          <td style="padding:8px;border-bottom:1px solid #e2e8f0;text-align:left;line-height:1.4;">Date: <strong class="text-success">${h.rescheduled_date}</strong><br><small style="color:#64748b;">Loc: ${h.rescheduled_venue_name || 'N/A'}</small><br><small style="color:#64748b;">Session: ${h.rescheduled_session || 'FN'}</small></td>
          <td style="padding:8px;border-bottom:1px solid #e2e8f0;text-align:left;font-size:11px;color:#64748b;">${formattedAt}</td>
        </tr>`;
    });
    html += '</tbody></table></div>';
    
    Swal.fire({
      title: 'Reschedule History: ' + batchNumber,
      html: html,
      width: '650px',
      confirmButtonText: 'Close',
      confirmButtonColor: '#4f46e5'
    });
  } catch(e) {
    Swal.fire('Error', 'Unable to fetch reschedule history.', 'error');
  }
}

function viewBatchDetails(batch) {
  const sessionLabel = batch.session_name || 'FN';
  const sessionColor = sessionLabel === 'AN' ? '#92400e' : '#1d4ed8';
  const sessionBg    = sessionLabel === 'AN' ? '#fde68a' : '#dbeafe';
  const content = `
    <div style="text-align: left; font-size: 14px; line-height: 1.6;">
      <table style="width: 100%; border-collapse: collapse;">
        <tr><td style="padding: 6px; font-weight: bold; width: 140px; border-bottom:1px solid #f1f5f9;">Batch No:</td><td style="padding: 6px; border-bottom:1px solid #f1f5f9;">${batch.batch_number}</td></tr>
        <tr><td style="padding: 6px; font-weight: bold; border-bottom:1px solid #f1f5f9;">Token:</td><td style="padding: 6px; border-bottom:1px solid #f1f5f9;">${batch.batch_token || 'N/A'}</td></tr>
        <tr><td style="padding: 6px; font-weight: bold; border-bottom:1px solid #f1f5f9;">Training Date:</td><td style="padding: 6px; border-bottom:1px solid #f1f5f9;">${batch.training_date}</td></tr>
        <tr><td style="padding: 6px; font-weight: bold; border-bottom:1px solid #f1f5f9;">Session:</td><td style="padding: 6px; border-bottom:1px solid #f1f5f9;"><span style="background:${sessionBg};color:${sessionColor};padding:2px 10px;border-radius:6px;font-weight:900;font-size:13px;">${sessionLabel}</span></td></tr>
        <tr><td style="padding: 6px; font-weight: bold; border-bottom:1px solid #f1f5f9;">Time:</td><td style="padding: 6px; border-bottom:1px solid #f1f5f9;">${batch.time_from || ''} - ${batch.time_to || ''}</td></tr>
        <tr><td style="padding: 6px; font-weight: bold; border-bottom:1px solid #f1f5f9;">Location/Venue:</td><td style="padding: 6px; border-bottom:1px solid #f1f5f9;">${batch.venue_name || 'N/A'}</td></tr>
        <tr><td style="padding: 6px; font-weight: bold; border-bottom:1px solid #f1f5f9;">Language:</td><td style="padding: 6px; border-bottom:1px solid #f1f5f9;">${batch.language_name || 'N/A'}</td></tr>
        <tr><td style="padding: 6px; font-weight: bold; border-bottom:1px solid #f1f5f9;">Training Type:</td><td style="padding: 6px; border-bottom:1px solid #f1f5f9;">${batch.training_type || 'N/A'}</td></tr>
        <tr><td style="padding: 6px; font-weight: bold; border-bottom:1px solid #f1f5f9;">Instructor/Trainer:</td><td style="padding: 6px; border-bottom:1px solid #f1f5f9;">${batch.instructor_name || 'Auto / Not assigned'}</td></tr>
        <tr><td style="padding: 6px; font-weight: bold; border-bottom:1px solid #f1f5f9;">Capacity:</td><td style="padding: 6px; border-bottom:1px solid #f1f5f9;">${batch.capacity || 0}</td></tr>
        <tr><td style="padding: 6px; font-weight: bold; border-bottom:1px solid #f1f5f9;">Emergency Seats:</td><td style="padding: 6px; border-bottom:1px solid #f1f5f9;">${batch.emergency_seats || 0}</td></tr>
        <tr><td style="padding: 6px; font-weight: bold; border-bottom:1px solid #f1f5f9;">Workers Assigned:</td><td style="padding: 6px; border-bottom:1px solid #f1f5f9;">${batch.total_workers || 0}</td></tr>
        <tr><td style="padding: 6px; font-weight: bold;">Status:</td><td style="padding: 6px;">${batch.status || 'N/A'}</td></tr>
      </table>
    </div>
  `;
  Swal.fire({
    title: 'Batch Details',
    html: content,
    width: '500px',
    confirmButtonText: 'Close',
    confirmButtonColor: '#4f46e5'
  });
}

// ── (e/f) AJAX-based status toggle — fixes multi-batch bug caused by form-in-table HTML issue ──
function confirmStatusToggle(btn) {
  const batchId     = btn.dataset.batchId;
  const batchNumber = btn.dataset.batchNumber;
  const isActive    = btn.dataset.isActive === '1';
  const newStatus   = btn.dataset.newStatus;
  const workerCount = parseInt(btn.dataset.workerCount || '0', 10);
  const actionText  = isActive ? 'Inactivate' : 'Activate';

  // (g) Block inactivation if workers assigned
  if (isActive && workerCount > 0) {
    Swal.fire({
      icon: 'error',
      title: 'Action Blocked',
      text: 'This batch cannot be inactivated because it is already scheduled for training.',
      confirmButtonColor: '#ef4444'
    });
    return;
  }

  // (f) Confirmation with batch number
  Swal.fire({
    title: 'Are you sure?',
    html: `Do you want to <strong>${actionText.toLowerCase()}</strong> Batch No <strong>${batchNumber}</strong>?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: isActive ? '#f59e0b' : '#10b981',
    cancelButtonColor: '#64748b',
    confirmButtonText: `Yes, ${actionText} it!`,
    cancelButtonText: 'Cancel'
  }).then(async (result) => {
    if (!result.isConfirmed) return;

    // (e) Use AJAX to avoid form-in-table HTML bug
    try {
      const formData = new FormData();
      formData.append('batch_id', batchId);
      formData.append('status', newStatus);

      const res = await fetch('training_class_master.php?action=toggle_batch_status', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();

      if (data.success) {
        Swal.fire({
          icon: 'success',
          title: actionText + 'd!',
          text: `Batch No ${batchNumber} has been ${actionText.toLowerCase()}d successfully.`,
          confirmButtonColor: '#1e3a8a'
        }).then(() => {
          window.location.reload();
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: data.message || 'Failed to update batch status.',
          confirmButtonColor: '#ef4444'
        });
      }
    } catch (e) {
      Swal.fire('Error', 'Unable to update batch status. Please try again.', 'error');
    }
  });
}

// Scroll to recent-batches on hash
if (window.location.hash === '#recent-batches') {
  const el = document.getElementById('recent-batches');
  if (el) {
    setTimeout(() => {
      el.scrollIntoView({ behavior: 'smooth' });
    }, 300);
  }
}
</script>
<?php }
renderLayout('Training Class Master', 'renderContent', $role, $name);
?>
