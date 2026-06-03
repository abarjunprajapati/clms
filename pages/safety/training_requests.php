<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Safety Officer';

function safety_training_page_table_exists($conn, $table) {
    $table = mysqli_real_escape_string($conn, $table);
    $res = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return $res && mysqli_num_rows($res) > 0;
}

function safety_training_page_column_exists($conn, $table, $column) {
    $safeTable = str_replace('`', '``', $table);
    $column = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$column'");
    return $res && mysqli_num_rows($res) > 0;
}

function safety_training_page_ensure_column($conn, $table, $column, $definition) {
    if (!safety_training_page_table_exists($conn, $table) || safety_training_page_column_exists($conn, $table, $column)) return;
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = str_replace('`', '``', $column);
    @mysqli_query($conn, "ALTER TABLE `$safeTable` ADD COLUMN `$safeColumn` $definition");
}

function safety_training_page_ensure_schema($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_requests (
        id INT NOT NULL AUTO_INCREMENT,
        workman_id INT NOT NULL,
        contractor_id INT NOT NULL,
        requested_date DATE NULL,
        preferred_date DATE NULL,
        preferred_shift VARCHAR(20) DEFAULT 'morning',
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    foreach ([
        'scheduled_date' => 'DATE NULL',
        'scheduled_shift' => 'VARCHAR(20) NULL',
        'scheduled_venue' => 'VARCHAR(300) NULL',
        'scheduled_time' => 'VARCHAR(20) NULL',
        'safety_remarks' => 'TEXT NULL',
        'batch_number' => 'VARCHAR(100) NULL',
        'instructor' => 'VARCHAR(150) NULL',
        'conduct_remarks' => 'TEXT NULL',
        'source' => 'VARCHAR(30) NULL',
        'requested_by' => 'INT NULL',
        'contractor_confirmed' => 'TINYINT(1) DEFAULT 0',
        'scheduled_by' => 'INT NULL',
        'status' => "VARCHAR(50) DEFAULT 'pending'",
        'updated_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    ] as $column => $definition) {
        safety_training_page_ensure_column($conn, 'training_requests', $column, $definition);
    }
    @mysqli_query($conn, "ALTER TABLE training_requests MODIFY COLUMN status VARCHAR(50) DEFAULT 'pending'");

    if (safety_training_page_table_exists($conn, 'workmen')) {
        safety_training_page_ensure_column($conn, 'workmen', 'safety_training_status', "VARCHAR(50) DEFAULT 'PENDING_TRAINING'");
        safety_training_page_ensure_column($conn, 'workmen', 'training_status', "VARCHAR(50) DEFAULT 'pending'");
        @mysqli_query($conn, "ALTER TABLE workmen MODIFY COLUMN training_status VARCHAR(50) DEFAULT 'pending'");
        @mysqli_query($conn, "ALTER TABLE workmen MODIFY COLUMN safety_training_status VARCHAR(50) DEFAULT 'PENDING_TRAINING'");
    }
    if (safety_training_page_table_exists($conn, 'contractors')) {
        safety_training_page_ensure_column($conn, 'contractors', 'work_order_no', 'VARCHAR(100) NULL');
    }
}

function safety_training_page_seed_pending_requests($conn) {
    if (!safety_training_page_table_exists($conn, 'workmen') || !safety_training_page_table_exists($conn, 'training_requests')) {
        return;
    }

    $where = [
        "w.contractor_id IS NOT NULL",
        "COALESCE(w.status, '') <> 'draft'",
        "(w.training_status IS NULL OR LOWER(TRIM(w.training_status)) IN ('', 'pending', 'training_pending', 'fail', 'failed', 'training_failed'))",
        "(w.safety_training_status IS NULL OR UPPER(TRIM(w.safety_training_status)) IN ('', '0', 'PENDING_TRAINING', 'TRAINING_FAILED'))",
        "NOT EXISTS (
            SELECT 1
            FROM training_requests tr
            WHERE tr.workman_id = w.id
              AND tr.status IN ('pending', 'scheduled', 'contractor_confirmed', 'passed')
        )",
    ];

    if (safety_training_page_column_exists($conn, 'workmen', 'temp_id')) {
        $where[] = "COALESCE(w.temp_id, '') <> ''";
    }

    $sql = "
        INSERT INTO training_requests (workman_id, contractor_id, requested_date, preferred_date, preferred_shift, status, created_at, updated_at)
        SELECT w.id, w.contractor_id, CURDATE(), NULL, 'morning', 'pending', NOW(), NOW()
        FROM workmen w
        WHERE " . implode(' AND ', $where);

    @mysqli_query($conn, $sql);
}

function safety_training_page_sync_request_statuses($conn) {
    if (!safety_training_page_table_exists($conn, 'workmen') || !safety_training_page_table_exists($conn, 'training_requests')) {
        return;
    }

    @mysqli_query($conn, "
        UPDATE training_requests tr
        JOIN workmen w ON tr.workman_id = w.id
        SET tr.status = 'passed', tr.updated_at = NOW()
        WHERE tr.status NOT IN ('passed')
          AND (
              UPPER(TRIM(COALESCE(w.training_status, ''))) IN ('PASS', 'PASSED', 'QUALIFIED', 'COMPLETED', 'TRAINING_PASSED')
              OR UPPER(TRIM(COALESCE(w.safety_training_status, ''))) = 'TRAINING_PASSED'
          )
    ");

    @mysqli_query($conn, "
        UPDATE training_requests tr
        JOIN workmen w ON tr.workman_id = w.id
        SET tr.status = 'failed', tr.updated_at = NOW()
        WHERE tr.status NOT IN ('passed', 'failed')
          AND (
              UPPER(TRIM(COALESCE(w.training_status, ''))) IN ('FAIL', 'FAILED', 'TRAINING_FAILED')
              OR UPPER(TRIM(COALESCE(w.safety_training_status, ''))) = 'TRAINING_FAILED'
          )
    ");

    @mysqli_query($conn, "
        UPDATE training_requests tr
        JOIN workmen w ON tr.workman_id = w.id
        SET tr.status = 'scheduled', tr.updated_at = NOW()
        WHERE tr.status = 'pending'
          AND tr.scheduled_date IS NOT NULL
          AND (
              UPPER(TRIM(COALESCE(w.training_status, ''))) IN ('SCHEDULED', 'TRAINING_SCHEDULED')
              OR UPPER(TRIM(COALESCE(w.safety_training_status, ''))) IN ('TRAINING_SCHEDULED', 'TRAINING_CONFIRMED')
          )
    ");
}

function renderContent() {
    global $conn;
    safety_training_page_ensure_schema($conn);
    safety_training_page_sync_request_statuses($conn);
    $contractorNameParts = [];
    foreach (['contractor_name', 'vendor_name', 'name'] as $column) {
        if (safety_training_page_column_exists($conn, 'contractors', $column)) {
            $safeColumn = str_replace('`', '``', $column);
            $contractorNameParts[] = "c.`$safeColumn`";
        }
    }
    $contractorNameParts[] = "CONCAT('Contractor #', tr.contractor_id)";
    $contractorNameExpr = "COALESCE(" . implode(', ', $contractorNameParts) . ")";

    $workOrderParts = [];
    if (safety_training_page_column_exists($conn, 'contractors', 'work_order_no')) $workOrderParts[] = 'c.work_order_no';
    if (safety_training_page_column_exists($conn, 'workmen', 'work_order_no')) $workOrderParts[] = 'w.work_order_no';
    if (safety_training_page_column_exists($conn, 'workmen', 'application_no')) $workOrderParts[] = 'w.application_no';
    $workOrderParts[] = "'N/A'";
    $workOrderExpr = "COALESCE(" . implode(', ', $workOrderParts) . ")";
    $contractorRequestWhere = "(COALESCE(tr.requested_by, 0) > 0 OR COALESCE(TRIM(tr.training_type), '') <> '')";

    // Fetch Stats
    $stats = [
        'pending'    => db_single($conn, "SELECT COUNT(*) c FROM training_requests tr WHERE tr.status = 'pending' AND $contractorRequestWhere")['c'],
        'today'      => db_single($conn, "SELECT COUNT(*) c FROM training_requests tr WHERE tr.status IN ('scheduled','contractor_confirmed') AND tr.scheduled_date = CURDATE() AND $contractorRequestWhere")['c'],
        'total_pass' => db_single($conn, "SELECT COUNT(*) c FROM training_requests tr WHERE tr.status = 'passed' AND $contractorRequestWhere")['c'],
        'total_fail' => db_single($conn, "SELECT COUNT(*) c FROM training_requests tr WHERE tr.status = 'failed' AND $contractorRequestWhere")['c']
    ];

    // 1. Pending Requests (Needs Scheduling)
    $pending = db_fetch_all($conn, "
        SELECT tr.id as request_id, tr.*, w.name as worker_name, w.temp_id as worker_code, w.trade, w.aadhaar,
               $contractorNameExpr AS contractor_name, $workOrderExpr AS work_order_no
        FROM training_requests tr
        JOIN workmen w ON tr.workman_id = w.id
        LEFT JOIN contractors c ON tr.contractor_id = c.id
        WHERE tr.status IN ('pending', 'failed')
          AND $contractorRequestWhere
        ORDER BY tr.created_at DESC
    ");

    // 2. Active Batch (Attendance & Conduct Result)
    $active_batch = db_fetch_all($conn, "
        SELECT tr.*, w.name as worker_name, w.temp_id as worker_code, w.trade, w.aadhaar,
               $contractorNameExpr AS contractor_name
        FROM training_requests tr
        JOIN workmen w ON tr.workman_id = w.id
        LEFT JOIN contractors c ON tr.contractor_id = c.id
        WHERE tr.status IN ('scheduled', 'contractor_confirmed')
          AND $contractorRequestWhere
        ORDER BY tr.scheduled_date ASC, tr.scheduled_shift ASC
    ");

    // 3. Training History (Recent Results)
    $history = db_fetch_all($conn, "
        SELECT tr.*, w.name as worker_name, w.temp_id as worker_code, w.trade, w.aadhaar,
               $contractorNameExpr AS contractor_name
        FROM training_requests tr
        JOIN workmen w ON tr.workman_id = w.id
        LEFT JOIN contractors c ON tr.contractor_id = c.id
        WHERE tr.status IN ('passed', 'failed')
          AND $contractorRequestWhere
        ORDER BY tr.updated_at DESC LIMIT 50
    ");

    // 4. Contractor-wise Summary Report
    $contractor_report = db_fetch_all($conn, "
        SELECT $contractorNameExpr AS contractor_name,
               COUNT(tr.id) as total_requests,
               SUM(CASE WHEN tr.status = 'passed' THEN 1 ELSE 0 END) as passed_count,
               SUM(CASE WHEN tr.status = 'failed' THEN 1 ELSE 0 END) as failed_count,
               SUM(CASE WHEN tr.status IN ('pending', 'scheduled', 'contractor_confirmed') THEN 1 ELSE 0 END) as in_progress
        FROM training_requests tr
        LEFT JOIN contractors c ON c.id = tr.contractor_id
        WHERE $contractorRequestWhere
        GROUP BY tr.contractor_id, contractor_name
        ORDER BY passed_count DESC
    ");

    ?>

    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-user-shield" style="color:var(--primary); margin-right:12px;"></i> Safety Induction Desk</h2>
        <!-- <p class="page-subtitle">Standardized Safety Training Management & Pass Eligibility Control (PDF Page 19-24 Compliance)</p> -->
      </div>
      <div class="header-actions">
        <button class="btn btn-primary" onclick="location.reload()"><i class="fas fa-sync-alt"></i> Refresh Data</button>
      </div>
    </div>

    <!-- Dashboard Stats Cards -->
    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
      <div class="stat-card glass">
        <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #d97706;"><i class="fas fa-clock"></i></div>
        <div class="stat-value"><?= $stats['pending'] ?></div>
        <div class="stat-label">Pending Requests</div>
      </div>
      <div class="stat-card glass">
        <div class="stat-icon" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;"><i class="fas fa-calendar-day"></i></div>
        <div class="stat-value"><?= $stats['today'] ?></div>
        <div class="stat-label">Today's Batch</div>
      </div>
      <div class="stat-card glass">
        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #059669;"><i class="fas fa-check-double"></i></div>
        <div class="stat-value"><?= $stats['total_pass'] ?></div>
        <div class="stat-label">Certified Workers</div>
      </div>
      <div class="stat-card glass">
        <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #dc2626;"><i class="fas fa-user-times"></i></div>
        <div class="stat-value"><?= $stats['total_fail'] ?></div>
        <div class="stat-label">Re-Training Req.</div>
      </div>
    </div>

    <!-- Workflow Tabs -->
    <div class="tabs-container glass">
      <div class="tabs-header">
        <button class="tab-btn active" onclick="switchTab(event, 'pending-tab')"><i class="fas fa-list-ul"></i> 1. Schedule Batch</button>
        <button class="tab-btn" onclick="switchTab(event, 'conduct-tab')"><i class="fas fa-chalkboard-teacher"></i> 2. Attendance & Result</button>
        <button class="tab-btn" onclick="switchTab(event, 'history-tab')"><i class="fas fa-history"></i> 3. Training Audit Log</button>
        <button class="tab-btn" onclick="switchTab(event, 'reports-tab')"><i class="fas fa-chart-bar"></i> 4. Training Reports</button>
      </div>

      <div class="tabs-content">
        <!-- 1. PENDING TAB -->
        <div id="pending-tab" class="tab-panel active">
           <?php if (empty($pending)): ?>
             <div class="empty-state">
               <i class="fas fa-check-circle"></i>
               <p>All workers are scheduled. No pending requests.</p>
             </div>
           <?php else: ?>
             <table class="data-table">
               <thead>
                 <tr>
                   <th>Worker Info</th>
                   <th>Contractor Details</th>
                   <th>Preferred Date</th>
                   <th>Action</th>
                 </tr>
               </thead>
               <tbody>
                 <?php foreach ($pending as $r): ?>
                 <tr>
                   <td>
                     <a href="worker_history.php?id=<?= $r['workman_id'] ?>" style="font-weight:700; color:var(--primary); text-decoration:none;"><?= htmlspecialchars($r['worker_name']) ?></a>
                     <div style="font-size:11px; color:var(--text-muted);"><?= htmlspecialchars($r['trade']) ?> | <?= htmlspecialchars($r['aadhaar']) ?></div>
                   </td>
                   <td>
                     <div style="font-weight:600;"><?= htmlspecialchars($r['contractor_name']) ?></div>
                     <div style="font-size:11px; color:var(--text-muted);">WO: <?= htmlspecialchars($r['work_order_no'] ?? 'N/A') ?></div>
                   </td>
                   <td>
                     <span class="badge badge-gray"><?= !empty($r['preferred_date']) ? date('d M Y', strtotime($r['preferred_date'])) : 'Any Date' ?></span>
                     <div style="font-size:11px; margin-top:2px; color:var(--text-muted);"><?= ucfirst($r['preferred_shift'] ?? 'morning') ?> Shift</div>
                   </td>
                   <td>
                     <button class="btn btn-sm btn-primary" onclick="openScheduleModal(<?= htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8') ?>)">Assign Batch</button>
                   </td>
                 </tr>
                 <?php endforeach; ?>
               </tbody>
             </table>
           <?php endif; ?>
        </div>

        <!-- 2. CONDUCT TAB -->
        <div id="conduct-tab" class="tab-panel">
           <?php if (empty($active_batch)): ?>
             <div class="empty-state">
               <i class="fas fa-calendar-times"></i>
               <p>No active batches found. Schedule a worker first.</p>
             </div>
           <?php else: ?>
             <table class="data-table">
               <thead>
                 <tr>
                   <th>Worker Details</th>
                   <th>Batch & Instructor</th>
                   <th>Date & Venue</th>
                   <th>Attendance</th>
                   <th>Result</th>
                 </tr>
               </thead>
               <tbody>
                 <?php foreach ($active_batch as $r): ?>
                 <tr>
                   <td>
                      <a href="worker_history.php?id=<?= $r['workman_id'] ?>" style="font-weight:700; color:var(--primary); text-decoration:none;"><?= htmlspecialchars($r['worker_name']) ?></a>
                      <div style="font-size:10px; color:var(--text-muted);"><?= htmlspecialchars($r['contractor_name']) ?></div>
                   </td>
                   <td>
                      <div style="font-weight:600; color:var(--primary);"><?= htmlspecialchars($r['batch_number'] ?? 'N/A') ?></div>
                      <div style="font-size:10px; color:var(--text-muted);"><?= htmlspecialchars($r['instructor'] ?? 'Safety Officer') ?></div>
                   </td>
                   <td>
                      <div style="font-weight:600;"><?= !empty($r['scheduled_date']) ? date('d M Y', strtotime($r['scheduled_date'])) : '—' ?></div>
                      <div style="font-size:10px; color:var(--text-muted);"><?= htmlspecialchars($r['scheduled_venue'] ?? 'Safety Hall') ?> | <?= ucfirst($r['scheduled_shift'] ?? '') ?></div>
                   </td>
                   <td>
                     <select class="form-control form-control-sm" id="att_<?= $r['id'] ?>" style="width:100px; font-weight:bold;">
                       <option value="present">PRESENT</option>
                       <option value="absent">ABSENT</option>
                     </select>
                   </td>
                   <td>
                     <div style="display:flex; gap:5px;">
                       <button class="btn btn-sm btn-success" style="padding: 4px 8px;" onclick="markResult(<?= $r['id'] ?>, 'passed')">PASS</button>
                       <button class="btn btn-sm btn-danger" style="padding: 4px 8px;" onclick="markResult(<?= $r['id'] ?>, 'failed')">FAIL</button>
                     </div>
                   </td>
                 </tr>
                 <?php endforeach; ?>
               </tbody>
             </table>
           <?php endif; ?>
        </div>

        <!-- 3. HISTORY TAB -->
        <div id="history-tab" class="tab-panel">
           <?php if (empty($history)): ?>
             <div class="empty-state">
               <i class="fas fa-history"></i>
               <p>No completed safety training records found yet.</p>
             </div>
           <?php else: ?>
           <table class="data-table">
             <thead>
               <tr>
                 <th>Worker</th>
                 <th>Contractor</th>
                 <th>Training Date</th>
                 <th>Status</th>
                 <th>Remarks</th>
               </tr>
             </thead>
             <tbody>
               <?php foreach ($history as $r): ?>
               <tr>
                 <td><a href="worker_history.php?id=<?= $r['workman_id'] ?>" style="font-weight:700; color:var(--primary); text-decoration:none;"><?= htmlspecialchars($r['worker_name']) ?></a></td>
                 <td><?= htmlspecialchars($r['contractor_name']) ?></td>
                 <td><?= !empty($r['scheduled_date']) ? date('d M Y', strtotime($r['scheduled_date'])) : '—' ?></td>
                 <td>
                   <span class="badge <?= $r['status'] === 'passed' ? 'badge-success' : 'badge-danger' ?>">
                     <?= strtoupper($r['status']) ?>
                   </span>
                 </td>
                 <td>
                    <?php if ($r['status'] === 'passed'): ?>
                      <button class="btn btn-sm btn-outline" onclick='generateCertificate(<?= json_encode($r) ?>)' style="padding: 4px 10px; font-size: 11px;">
                        <i class="fas fa-file-contract"></i> Certificate
                      </button>
                    <?php else: ?>
                      <span style="font-size:11px; color:var(--text-muted);"><?= htmlspecialchars($r['conduct_remarks'] ?? '—') ?></span>
                    <?php endif; ?>
                 </td>
               </tr>
               <?php endforeach; ?>
             </tbody>
           </table>
           <?php endif; ?>
        </div>

        <!-- 4. REPORTS TAB -->
        <div id="reports-tab" class="tab-panel">
           <div class="card-header" style="padding: 0 0 20px 0; border-bottom: 1px solid rgba(0,0,0,0.05); margin-bottom: 20px;">
              <h3 style="margin:0; font-size:16px;"><i class="fas fa-chart-pie" style="color:var(--primary);"></i> Contractor-wise Training Summary</h3>
           </div>
           <?php if (empty($contractor_report)): ?>
             <div class="empty-state">
               <i class="fas fa-chart-bar"></i>
               <p>No training request summary available yet.</p>
             </div>
           <?php else: ?>
           <table class="data-table">
             <thead>
               <tr>
                 <th>Contractor Name</th>
                 <th>Total Requests</th>
                 <th style="color:#059669;">Passed</th>
                 <th style="color:#dc2626;">Failed</th>
                 <th style="color:#2563eb;">In Progress</th>
                 <th>Success Rate</th>
               </tr>
             </thead>
             <tbody>
               <?php foreach ($contractor_report as $cr): 
                   $rate = ($cr['total_requests'] > 0) ? round(($cr['passed_count'] / $cr['total_requests']) * 100) : 0;
               ?>
               <tr>
                 <td><strong><?= htmlspecialchars($cr['contractor_name']) ?></strong></td>
                 <td><span class="badge badge-gray"><?= $cr['total_requests'] ?></span></td>
                 <td><b style="color:#059669;"><?= $cr['passed_count'] ?></b></td>
                 <td><b style="color:#dc2626;"><?= $cr['failed_count'] ?></b></td>
                 <td><b><?= $cr['in_progress'] ?></b></td>
                 <td>
                   <div style="display:flex; align-items:center; gap:8px;">
                     <div style="flex:1; height:6px; background:#e2e8f0; border-radius:3px; overflow:hidden;">
                       <div style="width:<?= $rate ?>%; height:100%; background:var(--primary);"></div>
                     </div>
                     <span style="font-size:11px; font-weight:700;"><?= $rate ?>%</span>
                   </div>
                 </td>
               </tr>
               <?php endforeach; ?>
             </tbody>
           </table>
           <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Hidden Certificate Template & Logic -->
    <?php include __DIR__ . '/../../include/safety_certificate_logic.php'; ?>

    <!-- Modals -->
    <?php include __DIR__ . '/../../include/modals/safety_modals.php'; ?>

    <script>
    function switchTab(evt, tabId) {
      const panels = document.querySelectorAll('.tab-panel');
      const buttons = document.querySelectorAll('.tab-btn');
      panels.forEach(p => p.classList.remove('active'));
      buttons.forEach(b => b.classList.remove('active'));
      document.getElementById(tabId).classList.add('active');
      evt.currentTarget.classList.add('active');
    }

    async function markResult(reqId, result) {
        const attendance = document.getElementById('att_' + reqId).value;
        const remarks = prompt("Enter any remarks for the worker (Optional):");
        
        if (attendance === 'absent') {
            if(!confirm('Worker is absent. Request will be sent back to scheduling. Continue?')) return;
        }

        try {
            const res = await fetch('../../api/safety/conduct_training.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ 
                    request_id: reqId, 
                    result: result, 
                    attendance: attendance,
                    remarks: remarks 
                })
            });
            const data = await res.json();
            if (data.success) {
                alert('Success: result uploaded and notification sent to contractor.');
                location.reload();
            } else alert(data.error);
        } catch (e) { alert('Network error'); }
    }


    function openScheduleModal(r) {
        document.getElementById('scheduleReqId').value = r.request_id || r.id || '';
        document.getElementById('scheduleWorkerInfo').innerHTML = `
           <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
             <div><small>WORKER</small><br><b>${r.worker_name}</b> (Req #${r.id || 'N/A'})</div>
             <div><small>TRADE</small><br><b>${r.trade}</b></div>
             <div><small>CONTRACTOR</small><br><b>${r.contractor_name}</b></div>
             <div><small>PREFERRED</small><br><b>${r.preferred_date || 'Flexible'}</b></div>
           </div>
        `;
        document.getElementById('scheduleModal').classList.remove('hidden');
    }
    </script>

    <style>
      .tabs-container { border-radius: 16px; overflow: hidden; margin-top: 20px; border: 1px solid rgba(255,255,255,0.12); }
      .tabs-header { display: flex; background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color); padding: 0 10px; }
      .tab-btn { padding: 18px 24px; background: none; border: none; font-weight: 700; color: var(--text-muted); cursor: pointer; display: flex; align-items: center; gap: 8px; border-bottom: 3px solid transparent; transition: 0.2s; }
      .tab-btn i { font-size: 14px; }
      .tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); }
      .tab-btn:hover { background: rgba(37, 99, 235, 0.05); }
      
      .tab-panel { display: none; padding: 20px; animation: fadeIn 0.3s ease; }
      .tab-panel.active { display: block; }
      
      @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
      
      .empty-state { padding: 60px; text-align: center; color: var(--text-muted); }
      .empty-state i { font-size: 48px; opacity: 0.2; margin-bottom: 15px; display: block; }
      
      .badge-gray { background: #f1f5f9; color: #475569; }
      .badge-danger { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
    </style>
    <?php
}

renderLayout("Safety Training Dashboard", 'renderContent', $role, $name);
