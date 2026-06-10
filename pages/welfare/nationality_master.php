<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'welfare_user', 'admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';
require_once __DIR__ . '/../../include/nationality_location_masters.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Admin';
$nationalityMasterReady = clms_ensure_nationality_location_masters($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $type = $_POST['type'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'add_nationality') {
        $value = trim($_POST['nationality'] ?? '');
        if ($value !== '') {
            db_execute($conn, "INSERT INTO master_nationalities (nationality, status, created_at, updated_at) VALUES (?, 'active', NOW(), NOW()) ON DUPLICATE KEY UPDATE status = 'active', updated_at = NOW()", 's', [$value]);
        }
    } elseif ($action === 'add_religion') {
        $value = trim($_POST['religion'] ?? '');
        if ($value !== '') {
            db_execute($conn, "INSERT INTO master_religions (religion, status, created_at, updated_at) VALUES (?, 'active', NOW(), NOW()) ON DUPLICATE KEY UPDATE status = 'active', updated_at = NOW()", 's', [$value]);
        }
    } elseif ($action === 'add_mapping') {
        $state = trim($_POST['state_name'] ?? '');
        $district = trim($_POST['district_name'] ?? '');
        if ($state !== '' && $district !== '') {
            db_execute($conn, "INSERT INTO master_state_districts (state_name, district_name, status, created_at, updated_at) VALUES (?, ?, 'active', NOW(), NOW()) ON DUPLICATE KEY UPDATE status = 'active', updated_at = NOW()", 'ss', [$state, $district]);
        }
    } elseif ($action === 'inactive' && $id > 0) {
        if ($type === 'nationality') {
            db_execute($conn, "UPDATE master_nationalities SET status = 'inactive', updated_at = NOW() WHERE id = ?", 'i', [$id]);
        } elseif ($type === 'religion') {
            db_execute($conn, "UPDATE master_religions SET status = 'inactive', updated_at = NOW() WHERE id = ?", 'i', [$id]);
        } elseif ($type === 'mapping') {
            db_execute($conn, "UPDATE master_state_districts SET status = 'inactive', updated_at = NOW() WHERE id = ?", 'i', [$id]);
        }
    }

    header('Location: nationality_master.php');
    exit;
}

function renderContent() {
    global $conn, $nationalityMasterReady;
    if (!$nationalityMasterReady) {
        ?>
        <div class="content-header">
          <div><h2 class="page-title">Nationality Masters / State-District Mapping</h2></div>
        </div>
        <div class="alert alert-danger" style="margin:20px;">
          <strong>Master tables could not be initialized.</strong>
          <div style="margin-top:6px;">Please check database permission/schema for master_nationalities, master_religions and master_state_districts.</div>
        </div>
        <?php
        return;
    }
    $nationalities = db_fetch_all($conn, "SELECT * FROM master_nationalities ORDER BY status ASC, nationality ASC");
    $religions = db_fetch_all($conn, "SELECT * FROM master_religions ORDER BY status ASC, religion ASC");
    $mappings = db_fetch_all($conn, "SELECT * FROM master_state_districts ORDER BY state_name ASC, district_name ASC");
    ?>

<div class="content-header">
  <div>
    <h2 class="page-title">Nationality Masters / State-District Mapping</h2>
  </div>
</div>

<div class="master-grid">
  <section class="card glass">
    <div class="card-header"><div class="card-title">Nationality Master</div></div>
    <div class="card-body">
      <form method="POST" class="inline-form">
        <input type="hidden" name="action" value="add_nationality">
        <input class="form-control" name="nationality" placeholder="Nationality" required>
        <button class="btn btn-primary" type="submit">Add</button>
      </form>
      <?= renderMasterRows($nationalities, 'nationality', 'nationality') ?>
    </div>
  </section>

  <section class="card glass">
    <div class="card-header"><div class="card-title">Religion Master</div></div>
    <div class="card-body">
      <form method="POST" class="inline-form">
        <input type="hidden" name="action" value="add_religion">
        <input class="form-control" name="religion" placeholder="Religion" required>
        <button class="btn btn-primary" type="submit">Add</button>
      </form>
      <?= renderMasterRows($religions, 'religion', 'religion') ?>
    </div>
  </section>
</div>

<section class="card glass" style="margin-top:18px;">
  <div class="card-header"><div class="card-title">State / District Mapping</div></div>
  <div class="card-body">
    <form method="POST" class="mapping-form">
      <input type="hidden" name="action" value="add_mapping">
      <input class="form-control" name="state_name" placeholder="State" required>
      <input class="form-control" name="district_name" placeholder="District" required>
      <button class="btn btn-primary" type="submit">Add</button>
    </form>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>State</th><th>District</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
          <?php foreach ($mappings as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['state_name']) ?></td>
            <td><?= htmlspecialchars($row['district_name']) ?></td>
            <td><span class="badge <?= strtolower($row['status']) === 'active' ? 'badge-success' : 'badge-gray' ?>"><?= htmlspecialchars(ucfirst($row['status'])) ?></span></td>
            <td><?= inactiveForm('mapping', (int)$row['id'], strtolower($row['status']) === 'active') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<style>
  .master-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
  .inline-form, .mapping-form { display:grid; gap:10px; margin-bottom:14px; }
  .inline-form { grid-template-columns:1fr auto; }
  .mapping-form { grid-template-columns:1fr 1fr auto; }
  .form-control { width:100%; padding:10px 14px; border-radius:10px; border:1.5px solid var(--border-color); background:var(--input-bg, rgba(255,255,255,.05)); color:var(--text-primary); font-size:14px; box-sizing:border-box; }
  .master-list { display:grid; gap:8px; max-height:320px; overflow:auto; }
  .master-row { display:grid; grid-template-columns:1fr auto auto; align-items:center; gap:10px; border:1px solid #e2e8f0; border-radius:8px; padding:10px 12px; background:#fff; }
  .table-wrap { overflow:auto; max-height:420px; }
  @media (max-width:900px) { .master-grid, .mapping-form { grid-template-columns:1fr; } .inline-form { grid-template-columns:1fr; } }
</style>

<?php
}

function inactiveForm($type, $id, $active) {
    if (!$active) return '-';
    return '<form method="POST" onsubmit="return confirm(\'Set this entry as inactive?\');" style="margin:0;">'
        . '<input type="hidden" name="action" value="inactive">'
        . '<input type="hidden" name="type" value="' . htmlspecialchars($type) . '">'
        . '<input type="hidden" name="id" value="' . (int)$id . '">'
        . '<button class="btn btn-sm btn-outline" type="submit">Inactive</button>'
        . '</form>';
}

function renderMasterRows($rows, $column, $type) {
    $html = '<div class="master-list">';
    foreach ($rows as $row) {
        $active = strtolower((string)$row['status']) === 'active';
        $html .= '<div class="master-row">'
            . '<strong>' . htmlspecialchars($row[$column]) . '</strong>'
            . '<span class="badge ' . ($active ? 'badge-success' : 'badge-gray') . '">' . htmlspecialchars(ucfirst($row['status'])) . '</span>'
            . inactiveForm($type, (int)$row['id'], $active)
            . '</div>';
    }
    $html .= '</div>';
    return $html;
}

renderLayout('Nationality Masters', 'renderContent', $role, $name);
