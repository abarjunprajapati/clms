<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['execution_officer', 'execution', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/execution_context.php';
include __DIR__ . '/../../include/training_flow.php';
include __DIR__ . '/../../include/payment_flow.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Execution Officer';
$userId = (int)($_SESSION['user_id'] ?? 0);
$officerId = clms_execution_get_officer_id($conn, $userId);

function executionTrainingDeskContext($conn, $officerId, $userId) {
    $officer = db_single($conn, "SELECT employee_code FROM execution_officers WHERE id = ? LIMIT 1", 'i', [(int)$officerId]);
    $employeeExpr = clms_execution_column_exists($conn, 'users', 'employee_code') ? 'employee_code' : "'' AS employee_code";
    $loginUser = db_single($conn, "SELECT contractor_id, $employeeExpr FROM users WHERE id = ? LIMIT 1", 'i', [(int)$userId]);
    $codes = array_values(array_unique(array_filter(array_map(function($code) {
        return strtoupper(trim((string)$code));
    }, [
        $officer['employee_code'] ?? '',
        $loginUser['employee_code'] ?? '',
        $loginUser['contractor_id'] ?? '',
    ]))));
    $names = array_values(array_unique(array_filter(array_map(function($name) {
        return strtoupper(trim((string)$name));
    }, [$_SESSION['name'] ?? '']))));

    $codePlaceholders = implode(',', array_fill(0, max(1, count($codes)), '?'));
    $namePlaceholders = implode(',', array_fill(0, max(1, count($names)), '?'));
    return [
        'where' => "(w.executing_officer_id IN (?, ?) OR UPPER(COALESCE(w.executing_officer_code, '')) IN ($codePlaceholders) OR UPPER(COALESCE(w.executing_officer_name, '')) IN ($namePlaceholders))",
        'types' => 'ii' . str_repeat('s', max(1, count($codes))) . str_repeat('s', max(1, count($names))),
        'params' => array_merge([(int)$officerId, (int)$userId], $codes ?: [''], $names ?: ['']),
    ];
}

function executionTrainingDocUrl($path) {
    $path = trim((string)$path);
    if ($path === '') return '';
    if (preg_match('~^https?://~i', $path)) return $path;
    return '../../uploads/workers/' . rawurlencode(basename($path));
}

function renderContent() {
    global $conn, $officerId, $userId;
    clms_training_ensure_schema($conn);
    clms_ensure_payment_flow($conn);
    $ctx = executionTrainingDeskContext($conn, $officerId, $userId);

    $autoApproveRows = db_fetch_all($conn, "
        SELECT w.id
        FROM workmen w
        WHERE COALESCE(w.training_approval_doc, '') <> ''
          AND COALESCE(w.execution_training_status, 'pending_eo') IN ('pending_eo','pending')
          AND (
            UPPER(COALESCE(w.work_order_source, '')) <> 'PWO'
            OR EXISTS (
              SELECT 1
              FROM training_payment_request_workers pw
              JOIN training_payment_requests pr ON pr.id = pw.payment_request_id
              WHERE pw.workman_id = w.id
                AND pr.status = 'paid'
            )
          )
          AND {$ctx['where']}
        LIMIT 100
    ", $ctx['types'], $ctx['params']);
    foreach ($autoApproveRows as $autoRow) {
        clms_training_auto_approve_attached_document(
            $conn,
            (int)$autoRow['id'],
            (int)($officerId ?: $userId),
            'Auto-approved because Training Attendance Approval document is attached.'
        );
    }

    clms_training_seed_approved_queue($conn);

    $rows = db_fetch_all($conn, "
        SELECT w.id, w.name, w.gender, w.dob, w.aadhaar, w.temp_id, w.worker_type,
               w.department, w.nature_of_work, w.trade, w.training_approval_doc,
               w.execution_training_status, w.execution_training_reviewed_by,
               w.executing_officer_code, w.executing_officer_name, c.contractor_name
        FROM workmen w
        LEFT JOIN contractors c ON c.id = w.contractor_id
        WHERE COALESCE(w.execution_training_status, 'pending_eo') IN ('pending_eo','pending','approved')
          AND EXISTS (
              SELECT 1
              FROM training_requests tr_submit
              WHERE tr_submit.workman_id = w.id
                AND tr_submit.status IN ('pending_eo','welfare_pending','pending','scheduled','contractor_confirmed','passed')
          )
          AND (
            UPPER(COALESCE(w.work_order_source, '')) <> 'PWO'
            OR EXISTS (
              SELECT 1
              FROM training_payment_request_workers pw
              JOIN training_payment_requests pr ON pr.id = pw.payment_request_id
              WHERE pw.workman_id = w.id
                AND pr.status = 'paid'
            )
          )
          AND {$ctx['where']}
        ORDER BY COALESCE(w.execution_training_reviewed_at, w.created_at) DESC
        LIMIT 100
    ", $ctx['types'], $ctx['params']);
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-file-signature" style="color:#6366f1;margin-right:10px"></i>Training Attendance Approval Desk</h2>
      </div>
      <a class="btn btn-outline" href="dashboard.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
    </div>

    <div class="card glass">
      <div class="card-header">
        <div class="card-title">Training Attendance Review</div>
        <span class="badge badge-warning"><?= count($rows) ?> Records</span>
      </div>
      <div class="card-body" style="padding:0">
        <table class="data-table">
          <thead>
            <tr>
              <th>Person Details</th>
              <th>Aadhaar</th>
              <th>Pass / Role</th>
              <th>Department / Work</th>
              <th>Temp ID</th>
              <th>Contractor</th>
              <th>Document / E-Code</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rows)): ?>
              <tr><td colspan="9" style="text-align:center;padding:28px;color:#64748b;">No training attendance approvals pending.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
              <?php
                $docUrl = executionTrainingDocUrl($r['training_approval_doc'] ?? '');
                $hasDoc = $docUrl !== '';
                $approved = strtolower((string)($r['execution_training_status'] ?? '')) === 'approved';
              ?>
              <tr id="training-row-<?= (int)$r['id'] ?>">
                <td>
                  <strong><?= htmlspecialchars($r['name'] ?? '') ?></strong><br>
                  <small><?= htmlspecialchars($r['gender'] ?? '') ?><?= !empty($r['dob']) ? ' | ' . htmlspecialchars($r['dob']) : '' ?></small>
                </td>
                <td><code><?= htmlspecialchars($r['aadhaar'] ?? '') ?></code></td>
                <td><span class="badge badge-gray"><?= htmlspecialchars(str_replace(' Pass', '', (string)($r['worker_type'] ?? 'Workman'))) ?></span><br><small><?= htmlspecialchars($r['trade'] ?? '') ?></small></td>
                <td><div><?= htmlspecialchars($r['department'] ?? '') ?></div><small><?= htmlspecialchars($r['nature_of_work'] ?? '') ?></small></td>
                <td><code class="text-primary"><?= htmlspecialchars($r['temp_id'] ?? 'PENDING') ?></code></td>
                <td><?= htmlspecialchars($r['contractor_name'] ?? '-') ?></td>
                <td>
                  <?php if ($hasDoc): ?>
                    <a class="btn btn-sm btn-outline" target="_blank" href="<?= htmlspecialchars($docUrl) ?>"><i class="fas fa-file-pdf"></i> View</a>
                  <?php else: ?>
                    <span class="badge badge-warning">No attachment</span>
                  <?php endif; ?>
                  <div style="margin-top:4px;"><code style="background:rgba(14,165,233,0.1);color:#0369a1;padding:3px 8px;border-radius:6px;font-size:12px;font-weight:700;"><?= htmlspecialchars($r['executing_officer_code'] ?? '') ?></code></div>
                  <div style="font-size:11px;color:#64748b;margin-top:3px;"><?= htmlspecialchars($r['executing_officer_name'] ?? '') ?></div>
                </td>
                <td>
                  <?php if ($approved): ?>
                    <span class="badge badge-success">Approved</span><br><small>Forwarded to Safety Training</small>
                  <?php else: ?>
                    <span class="badge badge-warning">EO Pending</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($hasDoc): ?>
                    <a class="btn btn-sm btn-outline" target="_blank" href="<?= htmlspecialchars($docUrl) ?>"><i class="fas fa-eye"></i> View</a>
                  <?php endif; ?>
                  <?php if (!$approved): ?>
                    <button class="btn btn-sm btn-success" onclick="reviewTraining(<?= (int)$r['id'] ?>, 'approved')"><i class="fas fa-check"></i> Approve</button>
                    <button class="btn btn-sm btn-danger" onclick="reviewTraining(<?= (int)$r['id'] ?>, 'rejected')"><i class="fas fa-times"></i> Reject</button>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <script>
    async function reviewTraining(workmanId, decision) {
      const title = decision === 'approved' ? 'Approve training attendance?' : 'Reject training attendance?';
      const prompt = await Swal.fire({
        icon: decision === 'approved' ? 'question' : 'warning',
        title,
        input: 'textarea',
        inputPlaceholder: 'Remarks',
        showCancelButton: true,
        confirmButtonText: decision === 'approved' ? 'Approve' : 'Reject',
        confirmButtonColor: decision === 'approved' ? '#10b981' : '#ef4444'
      });
      if (!prompt.isConfirmed) return;

      try {
        const response = await fetch('../../api/execution/approve_training_attendance.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ workman_id: workmanId, decision, remarks: prompt.value || '' })
        });
        const result = await response.json();
        if (result.status) {
          Swal.fire('Updated', result.message || 'Training attendance reviewed.', 'success').then(() => location.reload());
        } else {
          Swal.fire('Action Failed', result.message || 'Unable to update training attendance.', 'error');
        }
      } catch (err) {
        Swal.fire('Connection Error', 'Server response could not be processed.', 'error');
      }
    }
    </script>
    <?php
}

renderLayout('Training Attendance Approval Desk', 'renderContent', $role, $name);
?>
