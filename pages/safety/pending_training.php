<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Safety Officer';

function pendingTrainingTableExists($conn, $table) {
    static $cache = [];
    if (isset($cache[$table])) {
        return $cache[$table];
    }

    $safeTable = clms_db_real_escape_string($conn, $table);
    $result = clms_db_query($conn, "SHOW TABLES LIKE '$safeTable'");
    $cache[$table] = $result && clms_db_num_rows($result) > 0;
    return $cache[$table];
}

function pendingTrainingColumnExists($conn, $table, $column) {
    static $cache = [];
    $key = $table . '.' . $column;
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    if (!pendingTrainingTableExists($conn, $table)) {
        $cache[$key] = false;
        return false;
    }

    $safeColumn = clms_db_real_escape_string($conn, $column);
    $result = clms_db_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$safeColumn'");
    $cache[$key] = $result && clms_db_num_rows($result) > 0;
    return $cache[$key];
}

function pendingTrainingColumnSql($conn, $table, $alias, $column, $fallback = 'NULL') {
    return pendingTrainingColumnExists($conn, $table, $column) ? "$alias.`$column`" : $fallback;
}

function pendingTrainingAddRows($rows, &$workers, &$seen) {
    foreach ($rows as $row) {
        $workerId = (int)($row['worker_id'] ?? 0);
        if (!$workerId || isset($seen[$workerId])) {
            continue;
        }
        $seen[$workerId] = true;
        $workers[] = $row;
    }
}

function renderContent() {
    global $conn;

    $contractor_id = isset($_GET['contractor_id']) ? (int)$_GET['contractor_id'] : 0;

    $contractors = pendingTrainingTableExists($conn, 'contractors')
        ? db_fetch_all($conn, "SELECT id, contractor_name FROM contractors ORDER BY contractor_name ASC")
        : [];

    $pending_workers = [];
    $seen = [];

    $workerName = pendingTrainingColumnSql($conn, 'workmen', 'w', 'name', "'Worker'");
    $workerCode = pendingTrainingColumnSql($conn, 'workmen', 'w', 'temp_id', "CONCAT('W-', w.id)");
    $workerTrade = pendingTrainingColumnSql($conn, 'workmen', 'w', 'trade', "''");
    $workerCreated = pendingTrainingColumnSql($conn, 'workmen', 'w', 'created_at', 'NOW()');
    $trainingStatus = pendingTrainingColumnSql($conn, 'workmen', 'w', 'training_status', "'pending'");
    $safetyStatus = pendingTrainingColumnSql($conn, 'workmen', 'w', 'safety_training_status', 'NULL');
    $contractorName = pendingTrainingColumnSql($conn, 'contractors', 'c', 'contractor_name', "'N/A'");

    if (pendingTrainingTableExists($conn, 'training_requests') && pendingTrainingColumnExists($conn, 'training_requests', 'workman_id')) {
        $trStatus = pendingTrainingColumnSql($conn, 'training_requests', 'tr', 'status', "'pending'");
        $trDate = pendingTrainingColumnSql($conn, 'training_requests', 'tr', 'created_at', $workerCreated);
        $trContractorWhere = pendingTrainingColumnExists($conn, 'training_requests', 'contractor_id') ? 'tr.contractor_id' : 'w.contractor_id';

        $where = "LOWER(COALESCE($trStatus, 'pending')) IN ('pending','failed','correction_required')";
        $params = [];
        $types = '';
        if ($contractor_id) {
            $where .= " AND $trContractorWhere = ?";
            $params[] = $contractor_id;
            $types .= 'i';
        }

        $requestRows = db_fetch_all($conn, "
            SELECT
                w.id AS worker_id,
                $workerName AS name,
                $workerCode AS temp_id,
                $workerTrade AS trade,
                w.contractor_id,
                $contractorName AS contractor_name,
                COALESCE($trainingStatus, 'pending') AS training_status,
                COALESCE($safetyStatus, '') AS safety_training_status,
                $trDate AS queue_date,
                'Training Request' AS source
            FROM training_requests tr
            JOIN workmen w ON tr.workman_id = w.id
            LEFT JOIN contractors c ON w.contractor_id = c.id
            WHERE $where
            ORDER BY $trDate ASC
        ", $types, $params);
        pendingTrainingAddRows($requestRows, $pending_workers, $seen);
    }

    if (pendingTrainingTableExists($conn, 'workmen')) {
        $where = "
            LOWER(COALESCE($trainingStatus, 'pending')) IN ('pending','training_pending','fail','failed','training_failed')
            OR LOWER(COALESCE($safetyStatus, 'pending')) IN ('pending_training','training_failed','failed_training','pending','0','')
        ";
        $params = [];
        $types = '';
        if ($contractor_id) {
            $where = "($where) AND w.contractor_id = ?";
            $params[] = $contractor_id;
            $types .= 'i';
        }

        $workerRows = db_fetch_all($conn, "
            SELECT
                w.id AS worker_id,
                $workerName AS name,
                $workerCode AS temp_id,
                $workerTrade AS trade,
                w.contractor_id,
                $contractorName AS contractor_name,
                COALESCE($trainingStatus, 'pending') AS training_status,
                COALESCE($safetyStatus, '') AS safety_training_status,
                $workerCreated AS queue_date,
                'Worker Status' AS source
            FROM workmen w
            LEFT JOIN contractors c ON w.contractor_id = c.id
            WHERE $where
            ORDER BY $workerCreated ASC
        ", $types, $params);
        pendingTrainingAddRows($workerRows, $pending_workers, $seen);
    }

    usort($pending_workers, function($a, $b) {
        $contractorCompare = strcasecmp($a['contractor_name'] ?? '', $b['contractor_name'] ?? '');
        if ($contractorCompare !== 0) {
            return $contractorCompare;
        }
        return strtotime($a['queue_date'] ?? '') <=> strtotime($b['queue_date'] ?? '');
    });

    $contractor_summary = [];
    foreach ($pending_workers as $worker) {
        $cid = (int)($worker['contractor_id'] ?? 0);
        if (!isset($contractor_summary[$cid])) {
            $contractor_summary[$cid] = [
                'contractor_name' => $worker['contractor_name'] ?? 'N/A',
                'count' => 0
            ];
        }
        $contractor_summary[$cid]['count']++;
    }
    usort($contractor_summary, function($a, $b) {
        return (int)$b['count'] <=> (int)$a['count'];
    });
    ?>
    <div class="content-header pending-training-header">
      <div>
        <h2 class="page-title"><i class="fas fa-hourglass-half"></i> Pending Training Queue</h2>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="training_requests.php" class="btn btn-primary"><i class="fas fa-envelope-open-text"></i> Process Requests</a>
        <a href="training_schedule.php" class="btn btn-outline"><i class="fas fa-calendar-alt"></i> Schedule</a>
      </div>
    </div>

    <div class="pending-summary">
      <div class="pending-stat"><i class="fas fa-users"></i><strong><?= count($pending_workers) ?></strong><span>Total Pending</span></div>
      <div class="pending-stat"><i class="fas fa-building"></i><strong><?= count($contractor_summary) ?></strong><span>Contractors</span></div>
      <div class="pending-stat warning"><i class="fas fa-clock"></i><strong><?= count(array_filter($pending_workers, function($w) { return floor((time() - strtotime($w['queue_date'] ?? date('Y-m-d'))) / 86400) > 7; })) ?></strong><span>Over 7 Days</span></div>
    </div>

    <div class="card glass pending-filter">
      <div class="card-body">
        <form method="GET" style="display:grid;grid-template-columns:minmax(220px,1fr) auto auto;gap:10px;align-items:end">
          <div class="form-group">
            <label class="form-label">Contractor</label>
            <select name="contractor_id" class="form-control">
              <option value="">All Contractors</option>
              <?php foreach ($contractors as $contractor): ?>
              <option value="<?= (int)$contractor['id'] ?>" <?= $contractor_id === (int)$contractor['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($contractor['contractor_name'] ?? 'N/A') ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
          <a href="pending_training.php" class="btn btn-outline"><i class="fas fa-rotate-left"></i> Reset</a>
        </form>
      </div>
    </div>

    <?php if (!empty($contractor_summary)): ?>
    <div class="contractor-pending-grid">
      <?php foreach ($contractor_summary as $summary): ?>
      <div class="contractor-pending-card">
        <span><?= htmlspecialchars($summary['contractor_name']) ?></span>
        <strong><?= (int)$summary['count'] ?></strong>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="card glass">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-hourglass-half"></i> Contractor-wise Pending Workers (<?= count($pending_workers) ?>)</div>
      </div>
      <div class="card-body" style="padding:0">
        <table class="data-table">
          <thead>
            <tr>
              <th>Contractor</th>
              <th>Worker Name</th>
              <th>Code</th>
              <th>Trade</th>
              <th>Status</th>
              <th>Days in Queue</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($pending_workers as $w):
                $queueTime = !empty($w['queue_date']) ? strtotime($w['queue_date']) : time();
                $days = max(0, floor((time() - $queueTime) / 86400));
                $statusText = trim((string)($w['training_status'] ?: $w['safety_training_status'] ?: 'pending'));
            ?>
            <tr>
              <td><?= htmlspecialchars($w['contractor_name'] ?? 'N/A') ?></td>
              <td><strong><?= htmlspecialchars($w['name'] ?? 'Worker') ?></strong></td>
              <td><?= htmlspecialchars($w['temp_id'] ?? '') ?></td>
              <td><?= htmlspecialchars($w['trade'] ?? '') ?></td>
              <td>
                <span class="badge badge-warning"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $statusText))) ?></span>
                <div style="font-size:10px;color:var(--text-muted);margin-top:3px"><?= htmlspecialchars($w['source'] ?? '') ?></div>
              </td>
              <td>
                <span class="<?= $days > 7 ? 'text-danger' : ($days > 3 ? 'text-warning' : '') ?>" style="font-weight:700">
                  <?= $days ?> Days
                </span>
              </td>
              <td>
                <a href="training_requests.php?search=<?= urlencode($w['temp_id'] ?: $w['name']) ?>" class="btn btn-sm btn-outline">Schedule</a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($pending_workers)): ?>
            <tr><td colspan="7" style="text-align:center;padding:40px;">Queue is clear. No pending workers.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <style>
      .pending-training-header{display:flex;justify-content:space-between;align-items:flex-end;gap:14px;margin-bottom:16px}
      .pending-training-header .page-title{display:flex;align-items:center;gap:10px}
      .pending-summary{display:grid;grid-template-columns:repeat(3,minmax(160px,1fr));gap:12px;margin-bottom:16px}
      .pending-stat{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:14px;display:flex;align-items:center;gap:10px}
      .pending-stat i{width:34px;height:34px;border-radius:8px;background:#eef2ff;color:#4f46e5;display:flex;align-items:center;justify-content:center}
      .pending-stat.warning i{background:#fef3c7;color:#d97706}
      .pending-stat strong{font-size:24px;color:#111827}
      .pending-stat span{font-size:11px;color:#64748b;font-weight:800;text-transform:uppercase}
      .pending-filter{margin-bottom:16px}
      .contractor-pending-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;margin-bottom:16px}
      .contractor-pending-card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:12px;display:flex;justify-content:space-between;align-items:center}
      .contractor-pending-card span{font-weight:700;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;padding-right:10px}
      .contractor-pending-card strong{font-size:20px;color:#4f46e5}
      @media(max-width:760px){.pending-training-header{flex-direction:column;align-items:stretch}.pending-summary{grid-template-columns:1fr}.pending-filter form{grid-template-columns:1fr!important}}
    </style>
    <?php
}

renderLayout("Pending Queue", 'renderContent', $role, $name);
