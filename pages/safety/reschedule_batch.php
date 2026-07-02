<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';
require_once __DIR__ . '/../../include/safety_training_control.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Safety Officer';
clms_safety_ensure_control_schema($conn);

$batchId = (int)($_GET['batch_id'] ?? 0);
if (!$batchId && isset($_POST['batch_id'])) {
    $batchId = (int)$_POST['batch_id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (($_POST['batch_action'] ?? '') === 'reschedule') {
            $result = clms_safety_reschedule_batch($conn, $batchId, $_POST, (int)($_SESSION['user_id'] ?? 0));
            $_SESSION['success'] = 'Batch ' . $result['batch_number'] . ' rescheduled to ' . date('d M Y', strtotime($result['training_date'])) . ' (' . $result['session_name'] . ').';
            header('Location: reschedule_batch.php?batch_id=' . $batchId . '&rescheduled=1');
            exit;
        }
    } catch (Throwable $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: reschedule_batch.php?batch_id=' . $batchId);
        exit;
    }
}

function renderContent() {
    global $conn, $batchId;

    // Load all batches (not just recent 20)
    $allBatches = db_fetch_all($conn, "
        SELECT b.id, b.batch_number, b.batch_token, b.training_date, b.session_name,
               b.venue_name, b.language_name, b.training_type, b.status,
               COALESCE(wc.total_workers, 0) AS total_workers
        FROM training_class_batches b
        LEFT JOIN (
            SELECT batch_id, COUNT(*) total_workers
            FROM training_batch_workers
            WHERE ticked = 1
            GROUP BY batch_id
        ) wc ON wc.batch_id = b.id
        ORDER BY b.training_date DESC, b.id DESC
        LIMIT 100
    ");

    $selectedBatch = $batchId
        ? db_single($conn, "SELECT * FROM training_class_batches WHERE id = ? LIMIT 1", 'i', [$batchId])
        : null;

    $venues = clms_safety_active_rows(db_fetch_all($conn, "SELECT id, venue_code, venue_name, COALESCE(seats, 35) seats, from_date, to_date, status FROM training_venue_masters ORDER BY venue_name ASC"));
    $instructors = clms_safety_active_rows(db_fetch_all($conn, "SELECT id, instructor_code, instructor_name, from_date, to_date, status FROM safety_instructor_masters ORDER BY instructor_name ASC"));

    $successMsg = $_SESSION['success'] ?? '';
    $errorMsg   = $_SESSION['error'] ?? '';
    unset($_SESSION['success'], $_SESSION['error']);
?>
<div class="content-header safety-header">
  <div>
    <h2 class="page-title"><i class="fas fa-calendar-day"></i> Reschedule Batch</h2>
    <p class="page-subtitle">Select any batch and reschedule it to a new date, location and session.</p>
  </div>
  <a href="training_class_master.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Class Master</a>
</div>

<?php if ($successMsg): ?>
<div class="alert alert-success" style="margin-bottom:16px"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
<div class="alert alert-danger" style="margin-bottom:16px"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>

<!-- BATCH LIST SECTION -->
<div class="card glass" style="margin-bottom:20px">
  <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
    <div class="card-title"><i class="fas fa-list"></i> Select a Batch to Reschedule</div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <input type="text" id="batchSearch" class="form-control" placeholder="Search batch..." style="height:34px;width:180px" oninput="filterBatches()">
      <select id="statusFilter" class="form-control" style="height:34px;width:130px" onchange="filterBatches()">
        <option value="">All Status</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
      <select id="langFilter" class="form-control" style="height:34px;width:130px" onchange="filterBatches()">
        <option value="">All Languages</option>
        <?php
        $langs = array_unique(array_column($allBatches, 'language_name'));
        foreach ($langs as $l): ?>
        <option value="<?= htmlspecialchars(strtolower($l)) ?>"><?= htmlspecialchars($l) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="card-body" style="padding:0;overflow-x:auto">
    <table class="data-table" id="batchTable">
      <thead>
        <tr>
          <th>SNo</th>
          <th>Batch No</th>
          <th>Date</th>
          <th>Session</th>
          <th>Location</th>
          <th>Language</th>
          <th>Workers</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody id="batchTableBody">
        <?php $sno = 1; foreach ($allBatches as $b):
          $bActive = in_array(strtolower((string)($b['status'] ?? '')), ['open','scheduled','active'], true);
          $isPast  = $b['training_date'] < date('Y-m-d');
          $rowClass = ((int)$b['id'] === $batchId) ? 'selected-row' : '';
          $langLower = strtolower($b['language_name'] ?? '');
          $statusLower = $bActive ? 'active' : 'inactive';
        ?>
        <tr class="batch-row <?= $rowClass ?>"
            data-lang="<?= htmlspecialchars($langLower) ?>"
            data-status="<?= $statusLower ?>"
            data-search="<?= htmlspecialchars(strtolower($b['batch_number'] . ' ' . $b['venue_name'] . ' ' . $b['language_name'])) ?>">
          <td><?= $sno++ ?></td>
          <td><strong><?= htmlspecialchars($b['batch_number']) ?></strong><div style="font-size:10px;color:var(--text-muted)">Token: <?= htmlspecialchars($b['batch_token']) ?></div></td>
          <td><?= date('d M Y', strtotime($b['training_date'])) ?></td>
          <td><span class="session-badge <?= ($b['session_name'] ?? 'FN') === 'AN' ? 'session-badge-an' : 'session-badge-fn' ?>"><?= htmlspecialchars($b['session_name'] ?? 'FN') ?></span></td>
          <td><?= htmlspecialchars($b['venue_name']) ?></td>
          <td><?= htmlspecialchars($b['language_name']) ?></td>
          <td><?= (int)$b['total_workers'] ?> workers</td>
          <td><span class="badge <?= $bActive ? 'badge-success' : 'badge-gray' ?>"><?= $bActive ? 'Active' : 'Inactive' ?></span></td>
          <td>
            <a href="reschedule_batch.php?batch_id=<?= (int)$b['id'] ?>"
               class="btn btn-sm <?= ((int)$b['id'] === $batchId) ? 'btn-primary' : 'btn-warning' ?>">
              <i class="fas fa-calendar-day"></i> <?= ((int)$b['id'] === $batchId) ? 'Selected' : 'Reschedule' ?>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($allBatches)): ?>
        <tr><td colspan="9" style="text-align:center;padding:24px;color:var(--text-muted)">No batches found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- RESCHEDULE FORM -->
<?php if ($selectedBatch): ?>
<section class="card glass reschedule-card" style="max-width:860px">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-edit"></i> Reschedule: <strong><?= htmlspecialchars($selectedBatch['batch_number']) ?></strong></div>
    <div style="font-size:12px;color:var(--text-muted)">
      Current: <?= date('d M Y', strtotime($selectedBatch['training_date'])) ?>
      &nbsp;|&nbsp; <?= htmlspecialchars($selectedBatch['session_name'] ?? 'FN') ?>
      &nbsp;|&nbsp; <?= htmlspecialchars($selectedBatch['venue_name']) ?>
      &nbsp;|&nbsp; <?= htmlspecialchars($selectedBatch['language_name']) ?>
    </div>
  </div>
  <div class="card-body">
    <p class="page-subtitle" style="margin-bottom:16px">
      <i class="fas fa-info-circle"></i>
      Assigned workers, tokens and attempts will remain with this batch after rescheduling.
    </p>
    <form method="post" class="reschedule-form" onsubmit="return confirm('Reschedule batch <?= htmlspecialchars($selectedBatch['batch_number']) ?> and all assigned workers?');">
      <input type="hidden" name="batch_action" value="reschedule">
      <input type="hidden" name="batch_id" value="<?= (int)$selectedBatch['id'] ?>">
      <div class="reschform-grid">
        <label>New Date <span class="required">*</span>
          <input class="form-control" type="date" name="reschedule_date"
                 min="<?= date('Y-m-d') ?>"
                 value="<?= htmlspecialchars($selectedBatch['training_date']) ?>"
                 required>
        </label>
        <label>Location <span class="required">*</span>
          <select class="form-control" name="reschedule_venue_id" required>
            <?php foreach ($venues as $venue): ?>
            <option value="<?= (int)$venue['id'] ?>" <?= (int)$venue['id'] === (int)($selectedBatch['venue_id'] ?? 0) ? 'selected' : '' ?>>
              <?= htmlspecialchars(($venue['venue_code'] ? $venue['venue_code'] . ' - ' : '') . $venue['venue_name']) ?> (<?= (int)$venue['seats'] ?> seats)
            </option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Session <span class="required">*</span>
          <select class="form-control" name="reschedule_session_name">
            <option value="FN" <?= ($selectedBatch['session_name'] ?? '') === 'FN' ? 'selected' : '' ?>>FN (Forenoon)</option>
            <option value="AN" <?= ($selectedBatch['session_name'] ?? '') === 'AN' ? 'selected' : '' ?>>AN (Afternoon)</option>
          </select>
        </label>
        <label>Time From
          <input class="form-control" type="time" name="reschedule_time_from"
                 value="<?= htmlspecialchars(substr((string)($selectedBatch['time_from'] ?: ''), 0, 5)) ?>">
        </label>
        <label>Time To
          <input class="form-control" type="time" name="reschedule_time_to"
                 value="<?= htmlspecialchars(substr((string)($selectedBatch['time_to'] ?: ''), 0, 5)) ?>">
        </label>
        <label>Trainer
          <select class="form-control" name="reschedule_instructor_id">
            <option value="">Not assigned</option>
            <?php foreach ($instructors as $instructor): ?>
            <option value="<?= (int)$instructor['id'] ?>" <?= (int)$instructor['id'] === (int)($selectedBatch['instructor_id'] ?? 0) ? 'selected' : '' ?>>
              <?= htmlspecialchars(($instructor['instructor_code'] ? $instructor['instructor_code'] . ' - ' : '') . $instructor['instructor_name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
      <div style="margin-top:20px;display:flex;gap:10px;justify-content:flex-end">
        <a href="reschedule_batch.php" class="btn btn-outline"><i class="fas fa-times"></i> Cancel</a>
        <button type="submit" class="btn btn-warning"><i class="fas fa-calendar-day"></i> Confirm Reschedule</button>
      </div>
    </form>
  </div>
</section>
<?php else: ?>
<div class="alert alert-info" style="max-width:860px">
  <i class="fas fa-hand-point-up"></i> <strong>Select a batch</strong> from the table above by clicking the <strong>Reschedule</strong> button.
</div>
<?php endif; ?>

<style>
.content-header.safety-header{display:flex;justify-content:space-between;align-items:flex-end;gap:14px;margin-bottom:16px}
.reschform-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:14px}
.reschedule-form label,.reschform-grid label{display:flex;flex-direction:column;gap:5px;font-size:12px;font-weight:800;color:#475569}
.required{color:#ef4444}
.form-control{height:38px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px;background:#fff;font-size:13px}
.session-badge{display:inline-flex;align-items:center;justify-content:center;font-size:10px;font-weight:900;border-radius:6px;padding:2px 8px;letter-spacing:.5px}
.session-badge-fn{background:#dbeafe;color:#1d4ed8}
.session-badge-an{background:#fde68a;color:#92400e}
.selected-row{background:rgba(37,99,235,.07) !important}
.selected-row td{border-left:3px solid #2563eb}
.alert-success{background:#dcfce7;color:#166534;border:1px solid #bbf7d0;padding:12px 16px;border-radius:8px;display:flex;align-items:center;gap:8px}
.alert-danger{background:#fee2e2;color:#991b1b;border:1px solid #fecaca;padding:12px 16px;border-radius:8px;display:flex;align-items:center;gap:8px}
.alert-info{background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe;padding:14px 16px;border-radius:8px}
@media(max-width:700px){.reschform-grid{grid-template-columns:1fr}}
</style>
<script>
function filterBatches() {
    const search = document.getElementById('batchSearch').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const lang   = document.getElementById('langFilter').value;
    document.querySelectorAll('#batchTableBody .batch-row').forEach(row => {
        const matchSearch = !search || row.dataset.search.includes(search);
        const matchStatus = !status || row.dataset.status === status;
        const matchLang   = !lang   || row.dataset.lang === lang;
        row.style.display = (matchSearch && matchStatus && matchLang) ? '' : 'none';
    });
}

// Auto-scroll to form if batch selected
<?php if ($batchId): ?>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.reschedule-card');
    if (form) form.scrollIntoView({behavior:'smooth', block:'start'});
});
<?php endif; ?>
</script>
<?php
}

renderLayout('Reschedule Batch', 'renderContent', $role, $name);
