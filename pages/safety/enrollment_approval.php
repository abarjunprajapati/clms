<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/training_flow.php';
require_once __DIR__ . '/../../include/training_venue_master.php';
require_once __DIR__ . '/../../include/training_type_master.php';
require_once __DIR__ . '/../../include/safety_training_control.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Safety Officer';

function safetyApprovalTableExists($conn, $table) {
    $table = mysqli_real_escape_string($conn, $table);
    $res = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return $res && mysqli_num_rows($res) > 0;
}

function safetyApprovalColumnExists($conn, $table, $column) {
    if (!safetyApprovalTableExists($conn, $table)) return false;
    $safeTable = str_replace('`', '``', $table);
    $column = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$column'");
    return $res && mysqli_num_rows($res) > 0;
}

function renderContent() {
    global $conn;
    clms_safety_ensure_control_schema($conn);

    $hasRequests = safetyApprovalTableExists($conn, 'training_requests');
    $hasWorkmen = safetyApprovalTableExists($conn, 'workmen');

    $safetyApprovalRequests = [];
    if ($hasRequests && $hasWorkmen) {
        $approvalContractorNameParts = [];
        foreach (['contractor_name', 'vendor_name', 'name'] as $column) {
            if (safetyApprovalColumnExists($conn, 'contractors', $column)) {
                $approvalContractorNameParts[] = "c.`$column`";
            }
        }
        $approvalContractorNameParts[] = "CONCAT('Contractor #', w.contractor_id)";
        $approvalContractorNameExpr = "COALESCE(" . implode(', ', $approvalContractorNameParts) . ")";
        $safetyApprovalRequests = db_fetch_all($conn, "
            SELECT tr.id AS request_id, tr.status AS request_status, tr.source, tr.remarks AS request_remarks,
                   w.id AS workman_id, w.name AS worker_name, w.aadhaar, w.temp_id,
                   w.department, w.nature_of_work, w.safety_language,
                   w.executing_officer_code, w.executing_officer_name,
                   w.execution_training_remarks, w.training_approval_doc,
                   COALESCE(w.safety_enrollment_status, 'pending') AS safety_enrollment_status,
                   $approvalContractorNameExpr AS contractor_name
            FROM training_requests tr
            JOIN workmen w ON w.id = tr.workman_id
            LEFT JOIN contractors c ON c.id = w.contractor_id
            WHERE LOWER(COALESCE(tr.status, '')) IN ('pending_safety', 'welfare_pending')
              AND LOWER(COALESCE(w.execution_training_status, '')) = 'approved'
              AND LOWER(COALESCE(w.safety_enrollment_status, 'pending')) <> 'approved'
              AND tr.id = (
                  SELECT tr2.id
                  FROM training_requests tr2
                  WHERE tr2.workman_id = tr.workman_id
                  ORDER BY tr2.id DESC
                  LIMIT 1
              )
            ORDER BY COALESCE(tr.updated_at, tr.created_at) ASC, tr.id ASC
        ");
    }
    $safetyApprovalPending = count($safetyApprovalRequests);
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-user-check" style="color:#6366f1;margin-right:10px"></i>Safety Department Enrollment Approval Inbox</h2>
      </div>
      <a class="btn btn-outline" href="dashboard.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
    </div>

    <div class="card glass safety-approval-card" id="enrollment-approval-inbox" style="margin-top:20px;">
      <div class="card-header">
        <div>
          <div class="card-title">Pending Enrollments</div>
          <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">EO-approved enrollments must be approved here before Safety training scheduling.</div>
        </div>
        <span class="badge badge-warning"><?= $safetyApprovalPending ?> Pending</span>
      </div>
      <div class="card-body" style="padding:0">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Worker</th>
                <th>Contractor / Work</th>
                <th>Executing Officer</th>
                <th>Submission</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($safetyApprovalRequests as $approval): ?>
              <tr id="safety-approval-row-<?= (int)$approval['workman_id'] ?>">
                <td>
                  <strong><?= htmlspecialchars($approval['worker_name'] ?? '') ?></strong>
                  <div style="font-size:11px;color:var(--text-muted);margin-top:3px;"><code><?= htmlspecialchars($approval['temp_id'] ?: ('W-' . $approval['workman_id'])) ?></code></div>
                  <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">Aadhaar: <?= htmlspecialchars($approval['aadhaar'] ?? '-') ?></div>
                </td>
                <td>
                  <strong><?= htmlspecialchars($approval['contractor_name'] ?? 'N/A') ?></strong>
                  <div style="font-size:11px;color:var(--text-muted);margin-top:3px;"><?= htmlspecialchars($approval['department'] ?? '-') ?> / <?= htmlspecialchars($approval['nature_of_work'] ?? '-') ?></div>
                  <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">Language: <?= htmlspecialchars($approval['safety_language'] ?? '-') ?></div>
                </td>
                <td>
                  <code><?= htmlspecialchars($approval['executing_officer_code'] ?? '-') ?></code>
                  <div style="font-size:11px;color:var(--text-muted);margin-top:3px;"><?= htmlspecialchars($approval['executing_officer_name'] ?? '') ?></div>
                  <span class="badge badge-success" style="margin-top:4px;display:inline-block;">EO Approved</span>
                </td>
                <td>
                  <span class="badge badge-warning">Safety Approval Pending</span>
                  <div style="font-size:11px;color:var(--text-muted);margin-top:4px;"><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string)($approval['source'] ?? 'enrolment')))) ?></div>
                  <?php if (!empty($approval['training_approval_doc'])): ?>
                    <a class="btn btn-sm btn-outline" style="margin-top:6px;display:inline-block;" target="_blank" href="../../uploads/workers/<?= rawurlencode(basename((string)$approval['training_approval_doc'])) ?>"><i class="fas fa-file-pdf"></i> View Attachment</a>
                  <?php endif; ?>
                </td>
                <td>
                  <div style="display:flex;gap:5px;">
                    <button class="btn btn-sm btn-success" type="button" onclick="reviewSafetyEnrollment(<?= (int)$approval['workman_id'] ?>, 'approved')"><i class="fas fa-check"></i> Approve</button>
                    <button class="btn btn-sm btn-danger" type="button" onclick="reviewSafetyEnrollment(<?= (int)$approval['workman_id'] ?>, 'rejected')"><i class="fas fa-times"></i> Reject</button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($safetyApprovalRequests)): ?>
              <tr><td colspan="5" style="text-align:center;padding:26px;color:var(--text-muted)">No enrollment is waiting for Safety Department approval.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <script>
      async function reviewSafetyEnrollment(workmanId, decision) {
        const rejecting = decision === 'rejected';
        const prompt = await Swal.fire({
          icon: rejecting ? 'warning' : 'question',
          title: rejecting ? 'Reject enrollment?' : 'Approve enrollment?',
          text: rejecting
            ? 'The enrollment will return to the contractor for correction and resubmission.'
            : 'The worker will be released for Safety training scheduling.',
          input: 'textarea',
          inputLabel: rejecting ? 'Correction / rejection remarks' : 'Approval remarks (optional)',
          inputPlaceholder: rejecting ? 'Clearly mention what the contractor must correct.' : 'Enter remarks if required',
          showCancelButton: true,
          confirmButtonText: rejecting ? 'Reject & Return' : 'Approve Enrollment',
          confirmButtonColor: rejecting ? '#dc2626' : '#16a34a',
          inputValidator: value => rejecting && !String(value || '').trim()
            ? 'Rejection remarks are required.'
            : undefined
        });
        if (!prompt.isConfirmed) return;

        try {
          const response = await fetch('../../api/safety/review_enrollment.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-Token': window.CLMS_CSRF_TOKEN || ''
            },
            body: JSON.stringify({
              workman_id: workmanId,
              decision,
              remarks: String(prompt.value || '').trim()
            })
          });
          const result = await response.json();
          if (!response.ok || !result.success) {
            throw new Error(result.message || 'Unable to update enrollment approval.');
          }
          await Swal.fire('Updated', result.message, 'success');
          location.reload();
        } catch (error) {
          Swal.fire('Action Failed', error.message || 'Server response could not be processed.', 'error');
        }
      }
    </script>
    <?php
}

renderLayout('Safety Department Enrollment Approval Inbox', 'renderContent', $role, $name);
?>
