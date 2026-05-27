<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Safety Officer';

function renderContent() {
    global $conn;
    
    // Stats for Safety User
    $total_requests = db_count($conn, "SELECT COUNT(*) c FROM training_requests WHERE status='pending'");
    $pending_training = db_count($conn, "SELECT COUNT(*) c FROM workmen WHERE training_status='training_pending'");
    $scheduled_sessions = db_count($conn, "SELECT COUNT(*) c FROM training_schedule WHERE session_status='open'");
    $failed_workers = db_count($conn, "SELECT COUNT(*) c FROM workmen WHERE training_status='training_failed'");
    $completed_today = db_count($conn, "SELECT COUNT(*) c FROM training_schedule WHERE session_status='completed' AND session_date = CURDATE()");

    $upcoming_sessions = db_fetch_all($conn, "SELECT * FROM training_schedule WHERE session_status='open' ORDER BY session_date ASC, session_time ASC LIMIT 5");
    ?>
    <div class="content-header">
      <h2 class="page-title">Safety Execution Desk</h2>
      <!-- <p class="page-subtitle">Manage training lifecycle and worker safety certifications.</p> -->
    </div>

    <div class="stats-grid">
      <div class="stat-card glass">
        <div class="stat-icon" style="background:rgba(59,130,246,0.1);color:var(--info)"><i class="fas fa-envelope-open-text"></i></div>
        <div class="stat-value"><?= $total_requests ?></div>
        <div class="stat-label">New Requests</div>
      </div>
      <div class="stat-card glass">
        <div class="stat-icon" style="background:rgba(16,185,129,0.1);color:var(--success)"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-value"><?= $scheduled_sessions ?></div>
        <div class="stat-label">Active Sessions</div>
      </div>
      <div class="stat-card glass">
        <div class="stat-icon" style="background:rgba(245,158,11,0.1);color:var(--warning)"><i class="fas fa-users"></i></div>
        <div class="stat-value"><?= $pending_training ?></div>
        <div class="stat-label">Pending Workers</div>
      </div>
      <div class="stat-card glass">
        <div class="stat-icon" style="background:rgba(239,68,68,0.1);color:var(--danger)"><i class="fas fa-user-times"></i></div>
        <div class="stat-value"><?= $failed_workers ?></div>
        <div class="stat-label">Failed / Retraining</div>
      </div>
    </div>

    <div class="grid grid-2" style="margin-top:20px; gap:20px">
      <div class="card glass">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-clock"></i> Upcoming Training Sessions</div>
          <a href="training_schedule.php" class="btn btn-sm btn-primary">Plan Session</a>
        </div>
        <div class="card-body">
          <table class="data-table">
            <thead>
              <tr>
                <th>Date & Time</th>
                <th>Location</th>
                <th>Type</th>
                <th>Enrolled</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($upcoming_sessions as $session): ?>
              <tr>
                <td>
                    <div style="font-weight:600"><?= date('d M Y', strtotime($session['session_date'])) ?></div>
                    <div style="font-size:11px;opacity:0.7"><?= date('H:i', strtotime($session['session_time'])) ?></div>
                </td>
                <td><?= htmlspecialchars($session['location']) ?></td>
                <td><span class="badge badge-outline"><?= ucfirst($session['training_type']) ?></span></td>
                <td><?= $session['enrolled_count'] ?> / <?= $session['capacity'] ?></td>
                <td><a href="manage_session.php?id=<?= $session['id'] ?>" class="btn btn-sm btn-outline">Manage</a></td>
              </tr>
              <?php endforeach; if(empty($upcoming_sessions)): ?>
              <tr><td colspan="5" style="text-align:center;padding:20px;opacity:0.5">No upcoming sessions.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card glass">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-bolt"></i> Quick Actions</div>
        </div>
        <div class="card-body">
          <div class="action-list">
            <a href="training_requests.php" class="action-item">
                <i class="fas fa-plus-circle"></i>
                <div>
                    <div class="action-name">Process Training Requests</div>
                    <div class="action-desc">Assign pending workers to training batches.</div>
                </div>
            </a>
            <a href="manage_session.php" class="action-item">
                <i class="fas fa-check-double"></i>
                <div>
                    <div class="action-name">Mark Attendance</div>
                    <div class="action-desc">Verify presence for ongoing sessions.</div>
                </div>
            </a>
            <a href="reports.php" class="action-item">
                <i class="fas fa-file-invoice"></i>
                <div>
                    <div class="action-name">Generate Compliance Report</div>
                    <div class="action-desc">View pass/fail ratios and training history.</div>
                </div>
            </a>
          </div>
        </div>
      </div>
    </div>

    <style>
    .action-list { display: flex; flex-direction: column; gap: 10px; }
    .action-item { 
        display: flex; gap: 15px; padding: 15px; border-radius: 10px; 
        background: rgba(255,255,255,0.03); text-decoration: none; color: inherit;
        transition: all 0.2s; border: 1px solid rgba(255,255,255,0.05);
    }
    .action-item:hover { background: rgba(255,255,255,0.08); transform: translateY(-2px); }
    .action-item i { font-size: 20px; color: var(--primary); margin-top: 5px; }
    .action-name { font-weight: 600; font-size: 14px; }
    .action-desc { font-size: 12px; opacity: 0.6; }
    </style>
    <?php
}

renderLayout("Safety Dashboard", 'renderContent', $role, $name);

