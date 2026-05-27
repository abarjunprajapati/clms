<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Safety Officer';

function renderContent() {
    global $conn;
    
    // Fetch workers who are in training_pending status but NOT yet scheduled in any OPEN session
    // Or just all training_pending workers
    $pending_workers = db_fetch_all($conn, "
        SELECT w.*, c.contractor_name
        FROM workmen w
        JOIN contractors c ON w.contractor_id = c.id
        WHERE w.training_status = 'training_pending'
        ORDER BY w.created_at ASC
    ");

    ?>
    <div class="content-header">
      <h2 class="page-title">Pending Training Queue</h2>
      <!-- <p class="page-subtitle">Identify workers awaiting safety induction before they can be deployed.</p> -->
    </div>

    <div class="card glass">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-hourglass-half"></i> Backlog (<?= count($pending_workers) ?> Workers)</div>
        <a href="training_requests.php" class="btn btn-sm btn-primary">Process Requests</a>
      </div>
      <div class="card-body">
        <table class="data-table">
          <thead>
            <tr>
              <th>Worker Name</th>
              <th>Code</th>
              <th>Contractor</th>
              <th>Trade</th>
              <th>Days in Queue</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($pending_workers as $w): 
                $days = floor((time() - strtotime($w['created_at'])) / (60 * 60 * 24));
            ?>
            <tr>
              <td><strong><?= htmlspecialchars($w['name']) ?></strong></td>
              <td><?= htmlspecialchars($w['temp_id']) ?></td>
              <td><?= htmlspecialchars($w['contractor_name']) ?></td>
              <td><?= htmlspecialchars($w['trade']) ?></td>
              <td>
                <span class="<?= $days > 7 ? 'text-danger' : ($days > 3 ? 'text-warning' : '') ?>" style="font-weight:600">
                    <?= $days ?> Days
                </span>
              </td>
              <td>
                <a href="training_requests.php?search=<?= urlencode($w['temp_id']) ?>" class="btn btn-sm btn-outline">Schedule</a>
              </td>
            </tr>
            <?php endforeach; if(empty($pending_workers)): ?>
            <tr><td colspan="6" style="text-align:center;padding:40px;opacity:0.5">Queue is clear! No pending workers.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
}

renderLayout("Pending Queue", 'renderContent', $role, $name);

