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
      $action = $_POST['action'] ?? 'save';
      if ($action === 'set_status') {
        clms_safety_set_master_status($conn, 'training_language_masters', (int)($_POST['id'] ?? 0), $_POST['status'] ?? 'inactive');
        $_SESSION['safety_master_message'] = 'Language status updated.';
      } else {
        $id = (int)($_POST['id'] ?? 0);
        $languageName = trim($_POST['language_name'] ?? '');
        $status = clms_safety_master_status($_POST['status'] ?? 'active');
        list($fromDate, $toDate) = clms_safety_validate_master_dates($_POST['from_date'] ?? '', $_POST['to_date'] ?? '', $status);
        if ($languageName !== '') {
            if ($id > 0) {
                db_execute($conn, "UPDATE training_language_masters SET language_name = ?, from_date = ?, to_date = ?, status = ?, updated_at = NOW() WHERE id = ?", 'ssssi', array($languageName, $fromDate, $toDate, $status, $id));
            } else {
                db_execute($conn, "INSERT INTO training_language_masters (language_name, from_date, to_date, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE from_date = VALUES(from_date), to_date = VALUES(to_date), status = VALUES(status), updated_at = NOW()", 'ssssi', array($languageName, $fromDate, $toDate, $status, (int)($_SESSION['user_id'] ?? 0)));
            }
            $_SESSION['safety_master_message'] = 'Language saved successfully.';
        }
      }
    } catch (Throwable $e) {
      $_SESSION['safety_master_error'] = $e->getMessage();
    }
    header('Location: training_language_master.php');
    exit;
}

function renderContent() {
    global $conn;
    $rows = db_fetch_all($conn, "SELECT * FROM training_language_masters ORDER BY sort_order ASC, language_name ASC");
    $message = $_SESSION['safety_master_message'] ?? ''; $error = $_SESSION['safety_master_error'] ?? '';
    unset($_SESSION['safety_master_message'], $_SESSION['safety_master_error']);
?>
<div class="content-header"><h2 class="page-title"><i class="fas fa-language"></i> Training Language Master</h2></div>
<section class="card glass"><div class="card-header"><div class="card-title">Language</div></div><div class="card-body">
  <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?><?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="post" class="master-form" id="languageForm">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id">
    <input class="form-control" name="language_name" placeholder="Malayalam / English / Kannada / Tamil" required>
    <input class="form-control" type="date" name="from_date" value="<?= date('Y-m-d') ?>" required>
    <input class="form-control" type="date" name="to_date" min="<?= date('Y-m-d') ?>" value="9999-12-31" required>
    <select class="form-control" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select>
    <button class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
    <button class="btn btn-outline" type="button" onclick="resetLanguageForm()">Clear</button>
  </form>
  <table class="data-table"><thead><tr><th>Language</th><th>From Dt</th><th>To Dt</th><th>Status</th><th>Action</th></tr></thead><tbody>
  <?php foreach ($rows as $r): $active = strtolower((string)$r['status']) === 'active'; ?><tr><td><strong><?= htmlspecialchars($r['language_name']) ?></strong></td><td><?= !empty($r['from_date']) ? date('d/m/Y', strtotime($r['from_date'])) : '-' ?></td><td><?= !empty($r['to_date']) ? date('d/m/Y', strtotime($r['to_date'])) : '-' ?></td><td><span class="badge <?= $active ? 'badge-success' : 'badge-gray' ?>"><?= htmlspecialchars(ucfirst($r['status'])) ?></span></td><td><div class="row-actions"><button class="btn btn-sm btn-outline" type="button" onclick='editLanguage(<?= json_encode($r, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Edit</button><form method="post"><input type="hidden" name="action" value="set_status"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="status" value="<?= $active ? 'inactive' : 'active' ?>"><button class="btn btn-sm <?= $active ? 'btn-warning' : 'btn-success' ?>"><?= $active ? 'Inactive' : 'Active' ?></button></form></div></td></tr><?php endforeach; ?>
  </tbody></table>
</div></section>
<style>.master-form{display:grid;grid-template-columns:1fr 150px 150px 130px auto auto;gap:10px;margin-bottom:16px}.form-control{height:38px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px}.row-actions{display:flex;gap:8px;align-items:center}.row-actions form{margin:0}.alert{padding:10px 12px;border-radius:8px;margin-bottom:12px}.alert-success{background:#ecfdf5;color:#065f46}.alert-danger{background:#fef2f2;color:#991b1b}@media(max-width:900px){.master-form{grid-template-columns:1fr 1fr}}@media(max-width:700px){.master-form{grid-template-columns:1fr}}</style>
<script>
function editLanguage(row){
  const form = document.getElementById('languageForm');
  form.querySelector('[name="id"]').value = row.id || '';
  form.language_name.value = row.language_name || '';
  form.from_date.value = row.from_date || '<?= date('Y-m-d') ?>';
  form.to_date.value = row.to_date || '9999-12-31';
  form.status.value = row.status || 'active';
  form.scrollIntoView({behavior:'smooth', block:'center'});
}
function resetLanguageForm(){
  const form = document.getElementById('languageForm');
  form.reset();
  form.querySelector('[name="id"]').value = '';
  form.from_date.value = '<?= date('Y-m-d') ?>';
  form.to_date.value = '9999-12-31';
}
</script>
<?php }
renderLayout('Training Language Master', 'renderContent', $role, $name);
?>
