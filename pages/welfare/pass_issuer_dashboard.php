<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['pass_user']);

include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = get_normalized_role();
$name = $_SESSION['name'] ?? 'Pass Issuing Officer';

function renderContent() {
    global $conn;
    
    // Stats for Dashboard
    $pending_pass = db_count($conn, "SELECT COUNT(*) c FROM workmen WHERE welfare_user_verified = 1 AND pass_issuer_verified = 0 AND (safety_training_status = 1 OR training_status IN ('pass','passed','training_passed','qualified','completed')) AND is_blocked = 0");
    $temp_issued  = db_count($conn, "SELECT COUNT(*) c FROM workmen WHERE status = 'temporary_issued'");
    $perm_issued  = db_count($conn, "SELECT COUNT(*) c FROM workmen WHERE status IN ('acc_generated', 'permanent_active')");
    $rejected     = db_count($conn, "SELECT COUNT(*) c FROM workmen WHERE status = 'rejected' OR status = 'reupload_pending'");
    $expired      = db_count($conn, "SELECT COUNT(*) c FROM workmen WHERE status = 'expired' OR (valid_to < CURDATE() AND status != 'permanent_active')");
    
    ?>
    <div class="content-header">
      <h2 class="page-title">Pass Issuing Dashboard</h2>
      <!-- <p class="page-subtitle">Final authority for gate pass creation and lifecycle management.</p> -->
    </div>

    <div class="stats-grid">
      <div class="stat-card glass border-primary">
        <div class="stat-icon bg-soft-primary text-primary"><i class="fas fa-clock"></i></div>
        <div class="stat-value"><?= $pending_pass ?></div>
        <div class="stat-label">Pending Requests</div>
      </div>
      <div class="stat-card glass border-info">
        <div class="stat-icon bg-soft-info text-info"><i class="fas fa-id-badge"></i></div>
        <div class="stat-value"><?= $temp_issued ?></div>
        <div class="stat-label">Temporary Issued</div>
      </div>
      <div class="stat-card glass border-success">
        <div class="stat-icon bg-soft-success text-success"><i class="fas fa-id-card-clip"></i></div>
        <div class="stat-value"><?= $perm_issued ?></div>
        <div class="stat-label">Permanent (ACC) Issued</div>
      </div>
      <div class="stat-card glass border-danger">
        <div class="stat-icon bg-soft-danger text-danger"><i class="fas fa-user-xmark"></i></div>
        <div class="stat-value"><?= $rejected ?></div>
        <div class="stat-label">Rejected / Re-upload</div>
      </div>
      <div class="stat-card glass border-warning">
        <div class="stat-icon bg-soft-warning text-warning"><i class="fas fa-calendar-times"></i></div>
        <div class="stat-value"><?= $expired ?></div>
        <div class="stat-label">Expired Passes</div>
      </div>
    </div>

    <div class="grid grid-2" style="margin-top:24px;">
      <div class="card glass">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-bolt text-primary"></i> Quick Actions</div>
        </div>
        <div class="card-body">
          <div class="quick-actions">
            <a href="pending_requests.php" class="btn btn-primary btn-block"><i class="fas fa-list"></i> Process Pending Queue</a>
            <a href="issue_temp_pass.php" class="btn btn-outline-info btn-block"><i class="fas fa-id-badge"></i> Issue Temporary Pass</a>
            <a href="acc_generation.php" class="btn btn-outline-primary btn-block"><i class="fas fa-microchip"></i> Generate ACC Numbers</a>
            <a href="pass_validity.php" class="btn btn-outline-warning btn-block"><i class="fas fa-calendar-plus"></i> Extend Pass Validity</a>
          </div>
        </div>
      </div>

      <div class="card glass">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-history text-info"></i> Recent Issuance</div>
        </div>
        <div class="card-body" style="padding:0">
          <table class="data-table">
            <thead>
              <tr>
                <th>Workman</th>
                <th>Contractor</th>
                <th>Type</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $recent = db_fetch_all($conn, "SELECT w.*, c.contractor_name FROM workmen w JOIN contractors c ON w.contractor_id = c.id WHERE w.status IN ('temporary_issued', 'acc_generated', 'permanent_active') ORDER BY w.updated_at DESC LIMIT 5");
              foreach($recent as $r):
              ?>
              <tr>
                <td><?= htmlspecialchars($r['name']) ?></td>
                <td><?= htmlspecialchars($r['contractor_name']) ?></td>
                <td><?= ucfirst($r['status'] == 'temporary_issued' ? 'Temporary' : 'Permanent') ?></td>
                <td><span class="badge badge-<?= $r['status'] == 'permanent_active' ? 'success' : 'info' ?>"><?= str_replace('_', ' ', $r['status']) ?></span></td>
              </tr>
              <?php endforeach; ?>
              <?php if(empty($recent)): ?>
              <tr><td colspan="4" class="text-center">No recent issuance records found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <style>
      .bg-soft-primary { background: rgba(99, 102, 241, 0.1); }
      .bg-soft-info { background: rgba(6, 182, 212, 0.1); }
      .bg-soft-success { background: rgba(34, 197, 94, 0.1); }
      .bg-soft-danger { background: rgba(239, 68, 68, 0.1); }
      .bg-soft-warning { background: rgba(245, 158, 11, 0.1); }
      .border-primary { border-left: 4px solid #6366f1 !important; }
      .border-info { border-left: 4px solid #06b6d4 !important; }
      .border-success { border-left: 4px solid #22c55e !important; }
      .border-danger { border-left: 4px solid #ef4444 !important; }
      .border-warning { border-left: 4px solid #f59e0b !important; }
      .btn-block { display: block; width: 100%; margin-bottom: 12px; text-align: left; padding: 12px 16px; border-radius: 8px; }
      .btn-block i { margin-right: 12px; width: 20px; text-align: center; }
    </style>
    <?php
}

renderLayout("Pass Issuing Dashboard", 'renderContent', $role, $name);

