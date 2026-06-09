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
    $source = strtoupper(trim($_POST['fee_source'] ?? ''));
    if (in_array($source, array('PWO', 'PO', 'SO'), true)) {
        db_execute($conn, "INSERT INTO training_fee_masters (fee_source, amount, status, created_by, created_at, updated_at) VALUES (?, ?, 'active', ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE amount = VALUES(amount), status = 'active', updated_at = NOW()", 'sdi', array($source, max(0, (float)($_POST['amount'] ?? 0)), (int)($_SESSION['user_id'] ?? 0)));
    }
    header('Location: training_fee_master.php');
    exit;
}

function renderContent() {
    global $conn;
    $rows = db_fetch_all($conn, "SELECT * FROM training_fee_masters ORDER BY FIELD(fee_source, 'PWO', 'PO', 'SO'), fee_source");
?>
<div class="content-header"><h2 class="page-title"><i class="fas fa-indian-rupee-sign"></i> Training Fee Master</h2></div>
<section class="card glass"><div class="card-header"><div class="card-title">PWO / PO / SO Amount</div></div><div class="card-body">
  <form method="post" class="master-form">
    <select class="form-control" name="fee_source"><option>PWO</option><option>PO</option><option>SO</option></select>
    <input class="form-control" type="number" step="0.01" min="0" name="amount" value="0.00" required>
    <button class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
  </form>
  <table class="data-table"><thead><tr><th>Source</th><th>Amount</th><th>Status</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr><td><strong><?= htmlspecialchars($r['fee_source']) ?></strong></td><td><?= number_format((float)$r['amount'], 2) ?></td><td><span class="badge badge-success"><?= htmlspecialchars(ucfirst($r['status'])) ?></span></td></tr><?php endforeach; ?>
  </tbody></table>
</div></section>
<style>.master-form{display:grid;grid-template-columns:160px 160px auto;gap:10px;margin-bottom:16px}.form-control{height:38px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px}@media(max-width:700px){.master-form{grid-template-columns:1fr}}</style>
<?php }
renderLayout('Training Fee Master', 'renderContent', $role, $name);
?>
