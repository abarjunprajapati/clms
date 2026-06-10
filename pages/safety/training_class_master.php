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
        if (($_POST['batch_action'] ?? '') === 'delete') {
            $deletedBatch = clms_safety_delete_batch($conn, (int)($_POST['batch_id'] ?? 0));
            $_SESSION['success'] = 'Batch ' . $deletedBatch . ' deleted.';
            header('Location: training_class_master.php');
            exit;
        }

        $result = clms_safety_create_batch($conn, $_POST, (int)($_SESSION['user_id'] ?? 0));
        $_SESSION['success'] = 'Batch ' . $result['batch_number'] . ' created. Select workers and schedule the class.';
        $returnUrl = 'training_schedule.php?batch_id=' . (int)($result['batch_id'] ?? 0);
        if (!empty($_POST['source_request_id'])) {
            $returnUrl .= '&request_id=' . (int)$_POST['source_request_id'];
        }
        header('Location: ' . $returnUrl);
        exit;
    } catch (Throwable $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header('Location: training_class_master.php');
    exit;
}

function renderContent() {
    global $conn;
    $venues = clms_safety_active_rows(db_fetch_all($conn, "SELECT id, venue_code, venue_name, COALESCE(seats, 35) seats, status FROM training_venue_masters ORDER BY venue_name ASC"));
    $languages = clms_safety_active_rows(db_fetch_all($conn, "SELECT id, language_name, status FROM training_language_masters ORDER BY sort_order ASC, language_name ASC"));
    $types = clms_safety_active_rows(clms_get_training_type_rows($conn, false));
    $instructors = clms_safety_active_rows(db_fetch_all($conn, "SELECT id, instructor_code, instructor_name, status FROM safety_instructor_masters ORDER BY instructor_name ASC"));
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
?>
<div class="content-header"><div><h2 class="page-title"><i class="fas fa-calendar-plus"></i> Training Class Master</h2><p class="page-subtitle">Create batch token, auto-tick workers by language/date order and selected location seats.</p></div></div>
<?php if ($prefillRequest): ?>
<div class="alert alert-info">
  <i class="fas fa-info-circle"></i>
  <div>Assigning batch from request #<?= (int)$prefillRequest['id'] ?> for <?= htmlspecialchars($prefillRequest['worker_name']) ?>. To add this worker into an existing same-training batch, use <strong>Assign Workers</strong> on the batch below.</div>
</div>
<?php endif; ?>
<section class="card glass">
  <div class="card-header"><div class="card-title">Schedule Batch</div></div>
  <div class="card-body">
    <form method="post" class="class-form">
      <input type="hidden" name="source_request_id" value="<?= (int)$prefillRequestId ?>">
      <label>Training Date<input class="form-control" type="date" name="training_date" id="trainingDate" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required></label>
      <label>Training Location<select class="form-control" name="venue_id" id="venueSelect" onchange="updateSeats()" required><option value="">Select</option><?php foreach($venues as $v): ?><option value="<?= (int)$v['id'] ?>" data-seats="<?= (int)$v['seats'] ?>"><?= htmlspecialchars(($v['venue_code'] ? $v['venue_code'].' - ' : '').$v['venue_name']) ?></option><?php endforeach; ?></select></label>
      <label>Slots<input class="form-control" id="slotBox" value="" readonly></label>
      <label>Emergency Seats<input class="form-control" type="number" name="emergency_seats" id="emergencySeats" min="0" value="5" oninput="updateSeats()"></label>
      <label>Language<select class="form-control" name="language_id" id="languageSelect" required><option value="">Select</option><?php foreach($languages as $l): ?><option value="<?= (int)$l['id'] ?>" <?= strtolower(trim($l['language_name'])) === $prefillLanguage ? 'selected' : '' ?>><?= htmlspecialchars($l['language_name']) ?></option><?php endforeach; ?></select></label>
      <label>Session<select class="form-control" name="session_name" id="sessionSelect"><option value="FN">FN</option><option value="AN">AN</option></select></label>
      <label>Time From<input class="form-control" type="time" name="time_from" id="timeFrom"></label>
      <label>Time To<input class="form-control" type="time" name="time_to" id="timeTo"></label>
      <label>Training Type<select class="form-control" name="training_type_id" id="trainingTypeSelect" onchange="syncExistingBatch()" required><option value="">Select</option><?php foreach($types as $t): ?><option value="<?= (int)$t['id'] ?>" data-type-name="<?= htmlspecialchars($t['type_name']) ?>" <?= strtolower(trim($t['type_name'])) === $prefillType ? 'selected' : '' ?>><?= htmlspecialchars($t['type_name']) ?></option><?php endforeach; ?></select></label>
      <label>Trainer<select class="form-control" name="instructor_id" id="trainerSelect"><option value="">Auto / Not assigned</option><?php foreach($instructors as $i): ?><option value="<?= (int)$i['id'] ?>"><?= htmlspecialchars(($i['instructor_code'] ? $i['instructor_code'].' - ' : '').$i['instructor_name']) ?></option><?php endforeach; ?></select></label>
      <div id="existingBatchHint" class="existing-batch-hint" style="display:none"></div>
      <div class="actions">
        <button class="btn btn-outline" name="save_mode" value="draft"><i class="fas fa-file"></i> Save as Draft</button>
        <button class="btn btn-primary" name="save_mode" value="schedule"><i class="fas fa-check"></i> Submit Batch</button>
      </div>
    </form>
  </div>
</section>

<section class="card glass" style="margin-top:18px">
  <div class="card-header"><div class="card-title">Recent Batches</div><a href="training_batch_report.php" class="btn btn-sm btn-primary">Reports</a></div>
  <div class="card-body" style="padding:0">
    <table class="data-table"><thead><tr><th>Batch No</th><th>Date</th><th>Location</th><th>Language</th><th>Workers</th><th>Status</th><th>Action</th></tr></thead><tbody>
    <?php foreach($batches as $b): $emg = min((int)($b['emergency_seats'] ?? 0), (int)$b['capacity']); $regular = max(0, (int)$b['capacity'] - $emg); $canDelete = (int)$b['total_workers'] === 0; $assignUrl = 'training_schedule.php?batch_id=' . (int)$b['id'] . ($prefillRequestId ? '&request_id=' . (int)$prefillRequestId : ''); ?><tr><td><strong><?= htmlspecialchars($b['batch_number']) ?></strong><div style="font-size:11px;color:var(--text-muted)">Token: <?= htmlspecialchars($b['batch_token']) ?></div></td><td><?= date('d M Y', strtotime($b['training_date'])) ?></td><td><?= htmlspecialchars($b['venue_name']) ?></td><td><?= htmlspecialchars($b['language_name']) ?></td><td><?= (int)$b['total_workers'] ?> / <?= (int)$b['capacity'] ?><div style="font-size:11px;color:var(--text-muted)"><?= $regular ?> regular + <?= $emg ?> emergency</div></td><td><span class="badge badge-info"><?= htmlspecialchars(ucfirst($b['status'])) ?></span></td><td><div class="row-actions"><a class="btn btn-sm btn-primary" href="<?= htmlspecialchars($assignUrl) ?>">Assign Workers</a><a class="btn btn-sm btn-outline" href="training_batch_report.php?batch_id=<?= (int)$b['id'] ?>">Report</a><?php if ($canDelete): ?><form method="post" style="margin:0" onsubmit="return confirm('Delete empty batch <?= htmlspecialchars($b['batch_number'], ENT_QUOTES) ?>?');"><input type="hidden" name="batch_action" value="delete"><input type="hidden" name="batch_id" value="<?= (int)$b['id'] ?>"><button class="btn btn-sm btn-danger" type="submit">Delete</button></form><?php endif; ?></div></td></tr><?php endforeach; ?>
    </tbody></table>
  </div>
</section>
<style>.class-form{display:grid;grid-template-columns:repeat(3,minmax(180px,1fr));gap:12px}.class-form label{display:flex;flex-direction:column;gap:5px;font-size:12px;font-weight:800;color:#475569}.form-control{height:38px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px}.actions{grid-column:1/-1;display:flex;gap:10px;justify-content:flex-end}.row-actions{display:flex;gap:8px;flex-wrap:wrap}.existing-batch-hint{grid-column:1/-1;border:1px solid #bfdbfe;background:#eff6ff;border-radius:8px;padding:12px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}.existing-batch-hint strong{color:#1e40af}.existing-batch-hint span{font-size:12px;color:#475569}@media(max-width:900px){.class-form{grid-template-columns:1fr}.actions{justify-content:stretch;flex-direction:column}}</style>
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
  ];
}, $batches), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

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
  const hint = document.getElementById('existingBatchHint');
  const matches = existingBatches.filter(batch => norm(batch.training_type) === norm(typeName));
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
      <span>${batch.training_date} | ${batch.venue_name} | ${batch.language_name} | ${batch.total_workers}/${batch.capacity} workers, ${available} seats available.</span>
    </div>
    <a class="btn btn-sm btn-primary" href="training_schedule.php?batch_id=${batch.id}<?= $prefillRequestId ? '&request_id=' . (int)$prefillRequestId : '' ?>">Add Workers to Existing Batch</a>
  `;
  hint.style.display = 'flex';
}

updateSeats();
syncExistingBatch();
</script>
<?php }
renderLayout('Training Class Master', 'renderContent', $role, $name);
?>
