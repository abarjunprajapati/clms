<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Safety Officer';

function renderContent() {
    global $conn;
    
    // Fetch sessions with confirmed attendee count only.
    $sessions = db_fetch_all($conn, "
        SELECT ts.*,
               COALESCE(att.confirmed_count, 0) AS enrolled_count
        FROM training_schedule ts
        LEFT JOIN (
            SELECT tsw.session_id, COUNT(*) AS confirmed_count
            FROM training_session_workers tsw
            JOIN training_requests tr ON tr.id = tsw.training_request_id
            WHERE tr.status = 'contractor_confirmed'
            GROUP BY tsw.session_id
        ) att ON att.session_id = ts.id
        ORDER BY ts.session_date DESC, ts.session_time ASC
    ");
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
              <?php
                $capacity = max(1, (int)($sess['capacity'] ?? 0));
                $enrolled = (int)($sess['enrolled_count'] ?? 0);
                $progress = min(100, ($enrolled / $capacity) * 100);
                $sessionStatus = strtolower((string)($sess['session_status'] ?? 'open'));
              ?>
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
                  <div style="font-size:12px"><?= $enrolled ?> / <?= $capacity ?></div>
                  <div class="progress-bar-small">
                    <div class="progress-fill" style="width: <?= $progress ?>%"></div>
                  </div>
                </td>
                <td>
                  <?php if($sessionStatus == 'open'): ?>
                    <span class="badge badge-info">Open</span>
                  <?php elseif($sessionStatus == 'locked'): ?>
                    <span class="badge badge-warning">Locked</span>
                  <?php elseif($sessionStatus == 'cancelled'): ?>
                    <span class="badge badge-danger">Cancelled</span>
                  <?php else: ?>
                    <span class="badge badge-success">Completed</span>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="manage_session.php?id=<?= $sess['id'] ?>" class="btn btn-sm btn-outline">
                    <i class="fas fa-cog"></i> Manage
                  </a>
                  <?php if($sessionStatus !== 'completed' && $sessionStatus !== 'cancelled'): ?>
                    <button type="button" class="btn btn-sm btn-outline" onclick='openRescheduleSession(<?= json_encode([
                        'id' => (int)$sess['id'],
                        'date' => $sess['session_date'],
                        'time' => substr((string)$sess['session_time'], 0, 5),
                        'location' => $sess['location'],
                        'trainer' => $sess['trainer_name'],
                        'batch' => $sess['batch_number'] ?? '',
                    ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                      <i class="fas fa-clock"></i> Reschedule
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="cancelSession(<?= (int)$sess['id'] ?>)">
                      <i class="fas fa-ban"></i> Cancel
                    </button>
                  <?php endif; ?>
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
    <script>
    async function postSessionAction(payload) {
      const res = await fetch('../../api/safety/update_session.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (!data.success) throw new Error(data.message || 'Unable to update session.');
      return data;
    }

    async function cancelSession(id) {
      const reason = prompt('Reason for cancelling this training session:');
      if (reason === null) return;
      if (!reason.trim()) {
        alert('Cancellation reason is required.');
        return;
      }
      if (!confirm('Cancel this session and return workers to scheduling queue?')) return;
      try {
        const data = await postSessionAction({ action: 'cancel', session_id: id, reason });
        alert(data.message || 'Session cancelled.');
        location.reload();
      } catch (err) {
        alert(err.message);
      }
    }

    async function openRescheduleSession(session) {
      const date = prompt('New training date (YYYY-MM-DD):', session.date || '');
      if (date === null) return;
      const time = prompt('New training time (HH:MM):', session.time || '');
      if (time === null) return;
      const location = prompt('Training Hall / Venue:', session.location || '');
      if (location === null) return;
      const trainer = prompt('Instructor (optional):', session.trainer || '');
      if (trainer === null) return;
      const reason = prompt('Reason / remarks for reschedule:', 'Training schedule updated by Safety.');
      if (reason === null) return;
      if (!date.trim() || !time.trim() || !location.trim()) {
        alert('Date, time and venue are required.');
        return;
      }
      if (!confirm('Reschedule this session? Contractors will need to confirm again.')) return;
      try {
        const data = await postSessionAction({
          action: 'reschedule',
          session_id: session.id,
          session_date: date.trim(),
          session_time: time.trim(),
          location: location.trim(),
          trainer_name: trainer.trim(),
          batch_number: session.batch || '',
          reason: reason.trim()
        });
        alert(data.message || 'Session rescheduled.');
        location.reload();
      } catch (err) {
        alert(err.message);
      }
    }
    </script>
    <?php
}

renderLayout("Training Schedule", 'renderContent', $role, $name);
