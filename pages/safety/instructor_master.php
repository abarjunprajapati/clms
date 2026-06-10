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
    $action = $_POST['action'] ?? 'save';
    if ($action === 'delete') {
        db_execute($conn, "DELETE FROM safety_instructor_masters WHERE id = ?", 'i', array((int)($_POST['id'] ?? 0)));
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $code = trim($_POST['instructor_code'] ?? '');
        $instructorName = trim($_POST['instructor_name'] ?? '');
        $fromDate = $_POST['from_date'] ?: date('Y-m-d');
        $toDate = $_POST['to_date'] ?: '9999-12-31';
        if ($code !== '' && $instructorName !== '') {
            if ($id > 0) {
                db_execute(
                    $conn,
                    "UPDATE safety_instructor_masters
                     SET instructor_code = ?, instructor_name = ?, mobile = ?, email = ?, from_date = ?, to_date = ?, status = ?, updated_at = NOW()
                     WHERE id = ?",
                    'sssssssi',
                    array($code, $instructorName, trim($_POST['mobile'] ?? ''), trim($_POST['email'] ?? ''), $fromDate, $toDate, $_POST['status'] ?? 'active', $id)
                );
            } else {
                db_execute(
                    $conn,
                    "INSERT INTO safety_instructor_masters (instructor_code, instructor_name, mobile, email, from_date, to_date, status, created_by, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                     ON DUPLICATE KEY UPDATE instructor_name = VALUES(instructor_name), mobile = VALUES(mobile), email = VALUES(email), from_date = VALUES(from_date), to_date = VALUES(to_date), status = VALUES(status), updated_at = NOW()",
                    'sssssssi',
                    array($code, $instructorName, trim($_POST['mobile'] ?? ''), trim($_POST['email'] ?? ''), $fromDate, $toDate, $_POST['status'] ?? 'active', (int)($_SESSION['user_id'] ?? 0))
                );
            }
        }
    }
    header('Location: instructor_master.php');
    exit;
}

function renderContent() {
    global $conn;
    $rows = db_fetch_all($conn, "SELECT * FROM safety_instructor_masters ORDER BY instructor_name ASC");
?>
<div class="content-header"><h2 class="page-title"><i class="fas fa-person-chalkboard"></i> Instructor Master</h2></div>
<section class="card glass"><div class="card-header"><div class="card-title">Instructor Details</div></div><div class="card-body">
  <form method="post" class="master-form" id="instructorForm">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id">
    <input class="form-control" name="instructor_code" placeholder="Code" required>
    <input class="form-control" name="instructor_name" placeholder="Instructor name" required>
    <input class="form-control" name="mobile" placeholder="Mobile">
    <input class="form-control" type="email" name="email" placeholder="Email">
    <input class="form-control" type="date" name="from_date" value="<?= date('Y-m-d') ?>" required>
    <input class="form-control" type="date" name="to_date" value="9999-12-31" required>
    <select class="form-control" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select>
    <button class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
    <button class="btn btn-outline" type="button" onclick="resetInstructorForm()">Clear</button>
  </form>
  <table class="data-table"><thead><tr><th>Code</th><th>Name</th><th>Mobile</th><th>Email</th><th>From Dt</th><th>To Dt</th><th>Status</th><th>Action</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr><td><?= htmlspecialchars($r['instructor_code'] ?? '') ?></td><td><strong><?= htmlspecialchars($r['instructor_name']) ?></strong></td><td><?= htmlspecialchars($r['mobile'] ?? '') ?></td><td><?= htmlspecialchars($r['email'] ?? '') ?></td><td><?= !empty($r['from_date']) ? date('d/m/Y', strtotime($r['from_date'])) : '-' ?></td><td><?= !empty($r['to_date']) ? date('d/m/Y', strtotime($r['to_date'])) : '-' ?></td><td><span class="badge <?= strtolower($r['status']) === 'active' ? 'badge-success' : 'badge-gray' ?>"><?= htmlspecialchars(ucfirst($r['status'])) ?></span></td><td><div class="row-actions"><button class="btn btn-sm btn-outline" type="button" onclick='editInstructor(<?= json_encode($r, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Edit</button><form method="post" onsubmit="return confirm('Delete this instructor?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form></div></td></tr><?php endforeach; ?>
  </tbody></table>
</div></section>
<style>.master-form{display:grid;grid-template-columns:120px 1fr 130px 1fr 145px 145px 125px auto auto;gap:10px;margin-bottom:16px}.form-control{height:38px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px}.row-actions{display:flex;gap:8px;align-items:center}.row-actions form{margin:0}@media(max-width:1200px){.master-form{grid-template-columns:1fr 1fr}}@media(max-width:640px){.master-form{grid-template-columns:1fr}}</style>
<script>
function editInstructor(row){
  const form = document.getElementById('instructorForm');
  form.querySelector('[name="id"]').value = row.id || '';
  form.instructor_code.value = row.instructor_code || '';
  form.instructor_name.value = row.instructor_name || '';
  form.mobile.value = row.mobile || '';
  form.email.value = row.email || '';
  form.from_date.value = row.from_date || '<?= date('Y-m-d') ?>';
  form.to_date.value = row.to_date || '9999-12-31';
  form.status.value = row.status || 'active';
  form.scrollIntoView({behavior:'smooth', block:'center'});
}
function resetInstructorForm(){
  const form = document.getElementById('instructorForm');
  form.reset();
  form.querySelector('[name="id"]').value = '';
  form.from_date.value = '<?= date('Y-m-d') ?>';
  form.to_date.value = '9999-12-31';
}
</script>
<?php }
renderLayout('Instructor Master', 'renderContent', $role, $name);
?>
