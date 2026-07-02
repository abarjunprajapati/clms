<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['contractor']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';
include __DIR__ . '/../../include/AuditLogger.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'];
$vendor_code = $_SESSION['contractor_id'] ?? '';
$c_info = db_single($conn, "SELECT id FROM contractors WHERE vendor_code = ?", 's', [$vendor_code]);
$contractor_id = $c_info['id'] ?? (int)($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $workman_id = (int)$_POST['workman_id'];
    $action = $_POST['action']; // block or unblock
    $reason = $_POST['reason'];

    // Verify worker belongs to contractor
    $check = db_single($conn, "SELECT id FROM workmen WHERE id = ? AND contractor_id = ?", 'ii', [$workman_id, $contractor_id]);
    
    if ($check) {
        $is_blocked = ($action == 'block') ? 1 : 0;
        
        if ($action == 'block') {
            $stmt = $conn->prepare("UPDATE workmen SET is_blocked = ?, blocked_source = 'contractor', acc_number = NULL, acc_card_number = NULL WHERE id = ?");
        } else {
            $stmt = $conn->prepare("UPDATE workmen SET is_blocked = ?, blocked_source = NULL WHERE id = ?");
        }
        $stmt->bind_param("ii", $is_blocked, $workman_id);
        
        if ($stmt->execute()) {
            // Log the action
            AuditLogger::log($conn, $_SESSION['user_id'], strtoupper($action) . "_WORKER", "WORKER_MGMT", "Workman ID: $workman_id. Reason: $reason");
            
            // Record in blocks table
            $block_stmt = $conn->prepare("INSERT INTO blocks (entity_type, entity_id, block_reason, blocked_by) VALUES ('worker', ?, ?, ?)");
            if ($block_stmt) {
                $user_id = $_SESSION['user_id'];
                $block_stmt->bind_param("isi", $workman_id, $reason, $user_id);
                $block_stmt->execute();
            }
            
            $success = "Worker successfully " . $action . "ed.";
        } else {
            $error = "Error updating worker: " . $conn->error;
        }
    } else {
        $error = "Unauthorized to modify this worker.";
    }
}

function renderContent() {
    global $conn, $success, $error, $contractor_id;
    $workers = db_fetch_all($conn, "SELECT w.*, c.contractor_name FROM workmen w JOIN contractors c ON w.contractor_id = c.id WHERE w.contractor_id = ? ORDER BY w.name ASC", "i", [$contractor_id]);
    ?>
    <div class="content-header">
      <h2 class="page-title">Worker Blocking</h2>
      <p class="page-subtitle">Block or unblock your workmen.</p>
    </div>

    <?php if (isset($success)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire('Success', <?= json_encode($success) ?>, 'success');
            });
        </script>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire('Error', <?= json_encode($error) ?>, 'error');
            });
        </script>
    <?php endif; ?>

    <div class="card glass">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-users-slash"></i> Worker List</div>
      </div>
      <div class="card-body">
        <table class="data-table">
          <thead>
            <tr>
              <th style="width: 60px;">S.No</th>
              <th>Name</th>
              <th>Contractor</th>
              <th>Aadhaar</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php $sno = 1; foreach($workers as $w): ?>
            <tr>
              <td><?= $sno++ ?></td>
              <td><?= htmlspecialchars($w['name']) ?></td>
              <td><?= htmlspecialchars($w['contractor_name']) ?></td>
              <td><code><?= htmlspecialchars($w['aadhaar']) ?></code></td>
              <td>
                <span class="badge <?= $w['is_blocked'] ? 'badge-danger' : 'badge-success' ?>">
                  <?= $w['is_blocked'] ? 'Blocked' : ucfirst($w['status']) ?>
                </span>
              </td>
              <td>
                <form method="POST" style="display:inline-flex; gap:5px;">
                  <input type="hidden" name="workman_id" value="<?= $w['id'] ?>">
                  <input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason..." required style="width:150px">
                  <?php if ($w['is_blocked']): ?>
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

