<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Safety Officer';

function retrainingColumnExists($conn, $table, $column) {
    static $cache = [];
    $key = $table . '.' . $column;
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $safeColumn = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$safeColumn'");
    $cache[$key] = $result && mysqli_num_rows($result) > 0;
    return $cache[$key];
}

function retrainingColumnSql($conn, $table, $alias, $column, $fallback = 'NULL') {
    return retrainingColumnExists($conn, $table, $column) ? "$alias.`$column`" : $fallback;
}

function retrainingReason($status, $safetyStatus, $validTill) {
    $status = strtolower((string)$status);
    $safetyStatus = strtolower((string)$safetyStatus);
    if (!empty($validTill) && strtotime($validTill) < strtotime(date('Y-m-d'))) {
        return 'Certificate Expired';
    }
    if (strpos($status, 'fail') !== false || strpos($safetyStatus, 'fail') !== false) {
        return 'Failed Assessment';
    }
    return 'Needs Re-Training';
}

function renderContent() {
    global $conn;

    $nameExpr = retrainingColumnSql($conn, 'workmen', 'w', 'name', "'Worker'");
    $tempExpr = retrainingColumnSql($conn, 'workmen', 'w', 'temp_id', "CONCAT('W-', w.id)");
    $trainingExpr = retrainingColumnSql($conn, 'workmen', 'w', 'training_status', "'pending'");
    $safetyExpr = retrainingColumnSql($conn, 'workmen', 'w', 'safety_training_status', 'NULL');
    $validTillExpr = retrainingColumnSql($conn, 'workmen', 'w', 'training_valid_till', 'NULL');
    $contractorNameExpr = retrainingColumnSql($conn, 'contractors', 'c', 'contractor_name', "'N/A'");
    $resultJoin = "";
    $resultSelect = "NULL AS latest_training_date, 0 AS attempts_30";
    $resultTable = mysqli_query($conn, "SHOW TABLES LIKE 'training_results'");
    if ($resultTable && mysqli_num_rows($resultTable) > 0) {
        $resultJoin = "
        LEFT JOIN (
            SELECT
                workman_id,
                MIN(DATE(created_at)) AS first_training_date,
                MAX(DATE(created_at)) AS latest_training_date,
                SUM(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS attempts_30
            FROM training_results
            GROUP BY workman_id
        ) trr ON trr.workman_id = w.id";
        $resultSelect = "trr.latest_training_date, COALESCE(trr.attempts_30, 0) AS attempts_30, trr.first_training_date";
    }

    $retrain_list = db_fetch_all($conn, "
        SELECT
            w.id,
            $nameExpr AS name,
            $tempExpr AS temp_id,
            $trainingExpr AS training_status,
            $safetyExpr AS safety_training_status,
            $validTillExpr AS training_valid_till,
            $contractorNameExpr AS contractor_name,
            $resultSelect
        FROM workmen w
        LEFT JOIN contractors c ON w.contractor_id = c.id
        $resultJoin
        WHERE
            LOWER(COALESCE($trainingExpr, '')) IN ('fail', 'failed', 'training_failed', 'training_expired', 'expired')
            OR LOWER(COALESCE($safetyExpr, '')) IN ('training_failed', 'failed_training', 'fail', 'failed')
            OR ($validTillExpr IS NOT NULL AND $validTillExpr <> '' AND $validTillExpr < CURDATE())
        ORDER BY
            CASE WHEN $validTillExpr IS NOT NULL AND $validTillExpr <> '' AND $validTillExpr < CURDATE() THEN 0 ELSE 1 END,
            name ASC
    ");

    ?>
    <div class="content-header retraining-header">
      <div>
        <h2 class="page-title"><i class="fas fa-redo"></i> Re-Training Management</h2>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="training_requests.php" class="btn btn-outline"><i class="fas fa-envelope-open-text"></i> Requests</a>
        <a href="training_status.php" class="btn btn-primary"><i class="fas fa-user-check"></i> Status Tracker</a>
      </div>
    </div>

    <?php if (!empty($_GET['success'])): ?>
      <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php elseif (!empty($_GET['error'])): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <div class="retraining-stats">
      <div class="retraining-stat"><i class="fas fa-users"></i><strong><?= count($retrain_list) ?></strong><span>Total Cases</span></div>
      <div class="retraining-stat danger"><i class="fas fa-times-circle"></i><strong><?= count(array_filter($retrain_list, function($w) { return retrainingReason($w['training_status'], $w['safety_training_status'], $w['training_valid_till']) === 'Failed Assessment'; })) ?></strong><span>Failed</span></div>
      <div class="retraining-stat warning"><i class="fas fa-calendar-times"></i><strong><?= count(array_filter($retrain_list, function($w) { return retrainingReason($w['training_status'], $w['safety_training_status'], $w['training_valid_till']) === 'Certificate Expired'; })) ?></strong><span>Expired</span></div>
    </div>

    <div class="card glass">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-redo"></i> Workers Needing Re-Training (<?= count($retrain_list) ?>)</div>
      </div>
      <div class="card-body">
        <table class="data-table">
          <thead>
            <tr>
              <th>Worker Name</th>
              <th>Status</th>
              <th>Reason</th>
              <th>Last Training</th>
              <th>Contractor</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($retrain_list as $w): ?>
            <?php $reason = retrainingReason($w['training_status'], $w['safety_training_status'], $w['training_valid_till']); ?>
            <tr>
              <td>
                <div style="font-weight:600"><?= htmlspecialchars($w['name']) ?></div>
                <div style="font-size:11px;opacity:0.7"><?= htmlspecialchars($w['temp_id']) ?></div>
              </td>
              <td>
                <span class="badge badge-danger"><?= htmlspecialchars(strtoupper(str_replace('training_', '', $w['training_status'] ?: $w['safety_training_status']))) ?></span>
              </td>
              <td>
                <?= htmlspecialchars($reason) ?>
              </td>
              <td>
                <?php if (!empty($w['training_valid_till'])): ?>
                  <?= date('d M Y', strtotime($w['training_valid_till'] . ' -1 year')) ?>
                  <div style="font-size:11px;color:var(--text-muted)">Valid till: <?= date('d M Y', strtotime($w['training_valid_till'])) ?></div>
                <?php else: ?>
                  N/A
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($w['contractor_name']) ?></td>
              <td>
                <?php
                  $firstTrainingDate = $w['first_training_date'] ?? $w['latest_training_date'] ?? null;
                  $daysSinceFirst = $firstTrainingDate ? floor((strtotime(date('Y-m-d')) - strtotime($firstTrainingDate)) / 86400) : 0;
                  $attempts = (int)($w['attempts_30'] ?? 0);
                  $retestBlocked = ($attempts >= 3) || ($firstTrainingDate && $daysSinceFirst > 30);
                  $blockMessage = $attempts >= 3
                      ? 'Maximum Attempt Reached'
                      : (($firstTrainingDate && $daysSinceFirst > 30) ? 'Retest period exceeded 30 days. Please apply for training again.' : '');
                ?>
                <?php if ($retestBlocked): ?>
                  <span class="badge badge-danger"><?= htmlspecialchars($blockMessage) ?></span>
                  <div style="font-size:11px;color:var(--text-muted);margin-top:4px"><?= $attempts ?> attempt(s), <?= (int)$daysSinceFirst ?> day(s)</div>
                <?php else: ?>
                <form action="../../api/safety/request_retraining.php" method="POST" style="display:inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="workman_id" value="<?= $w['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-primary">Allow Retest</button>
                </form>
                <div style="font-size:11px;color:var(--text-muted);margin-top:4px"><?= $attempts ?> / 3 attempt(s)</div>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; if(empty($retrain_list)): ?>
            <tr><td colspan="6" style="text-align:center;padding:40px;">No workers currently need re-training.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <style>
      .retraining-header{display:flex;justify-content:space-between;align-items:flex-end;gap:14px;margin-bottom:16px}
      .retraining-header .page-title{display:flex;align-items:center;gap:10px}
      .retraining-stats{display:grid;grid-template-columns:repeat(3,minmax(160px,1fr));gap:12px;margin-bottom:16px}
      .retraining-stat{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:14px;display:flex;align-items:center;gap:10px}
      .retraining-stat i{width:34px;height:34px;border-radius:8px;background:#eef2ff;color:#4f46e5;display:flex;align-items:center;justify-content:center}
      .retraining-stat.danger i{background:#fee2e2;color:#dc2626}
      .retraining-stat.warning i{background:#fef3c7;color:#d97706}
      .retraining-stat strong{font-size:24px;color:#111827}
      .retraining-stat span{font-size:11px;color:#64748b;font-weight:800;text-transform:uppercase}
      @media(max-width:760px){.retraining-header{flex-direction:column;align-items:stretch}.retraining-stats{grid-template-columns:1fr}}
    </style>
    <?php
}

renderLayout("Re-Training", 'renderContent', $role, $name);
