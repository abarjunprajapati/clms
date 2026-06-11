<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Safety Officer';
$session_id = $_GET['id'] ?? null;

if (!$session_id) {
    header("Location: training_schedule.php");
    exit;
}

$session = db_single($conn, "SELECT * FROM training_schedule WHERE id=?", 'i', [$session_id]);
if (!$session) {
    die("Session not found.");
}

if (!empty($session['batch_number'])) {
    $batch = db_single($conn, "SELECT id FROM training_class_batches WHERE batch_number = ? LIMIT 1", 's', [$session['batch_number']]);
    if ($batch) {
        db_execute(
            $conn,
            "INSERT INTO training_session_workers (session_id, workman_id, training_request_id, attendance_status, result, created_at)
             SELECT ?, tbw.workman_id, tbw.training_request_id, 'pending', 'pending', NOW()
             FROM training_batch_workers tbw
             JOIN training_requests tr ON tr.id = tbw.training_request_id
             WHERE tbw.batch_id = ?
               AND tbw.ticked = 1
               AND tr.status IN ('scheduled', 'contractor_confirmed')
               AND NOT EXISTS (
                   SELECT 1
                   FROM training_session_workers tsw
                   WHERE tsw.training_request_id = tbw.training_request_id
               )",
            'ii',
            [(int)$session_id, (int)$batch['id']]
        );
    }
}

function safetySessionSetting($conn, $key, $default) {
    $table = mysqli_query($conn, "SHOW TABLES LIKE 'system_settings'");
    if (!$table || mysqli_num_rows($table) === 0) {
        return $default;
    }

    $row = db_single($conn, "SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1", 's', [$key]);
    return isset($row['setting_value']) && $row['setting_value'] !== '' ? $row['setting_value'] : $default;
}

function renderContent() {
    global $conn, $session, $session_id;
    $passMark = (int)safetySessionSetting($conn, 'training_pass_mark', 60);
    $validityDays = (int)safetySessionSetting($conn, 'training_validity_days', 365);
    
    // Fetch assigned workers
    $workers = db_fetch_all($conn, "
        SELECT sw.*, tr.status AS request_status, COALESCE(tr.contractor_confirmed, 0) AS contractor_confirmed,
               w.name, w.temp_id as worker_code, c.contractor_name, w.trade
        FROM training_session_workers sw
        JOIN training_requests tr ON tr.id = sw.training_request_id
        JOIN workmen w ON sw.workman_id = w.id
        JOIN contractors c ON w.contractor_id = c.id
        WHERE sw.session_id = ?
          AND tr.status IN ('scheduled', 'contractor_confirmed')
    ", 'i', [$session_id]);

$is_locked = in_array(strtolower((string)($session['session_status'] ?? 'open')), ['completed', 'cancelled'], true);
    ?>
    <div class="content-header">
      <div style="display:flex; justify-content:space-between; align-items:flex-start">
        <div>
          <h2 class="page-title">Session: <?= htmlspecialchars($session['location']) ?></h2>
          <p class="page-subtitle">
            <i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($session['session_date'])) ?> 
            | <i class="fas fa-clock"></i> <?= date('H:i', strtotime($session['session_time'])) ?>
            | <span class="badge badge-outline"><?= ucfirst($session['training_type']) ?></span>
          </p>
        </div>
        <div>
            <?php if($is_locked): ?>
                <span class="badge badge-success" style="padding:10px 20px"><i class="fas fa-lock"></i> SESSION COMPLETED</span>
            <?php else: ?>
                <form action="../../api/safety/complete_session.php" method="POST" onsubmit="return confirm('Lock this session and finalize results?')">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="session_id" value="<?= $session_id ?>">
                    <button type="submit" class="btn btn-success"><i class="fas fa-check-circle"></i> Finalize & Lock Session</button>
                </form>
            <?php endif; ?>
        </div>
      </div>
    </div>

    <?php if (!empty($_GET['success'])): ?>
        <div class="alert alert-info"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php elseif (!empty($_GET['error'])): ?>
        <div class="alert" style="background:rgba(239,68,68,0.1);color:#b91c1c;border-color:rgba(239,68,68,0.2)"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <!-- TABS NAVIGATION -->
    <div class="tabs-container">
      <div class="tabs-header">
        <div class="tab-item active" onclick="showTab(event, 'control')"><i class="fas fa-calendar-alt"></i> Schedule Control</div>
        <div class="tab-item" onclick="showTab(event, 'workers')"><i class="fas fa-users"></i> Assigned Workers</div>
        <div class="tab-item" onclick="showTab(event, 'attendance')"><i class="fas fa-clipboard-user"></i> Attendance</div>
        <div class="tab-item" onclick="showTab(event, 'results')"><i class="fas fa-poll-h"></i> Upload Results</div>
        <div class="tab-item" onclick="showTab(event, 'summary')"><i class="fas fa-info-circle"></i> Summary</div>
      </div>

      <div class="tab-content active" id="tab-control">
        <div class="card-body">
            <div class="alert alert-info">
                Use this control desk to postpone, advance, cancel or update this training session. Contractors will receive schedule update notifications where notification support is available.
            </div>
            <form action="../../api/safety/update_session.php" method="POST" class="schedule-control-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="session_id" value="<?= (int)$session_id ?>">
                <input type="hidden" name="schedule_action" value="update">
                <div class="control-grid">
                    <div class="form-group">
                        <label class="form-label">Training Date</label>
                        <input type="date" name="session_date" class="form-control" value="<?= htmlspecialchars($session['session_date'] ?? '') ?>" <?= $is_locked ? 'disabled' : '' ?> required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Training Time</label>
                        <input type="time" name="session_time" class="form-control" value="<?= !empty($session['session_time']) ? htmlspecialchars(substr($session['session_time'], 0, 5)) : '' ?>" <?= $is_locked ? 'disabled' : '' ?> required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Venue</label>
                        <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($session['location'] ?? '') ?>" <?= $is_locked ? 'disabled' : '' ?> required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Capacity</label>
                        <input type="number" min="1" name="capacity" class="form-control" value="<?= (int)($session['capacity'] ?? 30) ?>" <?= $is_locked ? 'disabled' : '' ?>>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Batch Number</label>
                        <input type="text" name="batch_number" class="form-control" value="<?= htmlspecialchars($session['batch_number'] ?? '') ?>" <?= $is_locked ? 'disabled' : '' ?>>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Instructor</label>
                        <input type="text" name="trainer_name" class="form-control" value="<?= htmlspecialchars($session['trainer_name'] ?? '') ?>" <?= $is_locked ? 'disabled' : '' ?>>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Training Type</label>
                        <select name="training_type" class="form-control" <?= $is_locked ? 'disabled' : '' ?>>
                            <?php foreach (['induction' => 'Safety Induction', 'refresher' => 'Refresher Training', 'special' => 'Specialized Safety Training'] as $value => $label): ?>
                                <option value="<?= $value ?>" <?= strtolower((string)($session['training_type'] ?? 'induction')) === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reason / Intimation Text</label>
                        <input type="text" name="change_reason" class="form-control" value="Training schedule updated by Safety." <?= $is_locked ? 'disabled' : '' ?>>
                    </div>
                </div>
                <?php if (!$is_locked): ?>
                <div class="control-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Schedule Update</button>
                    <button type="button" class="btn btn-outline btn-cancel-session"><i class="fas fa-ban"></i> Cancel Session</button>
                </div>
                <?php endif; ?>
            </form>
        </div>
      </div>

      <div class="tab-content" id="tab-workers">
        <div class="card-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Worker</th>
                        <th>Contractor</th>
                        <th>Trade</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($workers as $w): ?>
                    <tr>
                        <td>
                            <div style="font-weight:600"><?= htmlspecialchars($w['name']) ?></div>
                            <div style="font-size:11px;opacity:0.7"><?= htmlspecialchars($w['worker_code']) ?></div>
                        </td>
                        <td><?= htmlspecialchars($w['contractor_name']) ?></td>
                        <td><?= htmlspecialchars($w['trade']) ?></td>
                        <td>
                            <?php if(($w['request_status'] ?? '') === 'scheduled' && (int)($w['contractor_confirmed'] ?? 0) === 0): ?>
                                <span class="badge badge-info">Awaiting Contractor</span>
                            <?php elseif($w['attendance_status'] == 'present'): ?>
                                <span class="badge badge-success">Present</span>
                            <?php elseif($w['attendance_status'] == 'absent'): ?>
                                <span class="badge badge-danger">Absent</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if(!$is_locked): ?>
                                <form action="../../api/safety/remove_session_worker.php" method="POST" class="remove-attendee-form" style="display:inline">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="session_id" value="<?= (int)$session_id ?>">
                                    <input type="hidden" name="workman_id" value="<?= (int)$w['workman_id'] ?>">
                                    <input type="hidden" name="reason" value="Removed from this training session by Safety.">
                                    <button type="submit" class="btn btn-sm btn-outline"><i class="fas fa-user-minus"></i> Remove</button>
                                </form>
                            <?php else: ?>
                                <span style="font-size:12px;color:#64748b">Locked</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; if(empty($workers)): ?>
                    <tr><td colspan="5" style="text-align:center;padding:40px;opacity:0.5">No workers assigned to this session.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
      </div>

      <div class="tab-content" id="tab-attendance">
        <div class="card-body">
            <?php if($is_locked): ?>
                <div class="alert alert-info">Attendance is locked for this completed session.</div>
            <?php endif; ?>
            <form action="../../api/safety/save_attendance.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="session_id" value="<?= $session_id ?>">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Worker</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($workers as $w): ?>
                        <tr>
                            <td><?= htmlspecialchars($w['name']) ?> (<?= $w['worker_code'] ?>)</td>
                            <td>
                                <select name="attendance[<?= $w['workman_id'] ?>]" class="form-control" <?= $is_locked ? 'disabled' : '' ?>>
                                    <option value="pending" <?= $w['attendance_status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="present" <?= $w['attendance_status'] == 'present' ? 'selected' : '' ?>>Present</option>
                                    <option value="absent" <?= $w['attendance_status'] == 'absent' ? 'selected' : '' ?>>Absent</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="remarks[<?= $w['workman_id'] ?>]" class="form-control" value="<?= htmlspecialchars($w['remarks'] ?: '') ?>" <?= $is_locked ? 'disabled' : '' ?>>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if(!$is_locked && !empty($workers)): ?>
                <div style="margin-top:20px; text-align:right">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Attendance</button>
                </div>
                <?php endif; ?>
            </form>
        </div>
      </div>

      <div class="tab-content" id="tab-results">
        <div class="card-body">
            <?php if($is_locked): ?>
                <div class="alert alert-info">Results are locked for this completed session.</div>
            <?php endif; ?>
            <div class="alert alert-info">
                Pass mark: <strong><?= $passMark ?></strong> out of 100. Safety training validity: <strong><?= $validityDays ?> days</strong>.
            </div>
            <form action="../../api/safety/save_results.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="session_id" value="<?= $session_id ?>">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Worker</th>
                            <th>Attendance</th>
                            <th>Theory Marks</th>
                            <th>Practical Marks</th>
                            <th>Total / Result</th>
                            <th>Valid Till</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($workers as $w): ?>
                        <?php
                            $theoryScore = (int)($w['theory_score'] ?? 0);
                            $practicalScore = (int)($w['practical_score'] ?? 0);
                            $totalScore = (int)($w['total_score'] ?? ($theoryScore + $practicalScore));
                            $resultValue = strtolower((string)($w['result'] ?? 'pending'));
                            $validTill = $w['valid_till'] ?: date('Y-m-d', strtotime('+' . $validityDays . ' days'));
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($w['name']) ?></td>
                            <td>
                                <?php if($w['attendance_status'] == 'present'): ?>
                                    <span class="badge badge-success">Present</span>
                                <?php else: ?>
                                    <span class="badge badge-danger"><?= ucfirst($w['attendance_status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($w['attendance_status'] == 'present'): ?>
                                    <input type="number" min="0" max="100" name="theory_score[<?= $w['workman_id'] ?>]" class="form-control marks-input" data-worker="<?= (int)$w['workman_id'] ?>" value="<?= $theoryScore ?>" <?= $is_locked ? 'disabled' : '' ?>>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size:12px">Blocked (Not Present)</span>
                                    <input type="hidden" name="result[<?= $w['workman_id'] ?>]" value="fail">
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($w['attendance_status'] == 'present'): ?>
                                    <input type="number" min="0" max="100" name="practical_score[<?= $w['workman_id'] ?>]" class="form-control marks-input" data-worker="<?= (int)$w['workman_id'] ?>" value="<?= $practicalScore ?>" <?= $is_locked ? 'disabled' : '' ?>>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size:12px">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($w['attendance_status'] == 'present'): ?>
                                    <input type="hidden" name="result[<?= $w['workman_id'] ?>]" value="<?= htmlspecialchars($resultValue ?: 'pending') ?>">
                                    <strong id="total_<?= (int)$w['workman_id'] ?>"><?= $totalScore ?></strong>
                                    <span id="result_badge_<?= (int)$w['workman_id'] ?>" class="badge <?= $resultValue === 'pass' ? 'badge-success' : ($resultValue === 'fail' ? 'badge-danger' : 'badge-warning') ?>">
                                        <?= strtoupper($resultValue ?: 'PENDING') ?>
                                    </span>
                                <?php else: ?>
                                    <strong>0</strong> <span class="badge badge-danger">FAIL</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <input type="date" name="valid_till[<?= $w['workman_id'] ?>]" class="form-control" 
                                       value="<?= htmlspecialchars($validTill) ?>" 
                                       <?= ($is_locked || $w['attendance_status'] != 'present') ? 'disabled' : '' ?>>
                            </td>
                            <td>
                                <input type="text" name="result_remarks[<?= $w['workman_id'] ?>]" class="form-control" 
                                       value="<?= htmlspecialchars($w['remarks'] ?: '') ?>" 
                                       <?= ($is_locked || $w['attendance_status'] != 'present') ? 'disabled' : '' ?>>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if(!$is_locked && !empty($workers)): ?>
                <div style="margin-top:20px; text-align:right">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Update Bulk Results</button>
                </div>
                <?php endif; ?>
            </form>
        </div>
      </div>

      <div class="tab-content" id="tab-summary">
        <div class="card-body">
            <div class="grid grid-2" style="gap:30px">
                <div>
                    <h4 style="margin-bottom:15px">Session Details</h4>
                    <div class="info-row"><span>Venue:</span> <strong><?= htmlspecialchars($session['location']) ?></strong></div>
                    <div class="info-row"><span>Trainer:</span> <strong><?= htmlspecialchars($session['trainer_name'] ?: 'N/A') ?></strong></div>
                    <div class="info-row"><span>Type:</span> <strong><?= ucfirst($session['training_type']) ?></strong></div>
                    <div class="info-row"><span>Status:</span> <strong><?= ucfirst($session['session_status']) ?></strong></div>
                </div>
                <div>
                    <h4 style="margin-bottom:15px">Performance Metrics</h4>
                    <?php
                    $attended = count(array_filter($workers, function($w) { return $w['attendance_status'] == 'present'; }));
                    $passed = count(array_filter($workers, function($w) { return $w['result'] == 'pass'; }));
                    $failed = count(array_filter($workers, function($w) { return $w['result'] == 'fail'; }));
                    ?>
                    <div class="info-row"><span>Assigned:</span> <strong><?= count($workers) ?></strong></div>
                    <div class="info-row"><span>Attended:</span> <strong><?= $attended ?></strong></div>
                    <div class="info-row"><span>Passed:</span> <strong class="text-success"><?= $passed ?></strong></div>
                    <div class="info-row"><span>Failed:</span> <strong class="text-danger"><?= $failed ?></strong></div>
                </div>
            </div>
        </div>
      </div>
    </div>

    <style>
    .tabs-container { background: rgba(255,255,255,0.02); border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); overflow: hidden; margin-top:20px; }
    .tabs-header { display: flex; background: rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.05); }
    .tab-item { padding: 15px 25px; cursor: pointer; font-size: 14px; font-weight: 500; opacity: 0.6; transition: all 0.3s; border-bottom: 2px solid transparent; }
    .tab-item:hover { opacity: 1; background: rgba(255,255,255,0.02); }
    .tab-item.active { opacity: 1; color: var(--primary); border-bottom-color: var(--primary); background: rgba(255,255,255,0.05); }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
    .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.03); }
    .info-row span { opacity: 0.6; }
    .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; background: rgba(59,130,246,0.1); color: #93c5fd; border: 1px solid rgba(59,130,246,0.2); }
    .control-grid { display:grid; grid-template-columns:repeat(4,minmax(150px,1fr)); gap:14px; }
    .control-actions { display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap; margin-top:18px; }
    @media(max-width:1100px){ .control-grid { grid-template-columns:repeat(2,minmax(150px,1fr)); } }
    @media(max-width:640px){ .tabs-header { overflow-x:auto; } .tab-item { white-space:nowrap; } .control-grid { grid-template-columns:1fr; } }
    </style>

    <script src="../../js/utils.js"></script>
    <script>
    function showTab(evt, tabId) {
        document.querySelectorAll('.tab-item').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        
        evt.currentTarget.classList.add('active');
        document.getElementById('tab-' + tabId).classList.add('active');
    }

    // Handle Form Submissions via AJAX
    const safetyPassMark = <?= (int)$passMark ?>;

    function updateMarksResult(workerId) {
        const theory = Number(document.querySelector(`[name="theory_score[${workerId}]"]`)?.value || 0);
        const practical = Number(document.querySelector(`[name="practical_score[${workerId}]"]`)?.value || 0);
        const total = theory + practical;
        const result = total >= safetyPassMark ? 'pass' : 'fail';
        const totalEl = document.getElementById('total_' + workerId);
        const badge = document.getElementById('result_badge_' + workerId);
        const hidden = document.querySelector(`[name="result[${workerId}]"]`);

        if (totalEl) totalEl.textContent = total;
        if (hidden) hidden.value = result;
        if (badge) {
            badge.textContent = result.toUpperCase();
            badge.className = 'badge ' + (result === 'pass' ? 'badge-success' : 'badge-danger');
        }
    }

    document.querySelectorAll('.marks-input').forEach(input => {
        updateMarksResult(input.dataset.worker);
        input.addEventListener('input', () => updateMarksResult(input.dataset.worker));
    });

    document.querySelector('.btn-cancel-session')?.addEventListener('click', async () => {
        const form = document.querySelector('.schedule-control-form');
        if (!form) return;
        const confirmed = window.Swal
            ? await Swal.fire({
                title: 'Cancel training session?',
                text: 'Assigned workers will be returned to the scheduling queue.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, cancel session'
              })
            : { isConfirmed: confirm('Cancel this training session and return workers to scheduling queue?') };
        if (!confirmed.isConfirmed) return;

        const cancelBtn = document.querySelector('.btn-cancel-session');
        const originalText = cancelBtn?.innerHTML || '';
        try {
            if (cancelBtn) {
                cancelBtn.disabled = true;
                cancelBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelling...';
            }
            const formData = new FormData(form);
            formData.set('schedule_action', 'cancel');
            const response = await fetch(form.action, { method: 'POST', body: formData });
            const raw = await response.text();
            let result = {};
            try {
                result = raw ? JSON.parse(raw) : {};
            } catch (parseError) {
                result = { success: false, message: raw ? raw.replace(/<[^>]*>/g, ' ').trim() : `Server returned HTTP ${response.status}` };
            }
            if (!response.ok || !result.success) {
                throw new Error(result.message || result.error || `Server returned HTTP ${response.status}`);
            }
            if (window.Swal) {
                await Swal.fire('Session Cancelled', result.message || 'Session cancelled successfully.', 'success');
            }
            window.location.href = 'training_schedule.php?success=' + encodeURIComponent(result.message || 'Session cancelled successfully.');
        } catch (err) {
            if (window.Swal) {
                Swal.fire('Cancellation Failed', err.message || 'Unable to cancel session.', 'error');
            } else {
                alert('Cancellation Failed: ' + (err.message || 'Unable to cancel session.'));
            }
        } finally {
            if (cancelBtn) {
                cancelBtn.disabled = false;
                cancelBtn.innerHTML = originalText;
            }
        }
    });

    document.querySelectorAll('form').forEach(form => {
        if (form.action.includes('complete_session.php')) return; // Keep standard POST for finalize for now or convert too

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (form.classList.contains('remove-attendee-form')) {
                const confirmed = window.Swal
                    ? await Swal.fire({
                        title: 'Remove attendee?',
                        text: 'This worker will be sent back to the scheduling queue.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Remove'
                      })
                    : { isConfirmed: confirm('Remove this worker from the session?') };
                if (!confirmed.isConfirmed) return;
            }
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            try {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData
                });
                
                const raw = await response.text();
                let result = {};
                try {
                    result = raw ? JSON.parse(raw) : {};
                } catch (parseError) {
                    result = {
                        success: false,
                        message: raw ? raw.replace(/<[^>]*>/g, ' ').trim() : `Server returned HTTP ${response.status}`
                    };
                }

                if (!response.ok && !result.message && !result.error) {
                    result.message = `Server returned HTTP ${response.status}`;
                }

                if (result.success) {
                    if (window.Swal) {
                        await Swal.fire('Saved', result.message || 'Saved successfully', 'success');
                    } else {
                        alert(result.message || 'Saved successfully');
                    }
                    location.reload();
                } else if (window.Swal) {
                    Swal.fire('Error', result.message || result.error || 'Unknown error', 'error');
                } else {
                    alert('Error: ' + (result.message || result.error || 'Unknown error'));
                }
            } catch (err) {
                console.error(err);
                if (window.Swal) {
                    Swal.fire('Connection Error', err.message || 'Could not reach server', 'error');
                } else {
                    alert('Connection Error: ' + (err.message || 'Could not reach server'));
                }
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                if (form.classList.contains('schedule-control-form')) {
                    form.querySelector('[name="schedule_action"]').value = 'update';
                }
            }
        });
    });
    </script>
    <?php
}

renderLayout("Manage Session", 'renderContent', $role, $name);

