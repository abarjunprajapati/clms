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
            header('Location: training_schedule.php?batch_id=' . $batchId);
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
    
    $batch = $batchId ? db_single($conn, "SELECT * FROM training_class_batches WHERE id = ? LIMIT 1", 'i', [$batchId]) : null;
    
    if (!$batch) {
        echo '<div class="alert alert-warning">No batch found to reschedule.</div>';
        return;
    }

    $venues = clms_safety_active_rows(db_fetch_all($conn, "SELECT id, venue_code, venue_name, COALESCE(seats, 35) seats, from_date, to_date, status FROM training_venue_masters ORDER BY venue_name ASC"));
    $instructors = clms_safety_active_rows(db_fetch_all($conn, "SELECT id, instructor_code, instructor_name, from_date, to_date, status FROM safety_instructor_masters ORDER BY instructor_name ASC"));

?>
<div class="content-header">
  <div>
    <h2 class="page-title"><i class="fas fa-calendar-day"></i> Reschedule Batch: <?= htmlspecialchars($batch['batch_number']) ?></h2>
    <p class="page-subtitle">Use this when today's training cannot be conducted. Assigned workers, tokens and attempts remain with the same batch.</p>
  </div>
  <a href="training_schedule.php?batch_id=<?= $batchId ?>" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Schedule</a>
</div>

<section class="card glass reschedule-card" style="max-width: 800px;">
  <div class="card-body">
    <form method="post" class="reschedule-form" onsubmit="return confirm('Reschedule this batch and all assigned workers?');">
      <input type="hidden" name="batch_action" value="reschedule">
      <input type="hidden" name="batch_id" value="<?= (int)$batch['id'] ?>">
      <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(250px, 1fr));gap:15px;">
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
      </div>
      <div style="margin-top:20px;text-align:right;">
        <button type="submit" class="btn btn-warning"><i class="fas fa-calendar-day"></i> Reschedule Batch</button>
      </div>
    </form>
  </div>
</section>

<style>
  .content-header{display:flex;justify-content:space-between;align-items:flex-end;gap:14px;margin-bottom:16px}
  .reschedule-form label{display:flex;flex-direction:column;gap:5px;font-size:12px;font-weight:800;color:#475569}
  .form-control{height:38px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px;background:#fff}
</style>
<?php
}

renderLayout('Reschedule Batch', 'renderContent', $role, $name);
