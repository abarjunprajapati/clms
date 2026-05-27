<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Admin';

function renderContent() {
    global $conn;
    $passes = db_fetch_all($conn, "SELECT gp.*, w.name, w.temp_id as workman_temp_id, c.contractor_name 
                                   FROM gate_passes gp 
                                   JOIN workmen w ON gp.workman_id = w.id 
                                   JOIN contractors c ON w.contractor_id = c.id 
                                   WHERE gp.pass_type='temporary' ORDER BY gp.valid_to ASC");
    ?>
    <div class="content-header">
      <h2 class="page-title">Temporary Pass Control</h2>
      <!-- <p class="page-subtitle">Monitor validity and expiration of issued temporary passes.</p> -->
    </div>

    <div class="stats-grid">
      <div class="stat-card glass">
        <div class="stat-icon" style="background:rgba(59,130,246,0.1);color:var(--info)"><i class="fas fa-ticket"></i></div>
        <div class="stat-value"><?= count($passes) ?></div>
        <div class="stat-label">Total Temp Passes</div>
      </div>
      <?php
      $expired = 0;
      $expiring_soon = 0;
      foreach($passes as $p) {
          $to = strtotime($p['valid_to']);
          if ($to < time()) $expired++;
          elseif ($to < strtotime('+7 days')) $expiring_soon++;
      }
      ?>
      <div class="stat-card glass">
        <div class="stat-icon" style="background:rgba(239,68,68,0.1);color:var(--danger)"><i class="fas fa-calendar-times"></i></div>
        <div class="stat-value"><?= $expired ?></div>
        <div class="stat-label">Expired Passes</div>
      </div>
      <div class="stat-card glass">
        <div class="stat-icon" style="background:rgba(245,158,11,0.1);color:var(--warning)"><i class="fas fa-hourglass-end"></i></div>
        <div class="stat-value"><?= $expiring_soon ?></div>
        <div class="stat-label">Expiring within 7 days</div>
      </div>
    </div>

    <div class="card glass" style="margin-top:24px">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-history"></i> Temporary Pass Tracking</div>
      </div>
      <div class="card-body">
        <table class="data-table">
          <thead>
            <tr>
              <th>Workman ID</th>
              <th>Workman Name</th>
              <th>Contractor</th>
              <th>Issued Date</th>
              <th>Valid To</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($passes as $p): 
              $is_expired = strtotime($p['valid_to']) < time();
              $status_badge = $is_expired ? 'badge-danger' : 'badge-success';
              $status_text = $is_expired ? 'Expired' : 'Active';
            ?>
            <tr>
              <td><code><?= htmlspecialchars($p['workman_temp_id'] ?? 'N/A') ?></code></td>
              <td><strong><?= htmlspecialchars($p['name'] ?? 'Unknown') ?></strong></td>
              <td><?= htmlspecialchars($p['contractor_name'] ?? 'N/A') ?></td>
              <td><?= date('d M Y', strtotime($p['approved_date'] ?: $p['created_at'])) ?></td>
              <td>
                <span class="<?= $is_expired ? 'text-danger' : '' ?>">
                  <?= date('d M Y', strtotime($p['valid_to'])) ?>
                </span>
              </td>
              <td><span class="badge <?= $status_badge ?>"><?= $status_text ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
}

renderLayout("Temporary Pass Control", 'renderContent', $role, $name);

