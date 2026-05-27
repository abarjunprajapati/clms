<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Safety Officer';

function renderContent() {
    global $conn;
    
    $from_date = $_GET['from_date'] ?? date('Y-m-01');
    $to_date = $_GET['to_date'] ?? date('Y-m-d');
    $contractor_id = $_GET['contractor_id'] ?? '';

    // KPI calculation
    // Filter by activity date: scheduled_date -> preferred_date -> created_at
    $stats = db_single($conn, "
        SELECT 
            COUNT(*) as total_req,
            SUM(CASE WHEN status='passed' THEN 1 ELSE 0 END) as passed,
            SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) as failed,
            SUM(CASE WHEN status IN ('passed', 'failed') THEN 1 ELSE 0 END) as total_trained
        FROM training_requests tr
        WHERE tr.updated_at BETWEEN ? AND ?
    ", 'ss', ["$from_date 00:00:00", "$to_date 23:59:59"]);

    $pass_ratio = $stats['total_trained'] > 0 ? round(($stats['passed'] / $stats['total_trained']) * 100, 1) : 0;

    // Detailed report
    $where = "tr.updated_at BETWEEN ? AND ?";
    $params = ["$from_date 00:00:00", "$to_date 23:59:59"];
    $types = "ss";

    if ($contractor_id) {
        $where .= " AND tr.contractor_id = ?";
        $params[] = $contractor_id;
        $types .= "i";
    }

    $report_data = db_fetch_all($conn, "
        SELECT 
            tr.*, 
            w.name as worker_name, 
            w.temp_id as worker_code, 
            c.contractor_name,
            sw.attendance_status
        FROM training_requests tr
        JOIN workmen w ON tr.workman_id = w.id
        JOIN contractors c ON tr.contractor_id = c.id
        LEFT JOIN training_session_workers sw ON tr.id = sw.training_request_id
        WHERE $where
        ORDER BY COALESCE(tr.scheduled_date, tr.preferred_date, tr.created_at) DESC
    ", $types, $params);

    $contractors = db_fetch_all($conn, "SELECT id, contractor_name as name FROM contractors ORDER BY contractor_name ASC");

    ?>
    <div class="content-header">
      <h2 class="page-title">Safety Training Reports</h2>
      <!-- <p class="page-subtitle">Analyze training effectiveness and compliance ratios.</p> -->
    </div>

    <div class="grid grid-4" style="gap:20px; margin-bottom:20px">
        <div class="stat-card glass">
            <div class="stat-value"><?= $stats['total_trained'] ?></div>
            <div class="stat-label">Total Trained</div>
        </div>
        <div class="stat-card glass">
            <div class="stat-value text-success"><?= $stats['passed'] ?></div>
            <div class="stat-label">Total Passed</div>
        </div>
        <div class="stat-card glass">
            <div class="stat-value text-danger"><?= $stats['failed'] ?></div>
            <div class="stat-label">Total Failed</div>
        </div>
        <div class="stat-card glass">
            <div class="stat-value text-info"><?= $pass_ratio ?>%</div>
            <div class="stat-label">Pass Ratio</div>
        </div>
    </div>

    <div class="card glass" style="margin-bottom:20px">
        <div class="card-body">
            <form method="GET" class="grid grid-4" style="gap:15px; align-items:flex-end">
                <div class="form-group">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from_date" class="form-control" value="<?= $from_date ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to_date" class="form-control" value="<?= $to_date ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Contractor</label>
                    <select name="contractor_id" class="form-control">
                        <option value="">-- All Contractors --</option>
                        <?php foreach($contractors as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $contractor_id == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Generate</button>
                    <button type="button" class="btn btn-outline" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card glass">
      <div class="card-body">
        <table class="data-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Worker</th>
              <th>Contractor</th>
              <th>Type</th>
              <th>Attendance</th>
              <th>Result</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($report_data as $row): 
                $date = $row['updated_at'];
                $attendance = $row['attendance_status'] ?: ($row['status'] == 'pending' ? 'pending' : ($row['status'] == 'scheduled' ? 'scheduled' : '—'));
            ?>
            <tr>
              <td><?= date('d M Y', strtotime($date)) ?></td>
              <td>
                <div style="font-weight:600"><?= htmlspecialchars($row['worker_name']) ?></div>
                <div style="font-size:11px;opacity:0.7"><?= htmlspecialchars($row['worker_code']) ?></div>
              </td>
              <td><?= htmlspecialchars($row['contractor_name']) ?></td>
              <td><span class="badge badge-outline"><?= ucfirst($row['training_type'] ?: 'Induction') ?></span></td>
              <td>
                <span class="text-<?= ($attendance == 'present' || $row['status'] == 'passed') ? 'success' : ($attendance == 'absent' || $row['status'] == 'failed' ? 'danger' : 'info') ?>">
                    <?= ucfirst($attendance) ?>
                </span>
              </td>
              <td>
                <span class="badge <?= $row['status'] == 'passed' ? 'badge-success' : ($row['status'] == 'failed' ? 'badge-danger' : 'badge-warning') ?>">
                    <?= ucfirst($row['status']) ?>
                </span>
              </td>
            </tr>
            <?php endforeach; if(empty($report_data)): ?>
            <tr><td colspan="6" style="text-align:center;padding:40px;opacity:0.5">No data found for the selected period.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
}

renderLayout("Training Reports", 'renderContent', $role, $name);

