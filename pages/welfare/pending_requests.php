<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['pass_user', 'super_admin', 'welfare_user', 'welfare_admin']);

include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = get_normalized_role();
$name = $_SESSION['name'] ?? 'Pass Issuing Officer';

function renderContent() {
    global $conn;
    
    $query = "(SELECT
                w.*,
                c.contractor_name,
                gpr.request_no,
                COALESCE(gprw.status, gpr.status, 'approved') AS request_status,
                COALESCE(gprw.updated_at, gpr.updated_at, gprw.created_at, gpr.created_at) AS request_date
              FROM gate_pass_request_workers gprw
              JOIN gate_pass_requests gpr ON gprw.request_id = gpr.id
              JOIN workmen w ON gprw.workman_id = w.id
              LEFT JOIN contractors c ON w.contractor_id = c.id
              WHERE (
                  LOWER(COALESCE(gprw.status, '')) = 'approved'
                  OR LOWER(COALESCE(gpr.status, '')) = 'approved'
                )
                AND LOWER(COALESCE(gprw.status, '')) != 'issued'
                AND LOWER(COALESCE(gpr.status, '')) != 'issued'
                AND COALESCE(w.is_blocked, 0) = 0)
              UNION
              (SELECT
                w.*,
                c.contractor_name,
                CONCAT('VERIFIED-', w.id) AS request_no,
                'approved' AS request_status,
                COALESCE(w.updated_at, w.created_at) AS request_date
              FROM workmen w
              LEFT JOIN contractors c ON w.contractor_id = c.id
              WHERE w.pass_issuer_verified = 1
                AND w.status = 'verified'
                AND COALESCE(w.is_blocked, 0) = 0
                AND NOT EXISTS (
                  SELECT 1
                  FROM gate_pass_request_workers existing_gprw
                  WHERE existing_gprw.workman_id = w.id
                    AND LOWER(COALESCE(existing_gprw.status, '')) IN ('approved', 'issued')
                ))
              ORDER BY request_date ASC";
    $pending = db_fetch_all($conn, $query);
    ?>
    <div class="content-header">
      <h2 class="page-title">Pending Pass Requests</h2>
      <!-- <p class="page-subtitle">Workmen whose documents are pre-verified by Welfare User and Training is completed.</p> -->
    </div>

    <div class="card glass">
      <div class="card-body" style="padding:0">
        <table class="data-table">
          <thead>
            <tr>
              <th>Workman Name</th>
              <th>Contractor</th>
              <th>Training Status</th>
              <th>Welfare Status</th>
              <th>Request Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($pending as $p): ?>
            <tr>
              <td>
                <div style="font-weight:600"><?= htmlspecialchars($p['name'] ?? 'Unknown') ?></div>
                <div style="font-size:11px;opacity:0.6">ID: <?= htmlspecialchars($p['id'] ?? 'N/A') ?> | Type: <?= ucfirst($p['worker_type'] ?? 'Workmen') ?></div>
              </td>
              <td><?= htmlspecialchars($p['contractor_name'] ?? 'N/A') ?></td>
              <td><span class="badge badge-success"><i class="fas fa-check-circle"></i> Passed</span></td>
              <td><span class="badge badge-info"><i class="fas fa-user-check"></i> Welfare Verified</span></td>
              <td><?= date('d M Y', strtotime($p['request_date'])) ?></td>
              <td>
                <a href="issue_temp_pass.php?id=<?= $p['id'] ?>" class="btn btn-primary btn-sm">
                  <i class="fas fa-id-card"></i> Issue Temp Pass
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($pending)): ?>
            <tr>
              <td colspan="6" style="text-align:center;padding:40px;color:var(--gray-500)">
                <i class="fas fa-inbox" style="font-size:48px;opacity:0.3"></i><br>
                No pending pass requests found.
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
}

renderLayout("Pending Pass Requests", 'renderContent', $role, $name);
