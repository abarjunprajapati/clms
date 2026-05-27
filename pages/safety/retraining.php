<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Safety Officer';

function renderContent() {
    global $conn;
    
    // Fetch workers who failed OR expired
    $retrain_list = db_fetch_all($conn, "
        SELECT w.*, c.contractor_name
        FROM workmen w
        JOIN contractors c ON w.contractor_id = c.id
        WHERE w.training_status IN ('training_failed', 'training_expired')
        ORDER BY w.training_status DESC, w.name ASC
    ");

    ?>
    <div class="content-header">
      <h2 class="page-title">Re-Training Management</h2>

    </div>

    <div class="card glass">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-redo"></i> Workers Needing Re-Training (<?= count($retrain_list) ?>)</div>
      </div>
      <div class="card-body">
        <table class="data-table">
          <thead>
            <tr>
              <th>Worker Name</th>
              <th>Status</th>
              <th>Reason</th>
              <th>Last Training</th>
              <th>Contractor</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($retrain_list as $w): ?>
            <tr>
              <td>
                <div style="font-weight:600"><?= htmlspecialchars($w['name']) ?></div>
                <div style="font-size:11px;opacity:0.7"><?= htmlspecialchars($w['temp_id']) ?></div>
              </td>
              <td>
                <span class="badge badge-danger"><?= ucfirst(str_replace('training_', '', $w['training_status'])) ?></span>
              </td>
              <td>
                <?= $w['training_status'] == 'training_expired' ? 'Certificate Expired' : 'Failed Assessment' ?>
              </td>
              <td><?= $w['training_valid_till'] ? date('d M Y', strtotime($w['training_valid_till'] . ' -1 year')) : 'N/A' ?></td>
              <td><?= htmlspecialchars($w['contractor_name']) ?></td>
              <td>
                <form action="../../api/safety/request_retraining.php" method="POST" style="display:inline">
                    <input type="hidden" name="workman_id" value="<?= $w['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-primary">Reset to Pending</button>
                </form>
              </td>
            </tr>
            <?php endforeach; if(empty($retrain_list)): ?>
            <tr><td colspan="6" style="text-align:center;padding:40px;opacity:0.5">No workers currently need re-training.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
}

renderLayout("Re-Training", 'renderContent', $role, $name);

