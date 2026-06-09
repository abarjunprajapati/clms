<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin', 'welfare_admin', 'safety_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';
include __DIR__ . '/../../include/AuditLogger.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $workman_id = $_POST['workman_id'];
    $action = $_POST['action']; // block or unblock
    $reason = $_POST['reason'];

    $new_status = ($action == 'block') ? 'blocked' : 'active';
    
    $stmt = $conn->prepare("UPDATE workmen SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $workman_id);
    
    if ($stmt->execute()) {
        // Log the action
        AuditLogger::log($conn, $_SESSION['user_id'], strtoupper($action) . "_WORKER", "WORKER_MGMT", "Workman ID: $workman_id. Reason: $reason");
        
        // Record in worker_blocks table
        $block_stmt = $conn->prepare("INSERT INTO worker_blocks (workman_id, action, reason, performed_by) VALUES (?, ?, ?, ?)");
        $block_stmt->bind_param("isss", $workman_id, $action, $reason, $name);
        $block_stmt->execute();
        
        $success = "Worker successfully " . $action . "ed.";
    } else {
        $error = "Error updating worker: " . $conn->error;
    }
}

function renderContent() {
    global $conn, $success, $error;
    $workers = db_fetch_all($conn, "SELECT w.*, c.contractor_name FROM workmen w JOIN contractors c ON w.contractor_id = c.user_id ORDER BY w.name ASC");
    ?>
    <div class="content-header">
      <h2 class="page-title">Worker Lifecycle Management</h2>
      <p class="page-subtitle">Block, unblock, or transfer workers across companies.</p>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card glass">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-users-slash"></i> Worker List</div>
      </div>
      <div class="card-body">
        <table class="data-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Contractor</th>
              <th>Aadhaar</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($workers as $w): ?>
            <tr>
              <td><?= htmlspecialchars($w['name']) ?></td>
              <td><?= htmlspecialchars($w['contractor_name']) ?></td>
              <td><code><?= htmlspecialchars($w['aadhaar']) ?></code></td>
              <td>
                <span class="badge <?= $w['status'] == 'blocked' ? 'badge-danger' : 'badge-success' ?>">
                  <?= ucfirst($w['status']) ?>
                </span>
              </td>
              <td>
                <form method="POST" style="display:inline-flex; gap:5px;">
                  <input type="hidden" name="workman_id" value="<?= $w['id'] ?>">
                  <input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason..." required style="width:150px">
                  <?php if ($w['status'] == 'blocked'): ?>
                    <button type="submit" name="action" value="unblock" class="btn btn-sm btn-success">Unblock</button>
                  <?php else: ?>
                    <button type="submit" name="action" value="block" class="btn btn-sm btn-danger">Block</button>
                  <?php endif; ?>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
}

renderLayout("Worker Management", 'renderContent', $role, $name);

