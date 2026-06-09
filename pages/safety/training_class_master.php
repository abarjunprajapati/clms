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
        $result = clms_safety_create_batch($conn, $_POST, (int)($_SESSION['user_id'] ?? 0));
        $_SESSION['success'] = 'Batch ' . $result['batch_number'] . ' created. Open Training Schedule to assign workers.';
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
?>
<div class="content-header"><div><h2 class="page-title"><i class="fas fa-calendar-plus"></i> Training Class Master</h2><p class="page-subtitle">Create batch token, auto-tick workers by language/date order and selected location seats.</p></div></div>
<section class="card glass">
  <div class="card-header"><div class="card-title">Schedule Batch</div></div>
  <div class="card-body">
    <form method="post" class="class-form">
      <label>Training Date<input class="form-control" type="date" name="training_date" min="<?= date('Y-m-d') ?>" required></label>
      <label>Training Location<select class="form-control" name="venue_id" id="venueSelect" onchange="updateSeats()" required><option value="">Select</option><?php foreach($venues as $v): ?><option value="<?= (int)$v['id'] ?>" data-seats="<?= (int)$v['seats'] ?>"><?= htmlspecialchars(($v['venue_code'] ? $v['venue_code'].' - ' : '').$v['venue_name']) ?></option><?php endforeach; ?></select></label>
      <label>Slots<input class="form-control" id="slotBox" value="" readonly></label>
      <label>Language<select class="form-control" name="language_id" required><option value="">Select</option><?php foreach($languages as $l): ?><option value="<?= (int)$l['id'] ?>"><?= htmlspecialchars($l['language_name']) ?></option><?php endforeach; ?></select></label>
      <label>Session<select class="form-control" name="session_name"><option value="FN">FN</option><option value="AN">AN</option></select></label>
      <label>Time From<input class="form-control" type="time" name="time_from"></label>
      <label>Time To<input class="form-control" type="time" name="time_to"></label>
      <label>Training Type<select class="form-control" name="training_type_id" required><option value="">Select</option><?php foreach($types as $t): ?><option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['type_name']) ?></option><?php endforeach; ?></select></label>
      <label>Trainer<select class="form-control" name="instructor_id"><option value="">Auto / Not assigned</option><?php foreach($instructors as $i): ?><option value="<?= (int)$i['id'] ?>"><?= htmlspecialchars(($i['instructor_code'] ? $i['instructor_code'].' - ' : '').$i['instructor_name']) ?></option><?php endforeach; ?></select></label>
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
    <table class="data-table"><thead><tr><th>Batch No</th><th>Date</th><th>Location</th><th>Language</th><th>Workers</th><th>Status</th><th>Report</th></tr></thead><tbody>
    <?php foreach($batches as $b): ?><tr><td><strong><?= htmlspecialchars($b['batch_number']) ?></strong><div style="font-size:11px;color:var(--text-muted)">Token: <?= htmlspecialchars($b['batch_token']) ?></div></td><td><?= date('d M Y', strtotime($b['training_date'])) ?></td><td><?= htmlspecialchars($b['venue_name']) ?></td><td><?= htmlspecialchars($b['language_name']) ?></td><td><?= (int)$b['total_workers'] ?> / <?= (int)$b['capacity'] ?></td><td><span class="badge badge-info"><?= htmlspecialchars(ucfirst($b['status'])) ?></span></td><td><a class="btn btn-sm btn-outline" href="training_batch_report.php?batch_id=<?= (int)$b['id'] ?>">Open</a></td></tr><?php endforeach; ?>
    </tbody></table>
  </div>
</section>
<style>.class-form{display:grid;grid-template-columns:repeat(3,minmax(180px,1fr));gap:12px}.class-form label{display:flex;flex-direction:column;gap:5px;font-size:12px;font-weight:800;color:#475569}.form-control{height:38px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px}.actions{grid-column:1/-1;display:flex;gap:10px;justify-content:flex-end}@media(max-width:900px){.class-form{grid-template-columns:1fr}.actions{justify-content:stretch;flex-direction:column}}</style>
<script>function updateSeats(){var s=document.getElementById('venueSelect');var o=s.options[s.selectedIndex];document.getElementById('slotBox').value=o&&o.dataset.seats?o.dataset.seats+' seats':'';}updateSeats();</script>
<?php }
renderLayout('Training Class Master', 'renderContent', $role, $name);
?>
