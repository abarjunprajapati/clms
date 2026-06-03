<?php
require_once '../../include/auth.php';
checkAuth(['contractor', 'customer']);
include '../../include/config.php';
include '../../include/customer_portal_context.php';
include '../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';
$user_id = $_SESSION['user_id'];
clms_get_portal_contractor($conn);

function renderContent() {
    global $conn, $user_id;

    $contractor = db_single($conn, "SELECT id, contractor_name FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    $c_id = $contractor['id'] ?? null;

    // Eligible workers (pending training)
    $eligible_workers = $c_id ? db_fetch_all($conn,
        "SELECT id, name, trade, skill, temp_id FROM workmen
         WHERE contractor_id = ?
           AND COALESCE(execution_training_status, 'pending') = 'approved'
           AND training_status IN ('pending','training_pending','training_failed','fail')
         ORDER BY name",
        'i', [$c_id]) : [];

    // All training requests for this contractor with full details
    $my_requests = $c_id ? db_fetch_all($conn,
        "SELECT tr.*, w.name as worker_name, w.trade as worker_trade, COALESCE(w.execution_training_status, 'pending') AS execution_training_status
         FROM training_requests tr
         JOIN workmen w ON tr.workman_id = w.id
         WHERE tr.contractor_id = ?
         ORDER BY tr.created_at DESC",
        'i', [$c_id]) : [];

    // Count pending confirmations (badge alert)
    $need_confirm = array_filter($my_requests, function($r) { return $r['status'] === 'scheduled'; });
    ?>

    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-graduation-cap" style="color:#8b5cf6;margin-right:10px;"></i> Safety Training Request</h2>
        <!-- <p class="page-subtitle">Submit training requests for enrolled workmen. Gate Pass requires training clearance.</p> -->
      </div>
      <?php if (!empty($need_confirm)): ?>
      <div>
        <span class="badge badge-warning" style="font-size:13px; padding:8px 14px; animation: pulse 2s infinite;">
          <i class="fas fa-bell"></i> <?= count($need_confirm) ?> Schedule(s) Need Confirmation
        </span>
      </div>
      <?php endif; ?>
    </div>

    <?php if (!$c_id): ?>
    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i><div>Complete <a href="annexure-2a.php">Contractor Registration</a> first.</div></div>
    <?php return; endif; ?>

    <!-- Confirm Modal -->
    <div id="confirmModal" class="modal-backdrop hidden">
      <div class="modal-content glass" style="max-width:500px; padding:0;">
        <div class="modal-header" style="padding:20px; border-bottom:1px solid rgba(255,255,255,0.1);">
          <h3><i class="fas fa-calendar-check" style="color:#8b5cf6;"></i> Confirm Training Schedule</h3>
          <button class="btn-close" onclick="closeConfirmModal()">&times;</button>
        </div>
        <div class="modal-body" style="padding:24px;">
          <div id="scheduleInfoBox" style="background:rgba(139,92,246,0.1); border:1px solid rgba(139,92,246,0.3); border-radius:12px; padding:16px; margin-bottom:20px;">
            <!-- populated via JS -->
          </div>
          <div class="form-group">
            <label class="form-label">Your Remarks (optional)</label>
            <textarea class="form-control" id="contractorRemarks" rows="3" placeholder="Any specific requirements, concerns or acknowledgement..."></textarea>
          </div>
          <div style="margin-top:20px; display:flex; gap:12px; justify-content:flex-end;">
            <button class="btn btn-outline" onclick="closeConfirmModal()">Cancel</button>
            <button class="btn btn-primary" id="confirmTrainingBtn" onclick="submitConfirmation()">
              <i class="fas fa-check-circle"></i> Confirm Attendance
            </button>
          </div>
          <input type="hidden" id="confirmRequestId" value="">
        </div>
      </div>
    </div>

    <div style="display:grid; grid-template-columns:400px 1fr; gap:20px; align-items:start;">

      <!-- Training Request Form -->
      <div class="card glass">
        <div class="card-header"><div class="card-title"><i class="fas fa-paper-plane"></i> Submit Training Request</div></div>
        <div class="card-body">
          <?php if (empty($eligible_workers)): ?>
          <div class="empty-state" style="padding:30px 0; text-align:center; color:var(--text-muted);">
            <i class="fas fa-graduation-cap" style="font-size:40px;opacity:.2;display:block;margin-bottom:12px;"></i>
            <p style="font-weight:600;">No eligible workers</p>
            <p style="font-size:13px;">Worker training request ke liye Executive Officer training attendance approval required hai.</p>
          </div>
          <?php else: ?>
          <form id="trainingForm">
            <div class="form-group">
              <label class="form-label required">Select Worker(s)</label>
              <select class="form-control" name="workman_ids[]" id="workerSelect" multiple size="<?= min(6, count($eligible_workers)) ?>" required>
                <?php foreach ($eligible_workers as $w): ?>
                <option value="<?= $w['id'] ?>">
                  <?= htmlspecialchars($w['name']) ?> — <?= htmlspecialchars($w['trade'] ?? '') ?>
                  <?= $w['temp_id'] ? '(' . htmlspecialchars($w['temp_id']) . ')' : '' ?>
                </option>
                <?php endforeach; ?>
              </select>
              <small style="font-size:11px;color:var(--text-muted);margin-top:3px;display:block;">Hold Ctrl/Cmd to select multiple</small>
            </div>

            <div class="form-group">
              <label class="form-label required">Training Type</label>
              <select class="form-control" name="training_type" required>
                <option value="">Select Training Type</option>
                <option value="Safety Induction">Safety Induction (Mandatory)</option>
                <option value="Fire Safety">Fire Safety Training</option>
                <option value="First Aid">First Aid Training</option>
                <option value="Permit to Work">Permit to Work (PTW)</option>
                <option value="Working at Height">Working at Height</option>
                <option value="Electrical Safety">Electrical Safety</option>
                <option value="Chemical Handling">Chemical Handling</option>
                <option value="PPE Usage">PPE Usage Training</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label required">Preferred Shift</label>
              <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:4px;">
                <label class="shift-option" id="shift-morning">
                  <input type="radio" name="preferred_shift" value="morning" required checked>
                  <div class="shift-card">
                    <i class="fas fa-sun" style="color:#f59e0b;"></i>
                    <strong>Morning</strong>
                    <small>8 AM – 12 PM</small>
                  </div>
                </label>
                <label class="shift-option" id="shift-evening">
                  <input type="radio" name="preferred_shift" value="evening">
                  <div class="shift-card">
                    <i class="fas fa-moon" style="color:#818cf8;"></i>
                    <strong>Evening</strong>
                    <small>2 PM – 6 PM</small>
                  </div>
                </label>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Preferred Date</label>
              <input type="date" class="form-control" name="preferred_date" min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
            </div>

            <div class="form-group">
              <label class="form-label">Remarks</label>
              <textarea class="form-control" name="remarks" rows="2" placeholder="Any special instructions..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;" id="submitTrainingBtn">
              <i class="fas fa-paper-plane"></i> Submit Training Request
            </button>
          </form>
          <?php endif; ?>
        </div>
      </div>

      <!-- Training Requests History -->
      <div class="card glass">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-history"></i> Training Request Status</div>
          <span class="badge badge-gray"><?= count($my_requests) ?> Records</span>
        </div>
        <div class="card-body" style="padding:0;">
          <?php if (empty($my_requests)): ?>
          <div style="padding:40px; text-align:center; color:var(--text-muted);">
            <i class="fas fa-clipboard-list" style="font-size:40px;opacity:.2;display:block;margin-bottom:12px;"></i>
            <p>No training requests submitted yet.</p>
          </div>
          <?php else: ?>
          <table class="data-table">
            <thead>
              <tr>
                <th>Worker</th>
                <th>Training Type</th>
                <th>Preferred</th>
                <th>Scheduled By Safety</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($my_requests as $r): ?>
            <?php
              $st = $r['status'] ?? 'pending';
              $executionApproved = strtolower((string)($r['execution_training_status'] ?? 'pending')) === 'approved';
              $displayStatus = (!$executionApproved && in_array($st, ['pending','failed','correction_required'], true)) ? 'exec_pending' : $st;
              $sc = [
                'exec_pending'          => 'badge-gray',
                'pending'              => 'badge-warning',
                'scheduled'            => 'badge-info',
                'contractor_confirmed' => 'badge-primary',
                'passed'               => 'badge-success',
                'completed'            => 'badge-success',
                'failed'               => 'badge-danger',
                'rejected'             => 'badge-danger',
              ];
            ?>
            <tr style="<?= $st === 'scheduled' ? 'background:rgba(99,102,241,0.06);' : '' ?>">
              <td>
                <div style="font-weight:600;"><?= htmlspecialchars($r['worker_name'] ?? '—') ?></div>
                <div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($r['worker_trade'] ?? '') ?></div>
              </td>
              <td><?= htmlspecialchars($r['training_type'] ?? '—') ?></td>
              <td>
                <?= $r['preferred_date'] ? date('d M Y', strtotime($r['preferred_date'])) : '—' ?>
                <?php if ($r['preferred_shift']): ?>
                <span class="badge badge-gray" style="font-size:10px;"><?= ucfirst($r['preferred_shift']) ?></span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($r['scheduled_date']): ?>
                  <div style="font-weight:600;"><?= date('d M Y', strtotime($r['scheduled_date'])) ?></div>
                  <div style="font-size:11px;">
                    <i class="fas <?= $r['scheduled_shift'] === 'morning' ? 'fa-sun' : 'fa-moon' ?>" style="color:<?= $r['scheduled_shift'] === 'morning' ? '#f59e0b' : '#818cf8' ?>;"></i>
                    <?= ucfirst($r['scheduled_shift']) ?> • <?= htmlspecialchars($r['scheduled_venue']) ?>
                  </div>
                  <?php if ($r['safety_remarks']): ?>
                  <div style="font-size:11px; color:var(--text-muted); margin-top:3px;"><i class="fas fa-comment-alt"></i> <?= htmlspecialchars($r['safety_remarks']) ?></div>
                  <?php endif; ?>
                <?php else: ?>
                  <span style="color:var(--text-muted); font-size:12px;">Awaiting schedule…</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge <?= $sc[$displayStatus] ?? 'badge-gray' ?>">
                  <?= $displayStatus === 'exec_pending' ? 'EXEC APPROVAL PENDING' : strtoupper(str_replace('_', ' ', $st)) ?>
                </span>
              </td>
              <td>
                <?php if ($st === 'scheduled'): ?>
                <button class="btn btn-sm btn-primary" onclick='openConfirmModal(<?= json_encode([
                  "id" => $r['id'],
                  "worker" => $r['worker_name'],
                  "date" => date('d M Y', strtotime($r['scheduled_date'])),
                  "shift" => $r['scheduled_shift'],
                  "venue" => $r['scheduled_venue'],
                  "time" => $r['scheduled_time'] ?? '',
                  "remarks" => $r['safety_remarks'] ?? ''
                ]) ?>)'>
                  <i class="fas fa-check"></i> Confirm
                </button>
                <?php elseif ($st === 'contractor_confirmed'): ?>
                <span style="font-size:11px; color:var(--success);"><i class="fas fa-check-circle"></i> Confirmed</span>
                <?php if ($r['contractor_remarks']): ?>
                <div style="font-size:10px; color:var(--text-muted);"><?= htmlspecialchars($r['contractor_remarks']) ?></div>
                <?php endif; ?>
                <?php elseif ($st === 'completed' || $st === 'passed'): ?>
                <span style="font-size:11px; color:var(--success);"><i class="fas fa-trophy"></i> Passed</span>
                <?php else: ?>
                <span style="font-size:11px; color:var(--text-muted);">—</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Info Box -->
    <div class="alert alert-info" style="margin-top:20px;">
      <i class="fas fa-info-circle"></i>
      <div>
        <strong>Training Flow:</strong>
        Submit request (choose Morning/Evening) →
        Safety Dept. schedules with date, shift & venue →
        <strong>You confirm attendance here</strong> →
        Safety conducts training →
        Result recorded (Pass/Fail) →
        Pass required for Gate Pass.
      </div>
    </div>

    <style>
    .form-group { margin-bottom:14px; }
    .form-label { display:block; font-size:13px; font-weight:600; margin-bottom:5px; }
    .form-label.required::after { content:' *'; color:#ef4444; }
    .form-control { width:100%; padding:9px 13px; border-radius:8px; border:1.5px solid var(--border-color); background:var(--input-bg, rgba(255,255,255,.04)); color:var(--text-primary); font-size:13px; transition:.2s; box-sizing:border-box; }
    .form-control:focus { outline:none; border-color:#8b5cf6; box-shadow:0 0 0 3px rgba(139,92,246,.12); }
    select[multiple] { padding:4px; }
    select[multiple] option { padding:6px 8px; border-radius:4px; }
    select[multiple] option:checked { background:rgba(139,92,246,.15); color:var(--text-primary); }
    .empty-state { text-align:center; color:var(--text-muted); }

    /* Shift Selector */
    .shift-option { cursor:pointer; display:block; }
    .shift-option input[type="radio"] { display:none; }
    .shift-card { display:flex; flex-direction:column; align-items:center; gap:4px; padding:14px 8px; border-radius:12px; border:2px solid var(--border-color); transition:.2s; text-align:center; }
    .shift-card i { font-size:22px; }
    .shift-card strong { font-size:13px; }
    .shift-card small { font-size:11px; color:var(--text-muted); }
    .shift-option input:checked + .shift-card { border-color:#8b5cf6; background:rgba(139,92,246,.12); }
    .shift-option:hover .shift-card { border-color:rgba(139,92,246,.5); }

    /* Modal */
    .modal-backdrop { position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.7); backdrop-filter:blur(5px); display:flex; align-items:center; justify-content:center; z-index:1100; }
    .modal-content { width:90%; border-radius:16px; border:1px solid rgba(255,255,255,0.15); box-shadow:0 25px 50px -12px rgba(0,0,0,0.5); }
    .hidden { display:none; }
    .modal-header { display:flex; justify-content:space-between; align-items:center; }
    .btn-close { background:none; border:none; font-size:28px; color:var(--text-muted); cursor:pointer; }

    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.6} }

    .toast-msg { position:fixed; bottom:30px; right:30px; z-index:9999; padding:14px 20px; border-radius:12px; display:flex; align-items:center; gap:10px; font-size:14px; font-weight:600; animation:slideUp .3s ease; box-shadow:0 8px 30px rgba(0,0,0,.2); }
    .toast-success { background:#10b981; color:white; }
    .toast-error   { background:#ef4444; color:white; }
    @keyframes slideUp { from{transform:translateY(30px);opacity:0;} to{transform:translateY(0);opacity:1;} }
    </style>

    <script>
    function showToast(msg, type='success') {
      let t = document.createElement('div');
      t.className = 'toast-msg toast-' + type;
      t.innerHTML = `<i class="fas fa-${type==='success'?'check-circle':'exclamation-circle'}"></i> ${msg}`;
      document.body.appendChild(t);
      setTimeout(() => t.remove(), 3500);
    }

    // Submit new training request
    document.getElementById('trainingForm')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = document.getElementById('submitTrainingBtn');
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

      const form = e.target;
      const selectedWorkers = Array.from(document.getElementById('workerSelect').selectedOptions).map(o => o.value);
      if (selectedWorkers.length === 0) {
        showToast('Please select at least one worker.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Training Request';
        return;
      }

      const data = {
        workman_ids:     selectedWorkers,
        training_type:   form.querySelector('[name="training_type"]').value,
        preferred_shift: form.querySelector('[name="preferred_shift"]:checked')?.value || 'morning',
        preferred_date:  form.querySelector('[name="preferred_date"]').value,
        remarks:         form.querySelector('[name="remarks"]').value
      };

      try {
        const res = await fetch('../../api/submit_training_request.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify(data)
        });
        const result = await res.json();
        if (result.success) {
          showToast('Training request submitted successfully!', 'success');
          setTimeout(() => location.reload(), 1800);
        } else {
          showToast('Error: ' + (result.message || 'Submission failed'), 'error');
        }
      } catch(err) {
        showToast('Network error — please try again.', 'error');
      }

      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Training Request';
    });

    // Confirm training modal
    function openConfirmModal(data) {
      document.getElementById('confirmRequestId').value = data.id;
      document.getElementById('contractorRemarks').value = '';
      const shiftLabel = data.shift === 'morning' ? '☀️ Morning (8 AM – 12 PM)' : '🌙 Evening (2 PM – 6 PM)';
      document.getElementById('scheduleInfoBox').innerHTML = `
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <div><div style="font-size:11px; font-weight:700; opacity:.6; text-transform:uppercase;">Worker</div><div style="font-weight:600;">${data.worker}</div></div>
          <div><div style="font-size:11px; font-weight:700; opacity:.6; text-transform:uppercase;">Date</div><div style="font-weight:600;">${data.date}</div></div>
          <div><div style="font-size:11px; font-weight:700; opacity:.6; text-transform:uppercase;">Shift</div><div style="font-weight:600;">${shiftLabel}</div></div>
          <div><div style="font-size:11px; font-weight:700; opacity:.6; text-transform:uppercase;">Venue</div><div style="font-weight:600;">${data.venue}</div></div>
          ${data.time ? `<div><div style="font-size:11px; font-weight:700; opacity:.6; text-transform:uppercase;">Time</div><div style="font-weight:600;">${data.time}</div></div>` : ''}
          ${data.remarks ? `<div style="grid-column:span 2;"><div style="font-size:11px; font-weight:700; opacity:.6; text-transform:uppercase;">Safety Remarks</div><div style="font-weight:500; color:var(--text-muted);">${data.remarks}</div></div>` : ''}
        </div>
      `;
      document.getElementById('confirmModal').classList.remove('hidden');
    }

    function closeConfirmModal() {
      document.getElementById('confirmModal').classList.add('hidden');
    }

    async function submitConfirmation() {
      const btn = document.getElementById('confirmTrainingBtn');
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Confirming...';

      const reqId   = document.getElementById('confirmRequestId').value;
      const remarks = document.getElementById('contractorRemarks').value;

      try {
        const res = await fetch('../../api/contractor/confirm_training.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ request_id: parseInt(reqId), contractor_remarks: remarks })
        });
        const result = await res.json();
        if (result.success) {
          showToast('Training confirmed! Safety team notified.', 'success');
          closeConfirmModal();
          setTimeout(() => location.reload(), 1800);
        } else {
          showToast('Error: ' + (result.error || 'Failed to confirm'), 'error');
          btn.disabled = false;
          btn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Attendance';
        }
      } catch (err) {
        showToast('Network error. Please try again.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Attendance';
      }
    }
    </script>
    <?php
}

renderLayout("Training Request", 'renderContent', $role, $name);
?>
