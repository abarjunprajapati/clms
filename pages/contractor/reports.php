<?php
require_once '../../include/auth.php';
checkAuth(['contractor', 'customer']);
include '../../include/config.php';
include '../../include/customer_portal_context.php';
include '../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';
$user_id = $_SESSION['user_id'];
$portalContractor = clms_get_portal_contractor($conn);

function contractorReportTableExists($conn, $table) {
    static $cache = [];
    if (isset($cache[$table])) {
        return $cache[$table];
    }

    $safeTable = clms_db_real_escape_string($conn, $table);
    $result = clms_db_query($conn, "SHOW TABLES LIKE '$safeTable'");
    $cache[$table] = $result && clms_db_num_rows($result) > 0;
    return $cache[$table];
}

function contractorReportColumnExists($conn, $table, $column) {
    static $cache = [];
    $key = $table . '.' . $column;
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    if (!contractorReportTableExists($conn, $table)) {
        $cache[$key] = false;
        return false;
    }

    $safeColumn = clms_db_real_escape_string($conn, $column);
    $result = clms_db_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$safeColumn'");
    $cache[$key] = $result && clms_db_num_rows($result) > 0;
    return $cache[$key];
}

function contractorReportCount($conn, $sql, $types = '', $params = []) {
    return db_count($conn, $sql, $types, $params);
}

function contractorReportWorkOrders($conn, $contractorId, $vendorCode, $fallbackWorkOrder) {
    $orders = [];
    $seen = [];

    if ($vendorCode !== '' && contractorReportTableExists($conn, 'work_orders') && contractorReportColumnExists($conn, 'work_orders', 'work_order_no')) {
        $vendorColumns = [];
        foreach (['vendor_code', 'contractor_vendor_code'] as $column) {
            if (contractorReportColumnExists($conn, 'work_orders', $column)) {
                $vendorColumns[] = "`$column` = ?";
            }
        }

        if ($vendorColumns) {
            $projectExpr = contractorReportColumnExists($conn, 'work_orders', 'project_name') ? 'project_name' : "'' AS project_name";
            $deptExpr = contractorReportColumnExists($conn, 'work_orders', 'department') ? 'department' : "'' AS department";
            $statusExpr = contractorReportColumnExists($conn, 'work_orders', 'wo_status') ? 'wo_status' : "'ACTIVE' AS wo_status";
            $where = implode(' OR ', $vendorColumns);
            $types = str_repeat('s', count($vendorColumns));
            $params = array_fill(0, count($vendorColumns), $vendorCode);
            $rows = db_fetch_all($conn, "
                SELECT work_order_no, $projectExpr, $deptExpr, $statusExpr
                FROM work_orders
                WHERE $where
                ORDER BY work_order_no DESC
                LIMIT 100
            ", $types, $params);

            foreach ($rows as $row) {
                $no = trim((string)($row['work_order_no'] ?? ''));
                if ($no === '' || isset($seen[$no])) {
                    continue;
                }
                $seen[$no] = true;
                $orders[] = $row;
            }
        }
    }

    if ($vendorCode !== '' && contractorReportTableExists($conn, 'sap_pwo_master')) {
        $rows = [];
        $pwoProjectExpr = contractorReportColumnExists($conn, 'sap_pwo_master', 'project') ? 'p.project' : 'NULL';
        $pwoVesselExpr = contractorReportColumnExists($conn, 'sap_pwo_master', 'vessel') ? 'p.vessel' : 'NULL';
        $pwoDescExpr = contractorReportColumnExists($conn, 'sap_pwo_master', 'pwo_description') ? 'p.pwo_description' : 'NULL';
        $pwoStatusExpr = contractorReportColumnExists($conn, 'sap_pwo_master', 'status') ? 'p.status' : "'active'";
        $pwoOrderExpr = contractorReportColumnExists($conn, 'sap_pwo_master', 'created_at') ? 'p.created_at DESC' : 'p.pwo_number DESC';

        if (
            contractorReportTableExists($conn, 'sap_po_master') &&
            contractorReportColumnExists($conn, 'sap_pwo_master', 'po_number') &&
            contractorReportColumnExists($conn, 'sap_po_master', 'po_number') &&
            contractorReportColumnExists($conn, 'sap_po_master', 'vendor_code')
        ) {
            $poDeptExpr = contractorReportColumnExists($conn, 'sap_po_master', 'purchasing_group') ? 'po.purchasing_group' : "''";
            $rows = db_fetch_all($conn, "
                SELECT p.pwo_number AS work_order_no,
                       COALESCE($pwoProjectExpr, $pwoVesselExpr, $pwoDescExpr, '') AS project_name,
                       COALESCE($poDeptExpr, '') AS department,
                       COALESCE($pwoStatusExpr, 'active') AS wo_status
                FROM sap_pwo_master p
                JOIN sap_po_master po ON p.po_number = po.po_number
                WHERE po.vendor_code = ?
                ORDER BY $pwoOrderExpr
                LIMIT 100
            ", 's', [$vendorCode]);
        } else {
            $vendorFilters = [];
            foreach (['vendor_code', 'customer_code'] as $column) {
                if (contractorReportColumnExists($conn, 'sap_pwo_master', $column)) {
                    $vendorFilters[] = "p.`$column` = ?";
                }
            }

            if ($vendorFilters) {
                $where = implode(' OR ', $vendorFilters);
                $types = str_repeat('s', count($vendorFilters));
                $params = array_fill(0, count($vendorFilters), $vendorCode);
                $rows = db_fetch_all($conn, "
                    SELECT p.pwo_number AS work_order_no,
                           COALESCE($pwoProjectExpr, $pwoVesselExpr, $pwoDescExpr, '') AS project_name,
                           '' AS department,
                           COALESCE($pwoStatusExpr, 'active') AS wo_status
                    FROM sap_pwo_master p
                    WHERE $where
                    ORDER BY $pwoOrderExpr
                    LIMIT 100
                ", $types, $params);
            }
        }

        foreach ($rows as $row) {
            $no = trim((string)($row['work_order_no'] ?? ''));
            if ($no === '' || isset($seen[$no])) {
                continue;
            }
            $seen[$no] = true;
            $orders[] = $row;
        }
    }

    if (contractorReportTableExists($conn, 'contractor_pwo_selection')) {
        $rows = db_fetch_all($conn, "
            SELECT pwo_number AS work_order_no, '' AS project_name, '' AS department, 'selected' AS wo_status
            FROM contractor_pwo_selection
            WHERE contractor_id = ?
            ORDER BY pwo_number DESC
        ", 'i', [$contractorId]);

        foreach ($rows as $row) {
            $no = trim((string)($row['work_order_no'] ?? ''));
            if ($no === '' || isset($seen[$no])) {
                continue;
            }
            $seen[$no] = true;
            $orders[] = $row;
        }
    }

    if ($fallbackWorkOrder && !isset($seen[$fallbackWorkOrder])) {
        $orders[] = [
            'work_order_no' => $fallbackWorkOrder,
            'project_name' => '',
            'department' => '',
            'wo_status' => 'active'
        ];
    }

    return $orders;
}

function renderContent() {
    global $conn, $user_id, $portalContractor;

    $contractor = $portalContractor ?: db_single($conn, "SELECT * FROM contractors WHERE user_id = ? ORDER BY id DESC LIMIT 1", 'i', [$user_id]);
    $c_id = (int)($contractor['id'] ?? 0);
    $v_code = trim((string)($contractor['vendor_code'] ?? ''));
    $fallbackWorkOrder = trim((string)($contractor['work_order_no'] ?? ''));

    if (!$c_id) {
        echo '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Complete your registration first to access reports.</div>';
        return;
    }

    $totalWorkers = contractorReportCount($conn, "SELECT COUNT(*) FROM workmen WHERE contractor_id = ?", 'i', [$c_id]);
    $activeWorkers = contractorReportCount($conn, "SELECT COUNT(*) FROM workmen WHERE contractor_id = ? AND status IN ('active','approved','temporary_issued','acc_generated','permanent_active')", 'i', [$c_id]);
    $gatePasses = contractorReportCount($conn, "
        SELECT COUNT(DISTINCT gp.id)
        FROM gate_passes gp
        JOIN workmen w ON gp.workman_id = w.id
        WHERE w.contractor_id = ?
          AND gp.status IN ('active','approved','issued')
    ", 'i', [$c_id]);

    $trainingPending = 0;
    $trainingConditions = [];
    foreach (['training_status', 'safety_status'] as $column) {
        if (contractorReportColumnExists($conn, 'workmen', $column)) {
            $trainingConditions[] = "COALESCE(`$column`, 'pending') IN ('pending','not_started','failed')";
        }
    }
    if ($trainingConditions) {
        $trainingPending = contractorReportCount($conn, "SELECT COUNT(*) FROM workmen WHERE contractor_id = ? AND (" . implode(' OR ', $trainingConditions) . ")", 'i', [$c_id]);
    }

    $documentsPending = contractorReportTableExists($conn, 'contractor_documents')
        ? contractorReportCount($conn, "SELECT COUNT(*) FROM contractor_documents WHERE contractor_id = ? AND COALESCE(status,'pending') IN ('pending','reupload_required','rejected')", 'i', [$c_id])
        : 0;

    $workOrders = contractorReportWorkOrders($conn, $c_id, $v_code, $fallbackWorkOrder);

    $reports = [
        'Compliance' => [
            ['Muster Roll', 'Form XVI attendance register', 'XVI', 'fa-clipboard-list', ['pdf', 'excel']],
            ['Wage Register', 'Form XVII wage and deduction summary', 'XVII', 'fa-indian-rupee-sign', ['pdf', 'excel']],
            ['PF and ESI Compliance', 'Monthly statutory contribution statement', 'COMP', 'fa-file-shield', ['pdf', 'excel']],
            ['Bonus Register', 'Annual bonus payment records', 'BONUS', 'fa-gift', ['pdf']]
        ],
        'Operations' => [
            ['Active Workforce Roster', 'Current enrolled worker snapshot', 'WORKERS', 'fa-users', ['excel']],
            ['Daily In/Out Punches', 'Attendance log for selected period', 'ATT', 'fa-clock', ['excel']],
            ['Safety Training Status', 'Training pending, pass and fail list', 'SAFE', 'fa-hard-hat', ['pdf', 'excel']],
            ['Gate Pass Expiry Forecast', 'Passes expiring soon by worker', 'EXP', 'fa-id-card', ['pdf', 'excel']]
        ]
    ];
    ?>
    <div class="content-header contractor-report-header">
      <div>
        <div class="cr-breadcrumb"><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a><span>/</span><strong>Reports</strong></div>
        <h2 class="page-title"><i class="fas fa-chart-bar"></i> Reports & Exports</h2>
      </div>
      <div class="cr-header-actions">
        <a href="attendance.php" class="btn btn-outline"><i class="fas fa-calendar-check"></i> Attendance</a>
        <button class="btn btn-primary" type="button" onclick="location.reload()"><i class="fas fa-sync-alt"></i> Refresh</button>
      </div>
    </div>

    <div class="cr-summary-grid">
      <div class="cr-stat"><i class="fas fa-users" style="color:#2563eb"></i><div><strong><?= $totalWorkers ?></strong><span>Total Workers</span></div></div>
      <div class="cr-stat"><i class="fas fa-user-check" style="color:#059669"></i><div><strong><?= $activeWorkers ?></strong><span>Active Workers</span></div></div>
      <div class="cr-stat"><i class="fas fa-id-card" style="color:#7c3aed"></i><div><strong><?= $gatePasses ?></strong><span>Active Passes</span></div></div>
      <div class="cr-stat"><i class="fas fa-hard-hat" style="color:#d97706"></i><div><strong><?= $trainingPending ?></strong><span>Training Pending</span></div></div>
      <div class="cr-stat"><i class="fas fa-file-circle-exclamation" style="color:#dc2626"></i><div><strong><?= $documentsPending ?></strong><span>Docs Pending</span></div></div>
    </div>

    <div class="card glass cr-filter-card">
      <div class="card-body">
        <div class="cr-filter-grid">
          <div class="form-field">
            <label>Work Order / PWO</label>
            <select class="form-control" id="report_wo">
              <option value="">All Work Orders</option>
              <?php foreach ($workOrders as $wo):
                $woNo = $wo['work_order_no'] ?? '';
                $meta = trim(($wo['project_name'] ?? '') . (!empty($wo['department']) ? ' - ' . $wo['department'] : ''));
              ?>
              <option value="<?= htmlspecialchars($woNo) ?>"><?= htmlspecialchars($woNo . ($meta ? ' - ' . $meta : '')) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-field">
            <label>From Date</label>
            <input type="date" class="form-control" id="date_from" value="<?= date('Y-m-01') ?>">
          </div>
          <div class="form-field">
            <label>To Date</label>
            <input type="date" class="form-control" id="date_to" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="cr-filter-actions">
            <button class="btn btn-primary" type="button" onclick="applyFilters(this)"><i class="fas fa-filter"></i> Apply</button>
            <button class="btn btn-outline" type="button" onclick="resetFilters()"><i class="fas fa-rotate-left"></i> Reset</button>
          </div>
        </div>
      </div>
    </div>

    <div class="cr-report-layout">
      <?php foreach ($reports as $section => $items): ?>
      <section class="cr-panel">
        <div class="cr-panel-head">
          <div>
            <h3><?= htmlspecialchars($section) ?></h3>
            <span><?= count($items) ?> reports available</span>
          </div>
          <i class="fas <?= $section === 'Compliance' ? 'fa-scale-balanced' : 'fa-chart-line' ?>"></i>
        </div>
        <div class="cr-report-list">
          <?php foreach ($items as $report):
            list($title, $desc, $code, $icon, $formats) = $report;
          ?>
          <div class="cr-report-item" data-report="<?= htmlspecialchars(strtolower($title . ' ' . $desc)) ?>">
            <button class="cr-report-main" type="button" onclick="generateReport('<?= htmlspecialchars($title, ENT_QUOTES) ?>','<?= htmlspecialchars($code, ENT_QUOTES) ?>','preview')">
              <span class="cr-report-icon"><i class="fas <?= htmlspecialchars($icon) ?>"></i></span>
              <span class="cr-report-copy">
                <strong><?= htmlspecialchars($title) ?></strong>
                <small><?= htmlspecialchars($desc) ?></small>
              </span>
            </button>
            <div class="cr-report-actions">
              <?php foreach ($formats as $format): ?>
              <button type="button" class="cr-icon-btn <?= $format ?>" title="<?= strtoupper($format) ?>" onclick="generateReport('<?= htmlspecialchars($title, ENT_QUOTES) ?>','<?= htmlspecialchars($code, ENT_QUOTES) ?>','<?= $format ?>')">
                <i class="fas <?= $format === 'pdf' ? 'fa-file-pdf' : 'fa-file-excel' ?>"></i>
              </button>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endforeach; ?>
    </div>

    <div id="export-overlay" class="cr-export-overlay" style="display:none;">
      <div class="cr-export-modal">
        <div class="cr-spinner"></div>
        <h3>Generating Report</h3>
        <p id="export-status">Preparing file</p>
      </div>
    </div>

    <style>
      .contractor-report-header{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin-bottom:18px}
      .contractor-report-header .page-title{display:flex;align-items:center;gap:10px;margin:4px 0 0}
      .cr-breadcrumb{display:flex;align-items:center;gap:8px;font-size:12px;color:var(--text-muted)}
      .cr-breadcrumb a{color:var(--primary);text-decoration:none;font-weight:700}
      .cr-header-actions{display:flex;gap:8px;flex-wrap:wrap}
      .cr-summary-grid{display:grid;grid-template-columns:repeat(5,minmax(140px,1fr));gap:12px;margin-bottom:16px}
      .cr-stat{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:14px;display:flex;align-items:center;gap:12px;box-shadow:0 1px 4px rgba(15,23,42,.05)}
      .cr-stat i{width:34px;height:34px;border-radius:8px;background:#f8fafc;display:flex;align-items:center;justify-content:center}
      .cr-stat strong{display:block;font-size:24px;line-height:1;color:#111827}
      .cr-stat span{display:block;font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase;margin-top:3px}
      .cr-filter-card{margin-bottom:18px}
      .cr-filter-card .card-body{padding:16px}
      .cr-filter-grid{display:grid;grid-template-columns:minmax(260px,1.4fr) minmax(150px,.65fr) minmax(150px,.65fr) auto;gap:12px;align-items:end}
      .form-field label{display:block;font-size:11px;color:#64748b;font-weight:800;text-transform:uppercase;margin-bottom:6px}
      .cr-filter-actions{display:flex;gap:8px}
      .cr-report-layout{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
      .cr-panel{background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;box-shadow:0 2px 10px rgba(15,23,42,.06)}
      .cr-panel-head{padding:16px 18px;border-bottom:1px solid #edf2f7;display:flex;justify-content:space-between;align-items:center;background:#f8fafc}
      .cr-panel-head h3{margin:0;font-size:16px;color:#111827}
      .cr-panel-head span{display:block;font-size:12px;color:#64748b;margin-top:3px}
      .cr-panel-head>i{font-size:20px;color:#475569}
      .cr-report-list{padding:8px}
      .cr-report-item{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:center;padding:10px;border-radius:8px;border:1px solid transparent}
      .cr-report-item:hover{background:#f8fafc;border-color:#e5e7eb}
      .cr-report-main{display:flex;align-items:center;gap:12px;background:transparent;border:0;text-align:left;padding:0;min-width:0;cursor:pointer}
      .cr-report-icon{width:38px;height:38px;border-radius:8px;background:#eef2ff;color:#4f46e5;display:flex;align-items:center;justify-content:center;flex:0 0 auto}
      .cr-report-copy{min-width:0}
      .cr-report-copy strong{display:block;font-size:13px;color:#111827}
      .cr-report-copy small{display:block;font-size:12px;color:#64748b;margin-top:2px;line-height:1.35}
      .cr-report-actions{display:flex;gap:6px}
      .cr-icon-btn{width:34px;height:34px;border-radius:8px;border:0;display:flex;align-items:center;justify-content:center;cursor:pointer}
      .cr-icon-btn.pdf{background:#fee2e2;color:#b91c1c}
      .cr-icon-btn.excel{background:#dcfce7;color:#15803d}
      .cr-icon-btn:hover{filter:brightness(.97)}
      .cr-export-overlay{position:fixed;inset:0;background:rgba(15,23,42,.35);z-index:9999;align-items:center;justify-content:center}
      .cr-export-modal{width:300px;background:#fff;border-radius:8px;padding:28px;text-align:center;box-shadow:0 24px 60px rgba(15,23,42,.25)}
      .cr-export-modal h3{margin:12px 0 4px;font-size:18px}
      .cr-export-modal p{margin:0;color:#64748b;font-size:13px}
      .cr-spinner{width:38px;height:38px;border-radius:50%;border:4px solid #e0e7ff;border-top-color:#4f46e5;margin:0 auto;animation:cr-spin .8s linear infinite}
      @keyframes cr-spin{to{transform:rotate(360deg)}}
      @media(max-width:1100px){.cr-summary-grid{grid-template-columns:repeat(auto-fit,minmax(160px,1fr))}.cr-filter-grid{grid-template-columns:1fr 1fr}.cr-report-layout{grid-template-columns:1fr}}
      @media(max-width:640px){.contractor-report-header{align-items:stretch;flex-direction:column}.cr-header-actions,.cr-filter-actions{width:100%}.cr-header-actions .btn,.cr-filter-actions .btn{flex:1}.cr-filter-grid{grid-template-columns:1fr}.cr-report-item{grid-template-columns:1fr}.cr-report-actions{padding-left:50px}}
    </style>

    <script>
      function currentReportContext() {
        return {
          workOrder: document.getElementById('report_wo').value || 'All',
          from: document.getElementById('date_from').value,
          to: document.getElementById('date_to').value
        };
      }

      function generateReport(reportName, code, format) {
        const ctx = currentReportContext();
        const overlay = document.getElementById('export-overlay');
        const status = document.getElementById('export-status');

        overlay.style.display = 'flex';
        status.textContent = `${reportName} - ${format.toUpperCase()} | ${ctx.from} to ${ctx.to}`;

        setTimeout(() => {
          overlay.style.display = 'none';
          alert(`${reportName}\nFormat: ${format.toUpperCase()}\nPeriod: ${ctx.from} to ${ctx.to}\nWork Order: ${ctx.workOrder}\n\nReport request prepared.`);
        }, 800);
      }

      function applyFilters(btn) {
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Applying';
        btn.disabled = true;
        setTimeout(() => {
          btn.innerHTML = original;
          btn.disabled = false;
        }, 450);
      }

      function resetFilters() {
        document.getElementById('report_wo').value = '';
        document.getElementById('date_from').value = '<?= date('Y-m-01') ?>';
        document.getElementById('date_to').value = '<?= date('Y-m-d') ?>';
      }
    </script>
    <?php
}

renderLayout('Reports & Analytics', 'renderContent', $role, $name);
?>
