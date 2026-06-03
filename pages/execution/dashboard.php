<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['execution_officer', 'execution', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/execution_context.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Execution Officer';
$userId = $_SESSION['user_id'];

// Get or create execution officer context for this login
$officerId = clms_execution_get_officer_id($conn, $userId);

function renderContent() {
    global $conn, $officerId;

    // KPI Queries (PDF Correct)
    $totalContractors = db_count($conn, "SELECT COUNT(*) FROM execution_officer_contractors WHERE execution_officer_id = ?", 'i', [$officerId]);
    $totalWorkOrders = db_count($conn, "SELECT COUNT(DISTINCT work_order_id) FROM execution_officer_workorders WHERE execution_officer_id = ? AND COALESCE(status, 'active') = 'active'", 'i', [$officerId]);
    if ($totalWorkOrders === 0) {
        $totalWorkOrders = db_count($conn, "SELECT COUNT(DISTINCT work_order_id) FROM execution_officer_contractors WHERE execution_officer_id = ? AND work_order_id IS NOT NULL", 'i', [$officerId]);
    }
    $activeWorkers = db_count($conn, "SELECT COUNT(*) FROM execution_worker_deployments WHERE execution_officer_id = ? AND status = 'active'", 'i', [$officerId]);
    $presentToday = db_count($conn, "SELECT COUNT(DISTINCT workman_id) FROM attendance WHERE workman_id IN (SELECT workman_id FROM execution_worker_deployments WHERE execution_officer_id = ? AND status = 'active') AND DATE(check_in) = CURDATE()", 'i', [$officerId]);
    $absentPlanned = max(0, $activeWorkers - $presentToday);
    
    // Idle Workers (Present today but NOT deployed)
    $idleWorkers = db_count($conn, "SELECT COUNT(DISTINCT a.workman_id) FROM attendance a 
                                JOIN workmen w ON a.workman_id = w.id 
                                WHERE DATE(a.check_in) = CURDATE() 
                                AND w.contractor_id IN (SELECT contractor_id FROM execution_officer_contractors WHERE execution_officer_id = ?)
                                AND a.workman_id NOT IN (SELECT workman_id FROM execution_worker_deployments WHERE status = 'active')", 'i', [$officerId]);

    $attendanceExceptions = db_count($conn, "SELECT COUNT(*) FROM attendance_exceptions ae WHERE DATE(ae.created_at) = CURDATE() AND ae.contractor_id IN (SELECT contractor_id FROM execution_officer_contractors WHERE execution_officer_id = ?)", 'i', [$officerId]);
    $totalObservations = db_count($conn, "SELECT COUNT(*) FROM execution_observations WHERE execution_officer_id = ?", 'i', [$officerId]);
    $pendingEscalations = db_count($conn, "SELECT COUNT(*) FROM execution_escalations WHERE execution_officer_id = ? AND COALESCE(status, 'open') != 'closed'", 'i', [$officerId]);
    $trainingPending = db_count($conn, "SELECT COUNT(*) FROM workmen w WHERE COALESCE(w.training_approval_doc, '') <> '' AND COALESCE(w.execution_training_status, 'pending') = 'pending' AND w.contractor_id IN (SELECT contractor_id FROM execution_officer_contractors WHERE execution_officer_id = ?)", 'i', [$officerId]);
    $trainingApproved = db_count($conn, "SELECT COUNT(*) FROM workmen w WHERE COALESCE(w.execution_training_status, 'pending') = 'approved' AND w.contractor_id IN (SELECT contractor_id FROM execution_officer_contractors WHERE execution_officer_id = ?)", 'i', [$officerId]);
    $openActions = db_count($conn, "SELECT COUNT(*) FROM execution_actions WHERE execution_officer_id = ? AND COALESCE(status, 'open') != 'closed'", 'i', [$officerId]);

    // Recent Observations
    $observations = db_fetch_all($conn, "SELECT o.*, w.name as workman_name, c.contractor_name 
                                        FROM execution_observations o 
                                        LEFT JOIN workmen w ON o.workman_id = w.id 
                                        LEFT JOIN contractors c ON o.contractor_id = c.id 
                                        WHERE o.execution_officer_id = ? 
                                        ORDER BY o.created_at DESC LIMIT 5", 'i', [$officerId]);

    // Recent Deployments
    $deployments = db_fetch_all($conn, "SELECT d.*, w.name as workman_name, c.contractor_name, dept.dept_name 
                                       FROM execution_worker_deployments d 
                                       LEFT JOIN workmen w ON d.workman_id = w.id 
                                       LEFT JOIN contractors c ON d.contractor_id = c.id 
                                       LEFT JOIN master_departments dept ON d.department_id = dept.id 
                                       WHERE d.execution_officer_id = ? 
                                       ORDER BY d.deployed_date DESC LIMIT 5", 'i', [$officerId]);

    $trainingQueue = db_fetch_all($conn, "SELECT w.id, w.name, w.aadhaar, w.training_approval_doc, w.execution_training_status, c.contractor_name
                                         FROM workmen w
                                         LEFT JOIN contractors c ON c.id = w.contractor_id
                                         WHERE COALESCE(w.training_approval_doc, '') <> ''
                                           AND COALESCE(w.execution_training_status, 'pending') = 'pending'
                                           AND w.contractor_id IN (SELECT contractor_id FROM execution_officer_contractors WHERE execution_officer_id = ?)
                                         ORDER BY w.created_at DESC LIMIT 6", 'i', [$officerId]);

    $exceptionList = db_fetch_all($conn, "SELECT ae.*, w.name AS workman_name, c.contractor_name
                                         FROM attendance_exceptions ae
                                         LEFT JOIN workmen w ON w.id = ae.workman_id
                                         LEFT JOIN contractors c ON c.id = ae.contractor_id
                                         WHERE ae.contractor_id IN (SELECT contractor_id FROM execution_officer_contractors WHERE execution_officer_id = ?)
                                         ORDER BY ae.created_at DESC LIMIT 5", 'i', [$officerId]);

    ?>
    <div class="content-header">
        <div>
            <h2 class="page-title"><i class="fas fa-shield-halved" style="color:#6366f1;margin-right:10px"></i>Execution Command Center</h2>
            <!-- <p class="page-subtitle">Real-time oversight of contractors, workforce deployments, and project efficiency.</p> -->
        </div>
        <div class="action-buttons">
            <button class="btn btn-primary" onclick="location.href='reports.php'"><i class="fas fa-file-export"></i> Analytics Hub</button>
        </div>
    </div>

    <!-- Main Dashboard Stats (PDF Correct) -->
    <div class="stats-container">
        <div class="stat-card blue">
            <div class="stat-icon"><i class="fas fa-building"></i></div>
            <div class="stat-content">
                <span class="stat-label">Assigned Contractors</span>
                <h3 class="stat-value"><?= $totalContractors ?></h3>
            </div>
            <div class="stat-progress"><div class="progress-bar" style="width: 100%"></div></div>
        </div>
        
        <div class="stat-card blue">
            <div class="stat-icon"><i class="fas fa-handshake"></i></div>
            <div class="stat-content">
                <span class="stat-label">Active Work Orders</span>
                <h3 class="stat-value"><?= $totalWorkOrders ?></h3>
            </div>
            <div class="stat-progress"><div class="progress-bar" style="width: 100%"></div></div>
        </div>

        <div class="stat-card green">
            <div class="stat-icon"><i class="fas fa-user-check"></i></div>
            <div class="stat-content">
                <span class="stat-label">Present Today</span>
                <h3 class="stat-value"><?= $presentToday ?></h3>
            </div>
            <div class="stat-progress"><div class="progress-bar" style="width: <?= ($activeWorkers > 0) ? round(($presentToday/$activeWorkers)*100) : 0 ?>%"></div></div>
        </div>

        <div class="stat-card amber">
            <div class="stat-icon"><i class="fas fa-user-clock"></i></div>
            <div class="stat-content">
                <span class="stat-label">Idle Workers</span>
                <h3 class="stat-value"><?= $idleWorkers ?></h3>
            </div>
            <div class="stat-progress"><div class="progress-bar" style="width: <?= ($presentToday > 0) ? round(($idleWorkers/$presentToday)*100) : 0 ?>%"></div></div>
        </div>

        <div class="stat-card purple">
            <div class="stat-icon"><i class="fas fa-triangle-exclamation"></i></div>
            <div class="stat-content">
                <span class="stat-label">Attend. Exceptions</span>
                <h3 class="stat-value"><?= $attendanceExceptions ?></h3>
            </div>
            <div class="stat-progress"><div class="progress-bar" style="width: 100%; background: #ef4444"></div></div>
        </div>

        <div class="stat-card red">
            <div class="stat-icon"><i class="fas fa-bullhorn"></i></div>
            <div class="stat-content">
                <span class="stat-label">Pending Escalations</span>
                <h3 class="stat-value"><?= $pendingEscalations ?></h3>
            </div>
            <div class="stat-progress"><div class="progress-bar" style="width: 100%; background: #ef4444"></div></div>
        </div>

        <div class="stat-card green">
            <div class="stat-icon"><i class="fas fa-user-check"></i></div>
            <div class="stat-content">
                <span class="stat-label">Training Approvals</span>
                <h3 class="stat-value"><?= $trainingApproved ?></h3>
            </div>
            <div class="stat-progress"><div class="progress-bar" style="width:100%"></div></div>
        </div>

        <div class="stat-card amber">
            <div class="stat-icon"><i class="fas fa-file-signature"></i></div>
            <div class="stat-content">
                <span class="stat-label">Training Review Pending</span>
                <h3 class="stat-value"><?= $trainingPending ?></h3>
            </div>
            <div class="stat-progress"><div class="progress-bar" style="width:100%"></div></div>
        </div>

        <div class="stat-card blue">
            <div class="stat-icon"><i class="fas fa-list-check"></i></div>
            <div class="stat-content">
                <span class="stat-label">Open Field Actions</span>
                <h3 class="stat-value"><?= $openActions ?></h3>
            </div>
            <div class="stat-progress"><div class="progress-bar" style="width:100%"></div></div>
        </div>
    </div>

    <div class="ops-strip">
        <div><span>Planned Deployment</span><strong><?= $activeWorkers ?></strong></div>
        <div><span>Present vs Planned</span><strong><?= $presentToday ?> / <?= $activeWorkers ?></strong></div>
        <div><span>Absent Planned</span><strong><?= $absentPlanned ?></strong></div>
        <div><span>Utilization</span><strong><?= $activeWorkers > 0 ? round(($presentToday / $activeWorkers) * 100) : 0 ?>%</strong></div>
    </div>

    <!-- Real-Time Charts Section (PDF Correct) -->
    <div class="charts-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap:24px; margin-bottom:24px;">
        <div class="card glass">
            <div class="card-header"><div class="card-title"><i class="fas fa-chart-pie"></i> Workforce Utilization Today</div></div>
            <div class="card-body">
                <canvas id="utilizationChart" height="200"></canvas>
            </div>
        </div>
        <div class="card glass">
            <div class="card-header"><div class="card-title"><i class="fas fa-chart-bar"></i> Observation Trends</div></div>
            <div class="card-body">
                <canvas id="observationChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Contractor Productivity Monitoring (PDF Requirement) -->
    <div class="card glass" style="margin-bottom:24px;">
        <div class="card-header"><div class="card-title"><i class="fas fa-gauge-high"></i> Contractor Productivity Monitoring</div></div>
        <div class="card-body">
            <canvas id="productivityChart" height="100"></canvas>
        </div>
    </div>

    <div class="review-grid">
        <div class="card glass">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-file-signature"></i> Training Attendance Approval Desk</div>
            </div>
            <div class="card-body" style="padding:0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Worker</th>
                            <th>Contractor</th>
                            <th>Document</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($trainingQueue)): ?>
                            <tr><td colspan="4" style="text-align:center;padding:22px;color:#64748b">No training attendance approvals pending.</td></tr>
                        <?php else: foreach($trainingQueue as $t): ?>
                            <tr id="training-row-<?= (int)$t['id'] ?>">
                                <td>
                                    <strong><?= htmlspecialchars($t['name'] ?? '') ?></strong><br>
                                    <small><?= htmlspecialchars($t['aadhaar'] ?? '') ?></small>
                                </td>
                                <td><?= htmlspecialchars($t['contractor_name'] ?? 'N/A') ?></td>
                                <td>
                                    <a class="btn btn-sm btn-outline" target="_blank" href="../../uploads/workers/<?= htmlspecialchars($t['training_approval_doc']) ?>">
                                        <i class="fas fa-file-pdf"></i> View
                                    </a>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-success" onclick="reviewTraining(<?= (int)$t['id'] ?>, 'approved')"><i class="fas fa-check"></i> Approve</button>
                                    <button class="btn btn-sm btn-danger" onclick="reviewTraining(<?= (int)$t['id'] ?>, 'rejected')"><i class="fas fa-times"></i> Reject</button>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card glass">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-triangle-exclamation"></i> Recent Attendance Exceptions</div>
                <a href="attendance_exceptions.php" class="btn btn-sm btn-link">Open Desk</a>
            </div>
            <div class="card-body" style="padding:0">
                <div class="exception-list">
                    <?php if(empty($exceptionList)): ?>
                        <div class="empty-state"><i class="fas fa-circle-check"></i><p>No recent exceptions in assigned contractors.</p></div>
                    <?php else: foreach($exceptionList as $ex): ?>
                        <div class="exception-item">
                            <div>
                                <strong><?= htmlspecialchars($ex['exception_type'] ?: 'Attendance Exception') ?></strong>
                                <span><?= htmlspecialchars($ex['workman_name'] ?: 'Unknown Worker') ?> · <?= htmlspecialchars($ex['contractor_name'] ?: 'N/A') ?></span>
                            </div>
                            <span class="badge badge-warning"><?= strtoupper(htmlspecialchars($ex['status'] ?: 'open')) ?></span>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="main-column">
            <!-- Recent Activity / Observations -->
            <div class="card glass">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-list-check"></i> Recent Field Observations</div>
                    <a href="observations.php" class="btn btn-sm btn-link">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="card-body" style="padding:0">
                    <div class="activity-list">
                        <?php if(empty($observations)): ?>
                            <div class="empty-state">
                                <i class="fas fa-clipboard-check"></i>
                                <p>No observations recorded in the last 24 hours.</p>
                            </div>
                        <?php else: foreach($observations as $o): ?>
                            <div class="activity-item">
                                <div class="severity-indicator <?= $o['severity'] ?>"></div>
                                <div class="activity-info">
                                    <div class="activity-header">
                                        <span class="workman-name"><?= htmlspecialchars($o['workman_name']) ?></span>
                                        <span class="activity-time"><?= date('H:i', strtotime($o['created_at'])) ?></span>
                                    </div>
                                    <div class="activity-desc"><?= htmlspecialchars($o['observation_type']) ?> - <?= htmlspecialchars($o['contractor_name']) ?></div>
                                    <?php if($o['severity'] == 'high'): ?>
                                        <span class="badge badge-danger" style="font-size:10px">CRITICAL</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>

            <!-- Deployment Overview -->
            <div class="card glass" style="margin-top:24px">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-map-location-dot"></i> Live Deployment Map</div>
                </div>
                <div class="card-body" style="padding:0">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Location/Dept</th>
                                <th>Contractor</th>
                                <th>Status</th>
                                <th>Deployment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($deployments)): ?>
                                <tr><td colspan="4" style="text-align:center;padding:20px;color:#64748b">No active deployments found.</td></tr>
                            <?php else: foreach($deployments as $d): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($d['dept_name']) ?></strong></td>
                                    <td><small><?= htmlspecialchars($d['contractor_name']) ?></small></td>
                                    <td><span class="badge badge-success">ACTIVE</span></td>
                                    <td><div class="mini-chart"><div class="mini-bar" style="width:75%"></div></div></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="side-column">
            <!-- Monitoring Desks -->
            <div class="card glass">
                <div class="card-header"><div class="card-title">Supervision Hub</div></div>
                <div class="card-body" style="padding:8px">
                    <div class="monitoring-links">
                        <a href="contractors.php" class="monitor-link">
                            <div class="m-icon"><i class="fas fa-building"></i></div>
                            <div class="m-text">
                                <strong>Assigned Contractors</strong>
                                <span>Audit vendor compliance</span>
                            </div>
                        </a>
                        <a href="work_orders.php" class="monitor-link">
                            <div class="m-icon"><i class="fas fa-handshake"></i></div>
                            <div class="m-text">
                                <strong>Work Order Tracking</strong>
                                <span>Monitor project progress</span>
                            </div>
                        </a>
                        <a href="deployments.php" class="monitor-link">
                            <div class="m-icon"><i class="fas fa-users-viewfinder"></i></div>
                            <div class="m-text">
                                <strong>Deployment Monitoring</strong>
                                <span>Track real-time stationing</span>
                            </div>
                        </a>
                        <a href="attendance.php" class="monitor-link">
                            <div class="m-icon"><i class="fas fa-calendar-check"></i></div>
                            <div class="m-text">
                                <strong>Attendance Monitoring</strong>
                                <span>Biometric audit logs</span>
                            </div>
                        </a>
                        <a href="attendance_exceptions.php" class="monitor-link">
                            <div class="m-icon"><i class="fas fa-triangle-exclamation"></i></div>
                            <div class="m-text">
                                <strong>System Exceptions</strong>
                                <span>Identify discrepancies</span>
                            </div>
                        </a>
                        <a href="observations.php" class="monitor-link">
                            <div class="m-icon"><i class="fas fa-pen-to-square"></i></div>
                            <div class="m-text">
                                <strong>Field Observations</strong>
                                <span>Log safety & site issues</span>
                            </div>
                        </a>
                        <a href="productivity.php" class="monitor-link">
                            <div class="m-icon"><i class="fas fa-chart-line"></i></div>
                            <div class="m-text">
                                <strong>Productivity Center</strong>
                                <span>Manpower utilization</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Escalation Widget -->
            <div class="card glass" style="margin-top:20px; background: linear-gradient(135deg, #1e293b 0%, #334155 100%); color:#fff">
                <div class="card-body">
                    <h4 style="margin:0 0 10px 0; font-size:15px"><i class="fas fa-bullhorn" style="color:#f59e0b"></i> Quick Escalation</h4>
                    <p style="font-size:12px; opacity:0.8; margin-bottom:15px">Report critical safety or compliance issues directly to Welfare/Safety.</p>
                    <button class="btn btn-primary" style="width:100%; background:#f59e0b; border:none" onclick="location.href='escalations.php'">Forward Recommendation</button>
                </div>
            </div>
        </div>
    </div>

    <style>
    .stats-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 24px; }
    .stat-card { background: #fff; padding: 20px; border-radius: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); position: relative; overflow: hidden; display: flex; flex-direction: column; gap: 12px; border: 1px solid #f1f5f9; }
    .stat-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
    .blue .stat-icon { background: rgba(59,130,246,0.1); color: #3b82f6; }
    .green .stat-icon { background: rgba(16,185,129,0.1); color: #10b981; }
    .purple .stat-icon { background: rgba(124,58,237,0.1); color: #7c3aed; }
    .amber .stat-icon { background: rgba(245,158,11,0.1); color: #f59e0b; }
    .red .stat-icon { background: rgba(239,68,68,0.1); color: #ef4444; }
    .stat-label { font-size: 13px; color: #64748b; font-weight: 600; }
    .stat-value { font-size: 28px; font-weight: 800; color: #1e293b; margin: 0; }
    .stat-progress { height: 4px; background: #f1f5f9; border-radius: 2px; }
    .progress-bar { height: 100%; border-radius: 2px; }
    .blue .progress-bar { background: #3b82f6; }
    .green .progress-bar { background: #10b981; }
    .purple .progress-bar { background: #7c3aed; }
    .amber .progress-bar { background: #f59e0b; }
    .red .progress-bar { background: #ef4444; }

    .dashboard-grid { display: grid; grid-template-columns: 1.5fr 0.5fr; gap: 24px; }
    .review-grid { display:grid; grid-template-columns: 1.25fr .75fr; gap:24px; margin-bottom:24px; }
    .ops-strip { display:grid; grid-template-columns:repeat(4,1fr); gap:1px; overflow:hidden; border:1px solid #e2e8f0; border-radius:14px; background:#e2e8f0; margin:-4px 0 24px; }
    .ops-strip div { background:#fff; padding:14px 18px; display:flex; flex-direction:column; gap:4px; }
    .ops-strip span { font-size:11px; text-transform:uppercase; color:#64748b; font-weight:800; }
    .ops-strip strong { font-size:18px; color:#0f172a; }
    .monitoring-links { display: flex; flex-direction: column; gap: 5px; }
    .monitor-link { display: flex; align-items: center; gap: 15px; padding: 12px; border-radius: 12px; text-decoration: none; transition: all 0.2s; border: 1px solid transparent; }
    .monitor-link:hover { background: #f8fafc; border-color: #e2e8f0; transform: translateX(5px); }
    .m-icon { width: 36px; height: 36px; border-radius: 8px; background: #fff; display: flex; align-items: center; justify-content: center; color: #6366f1; box-shadow: 0 2px 4px rgba(0,0,0,0.05); font-size: 14px; }
    .m-text { display: flex; flex-direction: column; }
    .m-text strong { font-size: 13px; color: #334155; }
    .m-text span { font-size: 11px; color: #64748b; }

    .activity-list { display: flex; flex-direction: column; }
    .activity-item { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; display: flex; gap: 15px; align-items: flex-start; }
    .severity-indicator { width: 4px; height: 35px; border-radius: 2px; flex-shrink: 0; }
    .severity-indicator.high { background: #ef4444; }
    .severity-indicator.medium { background: #f59e0b; }
    .severity-indicator.low { background: #3b82f6; }
    .activity-info { flex-grow: 1; }
    .activity-header { display: flex; justify-content: space-between; margin-bottom: 4px; }
    .workman-name { font-weight: 700; font-size: 13px; color: #1e293b; }
    .activity-time { font-size: 11px; color: #94a3b8; }
    .activity-desc { font-size: 12px; color: #64748b; }
    .empty-state { padding: 40px; text-align: center; color: #94a3b8; }
    .empty-state i { font-size: 32px; margin-bottom: 10px; opacity: 0.5; }

    .mini-chart { height: 8px; background: #f1f5f9; border-radius: 4px; width: 80px; overflow: hidden; }
    .mini-bar { height: 100%; background: #6366f1; border-radius: 4px; }
    .exception-list { display:flex; flex-direction:column; }
    .exception-item { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:15px 18px; border-bottom:1px solid #f1f5f9; }
    .exception-item strong { display:block; font-size:13px; color:#1e293b; }
    .exception-item span:not(.badge) { display:block; font-size:11px; color:#64748b; margin-top:4px; }
    @media (max-width: 1100px) {
        .charts-grid, .dashboard-grid, .review-grid, .ops-strip { grid-template-columns: 1fr !important; }
    }
    </style>

    <script>
    async function reviewTraining(workmanId, decision) {
        const title = decision === 'approved' ? 'Approve training attendance?' : 'Reject training attendance?';
        const prompt = await Swal.fire({
            icon: decision === 'approved' ? 'question' : 'warning',
            title,
            input: 'textarea',
            inputPlaceholder: 'Remarks',
            showCancelButton: true,
            confirmButtonText: decision === 'approved' ? 'Approve' : 'Reject',
            confirmButtonColor: decision === 'approved' ? '#10b981' : '#ef4444'
        });
        if (!prompt.isConfirmed) return;

        try {
            const response = await fetch('../../api/execution/approve_training_attendance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    workman_id: workmanId,
                    decision,
                    remarks: prompt.value || ''
                })
            });
            const result = await response.json();
            if (result.status) {
                document.getElementById(`training-row-${workmanId}`)?.remove();
                Swal.fire('Updated', result.message || 'Training attendance reviewed.', 'success');
            } else {
                Swal.fire('Action Failed', result.message || 'Unable to update training attendance.', 'error');
            }
        } catch (err) {
            Swal.fire('Connection Error', 'Server response could not be processed.', 'error');
        }
    }

    document.addEventListener('DOMContentLoaded', async () => {
        try {
            const response = await fetch('../../api/execution/get_dashboard_stats.php');
            const res = await response.json();
            if (!res.status) return;

            const charts = res.charts;

            // Utilization Chart
            new Chart(document.getElementById('utilizationChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Deployed', 'Idle (Present)', 'Absent (Planned)'],
                    datasets: [{
                        data: [charts.utilization.present, charts.utilization.idle, charts.utilization.absent],
                        backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                        borderWidth: 0
                    }]
                },
                options: { cutout: '70%', plugins: { legend: { position: 'bottom' } } }
            });

            // Observation Trends
            new Chart(document.getElementById('observationChart'), {
                type: 'bar',
                data: {
                    labels: charts.observations.map(o => o.observation_type),
                    datasets: [{
                        label: 'Observations',
                        data: charts.observations.map(o => o.count),
                        backgroundColor: '#6366f1',
                        borderRadius: 6




                    }]
                },
                options: { scales: { y: { beginAtZero: true } } }
            });

            // Productivity Chart
            new Chart(document.getElementById('productivityChart'), {
                type: 'line',
                data: {
                    labels: charts.productivity.map(c => c.contractor_name),
                    datasets: [{
                        label: 'Utilization Efficiency (%)',
                        data: charts.productivity.map(c => (c.deployed > 0 ? (c.present/c.deployed)*100 : 0)),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: { scales: { y: { beginAtZero: true, max: 100 } } }
            });

        } catch (err) {
            console.error('Failed to load dashboard charts:', err);
        }
    });
    </script>

    <?php
}

renderLayout("Execution Officer Dashboard", 'renderContent', $role, $name);
?>

