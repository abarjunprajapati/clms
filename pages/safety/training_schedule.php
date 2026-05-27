<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Safety Officer';

function renderContent() {
    global $conn;
    
    // Fetch all active/upcoming sessions
    $sessions = db_fetch_all($conn, "SELECT * FROM training_schedule ORDER BY session_date DESC, session_time ASC");
    ?>
    <div class="content-header">
      <h2 class="page-title">Training Schedule</h2>
      <!-- <p class="page-subtitle">Plan and manage safety training sessions.</p> -->
    </div>

    <div class="grid grid-3" style="gap:20px">
      <!-- CREATE SESSION FORM -->
      <div class="card glass" style="grid-column: span 1">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-calendar-plus"></i> Create New Session</div>
        </div>
        <div class="card-body">
          <form action="../../api/safety/create_session.php" method="POST">
            <div class="form-group">
              <label class="form-label">Training Date</label>
              <input type="date" name="session_date" class="form-control" required min="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Time Slot</label>
              <input type="time" name="session_time" class="form-control" required>
            </div>
            <div class="form-group">
              <label class="form-label">Location / Venue</label>
              <input type="text" name="location" class="form-control" placeholder="e.g. Training Hall A" required>
            </div>
            <div class="form-group">
              <label class="form-label">Training Type</label>
              <select name="training_type" class="form-control" required>
                <option value="induction">Safety Induction (Mandatory)</option>
                <option value="refresher">Refresher Training</option>
                <option value="special">Specialized Safety Training</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Batch Size (Capacity)</label>
              <input type="number" name="capacity" class="form-control" value="30" min="1" required>
            </div>
            <div class="form-group">
              <label class="form-label">Trainer Name</label>
              <input type="text" name="trainer_name" class="form-control" placeholder="Name of safety officer">
            </div>
            <div class="form-group">
              <label class="form-label">Remarks</label>
              <textarea name="remarks" class="form-control" rows="2"></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-block" style="margin-top:10px">
              <i class="fas fa-save"></i> Save Session
            </button>
          </form>
        </div>
      </div>

      <!-- SESSION LIST -->
      <div class="card glass" style="grid-column: span 2">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-list"></i> Scheduled Sessions</div>
        </div>
        <div class="card-body">
          <table class="data-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Details</th>
                <th>Type</th>
                <th>Capacity</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($sessions as $sess): ?>
              <tr>
                <td>
                  <div style="font-weight:600"><?= date('d M Y', strtotime($sess['session_date'])) ?></div>
                  <div style="font-size:11px;opacity:0.7"><?= date('H:i', strtotime($sess['session_time'])) ?></div>
                </td>
                <td>
                  <div style="font-size:13px;font-weight:500"><?= htmlspecialchars($sess['location']) ?></div>
                  <div style="font-size:11px;opacity:0.6">Trainer: <?= htmlspecialchars($sess['trainer_name'] ?: 'Not Assigned') ?></div>
                </td>
                <td><span class="badge badge-outline"><?= ucfirst($sess['training_type']) ?></span></td>
                <td>
                  <div style="font-size:12px"><?= $sess['enrolled_count'] ?> / <?= $sess['capacity'] ?></div>
                  <div class="progress-bar-small">
                    <div class="progress-fill" style="width: <?= ($sess['enrolled_count']/$sess['capacity'])*100 ?>%"></div>
                  </div>
                </td>
                <td>
                  <?php if($sess['session_status'] == 'open'): ?>
                    <span class="badge badge-info">Open</span>
                  <?php elseif($sess['session_status'] == 'locked'): ?>
                    <span class="badge badge-warning">Locked</span>
                  <?php else: ?>
                    <span class="badge badge-success">Completed</span>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="manage_session.php?id=<?= $sess['id'] ?>" class="btn btn-sm btn-outline">
                    <i class="fas fa-cog"></i> Manage
                  </a>
                </td>
              </tr>
              <?php endforeach; if(empty($sessions)): ?>
              <tr><td colspan="6" style="text-align:center;padding:40px;opacity:0.5">No sessions found. Create one to start.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <style>
    .progress-bar-small { height: 4px; background: rgba(255,255,255,0.1); border-radius: 2px; margin-top: 5px; width: 60px; overflow: hidden; }
    .progress-fill { height: 100%; background: var(--primary); }
    .btn-block { width: 100%; display: block; }
    </style>
    <?php
}

renderLayout("Training Schedule", 'renderContent', $role, $name);

