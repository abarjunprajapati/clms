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
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $code = trim($_POST['venue_code'] ?? '');
        $nameValue = trim($_POST['venue_name'] ?? '');
        $fromDate = $_POST['from_date'] ?: date('Y-m-d');
        $toDate = $_POST['to_date'] ?: '9999-12-31';
        if ($code !== '' && $nameValue !== '') {
            if ($id > 0) {
                db_execute(
                    $conn,
                    "UPDATE training_venue_masters
                     SET venue_code = ?, venue_name = ?, seats = ?, from_date = ?, to_date = ?, status = ?, updated_at = NOW()
                     WHERE id = ?",
                    'ssisssi',
                    array($code, $nameValue, max(1, (int)($_POST['seats'] ?? 35)), $fromDate, $toDate, $_POST['status'] ?? 'active', $id)
                );
            } else {
                db_execute(
                    $conn,
                    "INSERT INTO training_venue_masters (venue_code, venue_name, seats, from_date, to_date, status, created_by, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                     ON DUPLICATE KEY UPDATE venue_code = VALUES(venue_code), venue_name = VALUES(venue_name), seats = VALUES(seats), from_date = VALUES(from_date), to_date = VALUES(to_date), status = VALUES(status), updated_at = NOW()",
                    'ssisssi',
                    array($code, $nameValue, max(1, (int)($_POST['seats'] ?? 35)), $fromDate, $toDate, $_POST['status'] ?? 'active', (int)($_SESSION['user_id'] ?? 0))
                );
            }
        }
    } elseif ($action === 'delete') {
        db_execute(
            $conn,
            "DELETE FROM training_venue_masters WHERE id = ?",
            'i',
            array((int)($_POST['id'] ?? 0))
        );
    }
    header('Location: training_location_master.php');
    exit;
}

function renderContent() {
    global $conn;
    $rows = db_fetch_all($conn, "SELECT id, venue_code, venue_name, COALESCE(seats, 35) seats, from_date, to_date, status FROM training_venue_masters ORDER BY venue_name ASC");
?>
<div class="content-header"><h2 class="page-title"><i class="fas fa-location-dot"></i> Training Location Master</h2></div>
<section class="card glass"><div class="card-header"><div class="card-title">Location / Seats</div></div><div class="card-body">
  <form method="post" class="master-form" id="locationForm">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" id="venueId">
    <input class="form-control" name="venue_code" id="venueCode" placeholder="Code" required>
    <input class="form-control" name="venue_name" placeholder="Safety Room / Hall Name" required>
    <input class="form-control" type="number" name="seats" min="1" value="35" required>
    <input class="form-control" type="date" name="from_date" value="<?= date('Y-m-d') ?>" required>
    <input class="form-control" type="date" name="to_date" value="9999-12-31" required>
    <select class="form-control" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select>
    <button class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
    <button class="btn btn-outline" type="button" onclick="resetLocationForm()">Clear</button>
  </form>
  <table class="data-table"><thead><tr><th>Code</th><th>Name</th><th>Seats</th><th>From Dt</th><th>To Dt</th><th>Status</th><th>Action</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr><td><?= htmlspecialchars($r['venue_code'] ?? '') ?></td><td><strong><?= htmlspecialchars($r['venue_name']) ?></strong></td><td><?= (int)$r['seats'] ?></td><td><?= !empty($r['from_date']) ? date('d/m/Y', strtotime($r['from_date'])) : '-' ?></td><td><?= !empty($r['to_date']) ? date('d/m/Y', strtotime($r['to_date'])) : '-' ?></td><td><span class="badge <?= strtolower($r['status']) === 'active' ? 'badge-success' : 'badge-gray' ?>"><?= htmlspecialchars(ucfirst($r['status'])) ?></span></td><td><div class="row-actions"><button class="btn btn-sm btn-outline" type="button" onclick='editLocation(<?= json_encode($r, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Edit</button><form method="post" onsubmit="return confirm('Delete this location?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form></div></td></tr><?php endforeach; ?>
  </tbody></table>
</div></section>
<style>.master-form{display:grid;grid-template-columns:120px 1fr 90px 150px 150px 130px auto auto;gap:10px;margin-bottom:16px}.form-control{height:38px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px}.row-actions{display:flex;gap:8px;align-items:center}.row-actions form{margin:0}@media(max-width:1100px){.master-form{grid-template-columns:1fr 1fr}}@media(max-width:700px){.master-form{grid-template-columns:1fr}}</style>
<script>
function editLocation(row){
  const form = document.getElementById('locationForm');
  form.querySelector('[name="id"]').value = row.id || '';
  form.venue_code.value = row.venue_code || '';
  form.venue_name.value = row.venue_name || '';
  form.seats.value = row.seats || 35;
  form.from_date.value = row.from_date || '<?= date('Y-m-d') ?>';
  form.to_date.value = row.to_date || '9999-12-31';
  form.status.value = row.status || 'active';
  form.scrollIntoView({behavior:'smooth', block:'center'});
}
function resetLocationForm(){
  const form = document.getElementById('locationForm');
  form.reset();
  form.querySelector('[name="id"]').value = '';
  form.seats.value = 35;
  form.from_date.value = '<?= date('Y-m-d') ?>';
  form.to_date.value = '9999-12-31';
}
</script>
<?php }
renderLayout('Training Location Master', 'renderContent', $role, $name);
?>
