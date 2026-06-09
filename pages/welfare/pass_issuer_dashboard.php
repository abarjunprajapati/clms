<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['pass_user', 'pass_issuer', 'super_admin']);

include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = get_normalized_role();
$name = $_SESSION['name'] ?? 'Pass Issuing Officer';

function passDashTableExists($conn, $table) {
    $table = clms_db_real_escape_string($conn, $table);
    $res = clms_db_query($conn, "SHOW TABLES LIKE '$table'");
    return $res && clms_db_num_rows($res) > 0;
}

function passDashColumnExists($conn, $table, $column) {
    if (!passDashTableExists($conn, $table)) return false;
    $safeTable = str_replace('`', '``', $table);
    $column = clms_db_real_escape_string($conn, $column);
    $res = clms_db_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$column'");
    return $res && clms_db_num_rows($res) > 0;
}

function passDashEnsureColumn($conn, $table, $column, $definition) {
    if (!passDashTableExists($conn, $table) || passDashColumnExists($conn, $table, $column)) return;
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = str_replace('`', '``', $column);
    @clms_db_query($conn, "ALTER TABLE `$safeTable` ADD COLUMN `$safeColumn` $definition");
}

function passDashCol($conn, $table, $alias, $column, $fallback = 'NULL') {
    return passDashColumnExists($conn, $table, $column) ? "$alias.`$column`" : $fallback;
}

function renderContent() {
    global $conn;

    passDashEnsureColumn($conn, 'documents', 'remarks', 'TEXT NULL');
    passDashEnsureColumn($conn, 'documents', 'uploaded_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');

    $hasWorkmen = passDashTableExists($conn, 'workmen');
    $hasContractors = passDashTableExists($conn, 'contractors');
    $hasRequests = passDashTableExists($conn, 'gate_pass_requests');
    $hasRequestWorkers = passDashTableExists($conn, 'gate_pass_request_workers');
    $hasDocuments = passDashTableExists($conn, 'documents');
    $trainingPassedSql = "(
        LOWER(TRIM(COALESCE(w.training_status, ''))) IN ('pass','passed','training_passed','qualified','completed')
        OR LOWER(TRIM(COALESCE(w.safety_training_status, ''))) IN ('1','pass','passed','training_passed','qualified','completed')
    )";
    $trainingValidSql = "(w.training_valid_till IS NULL OR w.training_valid_till = '' OR w.training_valid_till >= CURDATE())";

    $docQueue = ($hasRequests && $hasRequestWorkers)
        ? db_count($conn, "SELECT COUNT(*) c FROM gate_pass_request_workers gprw JOIN gate_pass_requests gpr ON gpr.id = gprw.request_id JOIN workmen w ON w.id = gprw.workman_id WHERE COALESCE(gpr.status, 'pending') IN ('pending','reupload_required') AND COALESCE(gprw.status, 'pending') IN ('pending','reupload_required') AND $trainingPassedSql AND $trainingValidSql")
        : 0;
    $tempQueue = $hasWorkmen
        ? db_count($conn, "SELECT COUNT(*) c FROM workmen w WHERE COALESCE(pass_issuer_verified, 0) = 1 AND COALESCE(is_blocked, 0) = 0 AND COALESCE(status, '') = 'verified' AND $trainingPassedSql AND $trainingValidSql")
        : 0;
    if ($hasRequests && $hasRequestWorkers) {
        $tempQueue += db_count($conn, "SELECT COUNT(*) c FROM gate_pass_request_workers gprw JOIN gate_pass_requests gpr ON gpr.id = gprw.request_id JOIN workmen w ON w.id = gprw.workman_id WHERE COALESCE(gpr.status, '') = 'approved' AND COALESCE(gprw.status, '') = 'approved' AND COALESCE(w.is_blocked, 0) = 0 AND COALESCE(w.status, '') <> 'temporary_issued' AND $trainingPassedSql AND $trainingValidSql");
    }
    $tempIssued = $hasWorkmen
        ? db_count($conn, "SELECT COUNT(*) c FROM workmen WHERE status = 'temporary_issued' OR COALESCE(temp_pass_status, 0) = 1 OR COALESCE(temp_pass_no, '') <> ''")
        : 0;
    $accQueue = $hasWorkmen
        ? db_count($conn, "SELECT COUNT(*) c FROM workmen WHERE (status = 'temporary_issued' OR COALESCE(temp_pass_status, 0) = 1 OR COALESCE(temp_pass_no, '') <> '') AND COALESCE(acc_number, '') = ''")
        : 0;
    $bioQueue = $hasWorkmen
        ? db_count($conn, "SELECT COUNT(*) c FROM workmen WHERE COALESCE(acc_number, '') <> '' AND status <> 'permanent_active' AND COALESCE(biometric_status, 'pending') <> 'completed'")
        : 0;
    $permIssued = $hasWorkmen
        ? db_count($conn, "SELECT COUNT(*) c FROM workmen WHERE status = 'permanent_active'")
        : 0;
    $rejectedDocs = $hasDocuments
        ? db_count($conn, "SELECT COUNT(*) c FROM documents WHERE COALESCE(status, 'pending') IN ('rejected','reupload_required')")
        : 0;

    $expiryExpr = $hasWorkmen
        ? (passDashColumnExists($conn, 'workmen', 'temp_valid_to') && passDashColumnExists($conn, 'workmen', 'valid_to')
            ? "COALESCE(temp_valid_to, valid_to)"
            : (passDashColumnExists($conn, 'workmen', 'valid_to') ? "valid_to" : (passDashColumnExists($conn, 'workmen', 'temp_valid_to') ? "temp_valid_to" : "NULL")))
        : "NULL";
    $expiring = ($hasWorkmen && $expiryExpr !== "NULL")
        ? db_count($conn, "SELECT COUNT(*) c FROM workmen WHERE $expiryExpr IS NOT NULL AND $expiryExpr <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND COALESCE(status, '') IN ('temporary_issued','acc_generated','permanent_active')")
        : 0;

    $recent = [];
    if ($hasWorkmen) {
        $nameExpr = passDashCol($conn, 'workmen', 'w', 'name', "CONCAT('Worker #', w.id)");
        $statusExpr = passDashCol($conn, 'workmen', 'w', 'status', "''");
        $tempExpr = passDashCol($conn, 'workmen', 'w', 'temp_pass_no', "''");
        $accExpr = passDashCol($conn, 'workmen', 'w', 'acc_number', "''");
        $updatedExpr = passDashCol($conn, 'workmen', 'w', 'updated_at', 'w.id');
        $contractorExpr = ($hasContractors && passDashColumnExists($conn, 'contractors', 'contractor_name')) ? 'c.contractor_name' : "'N/A'";
        $recent = db_fetch_all($conn, "
            SELECT w.id, $nameExpr AS worker_name, $statusExpr AS status, $tempExpr AS temp_pass_no, $accExpr AS acc_number,
                   $contractorExpr AS contractor_name
            FROM workmen w
            LEFT JOIN contractors c ON c.id = w.contractor_id
            WHERE COALESCE(w.status, '') IN ('temporary_issued', 'acc_generated', 'permanent_active')
               OR COALESCE(w.temp_pass_no, '') <> ''
               OR COALESCE(w.acc_number, '') <> ''
            ORDER BY $updatedExpr DESC
            LIMIT 8
        ");
    }

    $recentRejectedDocs = [];
    if ($hasDocuments && $hasWorkmen) {
        $docTypeExpr = passDashCol($conn, 'documents', 'd', 'document_type', "'Document'");
        $remarksExpr = passDashCol($conn, 'documents', 'd', 'remarks', "''");
        $uploadedExpr = passDashCol($conn, 'documents', 'd', 'uploaded_at', 'd.id');
        $workerNameExpr = passDashCol($conn, 'workmen', 'w', 'name', "CONCAT('Worker #', w.id)");
        $contractorExpr = ($hasContractors && passDashColumnExists($conn, 'contractors', 'contractor_name')) ? 'c.contractor_name' : "'N/A'";
        $recentRejectedDocs = db_fetch_all($conn, "
            SELECT d.id, $docTypeExpr AS document_type, COALESCE(d.status, 'pending') AS status,
                   $remarksExpr AS remarks, w.id AS workman_id, $workerNameExpr AS worker_name,
                   $contractorExpr AS contractor_name
            FROM documents d
            JOIN workmen w ON w.id = d.workman_id
            LEFT JOIN contractors c ON c.id = w.contractor_id
            WHERE COALESCE(d.status, 'pending') IN ('rejected', 'reupload_required')
            ORDER BY $uploadedExpr DESC
            LIMIT 6
        ");
    }
    ?>
    <div class="content-header pass-header">
      <div>
        <h2 class="page-title"><i class="fas fa-id-badge"></i> Pass Issuing Dashboard</h2>
      </div>
      <div class="pass-actions">
        <a href="verify_documents.php" class="btn btn-primary"><i class="fas fa-file-circle-check"></i> Verify Docs</a>
        <a href="pending_requests.php" class="btn btn-outline"><i class="fas fa-id-card"></i> Issue Temp</a>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat-card glass border-primary"><div class="stat-icon bg-soft-primary text-primary"><i class="fas fa-file-signature"></i></div><div class="stat-value"><?= $docQueue ?></div><div class="stat-label">Document Queue</div></div>
      <div class="stat-card glass border-info"><div class="stat-icon bg-soft-info text-info"><i class="fas fa-id-badge"></i></div><div class="stat-value"><?= $tempQueue ?></div><div class="stat-label">Temp Pass Queue</div></div>
      <div class="stat-card glass border-success"><div class="stat-icon bg-soft-success text-success"><i class="fas fa-clock"></i></div><div class="stat-value"><?= $tempIssued ?></div><div class="stat-label">Temporary Issued</div></div>
      <div class="stat-card glass border-primary"><div class="stat-icon bg-soft-primary text-primary"><i class="fas fa-microchip"></i></div><div class="stat-value"><?= $accQueue ?></div><div class="stat-label">ACC Queue</div></div>
      <div class="stat-card glass border-warning"><div class="stat-icon bg-soft-warning text-warning"><i class="fas fa-fingerprint"></i></div><div class="stat-value"><?= $bioQueue ?></div><div class="stat-label">Biometric / Permanent</div></div>
      <div class="stat-card glass border-success"><div class="stat-icon bg-soft-success text-success"><i class="fas fa-id-card-clip"></i></div><div class="stat-value"><?= $permIssued ?></div><div class="stat-label">Permanent Active</div></div>
      <div class="stat-card glass border-danger"><div class="stat-icon bg-soft-danger text-danger"><i class="fas fa-file-circle-exclamation"></i></div><div class="stat-value"><?= $rejectedDocs ?></div><div class="stat-label">Rejected Docs</div></div>
      <div class="stat-card glass border-warning"><div class="stat-icon bg-soft-warning text-warning"><i class="fas fa-calendar-times"></i></div><div class="stat-value"><?= $expiring ?></div><div class="stat-label">Expiring Passes</div></div>
    </div>

    <div class="pass-step-grid">
      <a href="verify_documents.php" class="pass-step"><i class="fas fa-file-circle-check"></i><strong>1. Verify Documents</strong><span>Medical, PCC, ESI/EC and gate pass documents.</span></a>
      <a href="pending_requests.php" class="pass-step"><i class="fas fa-id-badge"></i><strong>2. Issue Temporary Pass</strong><span>Issue short-validity pass after final verification.</span></a>
      <a href="acc_generation.php" class="pass-step"><i class="fas fa-microchip"></i><strong>3. Generate ACC</strong><span>Create unique ACC number and SAP queue entry.</span></a>
      <a href="issue_acc_pass.php" class="pass-step"><i class="fas fa-fingerprint"></i><strong>4. Biometric & Permanent</strong><span>Complete biometric and activate permanent pass.</span></a>
      <a href="pass_validity.php" class="pass-step"><i class="fas fa-calendar-plus"></i><strong>5. Validity Control</strong><span>Track expiry and manage extensions.</span></a>
    </div>

    <div class="grid grid-2" style="margin-top:24px;gap:20px">
      <div class="card glass">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-history text-info"></i> Recent Issuance Activity</div>
        </div>
        <div class="card-body" style="padding:0">
          <table class="data-table">
            <thead><tr><th>Worker</th><th>Contractor</th><th>Pass / ACC</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($recent as $row): ?>
              <tr>
                <td><strong><?= htmlspecialchars($row['worker_name'] ?? '-') ?></strong></td>
                <td><?= htmlspecialchars($row['contractor_name'] ?? 'N/A') ?></td>
                <td>
                  <div><code><?= htmlspecialchars($row['temp_pass_no'] ?: '-') ?></code></div>
                  <div><code><?= htmlspecialchars($row['acc_number'] ?: '-') ?></code></div>
                </td>
                <td><span class="badge <?= ($row['status'] ?? '') === 'permanent_active' ? 'badge-success' : 'badge-info' ?>"><?= htmlspecialchars(strtoupper(str_replace('_', ' ', $row['status'] ?? ''))) ?></span></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($recent)): ?>
              <tr><td colspan="4" class="text-center" style="padding:30px;">No recent issuance records found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card glass">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-file-circle-exclamation text-danger"></i> Recent Rejected Documents</div>
          <a href="reupload_cases.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="card-body" style="padding:0">
          <table class="data-table">
            <thead><tr><th>Worker</th><th>Document</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
              <?php foreach ($recentRejectedDocs as $doc): ?>
              <tr>
                <td>
                  <strong><?= htmlspecialchars($doc['worker_name'] ?? '-') ?></strong>
                  <div style="font-size:11px;color:#64748b"><?= htmlspecialchars($doc['contractor_name'] ?? '-') ?></div>
                </td>
                <td>
                  <?= htmlspecialchars($doc['document_type'] ?? '-') ?>
                  <div style="font-size:11px;color:#dc2626;max-width:260px;white-space:normal"><?= htmlspecialchars($doc['remarks'] ?: '') ?></div>
                </td>
                <td><span class="badge badge-danger"><?= strtoupper(str_replace('_', ' ', $doc['status'])) ?></span></td>
                <td><a href="verify_documents.php?id=<?= (int)$doc['workman_id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i> View</a></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($recentRejectedDocs)): ?>
              <tr><td colspan="4" class="text-center" style="padding:30px;">No rejected document records found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <style>
      .pass-header{display:flex;justify-content:space-between;align-items:flex-end;gap:14px}
      .pass-header .page-title{display:flex;align-items:center;gap:10px}
      .pass-actions{display:flex;gap:8px;flex-wrap:wrap}
      .bg-soft-primary{background:rgba(99,102,241,.1)}
      .bg-soft-info{background:rgba(6,182,212,.1)}
      .bg-soft-success{background:rgba(34,197,94,.1)}
      .bg-soft-danger{background:rgba(239,68,68,.1)}
      .bg-soft-warning{background:rgba(245,158,11,.1)}
      .border-primary{border-left:4px solid #6366f1!important}
      .border-info{border-left:4px solid #06b6d4!important}
      .border-success{border-left:4px solid #22c55e!important}
      .border-danger{border-left:4px solid #ef4444!important}
      .border-warning{border-left:4px solid #f59e0b!important}
      .pass-step-grid{display:grid;grid-template-columns:repeat(5,minmax(150px,1fr));gap:12px;margin-top:22px}
      .pass-step{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:14px;text-decoration:none;color:inherit;display:flex;flex-direction:column;gap:7px;min-height:118px}
      .pass-step i{font-size:19px;color:var(--primary)}
      .pass-step strong{font-size:13px;color:#111827}
      .pass-step span{font-size:12px;color:#64748b;line-height:1.35}
      .pass-step:hover{border-color:#c7d2fe;background:#f8fafc}
      @media(max-width:1200px){.pass-step-grid{grid-template-columns:repeat(auto-fit,minmax(170px,1fr))}}
      @media(max-width:640px){.pass-header{flex-direction:column;align-items:stretch}.pass-actions .btn{flex:1}}
    </style>
    <?php
}

renderLayout("Pass Issuing Dashboard", 'renderContent', $role, $name);
?>
