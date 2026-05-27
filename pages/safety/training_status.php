<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Safety Officer';

function renderContent() {
    global $conn;
    
    $search = $_GET['search'] ?? '';
    $status_filter = $_GET['status'] ?? '';

    $where = "1=1";
    $params = [];
    $types = "";

    if ($search) {
        $where .= " AND (w.name LIKE ? OR w.temp_id LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types .= "ss";
    }

    if ($status_filter) {
        $where .= " AND w.training_status = ?";
        $params[] = $status_filter;
        $types .= "s";
    }

    $workers = db_fetch_all($conn, "
        SELECT 
            w.*, 
            COALESCE(sv.contractor_name, c.contractor_name) as display_contractor,
            tr.updated_at as last_result_date
        FROM workmen w
        LEFT JOIN contractors c ON w.contractor_id = c.id
        LEFT JOIN sap_vendors sv ON c.vendor_code = sv.vendor_code
        LEFT JOIN (
            SELECT tr1.*
            FROM training_results tr1
            INNER JOIN (
                SELECT workman_id, MAX(id) as max_id
                FROM training_results
                GROUP BY workman_id
            ) tr2 ON tr1.id = tr2.max_id
        ) tr ON w.id = tr.workman_id
        WHERE $where
        ORDER BY w.name ASC
    ", $types, $params);

    ?>
    <div class="content-header">
      <h2 class="page-title">Training Status Tracker</h2>
      <!-- <p class="page-subtitle">Monitor worker eligibility and certificate validity across the organization.</p> -->
    </div>

    <div class="card glass" style="margin-bottom:20px">
        <div class="card-body">
            <form method="GET" class="grid grid-4" style="gap:15px; align-items:flex-end">
                <div class="form-group">
                    <label class="form-label">Search Worker</label>
                    <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Name or Code">
                </div>
                <div class="form-group">
                    <label class="form-label">Filter Status</label>
                    <select name="status" class="form-control">
                        <option value="">-- All Statuses --</option>
                        <option value="training_pending" <?= $status_filter == 'training_pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="training_scheduled" <?= $status_filter == 'training_scheduled' ? 'selected' : '' ?>>Scheduled</option>
                        <option value="PASS" <?= $status_filter == 'PASS' ? 'selected' : '' ?>>Passed</option>
                        <option value="FAIL" <?= $status_filter == 'FAIL' ? 'selected' : '' ?>>Failed</option>
                        <option value="training_expired" <?= $status_filter == 'training_expired' ? 'selected' : '' ?>>Expired</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                    <a href="training_status.php" class="btn btn-outline">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card glass">
      <div class="card-body">
        <table class="data-table">
          <thead>
            <tr>
              <th>Worker Details</th>
              <th>Contractor</th>
              <th>Status</th>
              <th>Valid Till</th>
              <th>Eligibility</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($workers as $w): ?>
            <tr>
              <td>
                <div style="font-weight:600"><?= htmlspecialchars($w['name']) ?></div>
                <div style="font-size:11px;opacity:0.7"><?= htmlspecialchars($w['temp_id']) ?></div>
              </td>
              <td><?= htmlspecialchars($w['display_contractor']) ?></td>
              <td>
                <?php
                $raw_status = strtoupper($w['training_status'] ?? '');
                $status_badges = [
                    'TRAINING_PENDING'   => 'badge-warning',
                    'TRAINING_SCHEDULED' => 'badge-info',
                    'PASS'               => 'badge-success',
                    'PASSED'             => 'badge-success',
                    'FAIL'               => 'badge-danger',
                    'FAILED'             => 'badge-danger',
                    'EXPIRED'            => 'badge-danger'
                ];
                $badge = $status_badges[$raw_status] ?? 'badge-outline';
                $display_status = str_replace('TRAINING_', '', $raw_status);
                ?>
                <span class="badge <?= $badge ?>"><?= $display_status ?></span>
              </td>
              <td>
                <?php 
                $valid_till = $w['training_valid_till'];
                echo $valid_till ? date('d M Y', strtotime($valid_till)) : 'N/A';
                ?>
                <?php if($valid_till && strtotime($valid_till) < time()): ?>
                    <div style="color:var(--danger); font-size:10px; font-weight:700">EXPIRED</div>
                <?php endif; ?>
              </td>
              <td>
                <?php 
                $eligibility = $w['eligibility_status'] ?? 'NOT ELIGIBLE';
                if($eligibility === 'ELIGIBLE' && $valid_till && strtotime($valid_till) > time()): ?>
                    <span class="text-success" style="font-size:12px; font-weight:700"><i class="fas fa-check-circle"></i> ELIGIBLE</span>
                <?php else: ?>
                    <span class="text-danger" style="font-size:12px; font-weight:700"><i class="fas fa-times-circle"></i> NOT ELIGIBLE</span>
                <?php endif; ?>
              </td>
              <td>
                <div style="display:flex; gap:5px">
                  <a href="worker_history.php?id=<?= $w['id'] ?>" class="btn btn-sm btn-outline">History</a>
                  <?php if($raw_status === 'PASS' || $raw_status === 'PASSED'): ?>
                    <button class="btn btn-sm btn-primary" onclick='generateCertificate(<?= json_encode($w) ?>)'>
                      <i class="fas fa-file-pdf"></i> Download
                    </button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php include __DIR__ . '/../../include/safety_certificate_logic.php'; ?>
    <?php
}

renderLayout("Training Status", 'renderContent', $role, $name);

