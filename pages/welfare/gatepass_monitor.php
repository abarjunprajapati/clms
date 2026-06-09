<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin', 'pass_user', 'welfare_user']);

include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = get_normalized_role();
$name = $_SESSION['name'] ?? 'Officer';

function gatepassMonitorColumnExists($conn, $table, $column) {
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

function gatepassMonitorColumnSql($conn, $table, $alias, $column, $fallback = 'NULL') {
    return gatepassMonitorColumnExists($conn, $table, $column) ? "$alias.`$column`" : $fallback;
}

function renderContent() {
    global $conn;

    $gpPassNumber = gatepassMonitorColumnSql($conn, 'gate_passes', 'gp', 'pass_number');
    $gpAccCard = gatepassMonitorColumnSql($conn, 'gate_passes', 'gp', 'acc_card_number');
    $gpApprovedDate = gatepassMonitorColumnSql($conn, 'gate_passes', 'gp', 'approved_date');
    $gprUpdatedAt = gatepassMonitorColumnSql($conn, 'gate_pass_requests', 'gpr', 'updated_at', 'gpr.created_at');
    $gprwGatepassNo = gatepassMonitorColumnSql($conn, 'gate_pass_request_workers', 'gprw', 'gatepass_no');

    $wTempPassNo = gatepassMonitorColumnSql($conn, 'workmen', 'w', 'temp_pass_no');
    $wAccNumber = gatepassMonitorColumnSql($conn, 'workmen', 'w', 'acc_number');
    $wAccCard = gatepassMonitorColumnSql($conn, 'workmen', 'w', 'acc_card_number');
    $wTempId = gatepassMonitorColumnSql($conn, 'workmen', 'w', 'temp_id');
    $wValidFrom = gatepassMonitorColumnSql($conn, 'workmen', 'w', 'valid_from');
    $wValidTo = gatepassMonitorColumnSql($conn, 'workmen', 'w', 'valid_to');
    $wTempValidFrom = gatepassMonitorColumnSql($conn, 'workmen', 'w', 'temp_valid_from');
    $wTempValidTo = gatepassMonitorColumnSql($conn, 'workmen', 'w', 'temp_valid_to');
    $wUpdatedAt = gatepassMonitorColumnSql($conn, 'workmen', 'w', 'updated_at', 'w.created_at');

    $workmenPassConditions = ["w.status IN ('temporary_issued', 'acc_generated', 'permanent_active')"];
    foreach (['temp_pass_no', 'acc_number', 'acc_card_number'] as $column) {
        if (gatepassMonitorColumnExists($conn, 'workmen', $column)) {
            $workmenPassConditions[] = "COALESCE(w.`$column`, '') <> ''";
        }
    }
    $workmenPassWhere = implode("\n                  OR ", $workmenPassConditions);

    $contractors = db_fetch_all($conn, "
        SELECT DISTINCT c.id, c.contractor_name
        FROM contractors c
        WHERE EXISTS (
            SELECT 1
            FROM permanent_gate_passes pgp
            WHERE pgp.contractor_id = c.id
              AND COALESCE(pgp.status, 'active') = 'active'
        )
        OR EXISTS (
            SELECT 1
            FROM gate_passes gp
            JOIN workmen w ON gp.workman_id = w.id
            WHERE w.contractor_id = c.id
              AND gp.status IN ('active', 'approved', 'issued')
        )
        OR EXISTS (
            SELECT 1
            FROM gate_pass_requests gpr
            JOIN gate_pass_request_workers gprw ON gprw.request_id = gpr.id
            WHERE gpr.contractor_id = c.id
              AND gpr.status IN ('active', 'approved', 'issued')
        )
        OR EXISTS (
            SELECT 1
            FROM workmen w
            WHERE w.contractor_id = c.id
              AND ($workmenPassWhere)
        )
        ORDER BY c.contractor_name
    ");

    $rows = db_fetch_all($conn, "
        SELECT
            w.id AS worker_id,
            w.name,
            w.trade,
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
            w.trade,
            c.id AS contractor_id,
            c.contractor_name,
            COALESCE($gpPassNumber, $gpAccCard, $wAccNumber, $wTempPassNo, $wTempId) AS pass_no,
            CASE WHEN gp.pass_type = 'permanent' THEN 'Permanent' ELSE 'Temporary' END AS pass_type,
            gp.valid_from,
            gp.valid_to,
            gp.status AS pass_status,
            COALESCE($gpApprovedDate, gp.created_at, $wUpdatedAt, w.created_at) AS issued_at,
            CASE WHEN gp.pass_type = 'permanent' THEN 2 ELSE 3 END AS priority
        FROM gate_passes gp
        JOIN workmen w ON gp.workman_id = w.id
        JOIN contractors c ON w.contractor_id = c.id
        WHERE gp.status IN ('active', 'approved', 'issued')

        UNION ALL

        SELECT
            w.id AS worker_id,
            w.name,
            w.trade,
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
            w.trade,
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

        ORDER BY contractor_name, issued_at DESC
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
        $contractorCompare = strcasecmp($a['contractor_name'] ?? '', $b['contractor_name'] ?? '');
        if ($contractorCompare !== 0) {
            return $contractorCompare;
        }

        return strtotime($b['issued_at'] ?? '') <=> strtotime($a['issued_at'] ?? '');
    });
    ?>
    <div class="content-header">
      <h2 class="page-title">Gate Pass Monitoring</h2>
      <!-- <p class="page-subtitle">Overview of all active temporary and permanent gate passes.</p> -->
    </div>

    <div class="card glass">
      <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;">
        <div class="card-title">Active Gate Passes</div>
        <select class="form-control" id="contractorFilter" style="width:280px;max-width:100%;">
          <option value="">All Contractors</option>
          <?php foreach ($contractors as $contractor): ?>
          <option value="<?= (int)$contractor['id'] ?>"><?= htmlspecialchars($contractor['contractor_name'] ?? 'N/A') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="card-body" style="padding:0">
        <table class="data-table">
          <thead>
            <tr>
              <th>Workman</th>
              <th>Contractor</th>
              <th>Gate Pass No</th>
              <th>Type</th>
              <th>Validity</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($passes as $p):
              $validTo = $p['valid_to'] ?? null;
              $isExpired = $validTo && strtotime($validTo) < strtotime(date('Y-m-d'));
              $statusText = $isExpired ? 'EXPIRED' : strtoupper(str_replace('_', ' ', $p['pass_status'] ?? 'active'));
              $statusClass = $isExpired ? 'danger' : (($p['pass_status'] ?? '') === 'approved' ? 'info' : 'success');
            ?>
            <tr data-contractor-id="<?= (int)$p['contractor_id'] ?>">
              <td>
                <strong><?= htmlspecialchars($p['name']) ?></strong>
                <?php if (!empty($p['trade'])): ?>
                  <div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($p['trade']) ?></div>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($p['contractor_name']) ?></td>
              <td><code><?= htmlspecialchars($p['pass_no'] ?: 'N/A') ?></code></td>
              <td><span class="badge badge-outline"><?= htmlspecialchars(strtoupper($p['pass_type'] ?? 'Gate Pass')) ?></span></td>
              <td>
                <div style="font-size:11px;">
                  From: <?= !empty($p['valid_from']) ? date('d M Y', strtotime($p['valid_from'])) : '-' ?><br>
                  To: <span style="<?= $isExpired ? 'color:#ef4444;font-weight:700;' : '' ?>"><?= !empty($validTo) ? date('d M Y', strtotime($validTo)) : '-' ?></span>
                </div>
              </td>
              <td>
                <span class="badge badge-<?= $statusClass ?>">
                  <?= $statusText ?>
                </span>
              </td>
              <td>
                <a href="pass_status.php?search=<?= urlencode($p['name']) ?>" class="btn btn-sm btn-outline-primary">Track Lifecycle</a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($passes)): ?>
            <tr><td colspan="7" class="text-center" style="padding:40px;">No active gate passes found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
      var filter = document.getElementById('contractorFilter');
      if (!filter) return;

      filter.addEventListener('change', function() {
        var selected = this.value;
        document.querySelectorAll('.data-table tbody tr[data-contractor-id]').forEach(function(row) {
          row.style.display = (!selected || row.dataset.contractorId === selected) ? '' : 'none';
        });
      });
    });
    </script>
    <?php
}

renderLayout("Gate Pass Monitoring", 'renderContent', $role, $name);
