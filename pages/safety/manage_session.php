<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user']);
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

function renderContent() {
    global $conn, $session, $session_id;
    
    // Fetch assigned workers
    $workers = db_fetch_all($conn, "
        SELECT sw.*, w.name, w.temp_id as worker_code, c.contractor_name, w.trade
        FROM training_session_workers sw
        JOIN workmen w ON sw.workman_id = w.id
        JOIN contractors c ON w.contractor_id = c.id
        WHERE sw.session_id = ?
    ", 'i', [$session_id]);

    $is_locked = ($session['session_status'] == 'completed');
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
                    <input type="hidden" name="session_id" value="<?= $session_id ?>">
                    <button type="submit" class="btn btn-success"><i class="fas fa-check-circle"></i> Finalize & Lock Session</button>
                </form>
            <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- TABS NAVIGATION -->
    <div class="tabs-container">
      <div class="tabs-header">
        <div class="tab-item active" onclick="showTab('workers')"><i class="fas fa-users"></i> Assigned Workers</div>
        <div class="tab-item" onclick="showTab('attendance')"><i class="fas fa-clipboard-user"></i> Attendance</div>
        <div class="tab-item" onclick="showTab('results')"><i class="fas fa-poll-h"></i> Upload Results</div>
        <div class="tab-item" onclick="showTab('summary')"><i class="fas fa-info-circle"></i> Summary</div>
      </div>

      <div class="tab-content active" id="tab-workers">
        <div class="card-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Worker</th>
                        <th>Contractor</th>
                        <th>Trade</th>
                        <th>Status</th>
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
                            <?php if($w['attendance_status'] == 'present'): ?>
                                <span class="badge badge-success">Present</span>
                            <?php elseif($w['attendance_status'] == 'absent'): ?>
                                <span class="badge badge-danger">Absent</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Pending</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; if(empty($workers)): ?>
                    <tr><td colspan="4" style="text-align:center;padding:40px;opacity:0.5">No workers assigned to this session.</td></tr>
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
            <form action="../../api/safety/save_results.php" method="POST">
                <input type="hidden" name="session_id" value="<?= $session_id ?>">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Worker</th>
                            <th>Attendance</th>
                            <th>Result</th>
                            <th>Valid Till</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($workers as $w): ?>
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
                                    <select name="result[<?= $w['workman_id'] ?>]" class="form-control" <?= $is_locked ? 'disabled' : '' ?>>
                                        <option value="pending" <?= $w['result'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="pass" <?= $w['result'] == 'pass' ? 'selected' : '' ?>>Pass</option>
                                        <option value="fail" <?= $w['result'] == 'fail' ? 'selected' : '' ?>>Fail</option>
                                    </select>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size:12px">Blocked (Not Present)</span>
                                    <input type="hidden" name="result[<?= $w['workman_id'] ?>]" value="fail">
                                <?php endif; ?>
                            </td>
                            <td>
                                <input type="date" name="valid_till[<?= $w['workman_id'] ?>]" class="form-control" 
                                       value="<?= $w['valid_till'] ?: date('Y-m-d', strtotime('+1 year')) ?>" 
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
    </style>

    <script src="../../js/utils.js"></script>
    <script>
    function showTab(tabId) {
        document.querySelectorAll('.tab-item').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        
        event.currentTarget.classList.add('active');
        document.getElementById('tab-' + tabId).classList.add('active');
    }

    // Handle Form Submissions via AJAX
    document.querySelectorAll('form').forEach(form => {
        if (form.action.includes('complete_session.php')) return; // Keep standard POST for finalize for now or convert too

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
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
                
                const result = await response.json();
                if (result.success) {
                    alert(result.message || 'Saved successfully');
                    location.reload();
                } else {
                    alert('Error: ' + (result.message || result.error || 'Unknown error'));
                }
            } catch (err) {
                console.error(err);
                alert('Connection Error: Could not reach server');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    });
    </script>
    <?php
}

renderLayout("Manage Session", 'renderContent', $role, $name);

