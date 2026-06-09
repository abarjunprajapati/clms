<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';
require_once __DIR__ . '/../../include/training_venue_master.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        clms_add_training_venue($conn, $_POST['venue_name'] ?? '', (int)($_SESSION['user_id'] ?? 0));
    } elseif ($action === 'inactive') {
        clms_inactivate_training_venue($conn, (int)($_POST['id'] ?? 0));
    }
    header('Location: training_venue_master.php');
    exit;
}

function renderContent() {
    global $conn;
    $venues = clms_get_training_venue_rows($conn, false);
    ?>
<div class="content-header">
  <div><h2 class="page-title"><i class="fas fa-location-dot" style="color:#2563eb;margin-right:10px;"></i>Training Venue Master</h2></div>
</div>

<section class="card glass">
  <div class="card-header"><div class="card-title">Add Training Hall / Venue</div></div>
  <div class="card-body">
    <form method="POST" class="venue-form">
      <input type="hidden" name="action" value="add">
      <input class="form-control" name="venue_name" placeholder="Training Hall / Venue" required>
      <button class="btn btn-primary" type="submit"><i class="fas fa-plus"></i> Add</button>
    </form>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Venue</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
          <?php foreach ($venues as $row): $active = strtolower((string)$row['status']) === 'active'; ?>
          <tr>
            <td><strong><?= htmlspecialchars($row['venue_name']) ?></strong></td>
            <td><span class="badge <?= $active ? 'badge-success' : 'badge-gray' ?>"><?= htmlspecialchars(ucfirst($row['status'])) ?></span></td>
            <td>
              <?php if ($active): ?>
              <form method="POST" style="margin:0;" onsubmit="return confirm('Set this venue inactive?');">
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
  .venue-form { display:grid; grid-template-columns:1fr auto; gap:10px; margin-bottom:16px; }
  .form-control { width:100%; padding:10px 14px; border-radius:10px; border:1.5px solid var(--border-color); background:var(--input-bg, rgba(255,255,255,.05)); color:var(--text-primary); font-size:14px; box-sizing:border-box; }
  .table-wrap { overflow:auto; max-height:520px; }
  @media (max-width:700px) { .venue-form { grid-template-columns:1fr; } }
</style>
<?php
}

renderLayout('Training Venue Master', 'renderContent', $role, $name);
