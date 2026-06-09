<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Admin';

function tempPassControlColumnExists($conn, $table, $column) {
    static $cache = [];

    $allowedTables = ['gate_passes', 'workmen', 'gate_pass_requests', 'gate_pass_request_workers'];
    if (!in_array($table, $allowedTables, true)) {
        return false;
    }

    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $safeColumn = clms_db_real_escape_string($conn, $column);
    $result = clms_db_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$safeColumn'");
    $cache[$key] = $result && clms_db_num_rows($result) > 0;

    return $cache[$key];
}

function tempPassControlColumnSql($conn, $table, $alias, $column, $fallback = 'NULL') {
    return tempPassControlColumnExists($conn, $table, $column) ? "$alias.`$column`" : $fallback;
}

function renderContent() {
    global $conn;

    $gpPassNumber = tempPassControlColumnSql($conn, 'gate_passes', 'gp', 'pass_number');
    $gpApprovedDate = tempPassControlColumnSql($conn, 'gate_passes', 'gp', 'approved_date');
    $gprUpdatedAt = tempPassControlColumnSql($conn, 'gate_pass_requests', 'gpr', 'updated_at', 'gpr.created_at');
    $gprwGatepassNo = tempPassControlColumnSql($conn, 'gate_pass_request_workers', 'gprw', 'gatepass_no');

    $wTempPassNo = tempPassControlColumnSql($conn, 'workmen', 'w', 'temp_pass_no');
    $wTempId = tempPassControlColumnSql($conn, 'workmen', 'w', 'temp_id');
    $wTempValidFrom = tempPassControlColumnSql($conn, 'workmen', 'w', 'temp_valid_from');
    $wTempValidTo = tempPassControlColumnSql($conn, 'workmen', 'w', 'temp_valid_to');
    $wValidFrom = tempPassControlColumnSql($conn, 'workmen', 'w', 'valid_from');
    $wValidTo = tempPassControlColumnSql($conn, 'workmen', 'w', 'valid_to');
    $wUpdatedAt = tempPassControlColumnSql($conn, 'workmen', 'w', 'updated_at', 'w.created_at');

    $workmenTempConditions = ["w.status = 'temporary_issued'"];
    foreach (['temp_pass_status', 'temp_pass_no'] as $column) {
        if ($column === 'temp_pass_status' && tempPassControlColumnExists($conn, 'workmen', $column)) {
            $workmenTempConditions[] = "COALESCE(w.`$column`, 0) = 1";
        } elseif (tempPassControlColumnExists($conn, 'workmen', $column)) {
            $workmenTempConditions[] = "COALESCE(w.`$column`, '') <> ''";
        }
    }
    $workmenTempWhere = implode("\n           OR ", $workmenTempConditions);

    $rows = db_fetch_all($conn, "
        SELECT
            w.id AS workman_id,
            COALESCE($gpPassNumber, $wTempPassNo, $wTempId) AS pass_no,
            w.name,
            $wTempId AS workman_temp_id,
            c.contractor_name,
            gp.valid_from,
            gp.valid_to,
            gp.status AS pass_status,
            COALESCE($gpApprovedDate, gp.created_at, $wUpdatedAt, w.created_at) AS issued_at,
            1 AS priority
        FROM gate_passes gp
        JOIN workmen w ON gp.workman_id = w.id
        JOIN contractors c ON w.contractor_id = c.id
        WHERE gp.pass_type = 'temporary'
          AND gp.status IN ('active', 'approved', 'issued')

        UNION ALL

        SELECT
            w.id AS workman_id,
            COALESCE($gprwGatepassNo, gpr.request_no, $wTempPassNo, $wTempId) AS pass_no,
            w.name,
            $wTempId AS workman_temp_id,
            c.contractor_name,
            gpr.from_date AS valid_from,
            gpr.to_date AS valid_to,
            gpr.status AS pass_status,
            COALESCE($gprUpdatedAt, gpr.created_at, $wUpdatedAt, w.created_at) AS issued_at,
            2 AS priority
        FROM gate_pass_requests gpr
        JOIN gate_pass_request_workers gprw ON gprw.request_id = gpr.id
        JOIN workmen w ON gprw.workman_id = w.id
        JOIN contractors c ON COALESCE(gpr.contractor_id, w.contractor_id) = c.id
        WHERE LOWER(COALESCE(gpr.pass_type, 'temporary')) <> 'permanent'
          AND gpr.status IN ('active', 'approved', 'issued')

        UNION ALL

        SELECT
            w.id AS workman_id,
            COALESCE($wTempPassNo, $wTempId) AS pass_no,
            w.name,
            $wTempId AS workman_temp_id,
            c.contractor_name,
            COALESCE($wTempValidFrom, $wValidFrom) AS valid_from,
            COALESCE($wTempValidTo, $wValidTo) AS valid_to,
            'active' AS pass_status,
            COALESCE($wUpdatedAt, w.created_at) AS issued_at,
            3 AS priority
        FROM workmen w
        JOIN contractors c ON w.contractor_id = c.id
        WHERE $workmenTempWhere

        ORDER BY valid_to ASC, issued_at DESC
    ");

    $passesByKey = [];
    foreach ($rows as $row) {
        $workmanId = (int)($row['workman_id'] ?? 0);
        if (!$workmanId) {
            continue;
        }

        $passNo = trim((string)($row['pass_no'] ?? ''));
        $key = $workmanId . '|' . ($passNo !== '' ? $passNo : ('priority-' . (int)$row['priority']));
        if (!isset($passesByKey[$key]) || (int)$row['priority'] < (int)$passesByKey[$key]['priority']) {
            $passesByKey[$key] = $row;
        }
    }

    $passes = array_values($passesByKey);
    usort($passes, function($a, $b) {
        return strtotime($a['valid_to'] ?? '9999-12-31') <=> strtotime($b['valid_to'] ?? '9999-12-31');
    });
    ?>
    <div class="content-header">
      <h2 class="page-title">Temporary Pass Control</h2>
      <!-- <p class="page-subtitle">Monitor validity and expiration of issued temporary passes.</p> -->
    </div>

    <div class="stats-grid">
      <div class="stat-card glass">
        <div class="stat-icon" style="background:rgba(59,130,246,0.1);color:var(--info)"><i class="fas fa-ticket"></i></div>
        <div class="stat-value"><?= count($passes) ?></div>
        <div class="stat-label">Total Temp Passes</div>
      </div>
      <?php
      $expired = 0;
      $expiring_soon = 0;
      foreach($passes as $p) {
          $to = !empty($p['valid_to']) ? strtotime($p['valid_to']) : null;
          if (!$to) {
              continue;
          }
          if ($to < strtotime(date('Y-m-d'))) $expired++;
          elseif ($to < strtotime('+7 days')) $expiring_soon++;
      }
      ?>
      <div class="stat-card glass">
        <div class="stat-icon" style="background:rgba(239,68,68,0.1);color:var(--danger)"><i class="fas fa-calendar-times"></i></div>
        <div class="stat-value"><?= $expired ?></div>
        <div class="stat-label">Expired Passes</div>
      </div>
      <div class="stat-card glass">
        <div class="stat-icon" style="background:rgba(245,158,11,0.1);color:var(--warning)"><i class="fas fa-hourglass-end"></i></div>
        <div class="stat-value"><?= $expiring_soon ?></div>
        <div class="stat-label">Expiring within 7 days</div>
      </div>
    </div>

    <div class="card glass" style="margin-top:24px">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-history"></i> Temporary Pass Tracking</div>
      </div>
      <div class="card-body">
        <table class="data-table">
          <thead>
            <tr>
              <th>Pass No</th>
              <th>Workman ID</th>
              <th>Workman Name</th>
              <th>Contractor</th>
              <th>Issued Date</th>
              <th>Valid To</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($passes as $p):
              $validTo = $p['valid_to'] ?? null;
              $is_expired = $validTo && strtotime($validTo) < strtotime(date('Y-m-d'));
              $status_badge = $is_expired ? 'badge-danger' : 'badge-success';
              $status_text = $is_expired ? 'Expired' : 'Active';
            ?>
            <tr>
              <td><code><?= htmlspecialchars($p['pass_no'] ?: 'N/A') ?></code></td>
              <td><code><?= htmlspecialchars($p['workman_temp_id'] ?: ('W-' . (int)$p['workman_id'])) ?></code></td>
              <td><strong><?= htmlspecialchars($p['name'] ?? 'Unknown') ?></strong></td>
              <td><?= htmlspecialchars($p['contractor_name'] ?? 'N/A') ?></td>
              <td><?= !empty($p['issued_at']) ? date('d M Y', strtotime($p['issued_at'])) : '-' ?></td>
              <td>
                <span class="<?= $is_expired ? 'text-danger' : '' ?>">
                  <?= !empty($validTo) ? date('d M Y', strtotime($validTo)) : '-' ?>
                </span>
              </td>
              <td><span class="badge <?= $status_badge ?>"><?= $status_text ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($passes)): ?>
            <tr><td colspan="7" class="text-center" style="padding:40px;">No temporary passes found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
}

renderLayout("Temporary Pass Control", 'renderContent', $role, $name);
