<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Safety Officer';

function safetyReportsTableExists($conn, $table) {
    static $cache = [];
    if (isset($cache[$table])) {
        return $cache[$table];
    }

    $safeTable = mysqli_real_escape_string($conn, $table);
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$safeTable'");
    $cache[$table] = $result && mysqli_num_rows($result) > 0;
    return $cache[$table];
}

function safetyReportsColumnExists($conn, $table, $column) {
    static $cache = [];
    $key = $table . '.' . $column;
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    if (!safetyReportsTableExists($conn, $table)) {
        $cache[$key] = false;
        return false;
    }

    $safeColumn = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$safeColumn'");
    $cache[$key] = $result && mysqli_num_rows($result) > 0;
    return $cache[$key];
}

function safetyReportsColumnSql($conn, $table, $alias, $column, $fallback = 'NULL') {
    return safetyReportsColumnExists($conn, $table, $column) ? "$alias.`$column`" : $fallback;
}

function safetyReportsAddRows($rows, &$reportRows, &$seen) {
    foreach ($rows as $row) {
        $workerId = (int)($row['worker_id'] ?? 0);
        $sourceId = (string)($row['source_id'] ?? '');
        $status = strtolower((string)($row['status'] ?? 'pending'));
        $key = ($workerId ?: 'w0') . '|' . ($sourceId !== '' ? $sourceId : $status . '|' . ($row['report_date'] ?? ''));
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $reportRows[] = $row;
    }
}

function safetyReportsStatusBadge($status) {
    $status = strtolower((string)$status);
    if (in_array($status, ['passed', 'pass', 'training_passed', 'completed'], true)) {
        return 'badge-success';
    }
    if (in_array($status, ['failed', 'fail', 'training_failed', 'absent'], true)) {
        return 'badge-danger';
    }
    if (in_array($status, ['scheduled', 'training_scheduled', 'contractor_confirmed'], true)) {
        return 'badge-info';
    }
    return 'badge-warning';
}

function safetyReportsNormalStatus($status) {
    $status = strtolower(trim((string)$status));
    if (in_array($status, ['pass', 'passed', 'training_passed'], true)) return 'passed';
    if (in_array($status, ['fail', 'failed', 'training_failed'], true)) return 'failed';
    if (in_array($status, ['scheduled', 'training_scheduled', 'contractor_confirmed'], true)) return 'scheduled';
    if ($status === '') return 'pending';
    return $status;
}

function renderContent() {
    global $conn;

    $from_date = $_GET['from_date'] ?? date('Y-m-01');
    $to_date = $_GET['to_date'] ?? date('Y-m-d');
    $contractor_id = isset($_GET['contractor_id']) ? (int)$_GET['contractor_id'] : 0;
    $fromDateTime = $from_date . ' 00:00:00';
    $toDateTime = $to_date . ' 23:59:59';

    $contractors = safetyReportsTableExists($conn, 'contractors')
        ? db_fetch_all($conn, "SELECT id, contractor_name AS name FROM contractors ORDER BY contractor_name ASC")
        : [];

    $reportRows = [];
    $seen = [];

    if (safetyReportsTableExists($conn, 'training_requests') && safetyReportsColumnExists($conn, 'training_requests', 'workman_id')) {
        $dateExprs = [];
        foreach (['updated_at', 'scheduled_date', 'preferred_date', 'requested_date', 'created_at'] as $column) {
            if (safetyReportsColumnExists($conn, 'training_requests', $column)) {
                $dateExprs[] = "tr.`$column`";
            }
        }
        $dateExpr = $dateExprs ? 'COALESCE(' . implode(', ', $dateExprs) . ')' : 'CURDATE()';
        $statusExpr = safetyReportsColumnSql($conn, 'training_requests', 'tr', 'status', "'pending'");
        $typeExpr = safetyReportsColumnSql($conn, 'training_requests', 'tr', 'training_type', "'induction'");
        $workerName = safetyReportsColumnSql($conn, 'workmen', 'w', 'name', "'Worker'");
        $workerCode = safetyReportsColumnSql($conn, 'workmen', 'w', 'temp_id', "CONCAT('W-', w.id)");
        $contractorName = safetyReportsColumnSql($conn, 'contractors', 'c', 'contractor_name', "'N/A'");
        $contractorJoin = safetyReportsColumnExists($conn, 'training_requests', 'contractor_id') ? 'LEFT JOIN contractors c ON tr.contractor_id = c.id' : 'LEFT JOIN contractors c ON w.contractor_id = c.id';
        $attendanceJoin = '';
        $attendanceExpr = 'NULL';

        if (
            safetyReportsTableExists($conn, 'training_session_workers') &&
            safetyReportsColumnExists($conn, 'training_session_workers', 'training_request_id')
        ) {
            $attendanceJoin = 'LEFT JOIN training_session_workers sw ON tr.id = sw.training_request_id';
            $attendanceExpr = safetyReportsColumnSql($conn, 'training_session_workers', 'sw', 'attendance_status', 'NULL');
        }

        $where = "$dateExpr BETWEEN ? AND ?";
        $params = [$fromDateTime, $toDateTime];
        $types = 'ss';
        if ($contractor_id) {
            $where .= safetyReportsColumnExists($conn, 'training_requests', 'contractor_id') ? ' AND tr.contractor_id = ?' : ' AND w.contractor_id = ?';
            $params[] = $contractor_id;
            $types .= 'i';
        }

        $requestRows = db_fetch_all($conn, "
            SELECT
                tr.id AS source_id,
                w.id AS worker_id,
                $workerName AS worker_name,
                $workerCode AS worker_code,
                c.id AS contractor_id,
                $contractorName AS contractor_name,
                $typeExpr AS training_type,
                $attendanceExpr AS attendance_status,
                $statusExpr AS status,
                $dateExpr AS report_date,
                'Training Request' AS source
            FROM training_requests tr
            JOIN workmen w ON tr.workman_id = w.id
            $contractorJoin
            $attendanceJoin
            WHERE $where
            ORDER BY $dateExpr DESC
        ", $types, $params);
        safetyReportsAddRows($requestRows, $reportRows, $seen);
    }

    if (safetyReportsTableExists($conn, 'workmen')) {
        $workerDateExprs = [];
        foreach (['updated_at', 'created_at'] as $column) {
            if (safetyReportsColumnExists($conn, 'workmen', $column)) {
                $workerDateExprs[] = "w.`$column`";
            }
        }
        $workerDateExpr = $workerDateExprs ? 'COALESCE(' . implode(', ', $workerDateExprs) . ')' : 'CURDATE()';
        $workerName = safetyReportsColumnSql($conn, 'workmen', 'w', 'name', "'Worker'");
        $workerCode = safetyReportsColumnSql($conn, 'workmen', 'w', 'temp_id', "CONCAT('W-', w.id)");
        $trainingStatus = safetyReportsColumnSql($conn, 'workmen', 'w', 'training_status', "'pending'");
        $safetyStatus = safetyReportsColumnSql($conn, 'workmen', 'w', 'safety_training_status', 'NULL');
        $contractorName = safetyReportsColumnSql($conn, 'contractors', 'c', 'contractor_name', "'N/A'");

        $where = "$workerDateExpr BETWEEN ? AND ?";
        $params = [$fromDateTime, $toDateTime];
        $types = 'ss';
        if ($contractor_id) {
            $where .= ' AND w.contractor_id = ?';
            $params[] = $contractor_id;
            $types .= 'i';
        }

        $workerRows = db_fetch_all($conn, "
            SELECT
                CONCAT('workman-', w.id) AS source_id,
                w.id AS worker_id,
                $workerName AS worker_name,
                $workerCode AS worker_code,
                c.id AS contractor_id,
                $contractorName AS contractor_name,
                'induction' AS training_type,
                NULL AS attendance_status,
                COALESCE($trainingStatus, $safetyStatus, 'pending') AS status,
                $workerDateExpr AS report_date,
                'Worker Status' AS source
            FROM workmen w
            LEFT JOIN contractors c ON w.contractor_id = c.id
            WHERE $where
              AND LOWER(COALESCE($trainingStatus, $safetyStatus, 'pending')) IN ('pass','passed','fail','failed','training_passed','training_failed','training_pending','training_scheduled','pending','scheduled')
            ORDER BY $workerDateExpr DESC
        ", $types, $params);
        safetyReportsAddRows($workerRows, $reportRows, $seen);
    }

    usort($reportRows, function($a, $b) {
        return strtotime($b['report_date'] ?? '') <=> strtotime($a['report_date'] ?? '');
    });

    $stats = ['total_trained' => 0, 'passed' => 0, 'failed' => 0, 'scheduled' => 0, 'pending' => 0];
    foreach ($reportRows as $row) {
        $status = safetyReportsNormalStatus($row['status'] ?? '');
        if ($status === 'passed') {
            $stats['passed']++;
            $stats['total_trained']++;
        } elseif ($status === 'failed') {
            $stats['failed']++;
            $stats['total_trained']++;
        } elseif ($status === 'scheduled') {
            $stats['scheduled']++;
        } else {
            $stats['pending']++;
        }
    }
    $pass_ratio = $stats['total_trained'] > 0 ? round(($stats['passed'] / $stats['total_trained']) * 100, 1) : 0;
    ?>
    <div class="content-header safety-report-header">
      <div>
        <h2 class="page-title"><i class="fas fa-chart-bar"></i> Safety Training Reports</h2>
      </div>
      <div class="sr-actions">
        <a href="training_requests.php" class="btn btn-outline"><i class="fas fa-envelope-open-text"></i> Requests</a>
        <button type="button" class="btn btn-primary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
      </div>
    </div>

    <div class="sr-stats">
      <div class="sr-stat"><i class="fas fa-user-check" style="color:#059669"></i><strong><?= (int)$stats['total_trained'] ?></strong><span>Total Trained</span></div>
      <div class="sr-stat"><i class="fas fa-check-circle" style="color:#16a34a"></i><strong><?= (int)$stats['passed'] ?></strong><span>Passed</span></div>
      <div class="sr-stat"><i class="fas fa-times-circle" style="color:#dc2626"></i><strong><?= (int)$stats['failed'] ?></strong><span>Failed</span></div>
      <div class="sr-stat"><i class="fas fa-calendar-check" style="color:#2563eb"></i><strong><?= (int)$stats['scheduled'] ?></strong><span>Scheduled</span></div>
      <div class="sr-stat"><i class="fas fa-percent" style="color:#7c3aed"></i><strong><?= $pass_ratio ?>%</strong><span>Pass Ratio</span></div>
    </div>

    <div class="card glass sr-filter-card">
      <div class="card-body">
        <form method="GET" class="sr-filter-grid">
          <div class="form-group">
            <label class="form-label">From Date</label>
            <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($from_date) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">To Date</label>
            <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($to_date) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Contractor</label>
            <select name="contractor_id" class="form-control">
              <option value="">All Contractors</option>
              <?php foreach($contractors as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= $contractor_id === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name'] ?? 'N/A') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="sr-filter-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Generate</button>
            <a href="reports.php" class="btn btn-outline"><i class="fas fa-rotate-left"></i> Reset</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card glass">
      <div class="card-header">
        <div class="card-title">Training Report Details</div>
      </div>
      <div class="card-body" style="padding:0">
        <table class="data-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Worker</th>
              <th>Contractor</th>
              <th>Type</th>
              <th>Attendance</th>
              <th>Result</th>
              <th>Source</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($reportRows as $row):
              $status = safetyReportsNormalStatus($row['status'] ?? '');
              $attendance = $row['attendance_status'] ?: ($status === 'scheduled' ? 'scheduled' : ($status === 'pending' ? 'pending' : 'present'));
            ?>
            <tr>
              <td><?= !empty($row['report_date']) ? date('d M Y', strtotime($row['report_date'])) : '-' ?></td>
              <td>
                <div style="font-weight:700"><?= htmlspecialchars($row['worker_name'] ?? 'Worker') ?></div>
                <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($row['worker_code'] ?? '') ?></div>
              </td>
              <td><?= htmlspecialchars($row['contractor_name'] ?? 'N/A') ?></td>
              <td><span class="badge badge-outline"><?= htmlspecialchars(ucfirst((string)($row['training_type'] ?: 'Induction'))) ?></span></td>
              <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $attendance))) ?></td>
              <td><span class="badge <?= safetyReportsStatusBadge($status) ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $status))) ?></span></td>
              <td><span class="badge badge-gray"><?= htmlspecialchars($row['source'] ?? 'Report') ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($reportRows)): ?>
            <tr><td colspan="7" class="text-center" style="padding:40px;">No data found for the selected period.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <style>
      .safety-report-header{display:flex;align-items:flex-end;justify-content:space-between;gap:14px;margin-bottom:18px}
      .safety-report-header .page-title{display:flex;align-items:center;gap:10px}
      .sr-actions{display:flex;gap:8px;flex-wrap:wrap}
      .sr-stats{display:grid;grid-template-columns:repeat(5,minmax(140px,1fr));gap:12px;margin-bottom:16px}
      .sr-stat{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:14px;display:flex;align-items:center;gap:10px;box-shadow:0 1px 5px rgba(15,23,42,.05)}
      .sr-stat i{width:32px;height:32px;border-radius:8px;background:#f8fafc;display:flex;align-items:center;justify-content:center;flex:0 0 auto}
      .sr-stat strong{font-size:22px;color:#111827;line-height:1}
      .sr-stat span{font-size:11px;color:#64748b;font-weight:800;text-transform:uppercase}
      .sr-filter-card{margin-bottom:18px}
      .sr-filter-grid{display:grid;grid-template-columns:repeat(3,minmax(160px,1fr)) auto;gap:12px;align-items:end}
      .sr-filter-actions{display:flex;gap:8px}
      @media(max-width:1000px){.sr-stats{grid-template-columns:repeat(auto-fit,minmax(160px,1fr))}.sr-filter-grid{grid-template-columns:1fr 1fr}}
      @media(max-width:640px){.safety-report-header{flex-direction:column;align-items:stretch}.sr-actions,.sr-filter-actions{width:100%}.sr-actions .btn,.sr-filter-actions .btn{flex:1}.sr-filter-grid{grid-template-columns:1fr}}
      @media print{.sidebar,.topbar,.sr-actions,.sr-filter-card{display:none!important}.main-content{margin:0!important}.card{box-shadow:none!important}}
    </style>
    <?php
}

renderLayout("Training Reports", 'renderContent', $role, $name);
