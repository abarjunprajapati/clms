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
    if (($_POST['action'] ?? '') === 'inactive') {
        clms_inactivate_training_type($conn, (int)($_POST['id'] ?? 0));
    } else {
        clms_add_training_type($conn, $_POST['type_name'] ?? '', $_POST['duration_hours'] ?? 8, $_POST['pass_mark'] ?? 60);
    }
    header('Location: safety_training_type_master.php');
    exit;
}

function renderContent() {
    global $conn;
    $rows = clms_get_training_type_rows($conn, false);
?>
<div class="content-header"><h2 class="page-title"><i class="fas fa-list-check"></i> Training Type Master</h2></div>
<section class="card glass"><div class="card-header"><div class="card-title">Training Type / Pass Mark</div></div><div class="card-body">
  <form method="post" class="master-form">
    <input class="form-control" name="type_name" placeholder="HSE Induction Training" required>
    <input class="form-control" type="number" name="duration_hours" min="1" value="8">
    <input class="form-control" type="number" name="pass_mark" min="0" value="60">
    <button class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
  </form>
  <table class="data-table"><thead><tr><th>Type</th><th>Hours</th><th>Pass Mark</th><th>Status</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr><td><strong><?= htmlspecialchars($r['type_name']) ?></strong></td><td><?= (int)$r['duration_hours'] ?></td><td><?= (int)$r['pass_mark'] ?></td><td><span class="badge <?= strtolower($r['status']) === 'active' ? 'badge-success' : 'badge-gray' ?>"><?= htmlspecialchars(ucfirst($r['status'])) ?></span></td></tr><?php endforeach; ?>
  </tbody></table>
</div></section>
<style>.master-form{display:grid;grid-template-columns:1fr 120px 130px auto;gap:10px;margin-bottom:16px}.form-control{height:38px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px}@media(max-width:800px){.master-form{grid-template-columns:1fr}}</style>
<?php }
renderLayout('Training Type Master', 'renderContent', $role, $name);
?>
