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
        db_execute(
            $conn,
            "INSERT INTO training_venue_masters (venue_code, venue_name, seats, status, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE venue_code = VALUES(venue_code), seats = VALUES(seats), status = VALUES(status), updated_at = NOW()",
            'ssisi',
            array(trim($_POST['venue_code'] ?? ''), trim($_POST['venue_name'] ?? ''), max(1, (int)($_POST['seats'] ?? 35)), $_POST['status'] ?? 'active', (int)($_SESSION['user_id'] ?? 0))
        );
    }
    header('Location: training_location_master.php');
    exit;
}

function renderContent() {
    global $conn;
    $rows = db_fetch_all($conn, "SELECT id, venue_code, venue_name, COALESCE(seats, 35) seats, status FROM training_venue_masters ORDER BY venue_name ASC");
?>
<div class="content-header"><h2 class="page-title"><i class="fas fa-location-dot"></i> Training Location Master</h2></div>
<section class="card glass"><div class="card-header"><div class="card-title">Location / Seats</div></div><div class="card-body">
  <form method="post" class="master-form">
    <input type="hidden" name="action" value="save">
    <input class="form-control" name="venue_code" placeholder="Code e.g. GBC">
    <input class="form-control" name="venue_name" placeholder="Safety Room / Hall Name" required>
    <input class="form-control" type="number" name="seats" min="1" value="35" required>
    <select class="form-control" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select>
    <button class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
  </form>
  <table class="data-table"><thead><tr><th>Code</th><th>Name</th><th>Seats</th><th>Status</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr><td><?= htmlspecialchars($r['venue_code'] ?? '') ?></td><td><strong><?= htmlspecialchars($r['venue_name']) ?></strong></td><td><?= (int)$r['seats'] ?></td><td><span class="badge <?= strtolower($r['status']) === 'active' ? 'badge-success' : 'badge-gray' ?>"><?= htmlspecialchars(ucfirst($r['status'])) ?></span></td></tr><?php endforeach; ?>
  </tbody></table>
</div></section>
<style>.master-form{display:grid;grid-template-columns:130px 1fr 100px 130px auto;gap:10px;margin-bottom:16px}.form-control{height:38px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px}@media(max-width:900px){.master-form{grid-template-columns:1fr}}</style>
<?php }
renderLayout('Training Location Master', 'renderContent', $role, $name);
?>
