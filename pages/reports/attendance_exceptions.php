<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin', 'execution_officer']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    
    // Fetch exceptions (anomalies)
    $sql = "SELECT e.*, w.name as workman_name, c.contractor_name 
            FROM attendance_exceptions e 
            JOIN workmen w ON e.workman_id = w.id 
            JOIN contractors c ON w.contractor_id = c.id 
            ORDER BY e.created_at DESC LIMIT 100";
    // Note: attendance_exceptions table might be empty/new
    $exceptions = db_fetch_all($conn, $sql);
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-exclamation-circle" style="color:#ef4444;margin-right:10px;"></i> Attendance Exception Report</h2>
        <p class="page-subtitle">Monitoring anomalies like overtime, short-stay, and unauthorized entries.</p>
      </div>
      <div class="action-buttons">
        <button class="btn btn-secondary"><i class="fas fa-file-export"></i> Export PDF</button>
        <button class="btn btn-secondary"><i class="fas fa-file-excel"></i> Export Excel</button>
      </div>
    </div>

    <div class="card glass" style="padding:20px;margin-bottom:20px;">
        <form style="display:flex;gap:15px;align-items:flex-end;">
            <div style="flex:1;">
                <label style="display:block;font-size:12px;opacity:0.6;margin-bottom:5px;">Department</label>
                <select class="form-control" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:#fff;">
                    <option>All Departments</option>
                </select>
            </div>
            <div style="flex:1;">
                <label style="display:block;font-size:12px;opacity:0.6;margin-bottom:5px;">Exception Type</label>
                <select class="form-control" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:#fff;">
                    <option>Overtime (>12h)</option>
                    <option>Short Stay (<4h)</option>
                    <option>Unauthorized Entry</option>
                </select>
            </div>
            <button class="btn btn-primary" type="button">Filter Report</button>
        </form>
    </div>

    <div class="card glass" style="padding:0;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Workman</th>
                        <th>Contractor</th>
                        <th>Anomaly Type</th>
                        <th>Duration / Value</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($exceptions)): ?>
                        <tr><td colspan="6" style="text-align:center;padding:40px;opacity:0.5;">No attendance exceptions found for the selected period.</td></tr>
                    <?php else: ?>
                        <?php foreach($exceptions as $e): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($e['date'])) ?></td>
                                <td><?= htmlspecialchars($e['workman_name']) ?></td>
                                <td><?= htmlspecialchars($e['contractor_name']) ?></td>
                                <td><span style="color:#ef4444;"><?= htmlspecialchars($e['exception_type']) ?></span></td>
                                <td><?= $e['value'] ?></td>
                                <td><span class="status-pill status-pending">Unresolved</span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

renderLayout('Attendance Exception Report', 'renderContent', $_SESSION['role'], $_SESSION['name']);
?>
