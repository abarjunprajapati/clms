<?php
require_once '../../include/auth.php';
checkAuth(['contractor', 'customer']);
include '../../include/config.php';
include '../../include/customer_portal_context.php';
include '../../include/layout.php';
require_once '../../include/safety_training_control.php';
require_once '../../include/payment_flow.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';
$user_id = (int)($_SESSION['user_id'] ?? 0);
clms_get_portal_contractor($conn);
clms_safety_ensure_control_schema($conn);

function contractorBookTableExists($conn, $table) {
    $safeTable = mysqli_real_escape_string($conn, $table);
    $res = mysqli_query($conn, "SHOW TABLES LIKE '$safeTable'");
    return $res && mysqli_num_rows($res) > 0;
}

function contractorBookColumnExists($conn, $table, $column) {
    if (!contractorBookTableExists($conn, $table)) return false;
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$safeColumn'");
    return $res && mysqli_num_rows($res) > 0;
}

function contractorBookEnsureTrainingRequestColumns($conn) {
    clms_ensure_payment_flow($conn);
    clms_safety_ensure_column($conn, 'workmen', 'work_order_source', 'VARCHAR(20) NULL');
    clms_safety_ensure_column($conn, 'training_requests', 'preferred_shift', "VARCHAR(20) DEFAULT 'morning'");
    @mysqli_query($conn, "ALTER TABLE training_requests MODIFY COLUMN preferred_shift VARCHAR(20) DEFAULT 'morning'");
    foreach ([
        'contractor_confirmed' => 'TINYINT(1) DEFAULT 0',
        'scheduled_session_id' => 'INT NULL',
        'batch_number' => 'VARCHAR(100) NULL',
        'scheduled_date' => 'DATE NULL',
        'scheduled_shift' => 'VARCHAR(20) NULL',
        'scheduled_venue' => 'VARCHAR(300) NULL',
        'scheduled_time' => 'VARCHAR(20) NULL',
        'instructor' => 'VARCHAR(150) NULL',
        'scheduled_by' => 'INT NULL',
        'updated_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    ] as $column => $definition) {
        clms_safety_ensure_column($conn, 'training_requests', $column, $definition);
    }
}

function contractorBookNormalizeShift($value) {
    $value = strtolower(trim((string)$value));
    if ($value === 'an' || $value === 'pm' || $value === 'afternoon' || $value === 'evening') {
        return 'evening';
    }
    return 'morning';
}

function renderContent() {
    global $conn, $user_id;
    contractorBookEnsureTrainingRequestColumns($conn);

    $contractor = db_single($conn, "SELECT id, contractor_name FROM contractors WHERE user_id = ? LIMIT 1", 'i', [$user_id]);
    $contractorId = (int)($contractor['id'] ?? 0);
    $preselectWorkerId = (int)($_GET['worker_id'] ?? 0);
    $message = '';
    $messageType = 'success';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validate_csrf()) {
            http_response_code(403);
            $message = 'Session security token expired. Please refresh and try again.';
            $messageType = 'error';
        }
        $workerIds = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['worker_ids'] ?? [])))));
        if (!$workerIds && !empty($_POST['worker_id'])) {
            $workerIds = [(int)$_POST['worker_id']];
        }
        $batchId = (int)($_POST['batch_id'] ?? 0);

        $batch = db_single($conn, "SELECT * FROM training_class_batches WHERE id = ? LIMIT 1", 'i', [$batchId]);
        $preferredShift = $batch ? contractorBookNormalizeShift($batch['session_name'] ?? 'morning') : 'morning';
        $selectedCount = count($workerIds);
        $capacityInfo = $batch ? clms_safety_batch_capacity_summary($conn, $batch) : ['total' => 0];
        $alreadySelected = $batch ? db_count($conn, "SELECT COUNT(*) FROM training_batch_workers WHERE batch_id = ? AND ticked = 1", 'i', [$batchId]) : 0;
        $remainingSeats = $batch ? max(0, (int)$capacityInfo['total'] - (int)$alreadySelected) : 0;

        if ($messageType === 'error' && $message) {
            // keep the form visible below
        } elseif (!$contractorId || !$batch || !$workerIds) {
            $message = 'Please select a valid training batch and at least one worker.';
            $messageType = 'error';
        } elseif ($selectedCount > $remainingSeats) {
            $message = 'Maximum seat limit exceeded. Only ' . $remainingSeats . ' seat(s) are available in this batch.';
            $messageType = 'error';
        } else {
            $saved = 0;
            $skippedPayment = 0;
            $requestIds = [];
            foreach ($workerIds as $workerId) {
                $worker = db_single(
                    $conn,
                    "SELECT id, name, safety_language, training_status, safety_training_status, training_valid_till, work_order_source FROM workmen WHERE id = ? AND contractor_id = ? LIMIT 1",
                    'ii',
                    [$workerId, $contractorId]
                );
                if (!$worker) continue;
                if (strtoupper(trim((string)($worker['work_order_source'] ?? ''))) === 'PWO') {
                    $paid = db_single(
                        $conn,
                        "SELECT pr.id
                         FROM training_payment_request_workers pw
                         JOIN training_payment_requests pr ON pr.id = pw.payment_request_id
                         WHERE pw.workman_id = ?
                           AND pr.status = 'paid'
                         LIMIT 1",
                        'i',
                        [$workerId]
                    );
                    if (!$paid) {
                        $skippedPayment++;
                        continue;
                    }
                }
                if (strtolower(trim((string)($worker['safety_language'] ?: $batch['language_name']))) !== strtolower(trim((string)$batch['language_name']))) {
                    continue;
                }
                $workerTrainingStatus = strtolower((string)($worker['training_status'] ?? ''));
                $workerSafetyStatus = strtolower((string)($worker['safety_training_status'] ?? ''));
                $validTill = trim((string)($worker['training_valid_till'] ?? ''));
                $isTrainingValid = in_array($workerTrainingStatus, ['pass','passed','training_passed','qualified','completed'], true)
                    || in_array($workerSafetyStatus, ['training_passed','passed','pass','1'], true);
                if ($isTrainingValid && ($validTill === '' || strtotime($validTill) >= strtotime(date('Y-m-d')))) {
                    continue;
                }
                $active = db_single(
                    $conn,
                    "SELECT id, status FROM training_requests
                     WHERE workman_id = ?
                       AND LOWER(COALESCE(status, 'pending')) IN ('welfare_pending','pending','failed','fail','absent','training_failed','rejected','correction_required')
                     ORDER BY id DESC LIMIT 1",
                    'i',
                    [$workerId]
                );
                if ($active) {
                    db_execute(
                        $conn,
                        "UPDATE training_requests
                         SET training_type = ?, preferred_date = ?, preferred_shift = ?, remarks = ?, source = 'contractor_later_booking', updated_at = NOW()
                         WHERE id = ?",
                        'ssssi',
                        [
                            (string)$batch['training_type'],
                            (string)$batch['training_date'],
                            $preferredShift,
                            'Contractor selected this scheduled safety training batch from Book Safety Training.',
                            (int)$active['id']
                        ]
                    );
                    $requestIds[] = (int)$active['id'];
                } else {
                    db_execute(
                        $conn,
                        "INSERT INTO training_requests
                         (workman_id, contractor_id, training_type, requested_date, preferred_date, preferred_shift, remarks, source, requested_by, status, created_at, updated_at)
                         VALUES (?, ?, ?, CURDATE(), ?, ?, ?, 'contractor_later_booking', ?, 'pending', NOW(), NOW())",
                        'iissssi',
                        [
                            $workerId,
                            $contractorId,
                            (string)$batch['training_type'],
                            (string)$batch['training_date'],
                            $preferredShift,
                            'Contractor selected this scheduled safety training batch from Book Safety Training.',
                            $user_id
                        ]
                    );
                    $requestIds[] = (int)mysqli_insert_id($conn);
                }
                db_execute(
                    $conn,
                    "UPDATE workmen
                     SET training_status = 'pending', safety_training_status = 'PENDING_TRAINING',
                         safety_language = COALESCE(NULLIF(safety_language, ''), ?),
                         execution_training_status = 'pending_eo',
                         execution_training_remarks = 'Safety seat booking submitted. Waiting for Executing Officer approval.'
                     WHERE id = ?",
                    'si',
                    [(string)$batch['language_name'], $workerId]
                );
                $saved++;
            }
            if ($requestIds) {
                $bookingResult = clms_safety_add_requests_to_batch($conn, $batchId, $requestIds, $user_id);
                $message = 'Safety training booked for ' . $saved . ' worker(s) in batch ' . $bookingResult['batch_number'] . '.';
            } else {
                $message = $skippedPayment > 0
                    ? 'Please complete Safety Fee Payment before Safety Training & Seat Booking for PWO worker(s).'
                    : 'No eligible worker found for the selected batch language/status.';
                $messageType = 'error';
            }
        }
    }

    $batches = db_fetch_all($conn, "
        SELECT b.*,
               COALESCE(x.selected_count, 0) AS selected_count,
               0 AS seats_available
        FROM training_class_batches b
        LEFT JOIN (
            SELECT batch_id, COUNT(*) AS selected_count
            FROM training_batch_workers
            WHERE ticked = 1
            GROUP BY batch_id
        ) x ON x.batch_id = b.id
        WHERE b.training_date >= CURDATE()
          AND LOWER(COALESCE(b.status, 'draft')) IN ('draft','open','scheduled')
        ORDER BY b.training_date ASC, b.session_name ASC, b.id ASC
    ");
    foreach ($batches as &$batchRow) {
        $batchCapacity = clms_safety_batch_capacity_summary($conn, $batchRow);
        $batchRow['capacity'] = (int)$batchCapacity['total'];
        $batchRow['regular_seats'] = (int)$batchCapacity['regular'];
        $batchRow['emergency_seats'] = (int)$batchCapacity['emergency'];
        $batchRow['seats_available'] = max(0, (int)$batchCapacity['total'] - (int)($batchRow['selected_count'] ?? 0));
    }
    unset($batchRow);

    $workers = $contractorId ? db_fetch_all($conn, "
        SELECT w.id, w.name, w.aadhaar, w.temp_id, w.safety_language, w.training_status, w.safety_training_status, w.training_valid_till,
               w.work_order_source,
               CASE
                 WHEN UPPER(COALESCE(w.work_order_source, '')) = 'PWO'
                      AND NOT EXISTS (
                        SELECT 1
                        FROM training_payment_request_workers pw
                        JOIN training_payment_requests pr ON pr.id = pw.payment_request_id
                        WHERE pw.workman_id = w.id
                          AND pr.status = 'paid'
                      )
                 THEN 1 ELSE 0
               END AS payment_pending,
               tr.id AS active_request_id, tr.status AS request_status, tr.batch_number, tr.scheduled_date,
               tr.scheduled_shift, tr.contractor_confirmed,
               COALESCE(attempts.attempt_count, 0) AS attempt_count
        FROM workmen w
        LEFT JOIN training_requests tr ON tr.id = (
            SELECT tr2.id
            FROM training_requests tr2
            WHERE tr2.workman_id = w.id
              AND LOWER(COALESCE(tr2.status, 'pending')) IN ('welfare_pending','pending','scheduled','contractor_confirmed','passed')
            ORDER BY tr2.id DESC
            LIMIT 1
        )
        LEFT JOIN (
            SELECT workman_id, COUNT(*) AS attempt_count
            FROM training_requests
            WHERE contractor_id = ?
              AND LOWER(COALESCE(status, 'pending')) IN ('failed','fail','absent','passed')
              AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY workman_id
        ) attempts ON attempts.workman_id = w.id
        WHERE w.contractor_id = ?
          AND LOWER(COALESCE(w.status, 'pending')) NOT IN ('deleted','removed','blocked')
          AND (
              tr.id IS NULL
              OR LOWER(COALESCE(w.training_status, 'pending')) IN ('pending','training_pending','training_failed','fail','failed','absent','expired','training_expired')
              OR LOWER(COALESCE(w.safety_training_status, 'pending')) IN ('pending_training','pending','failed','expired','absent')
              OR (w.training_valid_till IS NOT NULL AND w.training_valid_till < CURDATE())
          )
          AND NOT (
              LOWER(COALESCE(w.training_status, '')) IN ('pass','passed','training_passed','qualified','completed')
              AND (w.training_valid_till IS NULL OR w.training_valid_till >= CURDATE())
          )
          AND NOT (
              LOWER(COALESCE(tr.status, '')) IN ('scheduled','contractor_confirmed','passed')
              AND LOWER(COALESCE(w.training_status, 'pending')) NOT IN ('training_failed','fail','failed','absent')
          )
        ORDER BY COALESCE(tr.requested_date, DATE(w.created_at), CURDATE()) ASC, w.id ASC
    ", 'ii', [$contractorId, $contractorId]) : [];

    $pendingBookings = count(array_filter($workers, function($worker) {
        return in_array(strtolower((string)($worker['request_status'] ?? $worker['training_status'] ?? 'pending')), ['pending','welfare_pending','training_pending'], true);
    }));
    $paymentPendingWorkers = count(array_filter($workers, function($worker) {
        return (int)($worker['payment_pending'] ?? 0) === 1;
    }));
    $retestWorkers = count(array_filter($workers, function($worker) {
        $status = strtolower((string)($worker['request_status'] ?? $worker['training_status'] ?? ''));
        return in_array($status, ['failed','fail','absent','training_failed'], true);
    }));
    $availableSeats = array_sum(array_map(function($batch) {
        return max(0, (int)($batch['seats_available'] ?? 0));
    }, $batches));
    $batchLanguages = array_values(array_unique(array_filter(array_map(function($batch) {
        return trim((string)($batch['language_name'] ?? ''));
    }, $batches))));
    ?>
    <style>
      .safety-book-page{display:grid;gap:14px}
      .safety-book-card{background:#fff;border:1px solid #d7dee8;border-radius:8px;padding:16px;box-shadow:var(--shadow-sm)}
      .booking-title{display:flex;align-items:center;gap:8px;font-size:17px;font-weight:800;margin:0 0 14px;color:#0f172a}
      .booking-top-grid{display:grid;grid-template-columns:1fr 1fr 1fr 160px 180px;gap:14px;align-items:end}
      .booking-top-grid .form-control[readonly]{background:#f8fafc;color:#334155}
      .selected-box{border:1px solid #cbd5e1;border-radius:8px;padding:12px;margin-top:14px}
      .selected-box h3{font-size:14px;margin:0 0 10px;color:#0f172a}
      .selected-table{width:100%;border-collapse:collapse}
      .selected-table th,.selected-table td{border-bottom:1px solid #e5e7eb;padding:8px 10px;font-size:12px;text-align:left}
      .selected-table th{background:#f8fafc;font-weight:800;color:#475569}
      .selected-actions{display:flex;justify-content:flex-end;margin-top:10px}
      .search-panel{border:1px solid #d7dee8;border-radius:8px;padding:14px;background:#fff}
      .search-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;align-items:end}
      .worker-picker-table{width:100%;border-collapse:collapse}
      .worker-picker-table th,.worker-picker-table td{padding:9px 10px;border-bottom:1px solid #e5e7eb;font-size:12px;text-align:left}
      .worker-picker-table th{background:#f8fafc;color:#334155;font-weight:800}
      .worker-picker-table input[type="checkbox"]{width:17px;height:17px}
      .selection-note{font-size:12px;color:#92400e;line-height:1.45;margin-top:10px}
      .payment-required-note{display:flex;justify-content:space-between;align-items:center;gap:12px;border:1px solid #fbbf24;background:#fffbeb;color:#92400e;border-radius:8px;padding:12px 14px;margin:0 0 14px;font-size:13px;font-weight:800;flex-wrap:wrap}
      .worker-payment-lock{display:inline-flex;align-items:center;gap:6px;color:#92400e;font-weight:800;font-size:12px}
      .seat-chip{display:inline-flex;align-items:center;justify-content:center;min-width:38px;border:1px solid #bfdbfe;background:#eff6ff;color:#1d4ed8;border-radius:6px;padding:8px 10px;font-weight:800}
      @media(max-width:1100px){.booking-top-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.search-grid{grid-template-columns:1fr}}
      @media(max-width:680px){.booking-top-grid{grid-template-columns:1fr}}

      .booking-shell{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:16px;align-items:start}
      .booking-card{background:#fff;border:1px solid var(--border);border-radius:8px;padding:16px;box-shadow:var(--shadow)}
      .booking-stats{display:grid;grid-template-columns:repeat(4,minmax(140px,1fr));gap:12px;margin-bottom:16px}
      .booking-stat{border:1px solid #dbeafe;background:#eff6ff;border-radius:8px;padding:12px}
      .booking-stat span{display:block;font-size:11px;color:#1d4ed8;font-weight:800;text-transform:uppercase}
      .booking-stat strong{display:block;font-size:24px;color:#0f172a;margin-top:4px}
      .booking-layout{display:grid;grid-template-columns:minmax(0,1fr);gap:14px}
      .batch-select-row{display:grid;grid-template-columns:minmax(220px,1fr) minmax(220px,.75fr);gap:12px;align-items:end;margin-bottom:12px}
      .booking-summary{display:flex;gap:10px;flex-wrap:wrap;margin-top:10px}
      .booking-pill{background:#eef4ff;border:1px solid #c7ddff;border-radius:8px;padding:8px 10px;font-weight:700;color:#1e40af}
      .batch-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;margin-top:12px}
      .batch-card{position:relative;border:1px solid #dbeafe;background:#f8fbff;border-radius:8px;padding:12px;cursor:pointer;transition:.18s}
      .batch-card:hover{border-color:#93c5fd;box-shadow:0 8px 20px rgba(37,99,235,.08)}
      .batch-card.active{border-color:#2563eb;background:#eff6ff;box-shadow:0 0 0 3px rgba(37,99,235,.12)}
      .batch-card input{position:absolute;top:12px;right:12px;width:17px;height:17px}
      .batch-date{font-weight:800;color:#0f172a;font-size:14px;padding-right:28px}
      .batch-meta{display:flex;gap:6px;flex-wrap:wrap;margin-top:8px}
      .mini-badge{display:inline-flex;align-items:center;gap:4px;border-radius:999px;padding:4px 8px;font-size:11px;font-weight:800;background:#fff;border:1px solid #e2e8f0;color:#475569}
      .selected-workers{border:1px solid #bfdbfe;background:#eff6ff;border-radius:8px;padding:12px;margin:14px 0}
      .selected-workers h3{margin:0 0 8px;font-size:14px;color:#1e3a8a}
      .selected-list{display:flex;gap:8px;flex-wrap:wrap}
      .selected-chip{background:#fff;border:1px solid #c7d2fe;border-radius:999px;padding:5px 10px;font-size:12px;font-weight:700;color:#1e40af}
      .worker-table input[type="checkbox"]{width:16px;height:16px}
      .worker-main{display:flex;align-items:center;gap:10px}
      .worker-avatar{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#e0e7ff;color:#4338ca;font-weight:800;font-size:12px;flex-shrink:0}
      .status-pill{display:inline-flex;align-items:center;border-radius:999px;padding:4px 8px;font-size:11px;font-weight:800;text-transform:uppercase}
      .status-pending{background:#fef3c7;color:#92400e}
      .status-scheduled{background:#dbeafe;color:#1d4ed8}
      .status-pass{background:#dcfce7;color:#166534}
      .status-fail{background:#fee2e2;color:#991b1b}
      .booking-side{position:sticky;top:16px}
      .side-step{display:flex;gap:10px;padding:10px 0;border-bottom:1px solid #e5e7eb}
      .side-step:last-child{border-bottom:0}
      .side-step i{width:28px;height:28px;border-radius:8px;background:#eef2ff;color:#4f46e5;display:flex;align-items:center;justify-content:center;flex-shrink:0}
      .side-step strong{display:block;font-size:12px;color:#111827}
      .side-step span{display:block;font-size:11px;color:#64748b;margin-top:2px;line-height:1.35}
      @media (max-width:1100px){.booking-shell{grid-template-columns:1fr}.booking-side{position:static}.booking-stats{grid-template-columns:repeat(2,minmax(140px,1fr))}}
      @media (max-width:760px){.batch-select-row,.booking-stats{grid-template-columns:1fr}}
    </style>

    <div class="page-header">
      <h2 class="page-title"><i class="fas fa-calendar-check" style="color:#2563eb;margin-right:10px;"></i>Book Safety Training</h2>
      <div class="page-subtitle">Use this when entitlement was saved without booking, training was missed, or validity expired.</div>
    </div>

    <?php if ($message): ?>
      <div class="alert alert-<?= $messageType === 'error' ? 'danger' : 'success' ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($paymentPendingWorkers > 0): ?>
      <div class="payment-required-note">
        <span><i class="fas fa-credit-card"></i> Pay Safety Fee first. Safety Training & Seat Booking will open after payment.</span>
        <a class="btn btn-sm btn-warning" href="../payment.php"><i class="fas fa-credit-card"></i> Safety Fee Payment</a>
      </div>
    <?php endif; ?>

    <div class="safety-book-page">
      <form method="POST" id="bookSafetyForm" class="safety-book-card">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="batch_id" id="batchSelect" required>

        <h3 class="booking-title"><i class="fas fa-calendar-check"></i> Book Safety Training</h3>

        <div class="booking-top-grid">
          <div>
            <label class="form-label">Lang</label>
            <select id="languageSelect" class="form-control" required>
              <option value="">Select</option>
              <?php foreach ($batchLanguages as $language): ?>
                <option value="<?= htmlspecialchars($language, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($language) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="form-label">Date</label>
            <select id="trainingDateSelect" class="form-control" required>
              <option value="">Select</option>
            </select>
          </div>
          <div>
            <label class="form-label">Session</label>
            <input type="text" id="sessionDisplay" class="form-control" readonly>
          </div>
          <div>
            <label class="form-label">Seat Availability</label>
            <div class="seat-chip" id="seatAvailability">0</div>
          </div>
          <div>
            <label class="form-label">Batch No</label>
            <input type="text" id="batchNumberDisplay" class="form-control" readonly>
          </div>
        </div>

        <div class="selected-box">
          <h3>Workmen Select For Training</h3>
          <div class="table-responsive">
            <table class="selected-table">
              <thead><tr><th>Sl No</th><th>Aadhaar No</th><th>Name</th></tr></thead>
              <tbody id="selectedWorkerRows">
                <tr><td colspan="3" style="text-align:center;color:#64748b;">No workmen selected.</td></tr>
              </tbody>
            </table>
          </div>
          <div class="selected-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit</button>
          </div>
        </div>

        <div class="search-panel" style="margin-top:14px;">
          <div class="search-grid">
            <div>
              <label class="form-label">Name</label>
              <input type="text" id="workerNameSearch" class="form-control" placeholder="Search name">
            </div>
            <div>
              <label class="form-label">Aadhaar No</label>
              <input type="text" id="workerAadhaarSearch" class="form-control" placeholder="Search Aadhaar">
            </div>
            <div>
              <label class="form-label">Workmen / Temp ID</label>
              <input type="text" id="workerTempSearch" class="form-control" placeholder="Search entitlement no">
            </div>
          </div>
        </div>

        <div class="safety-book-card" style="margin-top:14px;padding:0;">
          <div class="table-responsive">
            <table class="worker-picker-table" id="workerTable">
              <thead>
                <tr>
                  <th>Sl No</th>
                  <th>Aadhaar No</th>
                  <th>Name</th>
                  <th>Entitlement No</th>
                  <th>Option To Select</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$workers): ?>
                  <tr><td colspan="5" style="text-align:center;color:#64748b;">No worker is pending for safety training booking.</td></tr>
                <?php endif; ?>
                <?php foreach ($workers as $idx => $worker):
                  $checked = $preselectWorkerId && (int)$worker['id'] === $preselectWorkerId;
                  $safeLanguage = $worker['safety_language'] ?: 'Malayalam';
                  $entitlement = $worker['temp_id'] ?: ('W-' . $worker['id']);
                  $paymentPending = (int)($worker['payment_pending'] ?? 0) === 1;
                ?>
                  <tr data-worker-row
                      data-payment-pending="<?= $paymentPending ? '1' : '0' ?>"
                      data-language="<?= htmlspecialchars(strtolower($safeLanguage), ENT_QUOTES, 'UTF-8') ?>"
                      data-name="<?= htmlspecialchars(strtolower($worker['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                      data-aadhaar="<?= htmlspecialchars(strtolower($worker['aadhaar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                      data-temp="<?= htmlspecialchars(strtolower($entitlement), ENT_QUOTES, 'UTF-8') ?>">
                    <td><?= $idx + 1 ?></td>
                    <td><code><?= htmlspecialchars($worker['aadhaar'] ?? '') ?></code></td>
                    <td><?= htmlspecialchars($worker['name'] ?? '') ?></td>
                    <td><code><?= htmlspecialchars($entitlement) ?></code></td>
                    <td>
                      <?php if ($paymentPending): ?>
                        <span class="worker-payment-lock"><i class="fas fa-lock"></i> Pay Safety Fee first</span>
                      <?php else: ?>
                        <input type="checkbox"
                               class="worker-check"
                               name="worker_ids[]"
                               value="<?= (int)$worker['id'] ?>"
                               data-worker-name="<?= htmlspecialchars($worker['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               data-worker-aadhaar="<?= htmlspecialchars($worker['aadhaar'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               <?= $checked ? 'checked' : '' ?>>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="selection-note">
            Note: Checkboxes auto tick by default as per seat availability on selected date/session and selected language. You can change selection if needed.
          </div>
        </div>
      </form>
    </div>

    <script>
      const batches = <?= json_encode($batches, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
      const batchSelect = document.getElementById('batchSelect');
      const languageSelect = document.getElementById('languageSelect');
      const trainingDateSelect = document.getElementById('trainingDateSelect');
      const seatAvailability = document.getElementById('seatAvailability');
      const sessionDisplay = document.getElementById('sessionDisplay');
      const batchNumberDisplay = document.getElementById('batchNumberDisplay');
      const bookSafetyForm = document.getElementById('bookSafetyForm');
      const selectedWorkerRows = document.getElementById('selectedWorkerRows');
      const workerNameSearch = document.getElementById('workerNameSearch');
      const workerAadhaarSearch = document.getElementById('workerAadhaarSearch');
      const workerTempSearch = document.getElementById('workerTempSearch');
      const preselectWorkerId = <?= (int)$preselectWorkerId ?>;
      let userTouchedSelection = preselectWorkerId > 0;

      function norm(value) {
        return String(value || '').trim().toLowerCase();
      }

      function remainingSeats(batch) {
        return Math.max(0, Number(batch?.seats_available ?? (Number(batch?.capacity || 0) - Number(batch?.selected_count || 0))));
      }

      function matchingBatches() {
        const language = norm(languageSelect.value);
        return batches.filter(batch => !language || norm(batch.language_name) === language);
      }

      function populateDates() {
        const rows = matchingBatches();
        trainingDateSelect.innerHTML = '<option value="">Select</option>' + rows.map(batch => {
          const label = `${batch.training_date || ''}${batch.session_name ? ' - ' + batch.session_name : ''}`;
          return `<option value="${batch.id}">${escapeHtml(label)}</option>`;
        }).join('');
        if (rows.length) {
          trainingDateSelect.value = String(rows[0].id);
        }
        applySelectedBatch(true);
      }

      function selectedBatch() {
        const id = Number(trainingDateSelect.value || 0);
        return batches.find(batch => Number(batch.id) === id) || null;
      }

      function applySelectedBatch(autoTick) {
        const batch = selectedBatch();
        batchSelect.value = batch ? batch.id : '';
        seatAvailability.textContent = batch ? remainingSeats(batch) : '0';
        sessionDisplay.value = batch?.session_name || '';
        batchNumberDisplay.value = batch?.batch_number || '';
        filterWorkers();
        if (autoTick) autoSelectWorkers();
        refreshSelectedWorkers();
      }

      function filterWorkers() {
        const language = norm(languageSelect.value);
        const nameTerm = norm(workerNameSearch.value);
        const aadhaarTerm = norm(workerAadhaarSearch.value);
        const tempTerm = norm(workerTempSearch.value);
        document.querySelectorAll('[data-worker-row]').forEach(row => {
          const visible = (!language || row.dataset.language === language)
            && (!nameTerm || row.dataset.name.includes(nameTerm))
            && (!aadhaarTerm || row.dataset.aadhaar.includes(aadhaarTerm))
            && (!tempTerm || row.dataset.temp.includes(tempTerm));
          row.style.display = visible ? '' : 'none';
          if (!visible && row.dataset.language !== language) {
            const checkbox = row.querySelector('.worker-check');
            if (checkbox) checkbox.checked = false;
          }
        });
      }

      function autoSelectWorkers() {
        const batch = selectedBatch();
        const limit = remainingSeats(batch);
        const language = norm(languageSelect.value);
        const rows = Array.from(document.querySelectorAll('[data-worker-row]'))
          .filter(row => row.dataset.language === language);
        rows.forEach(row => {
          const checkbox = row.querySelector('.worker-check');
          if (checkbox) checkbox.checked = false;
        });
        if (!batch || !limit) return;
        let selected = 0;
        if (preselectWorkerId) {
          const preselected = document.querySelector(`.worker-check[value="${preselectWorkerId}"]`);
          if (preselected && preselected.closest('[data-worker-row]')?.dataset.language === language) {
            preselected.checked = true;
            selected = 1;
          }
        }
        rows.forEach(row => {
          if (selected >= limit) return;
          const checkbox = row.querySelector('.worker-check');
          if (!checkbox || checkbox.checked) return;
          checkbox.checked = true;
          selected++;
        });
      }

      function refreshSelectedWorkers() {
        const selected = Array.from(document.querySelectorAll('.worker-check:checked'));
        if (!selected.length) {
          selectedWorkerRows.innerHTML = '<tr><td colspan="3" style="text-align:center;color:#64748b;">No workmen selected.</td></tr>';
          return;
        }
        selectedWorkerRows.innerHTML = selected.map((input, index) => {
          return `<tr><td>${index + 1}</td><td>${escapeHtml(input.dataset.workerAadhaar || '-')}</td><td>${escapeHtml(input.dataset.workerName || 'Worker')}</td></tr>`;
        }).join('');
      }

      function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
      }

      languageSelect?.addEventListener('change', () => {
        userTouchedSelection = false;
        populateDates();
      });
      trainingDateSelect?.addEventListener('change', () => {
        userTouchedSelection = false;
        applySelectedBatch(true);
      });
      [workerNameSearch, workerAadhaarSearch, workerTempSearch].forEach(input => input?.addEventListener('input', () => {
        filterWorkers();
        refreshSelectedWorkers();
      }));
      document.addEventListener('change', event => {
        if (!event.target.classList.contains('worker-check')) return;
        userTouchedSelection = true;
        const batch = selectedBatch();
        const limit = remainingSeats(batch);
        const selected = document.querySelectorAll('.worker-check:checked').length;
        if (limit && selected > limit) {
          event.target.checked = false;
          alert('Maximum seat limit exceeded. Seat availability is ' + limit + '.');
        }
        refreshSelectedWorkers();
      });
      bookSafetyForm?.addEventListener('submit', event => {
        const batch = selectedBatch();
        const remaining = remainingSeats(batch);
        const selected = document.querySelectorAll('.worker-check:checked').length;
        if (!batchSelect.value) {
          event.preventDefault();
          alert('Please select language, date and session.');
          return;
        }
        if (!selected) {
          event.preventDefault();
          alert('Please select at least one worker.');
          return;
        }
        if (selected > remaining) {
          event.preventDefault();
          alert('Maximum seat limit exceeded.');
        }
      });

      if (languageSelect && languageSelect.options.length > 1) {
        languageSelect.selectedIndex = 1;
      }
      populateDates();
    </script>
    <?php
}

renderLayout('Book Safety Training', 'renderContent', $role, $name);
?>
