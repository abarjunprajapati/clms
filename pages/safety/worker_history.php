<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Safety Officer';
$worker_id = (int)($_GET['id'] ?? 0);

if (!$worker_id) {
    header("Location: training_requests.php");
    exit;
}

function renderContent() {
    global $conn, $worker_id;

    // Fetch Worker Details
    $worker = db_single($conn, "
        SELECT w.*, c.contractor_name, c.work_order_no 
        FROM workmen w 
        JOIN contractors c ON w.contractor_id = c.id 
        WHERE w.id = ?", 'i', [$worker_id]);

    if (!$worker) {
        echo "<div class='alert alert-danger'>Worker not found.</div>";
        return;
    }

    // Fetch Training History with Actual Session Data
    $training_history = db_fetch_all($conn, "
        SELECT 
            'training' as event_type,
            tr.id,
            tr.status,
            tsw.attendance_status as attendance,
            tr.conduct_remarks as remarks,
            COALESCE(ts.session_date, tr.updated_at) as event_date,
            ts.session_date,
            ts.session_time,
            ts.location as session_venue,
            ts.trainer_name as session_instructor,
            ts.batch_number as session_batch,
            ts.session_status
        FROM training_requests tr
        INNER JOIN training_session_workers tsw ON tr.id = tsw.training_request_id
        INNER JOIN training_schedule ts ON tsw.session_id = ts.id
        WHERE tr.workman_id = ?
        ORDER BY event_date DESC", 'i', [$worker_id]);

    // Fetch Workflow Logs for this worker's application
    $app_no = $worker['application_no'] ?? '';
    $workflow_history = [];
    if ($app_no) {
        $workflow_history = db_fetch_all($conn, "
            SELECT 
                'workflow' as event_type,
                id,
                to_status as status,
                action_name as action,
                remarks,
                created_at as event_date,
                action_by_role
            FROM workflow_logs
            WHERE application_id = ?
            ORDER BY created_at DESC", 's', [$app_no]);
    }

    // Combine and Sort History
    $history = array_merge($training_history, $workflow_history);
    usort($history, function($a, $b) {
        return strtotime($b['event_date']) - strtotime($a['event_date']);
    });

    ?>

    <div class="content-header">
      <div style="display:flex; align-items:center; gap:15px;">
        <a href="training_requests.php" class="btn btn-outline" style="border-radius:50%; width:40px; height:40px; padding:0; display:flex; align-items:center; justify-content:center;">
          <i class="fas fa-arrow-left"></i>
        </a>
        <div>
          <h2 class="page-title">Worker Training History</h2>
          <p class="page-subtitle">Full audit trail for safety induction attempts.</p>
        </div>
      </div>
    </div>

    <div style="display:grid; grid-template-columns: 320px 1fr; gap:25px; align-items:start;">
      <!-- Worker Profile Side Card -->
      <div class="card glass" style="position:sticky; top:20px;">
        <div class="card-body" style="text-align:center; padding:30px 20px;">
          <div style="width:100px; height:100px; border-radius:50%; background:var(--primary); color:white; display:flex; align-items:center; justify-content:center; margin:0 auto 15px; font-size:32px;">
            <i class="fas fa-user-graduate"></i>
          </div>
          <h3 style="margin-bottom:5px;"><?= htmlspecialchars($worker['name']) ?></h3>
          <p style="color:var(--text-muted); font-size:14px; margin-bottom:20px;"><?= htmlspecialchars($worker['trade']) ?></p>
          
          <div style="text-align:left; font-size:13px; border-top:1px solid rgba(0,0,0,0.05); padding-top:20px; display:flex; flex-direction:column; gap:12px;">
            <div><small style="text-transform:uppercase; opacity:0.6; font-weight:800;">Aadhaar Number</small><br><b><?= htmlspecialchars($worker['aadhaar']) ?></b></div>
            <div><small style="text-transform:uppercase; opacity:0.6; font-weight:800;">Contractor</small><br><b><?= htmlspecialchars($worker['contractor_name']) ?></b></div>
            <div><small style="text-transform:uppercase; opacity:0.6; font-weight:800;">Pass Type</small><br><b><?= htmlspecialchars($worker['pass_type'] ?? 'N/A') ?></b></div>
            <div>
              <small style="text-transform:uppercase; opacity:0.6; font-weight:800;">Current Status</small><br>
              <span class="badge <?= ($worker['training_status'] ?? '') === 'pass' ? 'badge-success' : 'badge-danger' ?>" style="font-size:11px;">
                <?= strtoupper($worker['training_status'] ?? 'PENDING') ?>
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- History Timeline -->
      <div class="card glass">
        <div class="card-header" style="padding:20px 25px;">
          <h3 class="card-title"><i class="fas fa-stream"></i> Training Log</h3>
        </div>
        <div class="card-body" style="padding:0;">
          <?php if (empty($history)): ?>
            <div style="padding:60px; text-align:center; color:var(--text-muted);">
              <i class="fas fa-history" style="font-size:40px; opacity:0.2; margin-bottom:15px; display:block;"></i>
              <p>No training history records found for this worker.</p>
            </div>
          <?php else: ?>
            <div class="timeline" style="padding:25px;">
              <?php foreach ($history as $h): ?>
              <div class="timeline-item" style="display:flex; gap:20px; margin-bottom:30px; position:relative;">
                <?php 
                  $is_training = ($h['event_type'] === 'training');
                  $status = strtolower($h['status']);
                  $color = '#e2e8f0';
                  if ($status === 'passed' || $status === 'pass' || strpos($status, 'active') !== false || strpos($status, 'issued') !== false) $color = '#10b981';
                  elseif ($status === 'failed' || $status === 'fail' || strpos($status, 'rejected') !== false || strpos($status, 'block') !== false) $color = '#ef4444';
                  elseif ($status === 'scheduled' || strpos($status, 'pending') !== false) $color = '#f59e0b';
                ?>
                <div class="timeline-marker" style="width:16px; height:16px; border-radius:50%; background:<?= $color ?>; margin-top:5px; flex-shrink:0; z-index:2; border:4px solid white; box-shadow:0 0 0 2px rgba(0,0,0,0.05);"></div>
                
                <div class="timeline-content glass" style="flex:1; padding:20px; border-radius:15px; border:1px solid rgba(0,0,0,0.05); background:rgba(255,255,255,0.4);">
                  <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:15px;">
                    <div>
                      <h4 style="margin:0; font-size:16px;">
                        <?= $is_training ? 'Safety Induction Session' : (ucwords(str_replace('_', ' ', $h['action'] ?? 'Status Update'))) ?>
                      </h4>
                      <small style="color:var(--text-muted);">
                        <i class="far fa-calendar-alt"></i> 
                        <?php if ($is_training): ?>
                           <?= date('d M Y', strtotime($h['session_date'])) ?> | <i class="far fa-clock"></i> <?= date('h:i A', strtotime($h['session_time'])) ?>
                        <?php else: ?>
                           <?= date('d M Y | h:i A', strtotime($h['event_date'])) ?>
                        <?php endif; ?>
                        <?php if(!$is_training && !empty($h['action_by_role'])): ?>
                           | <i class="fas fa-user-tag"></i> <?= strtoupper($h['action_by_role']) ?>
                        <?php endif; ?>
                      </small>
                    </div>
                    <span class="badge <?= ($color === '#10b981' ? 'badge-success' : ($color === '#ef4444' ? 'badge-danger' : 'badge-warning')) ?>">
                      <?= strtoupper(str_replace('_', ' ', $h['status'])) ?>
                    </span>
                  </div>

                  <?php if ($is_training): ?>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:15px; font-size:13px;">
                      <div><small style="opacity:0.6;">Batch Number</small><br><b><?= htmlspecialchars(!empty($h['session_batch']) ? $h['session_batch'] : 'N/A') ?></b></div>
                      <div><small style="opacity:0.6;">Instructor</small><br><b><?= htmlspecialchars(!empty($h['session_instructor']) ? $h['session_instructor'] : 'Safety Officer') ?></b></div>
                      <div><small style="opacity:0.6;">Venue</small><br><b><?= htmlspecialchars(!empty($h['session_venue']) ? $h['session_venue'] : 'N/A') ?></b></div>
                      <div><small style="opacity:0.6;">Attendance</small><br><b><?= strtoupper($h['attendance'] ?? 'N/A') ?></b></div>
                    </div>
                  <?php endif; ?>

                  <?php if (!empty($h['remarks'])): ?>
                  <div style="margin-top:15px; padding-top:15px; border-top:1px dashed rgba(0,0,0,0.1); font-size:13px; font-style:italic; color:#475569;">
                    "<?= htmlspecialchars($h['remarks']) ?>"
                  </div>
                  <?php endif; ?>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <style>
      .timeline-item:not(:last-child)::after {
        content: '';
        position: absolute;
        left: 7px;
        top: 20px;
        bottom: -20px;
        width: 2px;
        background: #e2e8f0;
        z-index: 1;
      }
      .badge-success { background: rgba(16, 185, 129, 0.12); color: #059669; font-weight: 800; }
      .badge-danger { background: rgba(239, 68, 68, 0.12); color: #dc2626; font-weight: 800; }
      .badge-warning { background: rgba(245, 158, 11, 0.12); color: #d97706; font-weight: 800; }
    </style>
    <?php
}

renderLayout("Worker Training History", 'renderContent', $role, $name);
