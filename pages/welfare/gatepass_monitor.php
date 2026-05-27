<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin', 'pass_user', 'welfare_user']);

include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = get_normalized_role();
$name = $_SESSION['name'] ?? 'Officer';

function renderContent() {
    global $conn;
    
    $query = "SELECT w.*, c.contractor_name, pgp.pass_no AS perm_pass_no 
              FROM workmen w 
              JOIN contractors c ON w.contractor_id = c.id 
              LEFT JOIN permanent_gate_passes pgp ON w.id = pgp.worker_id
              WHERE w.status IN ('temporary_issued', 'acc_generated', 'permanent_active') OR pgp.id IS NOT NULL
              ORDER BY w.updated_at DESC";
    $passes = db_fetch_all($conn, $query);
    ?>
    <div class="content-header">
      <h2 class="page-title">Gate Pass Monitoring</h2>
      <!-- <p class="page-subtitle">Overview of all active temporary and permanent gate passes.</p> -->
    </div>

    <div class="card glass">
      <div class="card-body" style="padding:0">
        <table class="data-table">
          <thead>
            <tr>
              <th>Workman</th>
              <th>Contractor</th>
              <th>ACC / Temp / Perm ID</th>
              <th>Validity</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($passes as $p): ?>
            <tr>
              <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
              <td><?= htmlspecialchars($p['contractor_name']) ?></td>
              <td>
                <code><?= $p['perm_pass_no'] ?: ($p['acc_number'] ?: ($p['temp_id'] ?: 'N/A')) ?></code>
                <?php if($p['perm_pass_no']): ?>
                  <div style="font-size:10px;color:var(--success);font-weight:bold;margin-top:2px;">PERMANENT PASS</div>
                <?php endif; ?>
              </td>
              <td><?= ($p['valid_to'] ?? $p['temp_valid_to']) ? date('d M Y', strtotime($p['valid_to'] ?? $p['temp_valid_to'])) : '—' ?></td>
              <td>
                <span class="badge badge-<?= ($p['status'] == 'permanent_active' || !empty($p['perm_pass_no'])) ? 'success' : 'info' ?>">
                  <?= (!empty($p['perm_pass_no'])) ? 'PERMANENT ACTIVE' : strtoupper(str_replace('_', ' ', $p['status'])) ?>
                </span>
              </td>
              <td>
                <a href="pass_status.php?search=<?= $p['name'] ?>" class="btn btn-sm btn-outline-primary">Track Lifecycle</a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($passes)): ?>
            <tr><td colspan="6" class="text-center" style="padding:40px;">No active gate passes found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
}

renderLayout("Gate Pass Monitoring", 'renderContent', $role, $name);

