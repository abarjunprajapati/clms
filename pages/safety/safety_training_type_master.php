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
        if (($_POST['action'] ?? '') === 'delete') {
            clms_delete_training_type($conn, (int)($_POST['id'] ?? 0));
            $_SESSION['training_type_message'] = ['type' => 'success', 'text' => 'Training type deleted.'];
        } elseif (($_POST['action'] ?? '') === 'inactive') {
            clms_inactivate_training_type($conn, (int)($_POST['id'] ?? 0));
            $_SESSION['training_type_message'] = ['type' => 'success', 'text' => 'Training type set inactive.'];
        } else {
            clms_add_training_type($conn, $_POST['type_name'] ?? '', $_POST['duration_hours'] ?? 8, $_POST['pass_mark'] ?? 60, $_POST['from_date'] ?? date('Y-m-d'), $_POST['to_date'] ?? '9999-12-31', (int)($_POST['id'] ?? 0));
            $_SESSION['training_type_message'] = ['type' => 'success', 'text' => 'Training type saved successfully.'];
        }
    } catch (Throwable $e) {
        $_SESSION['training_type_message'] = ['type' => 'danger', 'text' => $e->getMessage()];
    }
    header('Location: safety_training_type_master.php');
    exit;
}

function renderContent() {
    global $conn;
    $rows = clms_get_training_type_rows($conn, false);
    $message = $_SESSION['training_type_message'] ?? null;
    unset($_SESSION['training_type_message']);
?>
<div class="content-header"><h2 class="page-title"><i class="fas fa-list-check"></i> Training Type Master</h2></div>
<section class="card glass"><div class="card-header"><div class="card-title">Training Type / Pass Mark</div></div><div class="card-body">
  <?php if ($message): ?>
  <div class="alert alert-<?= htmlspecialchars($message['type']) ?>"><?= htmlspecialchars($message['text']) ?></div>
  <?php endif; ?>
  <form method="post" class="master-form" id="typeForm">
    <input type="hidden" name="action" value="add">
    <input type="hidden" name="id">
    <input class="form-control" name="type_name" placeholder="HSE Induction Training" required>
    <input class="form-control" type="number" name="duration_hours" min="1" value="8">
    <input class="form-control" type="number" name="pass_mark" min="0" max="100" value="60">
    <input class="form-control" type="date" name="from_date" value="<?= date('Y-m-d') ?>" required>
    <input class="form-control" type="date" name="to_date" value="9999-12-31" required>
    <button class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
    <button class="btn btn-outline" type="button" onclick="resetTypeForm()">Clear</button>
  </form>
  <table class="data-table"><thead><tr><th>Type</th><th>Hours</th><th>Pass Mark</th><th>From Dt</th><th>To Dt</th><th>Status</th><th>Action</th></tr></thead><tbody>
  <?php foreach ($rows as $r): $active = strtolower((string)$r['status']) === 'active'; ?><tr><td><strong><?= htmlspecialchars($r['type_name']) ?></strong></td><td><?= (int)$r['duration_hours'] ?></td><td><?= (int)$r['pass_mark'] ?></td><td><?= !empty($r['from_date']) ? date('d/m/Y', strtotime($r['from_date'])) : '-' ?></td><td><?= !empty($r['to_date']) ? date('d/m/Y', strtotime($r['to_date'])) : '-' ?></td><td><span class="badge <?= $active ? 'badge-success' : 'badge-gray' ?>"><?= htmlspecialchars(ucfirst($r['status'])) ?></span></td><td><div class="row-actions"><button class="btn btn-sm btn-outline" type="button" onclick='editType(<?= json_encode($r, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Edit</button><form method="post" onsubmit="return confirm('Delete this training type?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form></div></td></tr><?php endforeach; ?>
  </tbody></table>
</div></section>
<style>.master-form{display:grid;grid-template-columns:1fr 100px 110px 150px 150px auto auto;gap:10px;margin-bottom:16px}.form-control{height:38px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px}.row-actions{display:flex;gap:8px;align-items:center}.row-actions form{margin:0}.alert{border-radius:8px;margin-bottom:14px;padding:10px 12px}.alert-success{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46}.alert-danger{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}@media(max-width:1000px){.master-form{grid-template-columns:1fr 1fr}}@media(max-width:800px){.master-form{grid-template-columns:1fr}}</style>
<script>
function editType(row){
  const form = document.getElementById('typeForm');
  form.querySelector('[name="id"]').value = row.id || '';
  form.type_name.value = row.type_name || '';
  form.duration_hours.value = row.duration_hours || 8;
  form.pass_mark.value = row.pass_mark || 60;
  form.from_date.value = row.from_date || '<?= date('Y-m-d') ?>';
  form.to_date.value = row.to_date || '9999-12-31';
  form.scrollIntoView({behavior:'smooth', block:'center'});
}
function resetTypeForm(){
  const form = document.getElementById('typeForm');
  form.reset();
  form.querySelector('[name="id"]').value = '';
  form.duration_hours.value = 8;
  form.pass_mark.value = 60;
  form.from_date.value = '<?= date('Y-m-d') ?>';
  form.to_date.value = '9999-12-31';
}
</script>
<?php }
renderLayout('Training Type Master', 'renderContent', $role, $name);
?>
