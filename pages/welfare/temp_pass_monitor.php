<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin', 'pass_user']);

include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = get_normalized_role();
$name = $_SESSION['name'] ?? 'Officer';

function renderContent() {
    global $conn;
    
    $query = "SELECT w.*, c.contractor_name 
              FROM workmen w 
              JOIN contractors c ON w.contractor_id = c.id 
              WHERE w.status = 'temporary_issued'
              ORDER BY w.valid_to ASC";
    $passes = db_fetch_all($conn, $query);
    ?>
    <div class="content-header">
      <h2 class="page-title">Temporary Pass Monitoring</h2>
      <p class="page-subtitle">Track validity and expiration of short-term temporary gate passes.</p>
    </div>

    <div class="card glass">
      <div class="card-body" style="padding:0">
        <table class="data-table">
          <thead>
            <tr>
              <th>Workman</th>
              <th>Contractor</th>
              <th>Valid From</th>
              <th>Valid To</th>
              <th>Days Left</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($passes as $p): 
                $expiryDate = $p['temp_valid_to'] ?? $p['valid_to'];
                $days_left = ceil((strtotime($expiryDate) - time()) / (60 * 60 * 24));
            ?>
            <tr>
              <td><strong><?= htmlspecialchars($p['name'] ?? 'Unknown') ?></strong></td>
              <td><?= htmlspecialchars($p['contractor_name'] ?? 'N/A') ?></td>
              <td><?= date('d M Y', strtotime($p['temp_valid_from'] ?? $p['valid_from'])) ?></td>
              <td><?= date('d M Y', strtotime($p['temp_valid_to'] ?? $p['valid_to'])) ?></td>
              <td>
                <?php if($days_left < 0): ?>
                  <span class="badge badge-danger">Expired</span>
                <?php elseif($days_left <= 3): ?>
                  <span class="badge badge-warning"><?= $days_left ?> Days</span>
                <?php else: ?>
                  <span class="badge badge-info"><?= $days_left ?> Days</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="pass_validity.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-primary">Extend</a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($passes)): ?>
            <tr><td colspan="6" class="text-center" style="padding:40px;">No active temporary passes found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
}

renderLayout("Temporary Pass Monitoring", 'renderContent', $role, $name);

