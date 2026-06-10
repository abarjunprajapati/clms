<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['contractor']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';
require_once __DIR__ . '/../../include/safety_training_control.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';
$user_id = $_SESSION['user_id'] ?? 0;

function contractorTableExists(mysqli $conn, string $table): bool {
    $table = mysqli_real_escape_string($conn, $table);
    $result = mysqli_query($conn, "SHOW TABLES LIKE '{$table}'");
    return $result && mysqli_num_rows($result) > 0;
}

function contractorColumnExists(mysqli $conn, string $table, string $column): bool {
    if (!contractorTableExists($conn, $table)) {
        return false;
    }
    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    return $result && mysqli_num_rows($result) > 0;
}

function contractorSafeCount(mysqli $conn, string $table, string $where = '1=1'): int {
    if (!contractorTableExists($conn, $table)) {
        return 0;
    }
    try {
        $result = mysqli_query($conn, "SELECT COUNT(*) c FROM `{$table}` WHERE {$where}");
    } catch (mysqli_sql_exception $e) {
        return 0;
    }
    if (!$result) {
        return 0;
    }
    $row = mysqli_fetch_assoc($result);
    return (int)($row['c'] ?? 0);
}

function contractorSafeScalar(mysqli $conn, string $sql): int {
    try {
        $result = mysqli_query($conn, $sql);
    } catch (mysqli_sql_exception $e) {
        return 0;
    }
    if (!$result) {
        return 0;
    }
    $row = mysqli_fetch_assoc($result);
    return (int)($row['c'] ?? 0);
}

function contractorRecentRows(mysqli $conn, string $sql): array {
    try {
        $result = mysqli_query($conn, $sql);
    } catch (mysqli_sql_exception $e) {
        return [];
    }
    if (!$result) {
        return [];
    }
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

function contractorWorkerTypeWhere(string $kind): string {
    $map = [
        'contractor' => ["'contractor'", "'Contractor Pass'"],
        'representative' => ["'representative'", "'Representative Pass'"],
        'supervisor' => ["'supervisor'", "'Supervisor Pass'"],
        'workmen' => ["'workmen'", "'workman'", "'Workmen Pass'", "'Workman Pass'"],
    ];
    $values = $map[$kind] ?? ["'" . addslashes($kind) . "'"];
    return 'worker_type IN (' . implode(',', $values) . ')';
}

function renderContent() {
    global $conn, $user_id, $name;
    clms_safety_ensure_control_schema($conn);

    $contractor = db_single($conn, "SELECT * FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    
    // Fallback: If not found by user_id, try by contractor_id session variable
    if (!$contractor && !empty($_SESSION['contractor_id'])) {
        $contractor = db_single($conn, "SELECT * FROM contractors WHERE vendor_code = ?", 's', [$_SESSION['contractor_id']]);
        
        // If found by vendor_code but user_id was wrong, try to fix it in background
        if ($contractor && $user_id > 0) {
            db_execute($conn, "UPDATE contractors SET user_id = ? WHERE id = ?", 'ii', [$user_id, $contractor['id']]);
        }
    }
    $c_id = (int)($contractor['id'] ?? ($_SESSION['contractor_id'] ?? 0));
    $contractor_name = $contractor['contractor_name'] ?? $name;
    $vendor_code = $contractor['vendor_code'] ?? '';
    $contractor_status = strtolower($contractor['status'] ?? 'pending');
    $cidWhere = $c_id ? "contractor_id = {$c_id}" : '1=0';
    $annexure2a = $c_id ? db_single($conn, "SELECT workflow_status, submitted_at, updated_at FROM annexure2a WHERE contractor_id = ? ORDER BY id DESC LIMIT 1", 'i', [$c_id]) : null;
    $annexure2a_status = strtolower($annexure2a['workflow_status'] ?? '');
    $display_contractor_status = ($contractor_status === 'pending' && $annexure2a_status === 'resubmitted') ? 'resubmitted' : $contractor_status;

    $workmenTable = contractorTableExists($conn, 'workmen') ? 'workmen' : (contractorTableExists($conn, 'workers') ? 'workers' : 'workmen');
    $workerNameCol = contractorColumnExists($conn, $workmenTable, 'name') ? 'name' : 'worker_name';
    $workerContractorWhere = contractorColumnExists($conn, $workmenTable, 'contractor_id') ? $cidWhere : '1=0';
    $trainingCol = contractorColumnExists($conn, $workmenTable, 'training_status') ? 'training_status' : (contractorColumnExists($conn, $workmenTable, 'safety_status') ? 'safety_status' : null);

    $totalWorkers = contractorSafeCount($conn, $workmenTable, $workerContractorWhere);
    $trainingPassed = $trainingCol ? contractorSafeCount($conn, $workmenTable, "{$workerContractorWhere} AND {$trainingCol} IN ('pass','passed','completed','training_passed','qualified')") : 0;
    $pendingTraining = $trainingCol ? contractorSafeCount($conn, $workmenTable, "{$workerContractorWhere} AND ({$trainingCol} IS NULL OR {$trainingCol} IN ('pending','training_pending','training_scheduled','fail','failed','training_failed'))") : 0;
    if (contractorColumnExists($conn, 'gate_passes', 'contractor_id')) {
        $pendingPasses = contractorSafeCount($conn, 'gate_passes', "{$cidWhere} AND status = 'pending'");
        $approvedPasses = contractorSafeCount($conn, 'gate_passes', "{$cidWhere} AND status IN ('approved','active')");
    } elseif (contractorColumnExists($conn, 'gate_passes', 'workman_id') && contractorTableExists($conn, 'workmen')) {
        $pendingPasses = $c_id ? contractorSafeScalar($conn, "SELECT COUNT(*) c FROM gate_passes gp JOIN workmen w ON gp.workman_id = w.id WHERE w.contractor_id = {$c_id} AND gp.status = 'pending'") : 0;
        $approvedPasses = $c_id ? contractorSafeScalar($conn, "SELECT COUNT(*) c FROM gate_passes gp JOIN workmen w ON gp.workman_id = w.id WHERE w.contractor_id = {$c_id} AND gp.status IN ('approved','active')") : 0;
    } else {
        $pendingPasses = 0;
        $approvedPasses = 0;
    }
    $pendingCompliance = contractorTableExists($conn, 'compliance')
        ? contractorSafeCount($conn, 'compliance', "{$cidWhere} AND status = 'pending'")
        : contractorSafeCount($conn, 'compliance_uploads', "{$cidWhere} AND status = 'pending'");
    $pendingDocuments = contractorSafeCount($conn, 'contractor_documents', "{$cidWhere} AND status = 'pending'");

    // Additional KPIs for PDF compliance
    $representatives = contractorSafeCount($conn, $workmenTable, "{$workerContractorWhere} AND " . contractorWorkerTypeWhere('representative'));
    $supervisors = contractorSafeCount($conn, $workmenTable, "{$workerContractorWhere} AND " . contractorWorkerTypeWhere('supervisor'));
    $blockedWorkers = contractorSafeCount($conn, $workmenTable, "{$workerContractorWhere} AND is_blocked=1");
    $activePWOs = $vendor_code ? contractorSafeScalar($conn, "SELECT COUNT(*) c FROM sap_pwo_master p JOIN sap_po_master po ON p.po_number = po.po_number WHERE po.vendor_code = '{$vendor_code}'") : 0;
    $activeSalesOrders = $vendor_code ? contractorSafeScalar($conn, "SELECT COUNT(*) c FROM sap_sales_order_master WHERE vendor_code = '{$vendor_code}'") : 0;
    $activeACCCards = contractorSafeCount($conn, $workmenTable, "{$workerContractorWhere} AND status IN ('acc_generated', 'permanent_active')");
    $temporaryPasses = contractorSafeCount($conn, $workmenTable, "{$workerContractorWhere} AND status = 'temporary_issued'");


    $recentWorkers = [];
    if (contractorTableExists($conn, $workmenTable) && contractorColumnExists($conn, $workmenTable, 'contractor_id')) {
        $orderCol = contractorColumnExists($conn, $workmenTable, 'created_at') ? 'created_at' : 'id';
        $tempCol = contractorColumnExists($conn, $workmenTable, 'temp_id') ? 'temp_id' : 'id';
        $recentWorkers = contractorRecentRows($conn, "SELECT id, {$workerNameCol} AS worker_name, {$tempCol} AS temp_ref" . ($trainingCol ? ", {$trainingCol} AS training_status" : ", '' AS training_status") . " FROM `{$workmenTable}` WHERE {$workerContractorWhere} ORDER BY {$orderCol} DESC LIMIT 5");
    }

    $trainingStatusExpr = $trainingCol ? "LOWER(COALESCE(w.`{$trainingCol}`, 'pending'))" : "LOWER(COALESCE(w.training_status, 'pending'))";
    $trainingRequestReady = contractorSafeCount($conn, 'training_requests', "{$cidWhere} AND LOWER(COALESCE(status, 'pending')) IN ('pending','welfare_pending')");
    $bookingPending = contractorSafeCount($conn, 'training_requests', "{$cidWhere} AND LOWER(COALESCE(status, 'pending')) IN ('pending','welfare_pending')");
    $upcomingScheduled = contractorTableExists($conn, 'training_requests')
        ? contractorSafeScalar($conn, "SELECT COUNT(DISTINCT COALESCE(NULLIF(batch_number, ''), CONCAT('SESSION-', COALESCE(scheduled_session_id, id)))) c FROM training_requests WHERE {$cidWhere} AND LOWER(COALESCE(status, 'pending')) IN ('scheduled','contractor_confirmed') AND scheduled_date >= CURDATE()")
        : 0;
    $confirmedWorkers = contractorSafeCount($conn, 'training_requests', "{$cidWhere} AND (LOWER(COALESCE(status, 'pending')) = 'contractor_confirmed' OR COALESCE(contractor_confirmed, 0) = 1)");
    $retestRequired = contractorSafeCount($conn, 'training_requests', "{$cidWhere} AND LOWER(COALESCE(status, 'pending')) IN ('failed','fail','absent','training_failed')");

    $availableBatches = contractorTableExists($conn, 'training_class_batches') && contractorTableExists($conn, 'training_batch_workers')
        ? contractorRecentRows($conn, "
            SELECT b.id, b.batch_number, b.training_date, b.language_name, b.session_name, b.venue_name, b.capacity,
                   COALESCE(x.selected_count, 0) AS selected_count,
                   GREATEST(b.capacity - COALESCE(x.selected_count, 0), 0) AS seats_available
            FROM training_class_batches b
            LEFT JOIN (
                SELECT batch_id, COUNT(*) AS selected_count
                FROM training_batch_workers
                WHERE ticked = 1
                GROUP BY batch_id
            ) x ON x.batch_id = b.id
            WHERE b.training_date >= CURDATE()
              AND LOWER(COALESCE(b.status, 'scheduled')) IN ('draft','open','scheduled')
            ORDER BY b.training_date ASC, b.session_name ASC, b.id ASC
            LIMIT 5
        ")
        : [];

    $recentTrainingStatuses = [];
    if ($c_id && contractorTableExists($conn, 'training_requests') && contractorTableExists($conn, $workmenTable)) {
        $aadhaarExpr = contractorColumnExists($conn, $workmenTable, 'aadhaar') ? 'w.aadhaar' : "''";
        $tempExpr = contractorColumnExists($conn, $workmenTable, 'temp_id') ? 'w.temp_id' : 'w.id';
        $updatedExpr = contractorColumnExists($conn, 'training_requests', 'updated_at') ? 'tr.updated_at' : 'tr.id';
        $recentTrainingStatuses = contractorRecentRows($conn, "
            SELECT tr.id, tr.workman_id, COALESCE(tr.status, 'pending') AS status,
                   tr.scheduled_date, tr.batch_number,
                   w.`{$workerNameCol}` AS worker_name, {$aadhaarExpr} AS aadhaar, {$tempExpr} AS temp_ref,
                   {$trainingStatusExpr} AS worker_training_status
            FROM training_requests tr
            JOIN `{$workmenTable}` w ON w.id = tr.workman_id
            WHERE tr.contractor_id = {$c_id}
            ORDER BY {$updatedExpr} DESC, tr.id DESC
            LIMIT 6
        ");
    }

    $retestWorkers = [];
    if ($c_id && contractorTableExists($conn, 'training_requests') && contractorTableExists($conn, $workmenTable)) {
        $aadhaarExpr = contractorColumnExists($conn, $workmenTable, 'aadhaar') ? 'w.aadhaar' : "''";
        $tempExpr = contractorColumnExists($conn, $workmenTable, 'temp_id') ? 'w.temp_id' : 'w.id';
        $updatedExpr = contractorColumnExists($conn, 'training_requests', 'updated_at') ? 'last_req.updated_at' : 'last_req.id';
        $retestWorkers = contractorRecentRows($conn, "
            SELECT last_req.workman_id, last_req.status, last_req.updated_at, w.`{$workerNameCol}` AS worker_name,
                   {$aadhaarExpr} AS aadhaar, {$tempExpr} AS temp_ref,
                   COALESCE(attempts.attempt_count, 0) AS attempt_count,
                   GREATEST(0, 30 - DATEDIFF(CURDATE(), DATE(last_req.updated_at))) AS days_left
            FROM training_requests last_req
            JOIN `{$workmenTable}` w ON w.id = last_req.workman_id
            LEFT JOIN (
                SELECT workman_id, COUNT(*) AS attempt_count
                FROM training_requests
                WHERE contractor_id = {$c_id}
                  AND LOWER(COALESCE(status, 'pending')) IN ('failed','fail','absent','passed')
                  AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY workman_id
            ) attempts ON attempts.workman_id = last_req.workman_id
            WHERE last_req.contractor_id = {$c_id}
              AND LOWER(COALESCE(last_req.status, 'pending')) IN ('failed','fail','absent','training_failed')
              AND last_req.id = (
                  SELECT tr2.id
                  FROM training_requests tr2
                  WHERE tr2.workman_id = last_req.workman_id
                    AND tr2.contractor_id = last_req.contractor_id
                  ORDER BY tr2.id DESC
                  LIMIT 1
              )
            ORDER BY {$updatedExpr} DESC
            LIMIT 5
        ");
    }

    $a2Decision = [
        'title' => 'Contractor Registration',
        'status' => $display_contractor_status,
        'reason' => $contractor['approval_reason'] ?? '',
        'file' => $contractor['approval_pdf'] ?? '',
        'date' => $contractor['last_action_at'] ?? ($annexure2a['updated_at'] ?? $contractor['updated_at'] ?? '')
    ];

    $annexure2aHistory = [];
    if ($c_id && contractorTableExists($conn, 'contractor_annexure2a_history')) {
        $annexure2aHistory = db_fetch_all(
            $conn,
            "SELECT annexure2a_id, status, reason, updated_at FROM contractor_annexure2a_history WHERE contractor_id = ? ORDER BY updated_at DESC",
            'i',
            [$c_id]
        );
    }
    if (empty($annexure2aHistory) && !empty($a2Decision['date'])) {
        $annexure2aHistory[] = [
            'annexure2a_id' => '',
            'status' => $a2Decision['status'],
            'reason' => $a2Decision['reason'],
            'updated_at' => $a2Decision['date'],
            'file' => $a2Decision['file'],
        ];
    }

    $a3Decision = null;

    $decisionBadge = function($status) {
        $status = strtolower((string)$status);
        if ($status === 'approved') return 'badge-success';
        if (in_array($status, ['rejected', 'blocked'], true)) return 'badge-danger';
        if (in_array($status, ['correction_required', 'hold'], true)) return 'badge-warning';
        return 'badge-info';
    };
    $decisionFileUrl = function($path) {
        $path = trim((string)$path);
        if ($path === '') return '';
        if (preg_match('/^https?:\/\//i', $path)) return $path;
        return '../../uploads/' . ltrim($path, '/');
    };

    if ($contractor_status === 'approved') {
        $flow = [
            ['label' => 'Dashboard', 'detail' => 'Overview of your workforce and compliance', 'icon' => 'fa-tachometer-alt', 'link' => 'dashboard.php', 'status' => 'done', 'count' => 'Live'],
            // ['label' => 'Customer Registration', 'detail' => 'Customer mapped statutory and manpower submission', 'icon' => 'fa-file-signature', 'link' => 'annexure-3a.php', 'status' => 'active', 'count' => 'Open'],
            ['label' => 'Contractor', 'detail' => 'Enroll yourself for site access and training', 'icon' => 'fa-user-check', 'link' => 'enrolment-4a.php?type=contractor', 'status' => 'active', 'count' => contractorSafeCount($conn, 'workmen', "{$cidWhere} AND " . contractorWorkerTypeWhere('contractor'))],
            ['label' => 'Representative', 'detail' => 'Register official representatives (Max 2)', 'icon' => 'fa-user-tie', 'link' => 'enrolment-4a.php?type=representative', 'status' => 'active', 'count' => contractorSafeCount($conn, 'workmen', "{$cidWhere} AND " . contractorWorkerTypeWhere('representative'))],
            ['label' => 'Supervisor', 'detail' => 'Register site supervisors (1 per 50 workmen)', 'icon' => 'fa-user-shield', 'link' => 'enrolment-4a.php?type=supervisor', 'status' => 'active', 'count' => contractorSafeCount($conn, 'workmen', "{$cidWhere} AND " . contractorWorkerTypeWhere('supervisor'))],
            ['label' => 'Workmen', 'detail' => 'Register and manage your workers', 'icon' => 'fa-users', 'link' => 'enrolment-4a.php?type=workmen', 'status' => 'active', 'count' => contractorSafeCount($conn, 'workmen', "{$cidWhere} AND " . contractorWorkerTypeWhere('workmen'))],
            ['label' => 'Safety Training Request', 'detail' => 'Submit requests and confirm Safety schedule', 'icon' => 'fa-graduation-cap', 'link' => 'training_request.php', 'status' => 'active', 'count' => $trainingRequestReady],
            ['label' => 'Book Safety Training', 'detail' => 'Book appointment against available Safety batches', 'icon' => 'fa-calendar-check', 'link' => 'book_safety_training.php', 'status' => 'active', 'count' => $bookingPending],
            ['label' => 'Gate Pass', 'detail' => 'Generate temporary and monthly passes', 'icon' => 'fa-id-badge', 'link' => 'gatepass-6a.php', 'status' => 'active', 'count' => 'Generate'],
            ['label' => 'ACC Card', 'detail' => 'Track permanent biometric card status', 'icon' => 'fa-fingerprint', 'link' => 'pass_status.php', 'status' => 'active', 'count' => 'Track'],
            ['label' => 'Attendance', 'detail' => 'View daily and monthly attendance logs', 'icon' => 'fa-clipboard-list', 'link' => 'attendance.php', 'status' => 'active', 'count' => 'View'],
            ['label' => 'Documents', 'detail' => 'Manage all statutory and worker documents', 'icon' => 'fa-folder-open', 'link' => 'documents.php', 'status' => 'active', 'count' => 'Manage'],
            ['label' => 'Statutory Compliance', 'detail' => 'Monthly ESI, EPF, and KLWF uploads', 'icon' => 'fa-shield-check', 'link' => 'compliance.php', 'status' => 'active', 'count' => 'Upload'],
        ];
    } else {
        $flow = [
            ['label' => 'Contractor Registration', 'detail' => 'Submit contractor details for Welfare approval', 'icon' => 'fa-file-signature', 'link' => 'annexure-2a.php', 'status' => $contractor_status === 'approved' ? 'done' : 'pending', 'count' => ucfirst(str_replace('_', ' ', $display_contractor_status))],
            ['label' => 'Awaiting Approval', 'detail' => 'Please wait for Welfare user to verify your registration.', 'icon' => 'fa-clock', 'link' => '#', 'status' => 'active', 'count' => 'WAITING'],
        ];

        // Approval Timeline configuration
        $timeline = [
            'submitted' => ['icon' => 'fa-paper-plane', 'label' => 'Submitted', 'color' => 'var(--primary)', 'desc' => 'Registration data received.'],
            'resubmitted' => ['icon' => 'fa-rotate', 'label' => 'Resubmitted', 'color' => 'var(--warning)', 'desc' => 'Updated EC Policy / Labour License details sent to Welfare for approval.'],
            'pending' => ['icon' => 'fa-search', 'label' => 'Under Review', 'color' => 'var(--warning)', 'desc' => 'Welfare Admin is reviewing documents.'],
            'correction_required' => ['icon' => 'fa-edit', 'label' => 'Correction Required', 'color' => 'var(--warning)', 'desc' => 'Please update requested details.'],
            'hold' => ['icon' => 'fa-pause', 'label' => 'On Hold', 'color' => 'var(--gray-500)', 'desc' => 'Application temporarily paused.'],
            'rejected' => ['icon' => 'fa-times', 'label' => 'Rejected', 'color' => 'var(--danger)', 'desc' => 'Application denied.'],
            'approved' => ['icon' => 'fa-check', 'label' => 'Approved', 'color' => 'var(--success)', 'desc' => 'Registration successful!'],
            'blocked' => ['icon' => 'fa-ban', 'label' => 'Blocked', 'color' => 'var(--danger)', 'desc' => 'Contractor access revoked.']
        ];
        
        $current_step = $timeline[$display_contractor_status] ?? $timeline['pending'];
    }
    ?>


    <div class="content-header">
      <div>
        <h2 class="page-title"><?= $contractor_status === 'approved' ? ' Main Dashboard' : 'Contractor Onboarding' ?></h2>
        <p class="page-subtitle"><?= htmlspecialchars($contractor_name) ?> | <?= $contractor_status === 'approved' ? 'Site Operational Hub' : 'Complete your registration to unlock workforce modules.' ?></p>
      </div>
      
      <div style="display:flex; gap:10px;">
          <a href="pass_status.php" class="btn btn-primary" style="margin-left:10px;"><i class="fas fa-satellite-dish"></i> Track Pass Status</a>
      </div>
    </div>

                                                                                          <?php if ($contractor_status === 'approved'): ?>
                                                                                          <!-- <div class="card glass" style="background:linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color:white; padding:25px; margin-bottom:25px; border:none; box-shadow:0 15px 30px -10px rgba(79,70,229,0.5);">
                                                                                              <div style="display:flex; align-items:center; gap:25px;">
                                                                                                  <div style="width:60px; height:60px; background:rgba(255,255,255,0.2); border-radius:15px; display:flex; align-items:center; justify-content:center; font-size:28px;">
                                                                                                      <i class="fas fa-rocket"></i>
                                                                                                  </div>
                                                                                                  <div>
                                                                                                      <h3 style="margin-bottom:5px; font-weight:800; font-size:20px;">Customer Registration Access Enabled</h3>
                                                                                                      <p style="opacity:0.9; font-size:13px;">Your registration is approved. You can now register representatives, supervisors, and workmen for gate pass issuance.</p>
                                                                                                  </div>
                                                                                              </div>
                                                                                          </div> -->
                                                                                          <?php else: ?>
    <div class="card glass" style="margin-bottom: 25px;">
        <div class="card-header"><div class="card-title">Welfare Approval Timeline</div></div>
        <div class="card-body">
            <div class="timeline-container">
                <div class="timeline-step <?= in_array($display_contractor_status, ['submitted','resubmitted','pending','correction_required','hold','approved','rejected','blocked']) ? 'active' : '' ?>">
                    <div class="step-icon"><i class="fas fa-paper-plane"></i></div>
                    <div class="step-label">Submitted</div>
                </div>
                <div class="timeline-line <?= in_array($display_contractor_status, ['resubmitted','pending','correction_required','hold','approved','rejected','blocked']) ? 'active' : '' ?>"></div>
                <div class="timeline-step <?= in_array($display_contractor_status, ['resubmitted','pending','correction_required','hold','approved','rejected','blocked']) ? 'active' : '' ?>">
                    <div class="step-icon"><i class="fas fa-search"></i></div>
                    <div class="step-label">Under Review</div>
                </div>
                <div class="timeline-line <?= in_array($display_contractor_status, ['resubmitted','approved','rejected','blocked','correction_required']) ? 'active' : '' ?>"></div>
                <div class="timeline-step <?= in_array($display_contractor_status, ['resubmitted','approved','rejected','blocked','correction_required']) ? 'active' : '' ?>">
                    <div class="step-icon" style="color: <?= $current_step['color'] ?>;"><i class="fas <?= $current_step['icon'] ?>"></i></div>
                    <div class="step-label" style="color: <?= $current_step['color'] ?>; font-weight:800;"><?= $current_step['label'] ?></div>
                </div>
            </div>
            
            <div style="margin-top: 25px; padding: 15px; background: rgba(99,102,241,0.05); border-radius: 8px; border-left: 4px solid <?= $current_step['color'] ?>;">
                <h6 style="margin-bottom:5px; font-weight:700;">Current Status: <?= strtoupper($current_step['label']) ?></h6>
                <p style="margin:0; font-size:13px; color:var(--text-muted);"><?= $current_step['desc'] ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:22px;gap:15px;">
      <div class="stat-card glass"><div class="stat-icon" style="background:rgba(59,130,246,.12);color:#2563eb"><i class="fas fa-users"></i></div><div class="stat-value"><?= $totalWorkers ?></div><div class="stat-label">Total Workmen</div></div>
      <div class="stat-card glass"><div class="stat-icon" style="background:rgba(16,185,129,.12);color:#059669"><i class="fas fa-user-check"></i></div><div class="stat-value"><?= $trainingPassed ?></div><div class="stat-label">Safety Passed</div></div>
      <div class="stat-card glass"><div class="stat-icon" style="background:rgba(245,158,11,.14);color:#d97706"><i class="fas fa-hourglass-half"></i></div><div class="stat-value"><?= $pendingTraining ?></div><div class="stat-label">Training Pending</div></div>
      <div class="stat-card glass"><div class="stat-icon" style="background:rgba(99,102,241,.12);color:#4f46e5"><i class="fas fa-id-card"></i></div><div class="stat-value"><?= $approvedPasses ?></div><div class="stat-label">Gate Passes</div></div>
      
      <!-- Additional KPIs -->
      <div class="stat-card glass"><div class="stat-icon" style="background:rgba(236,72,153,.12);color:#db2777"><i class="fas fa-user-tie"></i></div><div class="stat-value"><?= $representatives ?></div><div class="stat-label">Representatives</div></div>
      <div class="stat-card glass"><div class="stat-icon" style="background:rgba(139,92,246,.12);color:#7c3aed"><i class="fas fa-user-shield"></i></div><div class="stat-value"><?= $supervisors ?></div><div class="stat-label">Supervisors</div></div>
      <div class="stat-card glass"><div class="stat-icon" style="background:rgba(239,68,68,.12);color:#dc2626"><i class="fas fa-user-slash"></i></div><div class="stat-value"><?= $blockedWorkers ?></div><div class="stat-label">Blocked Workers</div></div>
      <div class="stat-card glass"><div class="stat-icon" style="background:rgba(14,165,233,.12);color:#0284c7"><i class="fas fa-ship"></i></div><div class="stat-value"><?= $activePWOs ?></div><div class="stat-label">Active PWOs</div></div>
      <div class="stat-card glass"><div class="stat-icon" style="background:rgba(16,185,129,.12);color:#059669"><i class="fas fa-fingerprint"></i></div><div class="stat-value"><?= $activeACCCards ?></div><div class="stat-label">ACC Cards</div></div>
      <div class="stat-card glass"><div class="stat-icon" style="background:rgba(245,158,11,.12);color:#d97706"><i class="fas fa-clock"></i></div><div class="stat-value"><?= $temporaryPasses ?></div><div class="stat-label">Temp Passes</div></div>
    </div>

    <div class="contractor-flow">
      <?php foreach ($flow as $index => $step):
        $badgeClass = ['done' => 'badge-success', 'active' => 'badge-warning', 'locked' => 'badge-gray', 'pending' => 'badge-info'][$step['status']] ?? 'badge-gray';
      ?>
      <a class="flow-step <?= $step['status'] ?>" href="<?= $step['link'] ?>">
        <div class="flow-index"><?= $index + 1 ?></div>
        <div class="flow-icon"><i class="fas <?= $step['icon'] ?>"></i></div>
        <div class="flow-body">
          <div class="flow-title"><?= htmlspecialchars($step['label']) ?></div>
          <div class="flow-detail"><?= htmlspecialchars($step['detail']) ?></div>
        </div>
        <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars((string)$step['count']) ?></span>
      </a>
      <?php endforeach; ?>
    </div>

    <?php if ($contractor_status === 'approved'): ?>
    <div class="safety-booking-panel glass">
      <div class="safety-booking-head">
        <div>
          <h3><i class="fas fa-calendar-check"></i> Book Appointment / Book Safety Training</h3>
          <p>Contractor view for booking requests, open safety batches, worker results and retest follow-up.</p>
        </div>
        <div class="safety-booking-actions">
          <a href="training_request.php" class="btn btn-outline"><i class="fas fa-paper-plane"></i> Request Training</a>
          <a href="book_safety_training.php" class="btn btn-primary"><i class="fas fa-calendar-plus"></i> Book Appointment</a>
        </div>
      </div>

      <div class="booking-snapshot">
        <div class="booking-stat"><span>Pending Booking Requests</span><strong><?= (int)$bookingPending ?></strong></div>
        <div class="booking-stat"><span>Upcoming Scheduled Batches</span><strong><?= (int)$upcomingScheduled ?></strong></div>
        <div class="booking-stat"><span>Confirmed Workers</span><strong><?= (int)$confirmedWorkers ?></strong></div>
        <div class="booking-stat danger"><span>Failed / Absent / Retest</span><strong><?= (int)$retestRequired ?></strong></div>
      </div>

      <div class="safety-dashboard-grid">
        <div class="safety-mini-card">
          <div class="mini-head">
            <strong><i class="fas fa-chalkboard-user"></i> Upcoming Available Batches</strong>
            <a href="book_safety_training.php">View all</a>
          </div>
          <div class="table-responsive">
            <table class="data-table compact-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Language</th>
                  <th>Session</th>
                  <th>Seats</th>
                  <th>Batch No</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($availableBatches as $batch): ?>
                <tr>
                  <td><strong><?= !empty($batch['training_date']) ? date('d M Y', strtotime($batch['training_date'])) : '-' ?></strong></td>
                  <td><?= htmlspecialchars($batch['language_name'] ?? '-') ?></td>
                  <td><?= htmlspecialchars($batch['session_name'] ?? '-') ?></td>
                  <td><span class="badge badge-info"><?= (int)($batch['seats_available'] ?? 0) ?> / <?= (int)($batch['capacity'] ?? 0) ?></span></td>
                  <td><?= htmlspecialchars($batch['batch_number'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($availableBatches)): ?>
                <tr><td colspan="5" style="text-align:center;padding:22px;color:var(--gray-500);">No upcoming safety batch available.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="safety-mini-card">
          <div class="mini-head">
            <strong><i class="fas fa-clipboard-check"></i> Recent Training Status</strong>
            <a href="training_request.php">Open status</a>
          </div>
          <div class="recent-list training-status-list">
            <?php foreach ($recentTrainingStatuses as $row):
              $status = strtolower((string)($row['status'] ?? $row['worker_training_status'] ?? 'pending'));
              $badge = in_array($status, ['passed','pass','completed'], true) ? 'badge-success' : (in_array($status, ['failed','fail','absent','training_failed'], true) ? 'badge-danger' : (in_array($status, ['scheduled','contractor_confirmed'], true) ? 'badge-info' : 'badge-warning'));
            ?>
            <div class="recent-item">
              <div>
                <div style="font-weight:700;font-size:13px;"><?= htmlspecialchars($row['worker_name'] ?? 'Worker') ?></div>
                <div style="font-size:11px;color:var(--gray-500);">
                  <?= !empty($row['aadhaar']) ? 'Aadhaar: ' . htmlspecialchars((string)$row['aadhaar']) : 'Temp ID: ' . htmlspecialchars((string)($row['temp_ref'] ?? '-')) ?>
                  <?= !empty($row['batch_number']) ? ' | ' . htmlspecialchars($row['batch_number']) : '' ?>
                </div>
              </div>
              <span class="badge <?= $badge ?>"><?= htmlspecialchars(strtoupper(str_replace('_', ' ', $status))) ?></span>
            </div>
            <?php endforeach; ?>
            <?php if (empty($recentTrainingStatuses)): ?>
              <div style="text-align:center;color:var(--gray-500);padding:24px 0;">No training status found yet.</div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="retest-box">
        <div class="mini-head">
          <strong><i class="fas fa-rotate-left"></i> Retest Reminder</strong>
          <span>Failed/absent workers must be re-booked within 30 days. Maximum 3 attempts are allowed in the current safety cycle.</span>
        </div>
        <div class="retest-list">
          <?php foreach ($retestWorkers as $row):
            $attempts = (int)($row['attempt_count'] ?? 0);
            $blocked = $attempts >= 3;
          ?>
          <div class="retest-item">
            <div>
              <strong><?= htmlspecialchars($row['worker_name'] ?? 'Worker') ?></strong>
              <span><?= htmlspecialchars((string)($row['temp_ref'] ?? '-')) ?><?= !empty($row['aadhaar']) ? ' | ' . htmlspecialchars((string)$row['aadhaar']) : '' ?></span>
            </div>
            <span class="badge <?= $blocked ? 'badge-danger' : 'badge-warning' ?>">
              <?= $blocked ? 'MAX ATTEMPTS' : max(0, 3 - $attempts) . ' attempts left' ?>
            </span>
            <span class="badge badge-gray"><?= (int)($row['days_left'] ?? 0) ?> days left</span>
            <a href="book_safety_training.php" class="btn btn-sm btn-outline">Book Retest</a>
          </div>
          <?php endforeach; ?>
          <?php if (empty($retestWorkers)): ?>
            <div style="text-align:center;color:var(--gray-500);padding:18px 0;">No failed or absent worker pending retest.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:minmax(0,1.25fr) minmax(320px,.75fr);gap:20px;margin-top:22px;align-items:start;">
      <div class="card glass">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-bolt"></i> Contractor Actions</div>
        </div>
        <div class="card-body">
          <div class="action-grid">
            <a href="annexure-2a.php" class="action-tile"><i class="fas fa-building"></i><span>Contractor Registration</span></a>
            <?php if ($contractor_status === 'approved'): ?>
              <a href="annexure-2a.php?resubmit=1" class="action-tile"><i class="fas fa-rotate"></i><span>Resubmit EC / Labour License</span></a>
            <?php endif; ?>
            <!-- <a href="annexure-3a.php" class="action-tile"><i class="fas fa-file-signature"></i><span>Customer Registration</span></a> -->
            <a href="enrolment-4a.php" class="action-tile"><i class="fas fa-user-plus"></i><span>Enroll Worker 4A</span></a>
            <a href="training_request.php" class="action-tile"><i class="fas fa-calendar-check"></i><span>Request Training</span></a>
            <a href="book_safety_training.php" class="action-tile action-tile-primary"><i class="fas fa-book-medical"></i><span>Book Safety Training</span></a>
            <a href="gatepass-6a.php" class="action-tile"><i class="fas fa-id-badge"></i><span>Gate Pass 6A</span></a>
            <a href="documents.php" class="action-tile"><i class="fas fa-upload"></i><span>Upload Documents</span></a>
            <a href="compliance.php" class="action-tile"><i class="fas fa-file-upload"></i><span>ESI/PF/KLWF</span></a>
            <a href="../reports/muster_roll.php" class="action-tile"><i class="fas fa-clipboard-list"></i><span>Muster Roll</span></a>
            <a href="../worker/block_unblock.php" class="action-tile"><i class="fas fa-user-slash"></i><span>Block/Unblock</span></a>
          </div>
        </div>
      </div>

      <div class="card glass">
        <div class="card-header"><div class="card-title"><i class="fas fa-clock-rotate-left"></i> Recent Enrollments</div></div>
        <div class="card-body">
          <div class="recent-list">
            <?php foreach ($recentWorkers as $worker):
              $workerStatus = strtolower((string)($worker['training_status'] ?? 'pending'));
              $workerBadge = in_array($workerStatus, ['pass','passed','completed','training_passed','qualified'], true)
                  ? 'badge-success'
                  : (in_array($workerStatus, ['fail','failed','training_failed'], true) ? 'badge-danger' : 'badge-warning');
            ?>
              <div class="recent-item">
                <div>
                  <div style="font-weight:700;font-size:13px;"><?= htmlspecialchars($worker['worker_name'] ?? 'Worker') ?></div>
                  <div style="font-size:11px;color:var(--gray-500);">Temp ID: <?= htmlspecialchars((string)($worker['temp_ref'] ?? '-')) ?></div>
                </div>
                <span class="badge <?= $workerBadge ?>"><?= htmlspecialchars(strtoupper(str_replace('_', ' ', $workerStatus ?: 'pending'))) ?></span>
              </div>
            <?php endforeach; ?>
            <?php if (empty($recentWorkers)): ?>
              <div style="text-align:center;color:var(--gray-500);padding:24px 0;">No worker enrollment found yet.</div>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>

    <div class="alert alert-info" style="margin-top:20px;">
      <i class="fas fa-info-circle"></i>
      <div><strong>Critical rule:</strong> Gate pass opens only after safety training is passed and Annexure 6A documents are uploaded. Rejected items must be corrected and resubmitted.</div>
    </div>

    <style>
      .contractor-flow { display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px; }
      .flow-step { min-height:116px;display:flex;align-items:flex-start;gap:12px;padding:14px;border:1px solid var(--gray-200);border-radius:8px;background:var(--white);text-decoration:none;color:inherit;box-shadow:var(--shadow-sm);transition:.2s; }
      .flow-step:hover { transform:translateY(-2px);box-shadow:var(--shadow); }
      .flow-step.locked { opacity:.68; }
      .flow-index { width:26px;height:26px;border-radius:50%;background:var(--gray-100);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:12px;color:var(--gray-600);flex-shrink:0; }
      .flow-icon { width:38px;height:38px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:rgba(26,60,110,.1);color:var(--primary);flex-shrink:0; }
      .flow-body { flex:1;min-width:0; }
      .flow-title { font-weight:800;font-size:14px;margin-bottom:3px; }
      .flow-detail { font-size:12px;color:var(--gray-500);line-height:1.35; }
      .flow-step.done .flow-index { background:var(--success);color:#fff; }
      .flow-step.active .flow-index { background:var(--warning);color:#fff; }
      .action-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px; }
      .action-tile { min-height:74px;border:1px solid var(--gray-200);border-radius:8px;background:var(--gray-50);display:flex;align-items:center;gap:10px;padding:12px;text-decoration:none;color:inherit;font-weight:700;font-size:13px; }
      .action-tile:hover { background:var(--gray-100); }
      .action-tile i { width:32px;height:32px;border-radius:8px;background:#fff;color:var(--primary);display:flex;align-items:center;justify-content:center;box-shadow:var(--shadow-sm); }
      .action-tile-primary { background:#eef2ff;border-color:#c7d2fe; }
      .action-tile-primary i { background:#4f46e5;color:#fff; }
      .recent-list { display:flex;flex-direction:column;gap:10px; }
      .recent-item { display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 0;border-bottom:1px solid var(--gray-200); }
      .recent-item:last-child { border-bottom:0; }
      .safety-booking-panel { margin-top:20px;border:1px solid var(--gray-200);border-radius:8px;background:#fff;overflow:hidden; }
      .safety-booking-head { display:flex;justify-content:space-between;align-items:flex-start;gap:14px;padding:16px 18px;background:#f8fafc;border-bottom:1px solid var(--gray-200); }
      .safety-booking-head h3 { margin:0;display:flex;align-items:center;gap:8px;font-size:16px;color:#111827; }
      .safety-booking-head p { margin:5px 0 0;color:var(--gray-500);font-size:12px;line-height:1.4; }
      .safety-booking-actions { display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end; }
      .booking-snapshot { display:grid;grid-template-columns:repeat(4,minmax(145px,1fr));gap:12px;padding:16px 18px;border-bottom:1px solid var(--gray-200); }
      .booking-stat { border:1px solid #dbeafe;background:#eff6ff;border-radius:8px;padding:12px; }
      .booking-stat span { display:block;font-size:11px;color:#1e40af;font-weight:800;text-transform:uppercase;line-height:1.25; }
      .booking-stat strong { display:block;font-size:25px;color:#111827;margin-top:6px; }
      .booking-stat.danger { background:#fef2f2;border-color:#fecaca; }
      .booking-stat.danger span { color:#b91c1c; }
      .safety-dashboard-grid { display:grid;grid-template-columns:minmax(0,1.2fr) minmax(320px,.8fr);gap:16px;padding:16px 18px;align-items:start; }
      .safety-mini-card { border:1px solid var(--gray-200);border-radius:8px;background:#fff;overflow:hidden; }
      .mini-head { display:flex;justify-content:space-between;align-items:center;gap:10px;padding:12px 14px;background:#f9fafb;border-bottom:1px solid var(--gray-200); }
      .mini-head strong { display:flex;align-items:center;gap:8px;font-size:13px;color:#111827; }
      .mini-head a, .mini-head span { font-size:11px;color:var(--gray-500);font-weight:700;text-decoration:none; }
      .compact-table th, .compact-table td { padding:10px 12px;font-size:12px; }
      .training-status-list { padding:2px 14px; }
      .retest-box { margin:0 18px 18px;border:1px solid #fde68a;background:#fffbeb;border-radius:8px;overflow:hidden; }
      .retest-box .mini-head { background:#fef3c7;border-bottom-color:#fde68a; }
      .retest-list { display:flex;flex-direction:column;gap:0;padding:0 14px; }
      .retest-item { display:grid;grid-template-columns:minmax(170px,1fr) auto auto auto;align-items:center;gap:10px;padding:12px 0;border-bottom:1px solid #fde68a; }
      .retest-item:last-child { border-bottom:0; }
      .retest-item strong { display:block;font-size:13px;color:#111827; }
      .retest-item span:not(.badge) { display:block;font-size:11px;color:var(--gray-500);margin-top:2px; }
      .timeline-container { display:flex; align-items:center; justify-content:space-between; max-width:600px; margin:0 auto; padding: 20px 0; }
      .timeline-step { display:flex; flex-direction:column; align-items:center; position:relative; z-index:2; opacity:0.4; }
      .timeline-step.active { opacity:1; }
      .step-icon { width:45px; height:45px; border-radius:50%; background:var(--white); border:2px solid var(--gray-300); display:flex; align-items:center; justify-content:center; font-size:18px; color:var(--gray-400); margin-bottom:10px; transition:.3s; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
      .timeline-step.active .step-icon { border-color:var(--primary); color:var(--primary); }
      .step-label { font-size:12px; font-weight:600; color:var(--gray-600); }
      .timeline-line { flex:1; height:4px; background:var(--gray-200); margin:0 15px; position:relative; top:-12px; border-radius:2px; }
      .timeline-line.active { background:var(--primary); }
      
      @media (max-width: 1100px) {
        .booking-snapshot { grid-template-columns:repeat(2,minmax(145px,1fr)); }
        .safety-dashboard-grid { grid-template-columns:1fr; }
      }
      @media (max-width: 900px) { .content-header, .card-header, .safety-booking-head { align-items:flex-start; } }
      @media (max-width: 640px) {
        .booking-snapshot { grid-template-columns:1fr; }
        .safety-booking-head { flex-direction:column; }
        .safety-booking-actions .btn { width:100%;justify-content:center; }
        .retest-item { grid-template-columns:1fr;align-items:flex-start; }
      }

    </style>
    <?php
}

renderLayout("Contractor Workflow Dashboard", 'renderContent', $role, $name);
