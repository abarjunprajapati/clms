<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';
require_once __DIR__ . '/../../include/training_type_master.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        clms_add_training_type($conn, $_POST['type_name'] ?? '', $_POST['duration_hours'] ?? 8, $_POST['pass_mark'] ?? 60, $_POST['from_date'] ?? date('Y-m-d'), $_POST['to_date'] ?? '9999-12-31', (int)($_POST['id'] ?? 0));
    } elseif ($action === 'inactive') {
        clms_inactivate_training_type($conn, (int)($_POST['id'] ?? 0));
    } elseif ($action === 'delete') {
        clms_delete_training_type($conn, (int)($_POST['id'] ?? 0));
    }
    header('Location: training_type_master.php');
    exit;
}

function renderContent() {
    global $conn;
    $types = clms_get_training_type_rows($conn, false);
    ?>
<div class="content-header">
  <div><h2 class="page-title"><i class="fas fa-graduation-cap" style="color:#dc2626;margin-right:10px;"></i>Training Type Master</h2></div>
</div>

<section class="card glass">
  <div class="card-header"><div class="card-title">Add Training Type</div></div>
  <div class="card-body">
    <form method="POST" class="type-form" id="welfareTypeForm">
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="id">
      <input class="form-control" name="type_name" placeholder="Training Type" required>
      <input class="form-control" type="number" name="duration_hours" min="1" value="8" placeholder="Hours" required>
      <input class="form-control" type="number" name="pass_mark" min="0" max="100" value="60" placeholder="Pass Mark" required>
      <input class="form-control" type="date" name="from_date" value="<?= date('Y-m-d') ?>" required>
      <input class="form-control" type="date" name="to_date" value="9999-12-31" required>
      <button class="btn btn-primary" type="submit"><i class="fas fa-plus"></i> Add</button>
      <button class="btn btn-outline" type="button" onclick="resetWelfareTypeForm()">Clear</button>
    </form>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Training Type</th><th>Hours</th><th>Pass Mark</th><th>From Dt</th><th>To Dt</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
          <?php foreach ($types as $row): $active = strtolower((string)$row['status']) === 'active'; ?>
          <tr>
            <td><strong><?= htmlspecialchars($row['type_name']) ?></strong></td>
            <td><?= (int)($row['duration_hours'] ?? 8) ?></td>
            <td><?= (int)($row['pass_mark'] ?? 60) ?></td>
            <td><?= !empty($row['from_date']) ? date('d/m/Y', strtotime($row['from_date'])) : '-' ?></td>
            <td><?= !empty($row['to_date']) ? date('d/m/Y', strtotime($row['to_date'])) : '-' ?></td>
            <td><span class="badge <?= $active ? 'badge-success' : 'badge-gray' ?>"><?= htmlspecialchars(ucfirst($row['status'])) ?></span></td>
            <td>
              <div class="row-actions">
                <button class="btn btn-sm btn-outline" type="button" onclick='editWelfareType(<?= json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Edit</button>
                <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this training type?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                  <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<style>
  .type-form { display:grid; grid-template-columns:1fr 120px 130px 150px 150px auto auto; gap:10px; margin-bottom:16px; }
  .form-control { width:100%; padding:10px 14px; border-radius:10px; border:1.5px solid var(--border-color); background:var(--input-bg, rgba(255,255,255,.05)); color:var(--text-primary); font-size:14px; box-sizing:border-box; }
  .table-wrap { overflow:auto; max-height:520px; }
  .row-actions { display:flex; gap:8px; align-items:center; }
  @media (max-width:800px) { .type-form { grid-template-columns:1fr; } }
</style>
<script>
function editWelfareType(row){
  const form = document.getElementById('welfareTypeForm');
  form.querySelector('[name="id"]').value = row.id || '';
  form.type_name.value = row.type_name || '';
  form.duration_hours.value = row.duration_hours || 8;
  form.pass_mark.value = row.pass_mark || 60;
  form.from_date.value = row.from_date || '<?= date('Y-m-d') ?>';
  form.to_date.value = row.to_date || '9999-12-31';
  form.scrollIntoView({behavior:'smooth', block:'center'});
}
function resetWelfareTypeForm(){
  const form = document.getElementById('welfareTypeForm');
  form.reset();
  form.querySelector('[name="id"]').value = '';
  form.duration_hours.value = 8;
  form.pass_mark.value = 60;
  form.from_date.value = '<?= date('Y-m-d') ?>';
  form.to_date.value = '9999-12-31';
}
</script>
<?php
}

renderLayout('Training Type Master', 'renderContent', $role, $name);
