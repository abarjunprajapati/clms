<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin', 'admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Admin';

$exportDatasets = [
    'workmen' => [
        'label' => 'Workmen Master',
        'table' => 'workmen',
        'icon' => 'fa-users',
        'color' => '#6366f1',
        'description' => 'Worker identity, trade, contact, status, contractor mapping'
    ],
    'contractors' => [
        'label' => 'Contractor Master',
        'table' => 'contractors',
        'icon' => 'fa-building',
        'color' => '#0ea5e9',
        'description' => 'Contractor profile, vendor code, statutory fields, status'
    ],
    'gate_passes' => [
        'label' => 'Gate Pass History',
        'table' => 'gate_passes',
        'icon' => 'fa-id-card',
        'color' => '#10b981',
        'description' => 'Temporary and permanent pass issue history'
    ],
    'audit_logs' => [
        'label' => 'Audit Logs',
        'table' => 'audit_logs',
        'icon' => 'fa-clipboard-list',
        'color' => '#ef4444',
        'description' => 'System actions, users, modules, timestamps and IP data'
    ],
    'training_results' => [
        'label' => 'Training Results',
        'table' => 'training_results',
        'icon' => 'fa-graduation-cap',
        'color' => '#f59e0b',
        'description' => 'Safety training results and scores'
    ],
    'compliance' => [
        'label' => 'Compliance Records',
        'table' => 'compliance',
        'icon' => 'fa-shield-check',
        'color' => '#8b5cf6',
        'description' => 'ESI, PF, KLWF and compliance verification records'
    ],
    'blocked_workers' => [
        'label' => 'Blocked Workers',
        'table' => 'workmen',
        'icon' => 'fa-user-slash',
        'color' => '#dc2626',
        'description' => 'Workers currently marked as blocked'
    ],
];

if (isset($_GET['dataset']) && $_GET['dataset'] !== '') {
    $dataset = preg_replace('/[^a-z_]/', '', (string)$_GET['dataset']);
    if (isset($exportDatasets[$dataset])) {
        header('Location: ../../api/admin/export_data.php?dataset=' . urlencode($dataset));
        exit;
    }
}

function dataExportTableExists($conn, $table) {
    $table = mysqli_real_escape_string($conn, $table);
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return $result && mysqli_num_rows($result) > 0;
}

function dataExportCount($conn, $dataset, $table) {
    if (!dataExportTableExists($conn, $table)) {
        return 0;
    }

    $sql = "SELECT COUNT(*) AS c FROM `$table`";
    if ($dataset === 'blocked_workers') {
        $sql = "SELECT COUNT(*) AS c FROM workmen WHERE status='blocked'";
    }

    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);
    return (int)($row['c'] ?? 0);
}

function dataExportLastUpdated($conn, $table) {
    if (!dataExportTableExists($conn, $table)) {
        return 'Table missing';
    }

    $columns = [];
    $colResult = mysqli_query($conn, "SHOW COLUMNS FROM `$table`");
    while ($colResult && ($col = mysqli_fetch_assoc($colResult))) {
        $columns[] = $col['Field'];
    }

    $dateCol = in_array('updated_at', $columns, true) ? 'updated_at' : (in_array('created_at', $columns, true) ? 'created_at' : null);
    if (!$dateCol) {
        return 'Available';
    }

    $result = mysqli_query($conn, "SELECT MAX(`$dateCol`) AS last_at FROM `$table`");
    $row = $result ? mysqli_fetch_assoc($result) : null;
    if (empty($row['last_at'])) {
        return 'No records';
    }

    return date('d M Y, H:i', strtotime($row['last_at']));
}

function renderContent() {
    global $conn, $exportDatasets;

    $totalRows = 0;
    foreach ($exportDatasets as $key => &$dataset) {
        $dataset['rows'] = dataExportCount($conn, $key, $dataset['table']);
        $dataset['last_updated'] = dataExportLastUpdated($conn, $dataset['table']);
        $dataset['available'] = dataExportTableExists($conn, $dataset['table']);
        $totalRows += $dataset['rows'];
    }
    unset($dataset);
    ?>

    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-file-export" style="color:#6366f1;margin-right:10px;"></i> Data Export</h2>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="reports.php" class="btn btn-outline"><i class="fas fa-chart-bar"></i> Reports</a>
        <a href="audit_logs.php" class="btn btn-outline"><i class="fas fa-history"></i> Audit Logs</a>
      </div>
    </div>

    <div class="export-summary">
      <div class="summary-tile">
        <div class="summary-value"><?= count($exportDatasets) ?></div>
        <div class="summary-label">Datasets</div>
      </div>
      <div class="summary-tile">
        <div class="summary-value"><?= number_format($totalRows) ?></div>
        <div class="summary-label">Exportable Rows</div>
      </div>
      <div class="summary-tile">
        <div class="summary-value"><?= date('d M') ?></div>
        <div class="summary-label">Today</div>
      </div>
    </div>

    <div class="export-grid">
      <?php foreach ($exportDatasets as $key => $dataset): ?>
      <div class="export-card" style="border-left-color:<?= htmlspecialchars($dataset['color']) ?>;">
        <div class="export-card-head">
          <div class="export-icon" style="background:<?= htmlspecialchars($dataset['color']) ?>18;color:<?= htmlspecialchars($dataset['color']) ?>;">
            <i class="fas <?= htmlspecialchars($dataset['icon']) ?>"></i>
          </div>
          <div>
            <div class="export-title"><?= htmlspecialchars($dataset['label']) ?></div>
            <div class="export-subtitle"><?= htmlspecialchars($dataset['description']) ?></div>
          </div>
        </div>
        <div class="export-meta">
          <div><span>Rows</span><strong><?= number_format($dataset['rows']) ?></strong></div>
          <div><span>Status</span><strong class="<?= $dataset['available'] ? 'ok' : 'bad' ?>"><?= $dataset['available'] ? 'Ready' : 'Missing' ?></strong></div>
          <div><span>Last Updated</span><strong><?= htmlspecialchars($dataset['last_updated']) ?></strong></div>
        </div>
        <a class="btn btn-primary export-btn <?= $dataset['available'] ? '' : 'disabled' ?>" href="../../api/admin/export_data.php?dataset=<?= urlencode($key) ?>" <?= $dataset['available'] ? '' : 'aria-disabled="true" onclick="return false;"' ?>>
          <i class="fas fa-download"></i> Download CSV
        </a>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="card glass" style="margin-top:22px;">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-table"></i> Export Catalogue</div>
      </div>
      <div class="card-body" style="padding:0;">
        <table class="data-table">
          <thead>
            <tr>
              <th>Dataset</th>
              <th>Source Table</th>
              <th>Rows</th>
              <th>Last Updated</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($exportDatasets as $key => $dataset): ?>
            <tr>
              <td><strong><?= htmlspecialchars($dataset['label']) ?></strong></td>
              <td><code><?= htmlspecialchars($dataset['table']) ?></code></td>
              <td><?= number_format($dataset['rows']) ?></td>
              <td><?= htmlspecialchars($dataset['last_updated']) ?></td>
              <td>
                <a class="btn btn-sm btn-outline <?= $dataset['available'] ? '' : 'disabled' ?>" href="../../api/admin/export_data.php?dataset=<?= urlencode($key) ?>" <?= $dataset['available'] ? '' : 'aria-disabled="true" onclick="return false;"' ?>>
                  <i class="fas fa-file-csv"></i> CSV
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <style>
      .export-summary { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:18px; }
      .summary-tile { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:16px; box-shadow:0 2px 8px rgba(15,23,42,.05); }
      .summary-value { font-size:26px; font-weight:800; color:#1e293b; }
      .summary-label { font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase; margin-top:4px; }
      .export-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:16px; }
      .export-card { background:#fff; border:1px solid #e2e8f0; border-left:4px solid #6366f1; border-radius:12px; padding:16px; box-shadow:0 2px 10px rgba(15,23,42,.06); display:flex; flex-direction:column; gap:14px; }
      .export-card-head { display:flex; gap:12px; align-items:flex-start; }
      .export-icon { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex:0 0 42px; }
      .export-title { font-size:15px; font-weight:800; color:#1e293b; }
      .export-subtitle { font-size:12px; color:#64748b; line-height:1.4; margin-top:3px; }
      .export-meta { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
      .export-meta div { background:#f8fafc; border:1px solid #edf2f7; border-radius:8px; padding:9px; }
      .export-meta div:last-child { grid-column:span 2; }
      .export-meta span { display:block; font-size:10px; color:#64748b; font-weight:800; text-transform:uppercase; margin-bottom:3px; }
      .export-meta strong { font-size:12px; color:#1e293b; }
      .export-meta .ok { color:#16a34a; }
      .export-meta .bad { color:#dc2626; }
      .export-btn { width:100%; justify-content:center; }
      .btn.disabled { opacity:.5; pointer-events:none; cursor:not-allowed; }
      @media(max-width:760px){ .export-summary{grid-template-columns:1fr;} }
    </style>
    <?php
}

renderLayout("Data Export", 'renderContent', $role, $name);
