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
    db_execute($conn, "INSERT INTO training_language_masters (language_name, status, created_by, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = NOW()", 'ssi', array(trim($_POST['language_name'] ?? ''), $_POST['status'] ?? 'active', (int)($_SESSION['user_id'] ?? 0)));
    header('Location: training_language_master.php');
    exit;
}

function renderContent() {
    global $conn;
    $rows = db_fetch_all($conn, "SELECT * FROM training_language_masters ORDER BY sort_order ASC, language_name ASC");
?>
<div class="content-header"><h2 class="page-title"><i class="fas fa-language"></i> Training Language Master</h2></div>
<section class="card glass"><div class="card-header"><div class="card-title">Language</div></div><div class="card-body">
  <form method="post" class="master-form">
    <input class="form-control" name="language_name" placeholder="Malayalam / English / Kannada / Tamil" required>
    <select class="form-control" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select>
    <button class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
  </form>
  <table class="data-table"><thead><tr><th>Language</th><th>Status</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr><td><strong><?= htmlspecialchars($r['language_name']) ?></strong></td><td><span class="badge <?= strtolower($r['status']) === 'active' ? 'badge-success' : 'badge-gray' ?>"><?= htmlspecialchars(ucfirst($r['status'])) ?></span></td></tr><?php endforeach; ?>
  </tbody></table>
</div></section>
<style>.master-form{display:grid;grid-template-columns:1fr 130px auto;gap:10px;margin-bottom:16px}.form-control{height:38px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px}@media(max-width:700px){.master-form{grid-template-columns:1fr}}</style>
<?php }
renderLayout('Training Language Master', 'renderContent', $role, $name);
?>
