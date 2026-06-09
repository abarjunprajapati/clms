<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';
require_once __DIR__ . '/../../include/safety_training_control.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Safety Officer';
clms_safety_ensure_control_schema($conn);

function safetyControlRedirect($message, $type = 'success') {
    $_SESSION[$type] = $message;
    header('Location: safety_control_desk.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['safety_action'] ?? '';
    $userId = (int)($_SESSION['user_id'] ?? 0);

    try {
        if ($action === 'add_location') {
            $nameValue = trim((string)($_POST['venue_name'] ?? ''));
            if ($nameValue === '') throw new RuntimeException('Training location name is required.');
            db_execute(
                $conn,
                "INSERT INTO training_venue_masters (venue_code, venue_name, seats, status, created_by, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE venue_code = VALUES(venue_code), seats = VALUES(seats), status = VALUES(status), updated_at = NOW()",
                'ssisi',
                array(trim($_POST['venue_code'] ?? ''), $nameValue, max(1, (int)($_POST['seats'] ?? 35)), $_POST['status'] ?? 'active', $userId)
            );
            safetyControlRedirect('Training location saved.');
        }

        if ($action === 'add_instructor') {
            $nameValue = trim((string)($_POST['instructor_name'] ?? ''));
            if ($nameValue === '') throw new RuntimeException('Instructor name is required.');
            db_execute(
                $conn,
                "INSERT INTO safety_instructor_masters (instructor_code, instructor_name, mobile, email, status, created_by, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE instructor_code = VALUES(instructor_code), mobile = VALUES(mobile), email = VALUES(email), status = VALUES(status), updated_at = NOW()",
                'sssssi',
                array(trim($_POST['instructor_code'] ?? ''), $nameValue, trim($_POST['mobile'] ?? ''), trim($_POST['email'] ?? ''), $_POST['status'] ?? 'active', $userId)
            );
            safetyControlRedirect('Instructor saved.');
        }

        if ($action === 'add_language') {
            $language = trim((string)($_POST['language_name'] ?? ''));
            if ($language === '') throw new RuntimeException('Training language is required.');
            db_execute(
                $conn,
                "INSERT INTO training_language_masters (language_name, status, created_by, created_at, updated_at)
                 VALUES (?, ?, ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = NOW()",
                'ssi',
                array($language, $_POST['status'] ?? 'active', $userId)
            );
            safetyControlRedirect('Training language saved.');
        }

        if ($action === 'add_fee') {
            $source = strtoupper(trim((string)($_POST['fee_source'] ?? '')));
            if (!in_array($source, array('PWO', 'PO', 'SO'), true)) throw new RuntimeException('Fee source must be PWO, PO or SO.');
            db_execute(
                $conn,
                "INSERT INTO training_fee_masters (fee_source, amount, status, created_by, created_at, updated_at)
                 VALUES (?, ?, 'active', ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE amount = VALUES(amount), status = 'active', updated_at = NOW()",
                'sdi',
                array($source, max(0, (float)($_POST['amount'] ?? 0)), $userId)
            );
            safetyControlRedirect('Training fee saved.');
        }

        if ($action === 'add_type') {
            clms_add_training_type($conn, trim((string)($_POST['type_name'] ?? '')), (int)($_POST['duration_hours'] ?? 8), (int)($_POST['pass_mark'] ?? 60));
            safetyControlRedirect('Training type saved.');
        }

        if ($action === 'create_batch') {
            $result = clms_safety_create_batch($conn, $_POST, $userId);
            safetyControlRedirect('Batch ' . $result['batch_number'] . ' created. Open Training Schedule to assign workers.');
        }
    } catch (Throwable $e) {
        safetyControlRedirect($e->getMessage(), 'error');
    }
}

function renderContent() {
    global $conn;

    $venues = db_fetch_all($conn, "SELECT id, venue_code, venue_name, COALESCE(seats, 35) seats, status FROM training_venue_masters ORDER BY status ASC, venue_name ASC");
    $activeVenues = clms_safety_active_rows($venues);
    $instructors = db_fetch_all($conn, "SELECT id, instructor_code, instructor_name, mobile, email, status FROM safety_instructor_masters ORDER BY status ASC, instructor_name ASC");
    $activeInstructors = clms_safety_active_rows($instructors);
    $languages = db_fetch_all($conn, "SELECT id, language_name, status FROM training_language_masters ORDER BY sort_order ASC, language_name ASC");
    $activeLanguages = clms_safety_active_rows($languages);
    $trainingTypes = clms_get_training_type_rows($conn, false);
    $activeTrainingTypes = clms_safety_active_rows($trainingTypes);
    $feeRows = db_fetch_all($conn, "SELECT fee_source, amount, status FROM training_fee_masters ORDER BY FIELD(fee_source, 'PWO', 'PO', 'SO'), fee_source");
    $recentBatches = db_fetch_all($conn, "
        SELECT b.*, COALESCE(wc.total_workers, 0) total_workers
        FROM training_class_batches b
        LEFT JOIN (
            SELECT batch_id, COUNT(*) total_workers
            FROM training_batch_workers
            WHERE ticked = 1
            GROUP BY batch_id
        ) wc ON wc.batch_id = b.id
        ORDER BY b.created_at DESC, b.id DESC
        LIMIT 8
    ");
?>
<div class="content-header control-page-header">
  <div>
    <h2 class="page-title"><i class="fas fa-sliders"></i> Safety Login Control Desk</h2>
    <p class="page-subtitle">Configure masters and create training batches on this separate page.</p>
  </div>
  <div class="control-actions">
    <a href="dashboard.php" class="btn btn-outline"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="training_schedule.php" class="btn btn-primary"><i class="fas fa-calendar-alt"></i> Training Schedule</a>
  </div>
</div>

<section class="control-note-bar">
  <strong>Rule:</strong> workers are auto-picked by training language and enrolment/request date order, limited by selected location seats. Retest maximum 3 attempts within 30 days.
</section>

<section class="control-grid">
  <form method="post" class="control-box">
    <input type="hidden" name="safety_action" value="add_location">
    <h3><i class="fas fa-location-dot"></i> Training Location Master</h3>
    <div class="mini-row">
      <input name="venue_code" placeholder="Code e.g. CDC">
      <input name="venue_name" placeholder="Location name" required>
    </div>
    <div class="mini-row">
      <input type="number" name="seats" min="1" value="35">
      <select name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select>
      <button class="btn btn-sm btn-primary">Save</button>
    </div>
  </form>

  <form method="post" class="control-box">
    <input type="hidden" name="safety_action" value="add_instructor">
    <h3><i class="fas fa-person-chalkboard"></i> Instructor Master</h3>
    <div class="mini-row">
      <input name="instructor_code" placeholder="Code">
      <input name="instructor_name" placeholder="Instructor name" required>
    </div>
    <div class="mini-row">
      <input name="mobile" placeholder="Mobile">
      <input type="email" name="email" placeholder="Email">
      <select name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select>
      <button class="btn btn-sm btn-primary">Save</button>
    </div>
  </form>

  <form method="post" class="control-box">
    <input type="hidden" name="safety_action" value="add_type">
    <h3><i class="fas fa-list-check"></i> Training Type Master</h3>
    <div class="mini-row">
      <input name="type_name" placeholder="HSE Induction Training" required>
      <input type="number" name="duration_hours" min="1" value="8">
      <input type="number" name="pass_mark" min="0" value="60">
      <button class="btn btn-sm btn-primary">Save</button>
    </div>
  </form>

  <form method="post" class="control-box">
    <input type="hidden" name="safety_action" value="add_fee">
    <h3><i class="fas fa-indian-rupee-sign"></i> Training Fee Master</h3>
    <div class="mini-row">
      <select name="fee_source"><option>PWO</option><option>PO</option><option>SO</option></select>
      <input type="number" step="0.01" min="0" name="amount" value="0.00" required>
      <button class="btn btn-sm btn-primary">Save</button>
    </div>
    <div class="fee-strip">
      <?php foreach ($feeRows as $fee): ?>
        <span><?= htmlspecialchars($fee['fee_source']) ?>: <?= number_format((float)$fee['amount'], 2) ?></span>
      <?php endforeach; ?>
    </div>
  </form>

  <form method="post" class="control-box">
    <input type="hidden" name="safety_action" value="add_language">
    <h3><i class="fas fa-language"></i> Training Language Master</h3>
    <div class="mini-row">
      <input name="language_name" placeholder="Malayalam / English / Tamil" required>
      <select name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select>
      <button class="btn btn-sm btn-primary">Save</button>
    </div>
  </form>
</section>

<section class="class-panel">
  <form method="post">
    <input type="hidden" name="safety_action" value="create_batch">
    <div class="class-panel-head">
      <h3><i class="fas fa-calendar-plus"></i> Training Class Master</h3>
      <span>Submit creates batch no and report. Save Draft keeps it without worker scheduling.</span>
    </div>
    <div class="class-grid">
      <label>Training Date<input type="date" name="training_date" min="<?= date('Y-m-d') ?>" required></label>
      <label>Training Location
        <select name="venue_id" id="controlVenue" onchange="updateSeatHint()" required>
          <option value="">Select</option>
          <?php foreach ($activeVenues as $venue): ?>
            <option value="<?= (int)$venue['id'] ?>" data-seats="<?= (int)($venue['seats'] ?? 35) ?>"><?= htmlspecialchars(($venue['venue_code'] ? $venue['venue_code'] . ' - ' : '') . $venue['venue_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Slots<input id="seatHint" value="" readonly></label>
      <label>Language
        <select name="language_id" required>
          <option value="">Select</option>
          <?php foreach ($activeLanguages as $language): ?>
            <option value="<?= (int)$language['id'] ?>"><?= htmlspecialchars($language['language_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Session<select name="session_name"><option value="FN">FN</option><option value="AN">AN</option></select></label>
      <label>Time From<input type="time" name="time_from"></label>
      <label>Time To<input type="time" name="time_to"></label>
      <label>Training Type
        <select name="training_type_id" required>
          <option value="">Select</option>
          <?php foreach ($activeTrainingTypes as $type): ?>
            <option value="<?= (int)$type['id'] ?>"><?= htmlspecialchars($type['type_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Trainer
        <select name="instructor_id">
          <option value="">Auto / Not assigned</option>
          <?php foreach ($activeInstructors as $instructor): ?>
            <option value="<?= (int)$instructor['id'] ?>"><?= htmlspecialchars(($instructor['instructor_code'] ? $instructor['instructor_code'] . ' - ' : '') . $instructor['instructor_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>
    <div class="class-actions">
      <button class="btn btn-outline" name="save_mode" value="draft"><i class="fas fa-file"></i> Save as Draft</button>
      <button class="btn btn-primary" name="save_mode" value="schedule"><i class="fas fa-check"></i> Submit Batch</button>
    </div>
  </form>
</section>

<section class="card glass recent-card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-clock"></i> Recent Batches</div>
    <a href="training_batch_report.php" class="btn btn-sm btn-outline">Reports</a>
  </div>
  <div class="card-body" style="padding:0">
    <table class="data-table">
      <thead><tr><th>Batch No</th><th>Date</th><th>Location</th><th>Language</th><th>Workers</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach ($recentBatches as $batch): ?>
        <tr>
          <td><strong><?= htmlspecialchars($batch['batch_number']) ?></strong><div class="muted">Token: <?= htmlspecialchars($batch['batch_token']) ?></div></td>
          <td><?= date('d M Y', strtotime($batch['training_date'])) ?></td>
          <td><?= htmlspecialchars($batch['venue_name']) ?></td>
          <td><?= htmlspecialchars($batch['language_name']) ?></td>
          <td><?= (int)$batch['total_workers'] ?> / <?= (int)$batch['capacity'] ?></td>
          <td><span class="badge badge-info"><?= htmlspecialchars(ucfirst($batch['status'])) ?></span></td>
          <td><a class="btn btn-sm btn-primary" href="training_schedule.php?batch_id=<?= (int)$batch['id'] ?>">Open Schedule</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($recentBatches)): ?>
        <tr><td colspan="7" style="text-align:center;padding:24px;color:var(--text-muted)">No batches created yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<style>
  .control-page-header{display:flex;justify-content:space-between;align-items:flex-end;gap:14px;margin-bottom:14px}
  .control-actions,.class-actions{display:flex;gap:8px;flex-wrap:wrap}
  .control-note-bar{border:1px solid #dbeafe;background:#eff6ff;color:#1e3a8a;border-radius:8px;padding:12px 14px;margin-bottom:14px;font-size:12px}
  .control-grid{display:grid;grid-template-columns:repeat(2,minmax(280px,1fr));gap:12px;margin-bottom:14px}
  .control-box,.class-panel{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:12px}
  .control-box h3,.class-panel h3{margin:0 0 10px;display:flex;align-items:center;gap:8px;font-size:13px;color:#111827}
  .mini-row{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:8px}
  .mini-row input,.mini-row select,.class-grid input,.class-grid select{height:36px;border:1px solid #cbd5e1;border-radius:6px;padding:0 9px;font-size:12px;background:#fff;min-width:0}
  .mini-row input{flex:1 1 120px}.mini-row select{flex:0 0 120px}
  .fee-strip{display:flex;gap:6px;flex-wrap:wrap;margin-top:8px}
  .fee-strip span{font-size:11px;font-weight:800;color:#475569;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:999px;padding:4px 8px}
  .class-panel{margin-bottom:14px}
  .class-panel-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:12px}
  .class-panel-head span{color:#64748b;font-size:12px}
  .class-grid{display:grid;grid-template-columns:repeat(3,minmax(170px,1fr));gap:10px}
  .class-grid label{display:flex;flex-direction:column;gap:5px;font-size:11px;font-weight:800;color:#475569;text-transform:uppercase}
  .class-grid input,.class-grid select{width:100%;text-transform:none;font-weight:600;color:#111827}
  .class-grid input[readonly]{background:#eef2ff;color:#3730a3;border-color:#c7d2fe}
  .class-actions{justify-content:flex-end;margin-top:12px}
  .recent-card{margin-top:14px}
  .muted{font-size:11px;color:#64748b}
  @media(max-width:1000px){.control-grid{grid-template-columns:1fr}.class-grid{grid-template-columns:repeat(2,minmax(170px,1fr))}.control-page-header,.class-panel-head{flex-direction:column;align-items:stretch}}
  @media(max-width:640px){.class-grid{grid-template-columns:1fr}.control-actions .btn,.class-actions .btn{flex:1}}
</style>
<script>
function updateSeatHint() {
  const venue = document.getElementById('controlVenue');
  const selected = venue && venue.options[venue.selectedIndex];
  const seats = selected ? selected.getAttribute('data-seats') : '';
  const hint = document.getElementById('seatHint');
  if (hint) hint.value = seats ? `${seats} seats` : '';
}
updateSeatHint();
</script>
<?php
}

renderLayout('Safety Login Control Desk', 'renderContent', $role, $name);
?>
