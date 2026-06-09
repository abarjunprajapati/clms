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
    db_execute(
        $conn,
        "INSERT INTO safety_instructor_masters (instructor_code, instructor_name, mobile, email, status, created_by, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
         ON DUPLICATE KEY UPDATE instructor_code = VALUES(instructor_code), mobile = VALUES(mobile), email = VALUES(email), status = VALUES(status), updated_at = NOW()",
        'sssssi',
        array(trim($_POST['instructor_code'] ?? ''), trim($_POST['instructor_name'] ?? ''), trim($_POST['mobile'] ?? ''), trim($_POST['email'] ?? ''), $_POST['status'] ?? 'active', (int)($_SESSION['user_id'] ?? 0))
    );
    header('Location: instructor_master.php');
    exit;
}

function renderContent() {
    global $conn;
    $rows = db_fetch_all($conn, "SELECT * FROM safety_instructor_masters ORDER BY instructor_name ASC");
?>
<div class="content-header"><h2 class="page-title"><i class="fas fa-person-chalkboard"></i> Instructor Master</h2></div>
<section class="card glass"><div class="card-header"><div class="card-title">Instructor Details</div></div><div class="card-body">
  <form method="post" class="master-form">
    <input class="form-control" name="instructor_code" placeholder="Code">
    <input class="form-control" name="instructor_name" placeholder="Instructor name" required>
    <input class="form-control" name="mobile" placeholder="Mobile">
    <input class="form-control" type="email" name="email" placeholder="Email">
    <select class="form-control" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select>
    <button class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
  </form>
  <table class="data-table"><thead><tr><th>Code</th><th>Name</th><th>Mobile</th><th>Email</th><th>Status</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr><td><?= htmlspecialchars($r['instructor_code'] ?? '') ?></td><td><strong><?= htmlspecialchars($r['instructor_name']) ?></strong></td><td><?= htmlspecialchars($r['mobile'] ?? '') ?></td><td><?= htmlspecialchars($r['email'] ?? '') ?></td><td><span class="badge <?= strtolower($r['status']) === 'active' ? 'badge-success' : 'badge-gray' ?>"><?= htmlspecialchars(ucfirst($r['status'])) ?></span></td></tr><?php endforeach; ?>
  </tbody></table>
</div></section>
<style>.master-form{display:grid;grid-template-columns:130px 1fr 140px 1fr 130px auto;gap:10px;margin-bottom:16px}.form-control{height:38px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px}@media(max-width:1000px){.master-form{grid-template-columns:1fr 1fr}}@media(max-width:640px){.master-form{grid-template-columns:1fr}}</style>
<?php }
renderLayout('Instructor Master', 'renderContent', $role, $name);
?>
