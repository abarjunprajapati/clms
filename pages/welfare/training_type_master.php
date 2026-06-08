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
        clms_add_training_type($conn, $_POST['type_name'] ?? '', $_POST['duration_hours'] ?? 8, $_POST['pass_mark'] ?? 60);
    } elseif ($action === 'inactive') {
        clms_inactivate_training_type($conn, (int)($_POST['id'] ?? 0));
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
    <form method="POST" class="type-form">
      <input type="hidden" name="action" value="add">
      <input class="form-control" name="type_name" placeholder="Training Type" required>
      <input class="form-control" type="number" name="duration_hours" min="1" value="8" placeholder="Hours" required>
      <input class="form-control" type="number" name="pass_mark" min="0" max="100" value="60" placeholder="Pass Mark" required>
      <button class="btn btn-primary" type="submit"><i class="fas fa-plus"></i> Add</button>
    </form>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Training Type</th><th>Hours</th><th>Pass Mark</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
          <?php foreach ($types as $row): $active = strtolower((string)$row['status']) === 'active'; ?>
          <tr>
            <td><strong><?= htmlspecialchars($row['type_name']) ?></strong></td>
            <td><?= (int)($row['duration_hours'] ?? 8) ?></td>
            <td><?= (int)($row['pass_mark'] ?? 60) ?></td>
            <td><span class="badge <?= $active ? 'badge-success' : 'badge-gray' ?>"><?= htmlspecialchars(ucfirst($row['status'])) ?></span></td>
            <td>
              <?php if ($active): ?>
              <form method="POST" style="margin:0;" onsubmit="return confirm('Set this training type inactive?');">
                <input type="hidden" name="action" value="inactive">
                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                <button class="btn btn-sm btn-outline" type="submit">Inactive</button>
              </form>
              <?php else: ?>-<?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<style>
  .type-form { display:grid; grid-template-columns:1fr 120px 130px auto; gap:10px; margin-bottom:16px; }
  .form-control { width:100%; padding:10px 14px; border-radius:10px; border:1.5px solid var(--border-color); background:var(--input-bg, rgba(255,255,255,.05)); color:var(--text-primary); font-size:14px; box-sizing:border-box; }
  .table-wrap { overflow:auto; max-height:520px; }
  @media (max-width:800px) { .type-form { grid-template-columns:1fr; } }
</style>
<?php
}

renderLayout('Training Type Master', 'renderContent', $role, $name);
