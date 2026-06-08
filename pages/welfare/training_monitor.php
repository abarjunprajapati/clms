<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin', 'welfare_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';
include __DIR__ . '/../../include/training_flow.php';
include __DIR__ . '/../../include/payment_flow.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Admin';

function welfareTrainingTableExists($conn, $table) {
    $table = mysqli_real_escape_string($conn, $table);
    $res = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return $res && mysqli_num_rows($res) > 0;
}

function welfareTrainingColumnExists($conn, $table, $column) {
    $safeTable = str_replace('`', '``', $table);
    $column = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$column'");
    return $res && mysqli_num_rows($res) > 0;
}

function welfareTrainingEnsureColumn($conn, $table, $column, $definition) {
    if (!welfareTrainingTableExists($conn, $table) || welfareTrainingColumnExists($conn, $table, $column)) return;
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = str_replace('`', '``', $column);
    @mysqli_query($conn, "ALTER TABLE `$safeTable` ADD COLUMN `$safeColumn` $definition");
}

function welfareTrainingEnsureSchema($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS training_requests (
        id INT NOT NULL AUTO_INCREMENT,
        workman_id INT NOT NULL,
        contractor_id INT NOT NULL,
        training_type VARCHAR(100) NULL,
        requested_date DATE NULL,
        preferred_date DATE NULL,
        preferred_shift VARCHAR(20) DEFAULT 'morning',
        remarks TEXT NULL,
        source VARCHAR(30) NULL,
        requested_by INT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    foreach ([
        'training_type' => 'VARCHAR(100) NULL',
        'requested_date' => 'DATE NULL',
        'preferred_date' => 'DATE NULL',
        'preferred_shift' => "VARCHAR(20) DEFAULT 'morning'",
        'remarks' => 'TEXT NULL',
        'source' => 'VARCHAR(30) NULL',
        'requested_by' => 'INT NULL',
        'status' => "VARCHAR(50) DEFAULT 'pending'",
        'welfare_remarks' => 'TEXT NULL',
        'welfare_reviewed_by' => 'INT NULL',
        'welfare_reviewed_at' => 'DATETIME NULL',
        'updated_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    ] as $column => $definition) {
        welfareTrainingEnsureColumn($conn, 'training_requests', $column, $definition);
    }
    @mysqli_query($conn, "ALTER TABLE training_requests MODIFY COLUMN status VARCHAR(50) DEFAULT 'pending'");

    if (welfareTrainingTableExists($conn, 'workmen')) {
        foreach ([
            'training_status' => "VARCHAR(50) DEFAULT 'pending'",
            'safety_training_status' => "VARCHAR(50) DEFAULT 'PENDING_TRAINING'",
            'training_approval_doc' => 'VARCHAR(255) NULL',
            'executing_officer_code' => 'VARCHAR(50) NULL',
            'executing_officer_name' => 'VARCHAR(200) NULL',
            'execution_training_status' => "VARCHAR(30) DEFAULT 'pending'",
            'execution_training_reviewed_by' => 'BIGINT NULL',
        ] as $column => $definition) {
            welfareTrainingEnsureColumn($conn, 'workmen', $column, $definition);
        }
        @mysqli_query($conn, "ALTER TABLE workmen MODIFY COLUMN training_status VARCHAR(50) DEFAULT 'pending'");
        @mysqli_query($conn, "ALTER TABLE workmen MODIFY COLUMN safety_training_status VARCHAR(50) DEFAULT 'PENDING_TRAINING'");
        @mysqli_query($conn, "ALTER TABLE workmen MODIFY COLUMN execution_training_status VARCHAR(30) DEFAULT 'pending'");
    }
}

function welfareTrainingDocUrl($path) {
    $path = trim((string)$path);
    if ($path === '') return '';
    if (preg_match('~^https?://~i', $path)) return $path;
    return '../../uploads/workers/' . rawurlencode(basename($path));
}

function welfareTrainingSeedApprovedQueue($conn) {
    if (
        !welfareTrainingTableExists($conn, 'workmen') ||
        !welfareTrainingTableExists($conn, 'training_requests') ||
        !welfareTrainingTableExists($conn, 'training_payment_requests') ||
        !welfareTrainingTableExists($conn, 'training_payment_request_workers')
    ) {
        return;
    }

    @mysqli_query($conn, "
        INSERT INTO training_requests
            (workman_id, contractor_id, training_type, requested_date, preferred_date, preferred_shift, remarks, source, requested_by, status, created_at, updated_at)
        SELECT
            w.id,
            w.contractor_id,
            'Safety Induction',
            CURDATE(),
            CURDATE(),
            'morning',
            'Auto-created for Welfare check after Executing Officer approval.',
            'welfare_seed',
            COALESCE(w.execution_training_reviewed_by, 0),
            'welfare_pending',
            NOW(),
            NOW()
        FROM workmen w
        WHERE COALESCE(w.execution_training_status, '') = 'approved'
          AND COALESCE(w.execution_training_reviewed_by, 0) > 0
          AND COALESCE(w.contractor_id, 0) > 0
          AND EXISTS (
              SELECT 1
              FROM training_payment_request_workers pw
              JOIN training_payment_requests pr ON pr.id = pw.payment_request_id
              WHERE pw.workman_id = w.id
                AND pr.status = 'paid'
          )
          AND LOWER(TRIM(COALESCE(w.training_status, 'pending'))) IN ('', 'pending', 'training_pending', 'training_failed', 'fail', 'failed')
          AND NOT EXISTS (
              SELECT 1
              FROM training_requests tr
              WHERE tr.workman_id = w.id
                AND tr.status IN ('welfare_pending', 'pending', 'scheduled', 'contractor_confirmed', 'passed')
          )
          AND NOT EXISTS (
              SELECT 1
              FROM training_requests rejected
              WHERE rejected.workman_id = w.id
                AND rejected.status = 'welfare_rejected'
                AND rejected.welfare_reviewed_at IS NOT NULL
                AND rejected.welfare_reviewed_at >= COALESCE(w.updated_at, w.created_at, '1970-01-01')
          )
    ");

    @mysqli_query($conn, "
        UPDATE training_requests tr
        JOIN workmen w ON w.id = tr.workman_id
        SET tr.status = 'welfare_pending',
            tr.remarks = COALESCE(NULLIF(tr.remarks, ''), 'Corrected to Welfare queue after Executing Officer approval.'),
            tr.updated_at = NOW()
        WHERE LOWER(TRIM(COALESCE(tr.status, ''))) IN ('', 'pending')
          AND COALESCE(w.execution_training_status, '') = 'approved'
          AND COALESCE(w.execution_training_reviewed_by, 0) > 0
          AND EXISTS (
              SELECT 1
              FROM training_payment_request_workers pw
              JOIN training_payment_requests pr ON pr.id = pw.payment_request_id
              WHERE pw.workman_id = w.id
                AND pr.status = 'paid'
          )
          AND tr.welfare_reviewed_at IS NULL
          AND LOWER(TRIM(COALESCE(w.training_status, 'pending'))) IN ('', 'pending', 'training_pending', 'training_failed', 'fail', 'failed')
          AND NOT EXISTS (
              SELECT 1
              FROM training_requests decided
              WHERE decided.workman_id = w.id
                AND (
                    decided.status IN ('scheduled', 'contractor_confirmed', 'passed')
                    OR (
                        decided.status = 'welfare_rejected'
                        AND decided.welfare_reviewed_at IS NOT NULL
                        AND decided.welfare_reviewed_at >= tr.created_at
                    )
                )
          )
    ");

    @mysqli_query($conn, "
        UPDATE training_requests tr
        JOIN training_requests rejected
          ON rejected.workman_id = tr.workman_id
         AND rejected.status = 'welfare_rejected'
         AND rejected.welfare_reviewed_at IS NOT NULL
        SET tr.status = 'welfare_rejected',
            tr.welfare_remarks = COALESCE(NULLIF(tr.welfare_remarks, ''), rejected.welfare_remarks),
            tr.welfare_reviewed_by = COALESCE(tr.welfare_reviewed_by, rejected.welfare_reviewed_by),
            tr.welfare_reviewed_at = COALESCE(tr.welfare_reviewed_at, rejected.welfare_reviewed_at),
            tr.updated_at = NOW()
        WHERE LOWER(TRIM(COALESCE(tr.status, ''))) IN ('', 'pending', 'welfare_pending')
          AND tr.created_at <= rejected.welfare_reviewed_at
    ");
}

function renderContent() {
    global $conn;
    welfareTrainingEnsureSchema($conn);
    clms_release_all_paid_training_payments($conn, (int)($_SESSION['user_id'] ?? 0));
    clms_training_seed_approved_queue($conn);
    welfareTrainingSeedApprovedQueue($conn);

    $queue = db_fetch_all($conn, "
        SELECT tr.*, w.name AS worker_name, w.temp_id, w.trade, w.aadhaar, w.training_approval_doc,
               w.executing_officer_code, w.executing_officer_name,
               c.contractor_name
        FROM training_requests tr
        JOIN workmen w ON w.id = tr.workman_id
        LEFT JOIN contractors c ON c.id = tr.contractor_id
        WHERE tr.status = 'welfare_pending'
        ORDER BY tr.created_at DESC
    ");

    $recent = db_fetch_all($conn, "
        SELECT tr.*, w.name AS worker_name, w.temp_id, c.contractor_name, u.name AS reviewed_by_name
        FROM training_requests tr
        JOIN workmen w ON w.id = tr.workman_id
        LEFT JOIN contractors c ON c.id = tr.contractor_id
        LEFT JOIN users u ON u.id = tr.welfare_reviewed_by
        WHERE tr.status IN ('pending','welfare_rejected','scheduled','contractor_confirmed','passed','failed')
          AND tr.welfare_reviewed_at IS NOT NULL
        ORDER BY tr.welfare_reviewed_at DESC
        LIMIT 25
    ");

    $sql = "
        SELECT 
            w.temp_id, 
            w.name, 
            c.contractor_name, 
            st.training_date, 
            st.result, 
            st.valid_till, 
            st.trainer_name
        FROM safety_training st
        JOIN workmen w ON st.workman_id = w.id
        JOIN contractors c ON w.contractor_id = c.id
        
        UNION ALL
        
        SELECT 
            w.temp_id, 
            w.name, 
            c.contractor_name, 
            tr.created_at as training_date, 
            tr.result, 
            DATE_ADD(tr.created_at, INTERVAL 1 YEAR) as valid_till, 
            COALESCE(u.name, 'System') as trainer_name
        FROM training_results tr
        JOIN workmen w ON tr.workman_id = w.id
        JOIN contractors c ON w.contractor_id = c.id
        LEFT JOIN users u ON tr.recorded_by = u.id
        WHERE tr.result IN ('pass', 'passed', 'qualified', 'completed')
          AND tr.workman_id NOT IN (SELECT workman_id FROM safety_training)
        
        ORDER BY training_date DESC
    ";
    $trainings = db_fetch_all($conn, $sql);
    ?>
    <div class="content-header">
      <h2 class="page-title">Safety Training Monitoring</h2>
    </div>

    <div class="card glass">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-user-check"></i> Safety Training Approval Queue</div>
        <span class="badge badge-warning"><?= count($queue) ?> Pending Welfare Check</span>
      </div>
      <div class="card-body" style="padding:0;">
        <table class="data-table">
          <thead>
            <tr>
              <th>Worker</th>
              <th>Contractor</th>
              <th>Executing Officer</th>
              <th>Document</th>
              <th>Request</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($queue)): ?>
              <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--text-muted);">No training requests pending Welfare check.</td></tr>
            <?php endif; ?>
            <?php foreach ($queue as $r): ?>
            <tr>
              <td>
                <strong><?= htmlspecialchars($r['worker_name'] ?? '') ?></strong><br>
                <small><?= htmlspecialchars($r['temp_id'] ?? '-') ?> | <?= htmlspecialchars($r['trade'] ?? '-') ?></small>
              </td>
              <td><?= htmlspecialchars($r['contractor_name'] ?? '-') ?></td>
              <td>
                <code><?= htmlspecialchars($r['executing_officer_code'] ?? '-') ?></code><br>
                <small><?= htmlspecialchars($r['executing_officer_name'] ?? '-') ?></small>
              </td>
              <td>
                <?php $docUrl = welfareTrainingDocUrl($r['training_approval_doc'] ?? ''); ?>
                <?php if ($docUrl): ?>
                  <a class="btn btn-sm btn-outline" href="<?= htmlspecialchars($docUrl) ?>" target="_blank"><i class="fas fa-file-pdf"></i> View</a>
                <?php else: ?>
                  <span class="badge badge-gray">No upload</span>
                <?php endif; ?>
              </td>
              <td>
                <div><?= htmlspecialchars($r['training_type'] ?? 'Safety Induction') ?></div>
                <small><?= !empty($r['preferred_date']) ? date('d M Y', strtotime($r['preferred_date'])) : 'Any Date' ?> | <?= ucfirst($r['preferred_shift'] ?? 'morning') ?></small>
              </td>
              <td>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                  <?php if ($docUrl): ?>
                    <a class="btn btn-sm btn-outline" href="<?= htmlspecialchars($docUrl) ?>" target="_blank"><i class="fas fa-eye"></i> View</a>
                  <?php else: ?>
                    <span class="badge badge-gray">EO Approved</span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card glass" style="margin-top:20px;">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-history"></i> Recent Welfare Training Decisions</div>
      </div>
      <div class="card-body" style="padding:0;">
        <table class="data-table">
          <thead>
            <tr>
              <th>Worker</th>
              <th>Contractor</th>
              <th>Status</th>
              <th>Remarks</th>
              <th>Reviewed By</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recent)): ?>
              <tr><td colspan="5" style="text-align:center;padding:28px;color:var(--text-muted);">No Welfare decisions recorded yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($recent as $r): ?>
            <tr>
              <td><strong><?= htmlspecialchars($r['worker_name'] ?? '') ?></strong><br><small><?= htmlspecialchars($r['temp_id'] ?? '-') ?></small></td>
              <td><?= htmlspecialchars($r['contractor_name'] ?? '-') ?></td>
              <td><span class="badge <?= ($r['status'] ?? '') === 'welfare_rejected' ? 'badge-danger' : 'badge-success' ?>"><?= strtoupper(str_replace('_', ' ', $r['status'] ?? '')) ?></span></td>
              <td><?= htmlspecialchars($r['welfare_remarks'] ?? '-') ?></td>
              <td><?= htmlspecialchars($r['reviewed_by_name'] ?? '-') ?><br><small><?= !empty($r['welfare_reviewed_at']) ? date('d M Y H:i', strtotime($r['welfare_reviewed_at'])) : '-' ?></small></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card glass" style="margin-top:20px;">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-graduation-cap"></i> Training Results Summary</div>
      </div>
      <div class="card-body">
        <table class="data-table">
          <thead>
            <tr>
              <th>Workman ID</th>
              <th>Workman Name</th>
              <th>Contractor</th>
              <th>Training Date</th>
              <th>Result</th>
              <th>Valid Till</th>
              <th>Trainer</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($trainings as $t): 
              $isSuccess = in_array(strtolower($t['result']), ['pass', 'passed', 'qualified', 'completed']);
              $res_class = $isSuccess ? 'badge-success' : 'badge-danger';
            ?>
            <tr>
              <td><code><?= htmlspecialchars($t['temp_id'] ?? 'N/A') ?></code></td>
              <td><strong><?= htmlspecialchars($t['name'] ?? 'Unknown') ?></strong></td>
              <td><?= htmlspecialchars($t['contractor_name'] ?? 'N/A') ?></td>
              <td><?= date('d M Y', strtotime($t['training_date'] ?? 'now')) ?></td>
              <td><span class="badge <?= $res_class ?>"><?= strtoupper($t['result'] ?? '') ?></span></td>
              <td><?= ($t['valid_till'] ?? null) ? date('d M Y', strtotime($t['valid_till'])) : 'N/A' ?></td>
              <td><?= htmlspecialchars($t['trainer_name'] ?? 'System') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <script>
    async function reviewTraining(requestId, decision) {
      const remarks = prompt(decision === 'approve' ? 'Welfare approval remarks:' : 'Reject reason:');
      if (remarks === null) return;
      if (decision === 'reject' && !remarks.trim()) {
        alert('Reject reason required.');
        return;
      }

      try {
        const res = await fetch('../../api/welfare/review_training_request.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ request_id: requestId, decision, remarks })
        });
        const data = await res.json();
        if (data.success) {
          alert(data.message || 'Updated successfully.');
          location.reload();
        } else {
          alert(data.message || 'Action failed.');
        }
      } catch (err) {
        alert('Network error.');
      }
    }
    </script>
    <?php
}

renderLayout("Training Monitoring", 'renderContent', $role, $name);
