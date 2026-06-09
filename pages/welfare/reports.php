<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['pass_user', 'super_admin', 'welfare_admin', 'welfare_user']);

include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = get_normalized_role();
$name = $_SESSION['name'] ?? 'Officer';

function welfareReportsColumnExists($conn, $table, $column) {
    static $cache = [];

    $allowedTables = ['gate_passes', 'workmen', 'permanent_gate_passes', 'gate_pass_requests', 'gate_pass_request_workers'];
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

function welfareReportsColumnSql($conn, $table, $alias, $column, $fallback = 'NULL') {
    return welfareReportsColumnExists($conn, $table, $column) ? "$alias.`$column`" : $fallback;
}

function welfareReportsIsExpired($date) {
    return !empty($date) && strtotime($date) < strtotime(date('Y-m-d'));
}

function welfareReportsIsExpiringSoon($date) {
    if (empty($date)) {
        return false;
    }

    $validTo = strtotime($date);
    return $validTo >= strtotime(date('Y-m-d')) && $validTo <= strtotime('+7 days');
}

function renderContent() {
    global $conn;

    $gpPassNumber = welfareReportsColumnSql($conn, 'gate_passes', 'gp', 'pass_number');
    $gpAccCard = welfareReportsColumnSql($conn, 'gate_passes', 'gp', 'acc_card_number');
    $gpApprovedDate = welfareReportsColumnSql($conn, 'gate_passes', 'gp', 'approved_date');
    $gprUpdatedAt = welfareReportsColumnSql($conn, 'gate_pass_requests', 'gpr', 'updated_at', 'gpr.created_at');
    $gprwGatepassNo = welfareReportsColumnSql($conn, 'gate_pass_request_workers', 'gprw', 'gatepass_no');

    $wTempPassNo = welfareReportsColumnSql($conn, 'workmen', 'w', 'temp_pass_no');
    $wAccNumber = welfareReportsColumnSql($conn, 'workmen', 'w', 'acc_number');
    $wAccCard = welfareReportsColumnSql($conn, 'workmen', 'w', 'acc_card_number');
    $wTempId = welfareReportsColumnSql($conn, 'workmen', 'w', 'temp_id');
    $wValidFrom = welfareReportsColumnSql($conn, 'workmen', 'w', 'valid_from');
    $wValidTo = welfareReportsColumnSql($conn, 'workmen', 'w', 'valid_to');
    $wTempValidFrom = welfareReportsColumnSql($conn, 'workmen', 'w', 'temp_valid_from');
    $wTempValidTo = welfareReportsColumnSql($conn, 'workmen', 'w', 'temp_valid_to');
    $wUpdatedAt = welfareReportsColumnSql($conn, 'workmen', 'w', 'updated_at', 'w.created_at');

    $workmenPassConditions = ["w.status IN ('temporary_issued', 'acc_generated', 'permanent_active')"];
    foreach (['temp_pass_no', 'acc_number', 'acc_card_number'] as $column) {
        if (welfareReportsColumnExists($conn, 'workmen', $column)) {
            $workmenPassConditions[] = "COALESCE(w.`$column`, '') <> ''";
        }
    }
    $workmenPassWhere = implode("\n           OR ", $workmenPassConditions);

    $rows = db_fetch_all($conn, "
        SELECT
            w.id AS worker_id,
            w.name,
            c.id AS contractor_id,
            c.contractor_name,
            COALESCE(pgp.pass_no, $wAccNumber, $wAccCard) AS pass_no,
            'Permanent' AS pass_type,
            pgp.valid_from,
            pgp.valid_till AS valid_to,
            COALESCE(pgp.status, 'active') AS pass_status,
            COALESCE(pgp.issued_at, $wUpdatedAt, w.created_at) AS issued_at,
            1 AS priority
        FROM permanent_gate_passes pgp
        JOIN workmen w ON pgp.worker_id = w.id
        JOIN contractors c ON COALESCE(pgp.contractor_id, w.contractor_id) = c.id
        WHERE COALESCE(pgp.status, 'active') = 'active'

        UNION ALL

        SELECT
            w.id AS worker_id,
            w.name,
            c.id AS contractor_id,
            c.contractor_name,
            COALESCE($gpPassNumber, $gpAccCard, $wAccNumber, $wTempPassNo, $wTempId) AS pass_no,
            CASE WHEN LOWER(COALESCE(gp.pass_type, 'temporary')) = 'permanent' THEN 'Permanent' ELSE 'Temporary' END AS pass_type,
            gp.valid_from,
            gp.valid_to,
            gp.status AS pass_status,
            COALESCE($gpApprovedDate, gp.created_at, $wUpdatedAt, w.created_at) AS issued_at,
            CASE WHEN LOWER(COALESCE(gp.pass_type, 'temporary')) = 'permanent' THEN 2 ELSE 3 END AS priority
        FROM gate_passes gp
        JOIN workmen w ON gp.workman_id = w.id
        JOIN contractors c ON w.contractor_id = c.id
        WHERE gp.status IN ('active', 'approved', 'issued')

        UNION ALL

        SELECT
            w.id AS worker_id,
            w.name,
            c.id AS contractor_id,
            c.contractor_name,
            COALESCE($gprwGatepassNo, gpr.request_no, $wTempPassNo, $wTempId) AS pass_no,
            CASE WHEN LOWER(COALESCE(gpr.pass_type, 'temporary')) = 'permanent' THEN 'Permanent' ELSE 'Temporary' END AS pass_type,
            gpr.from_date AS valid_from,
            gpr.to_date AS valid_to,
            gpr.status AS pass_status,
            COALESCE($gprUpdatedAt, gpr.created_at, $wUpdatedAt, w.created_at) AS issued_at,
            CASE WHEN LOWER(COALESCE(gpr.pass_type, 'temporary')) = 'permanent' THEN 4 ELSE 5 END AS priority
        FROM gate_pass_requests gpr
        JOIN gate_pass_request_workers gprw ON gprw.request_id = gpr.id
        JOIN workmen w ON gprw.workman_id = w.id
        JOIN contractors c ON COALESCE(gpr.contractor_id, w.contractor_id) = c.id
        WHERE gpr.status IN ('active', 'approved', 'issued')

        UNION ALL

        SELECT
            w.id AS worker_id,
            w.name,
            c.id AS contractor_id,
            c.contractor_name,
            COALESCE($wAccNumber, $wAccCard, $wTempPassNo, $wTempId) AS pass_no,
            CASE
                WHEN COALESCE($wAccNumber, $wAccCard, '') <> '' OR w.status IN ('acc_generated', 'permanent_active') THEN 'Permanent'
                ELSE 'Temporary'
            END AS pass_type,
            COALESCE($wValidFrom, $wTempValidFrom) AS valid_from,
            COALESCE($wValidTo, $wTempValidTo) AS valid_to,
            CASE
                WHEN w.status = 'permanent_active' THEN 'active'
                WHEN w.status = 'acc_generated' THEN 'approved'
                ELSE 'active'
            END AS pass_status,
            COALESCE($wUpdatedAt, w.created_at) AS issued_at,
            CASE
                WHEN COALESCE($wAccNumber, $wAccCard, '') <> '' OR w.status IN ('acc_generated', 'permanent_active') THEN 6
                ELSE 7
            END AS priority
        FROM workmen w
        JOIN contractors c ON w.contractor_id = c.id
        WHERE $workmenPassWhere

        ORDER BY issued_at DESC
    ");

    $passesByKey = [];
    foreach ($rows as $row) {
        $workerId = (int)($row['worker_id'] ?? 0);
        if (!$workerId) {
            continue;
        }

        $passNo = trim((string)($row['pass_no'] ?? ''));
        $passType = strtolower((string)($row['pass_type'] ?? 'gate_pass'));
        $key = $workerId . '|' . $passType . '|' . ($passNo !== '' ? $passNo : ('priority-' . (int)$row['priority']));

        if (!isset($passesByKey[$key]) || (int)$row['priority'] < (int)$passesByKey[$key]['priority']) {
            $passesByKey[$key] = $row;
        }
    }

    $passes = array_values($passesByKey);
    usort($passes, function($a, $b) {
        return strtotime($b['issued_at'] ?? '') <=> strtotime($a['issued_at'] ?? '');
    });

    $total_passes = 0;
    $perm_active = 0;
    $temp_active = 0;
    $expiring_7 = 0;
    $contractorStats = [];

    foreach ($passes as $pass) {
        $contractorId = (int)($pass['contractor_id'] ?? 0);
        $contractorName = $pass['contractor_name'] ?? 'N/A';
        $passType = $pass['pass_type'] ?? 'Temporary';
        $isExpired = welfareReportsIsExpired($pass['valid_to'] ?? null);
        $isExpiring = welfareReportsIsExpiringSoon($pass['valid_to'] ?? null);

        if (!isset($contractorStats[$contractorId])) {
            $contractorStats[$contractorId] = [
                'contractor_name' => $contractorName,
                'total' => 0,
                'permanent' => 0,
                'temporary' => 0,
                'expiring' => 0
            ];
        }

        if ($isExpired) {
            continue;
        }

        $total_passes++;
        $contractorStats[$contractorId]['total']++;

        if ($passType === 'Permanent') {
            $perm_active++;
            $contractorStats[$contractorId]['permanent']++;
        } else {
            $temp_active++;
            $contractorStats[$contractorId]['temporary']++;
        }

        if ($isExpiring) {
            $expiring_7++;
            $contractorStats[$contractorId]['expiring']++;
        }
    }

    usort($contractorStats, function($a, $b) {
        return (int)$b['total'] <=> (int)$a['total'];
    });
    ?>
    <div class="content-header">
      <h2 class="page-title">Pass Issuance Reports</h2>
    </div>

    <div class="grid grid-4 mb-4">
      <div class="card glass">
        <div class="card-body">
          <div style="font-size:11px; opacity:0.6; text-transform:uppercase;">Total Active Passes</div>
          <div style="font-size:28px; font-weight:700; color:var(--primary)"><?= $total_passes ?></div>
        </div>
      </div>
      <div class="card glass">
        <div class="card-body">
          <div style="font-size:11px; opacity:0.6; text-transform:uppercase;">Permanent (ACC)</div>
          <div style="font-size:28px; font-weight:700; color:var(--success)"><?= $perm_active ?></div>
        </div>
      </div>
      <div class="card glass">
        <div class="card-body">
          <div style="font-size:11px; opacity:0.6; text-transform:uppercase;">Temporary Passes</div>
          <div style="font-size:28px; font-weight:700; color:var(--info)"><?= $temp_active ?></div>
        </div>
      </div>
      <div class="card glass">
        <div class="card-body">
          <div style="font-size:11px; opacity:0.6; text-transform:uppercase;">Expiring in 7 Days</div>
          <div style="font-size:28px; font-weight:700; color:var(--warning)"><?= $expiring_7 ?></div>
        </div>
      </div>
    </div>

    <div class="card glass">
      <div class="card-header">
        <div class="card-title">Contractor-wise Pass Distribution</div>
      </div>
      <div class="card-body" style="padding:0">
        <table class="data-table">
          <thead>
            <tr>
              <th>Contractor Name</th>
              <th>Total Active</th>
              <th>Permanent (ACC)</th>
              <th>Temporary</th>
              <th>Expiring 7 Days</th>
              <th>Permanent %</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($contractorStats as $cs):
                if ((int)$cs['total'] === 0) {
                    continue;
                }
                $util = $cs['total'] > 0 ? round(($cs['permanent'] / $cs['total']) * 100, 1) : 0;
            ?>
            <tr>
              <td><strong><?= htmlspecialchars($cs['contractor_name']) ?></strong></td>
              <td><?= (int)$cs['total'] ?></td>
              <td><span class="text-success"><?= (int)$cs['permanent'] ?></span></td>
              <td><span class="text-info"><?= (int)$cs['temporary'] ?></span></td>
              <td><span class="text-warning"><?= (int)$cs['expiring'] ?></span></td>
              <td>
                <div style="display:flex; align-items:center; gap:8px;">
                  <div style="flex:1; height:8px; background:rgba(255,255,255,0.05); border-radius:4px; overflow:hidden;">
                    <div style="width:<?= $util ?>%; height:100%; background:var(--success);"></div>
                  </div>
                  <span style="font-size:12px;"><?= $util ?>%</span>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($contractorStats) || $total_passes === 0): ?>
            <tr><td colspan="6" class="text-center" style="padding:40px;">No active passes found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card glass" style="margin-top:24px;">
      <div class="card-header">
        <div class="card-title">Latest Issued Passes</div>
      </div>
      <div class="card-body" style="padding:0">
        <table class="data-table">
          <thead>
            <tr>
              <th>Workman</th>
              <th>Contractor</th>
              <th>Pass No</th>
              <th>Type</th>
              <th>Valid To</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach(array_slice($passes, 0, 25) as $pass):
              $isExpired = welfareReportsIsExpired($pass['valid_to'] ?? null);
              $isExpiring = welfareReportsIsExpiringSoon($pass['valid_to'] ?? null);
              $statusClass = $isExpired ? 'danger' : ($isExpiring ? 'warning' : 'success');
              $statusText = $isExpired ? 'Expired' : ($isExpiring ? 'Expiring Soon' : 'Active');
            ?>
            <tr>
              <td><strong><?= htmlspecialchars($pass['name'] ?? 'Unknown') ?></strong></td>
              <td><?= htmlspecialchars($pass['contractor_name'] ?? 'N/A') ?></td>
              <td><code><?= htmlspecialchars($pass['pass_no'] ?: 'N/A') ?></code></td>
              <td><span class="badge badge-outline"><?= htmlspecialchars(strtoupper($pass['pass_type'] ?? 'Gate Pass')) ?></span></td>
              <td><?= !empty($pass['valid_to']) ? date('d M Y', strtotime($pass['valid_to'])) : '-' ?></td>
              <td><span class="badge badge-<?= $statusClass ?>"><?= $statusText ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($passes)): ?>
            <tr><td colspan="6" class="text-center" style="padding:40px;">No issued passes found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
}

renderLayout("Reports", 'renderContent', $role, $name);
