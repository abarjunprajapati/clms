<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['pass_user', 'super_admin', 'welfare_user', 'welfare_admin']);

include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = get_normalized_role();
$name = $_SESSION['name'] ?? 'Pass Issuing Officer';

function renderContent() {
    global $conn;
    
    $status_filter = $_GET['status'] ?? 'all';
    $search = $_GET['search'] ?? '';
    
    $where = "WHERE 1=1";
    if ($status_filter != 'all') {
        $where .= " AND w.status = " . db_quote($status_filter);
    }
    if ($search) {
        $where .= " AND (w.name LIKE " . db_quote("%$search%") . " OR w.acc_number LIKE " . db_quote("%$search%") . ")";
    }

    $query = "SELECT w.*, c.contractor_name 
              FROM workmen w 
              JOIN contractors c ON w.contractor_id = c.id 
              $where
              ORDER BY w.updated_at DESC";
    $passes = db_fetch_all($conn, $query);
    ?>
    <div class="content-header">
      <h2 class="page-title">Pass Status Tracking</h2>
      <!-- <p class="page-subtitle">Monitor the complete lifecycle of temporary and permanent gate passes.</p> -->
    </div>

    <div class="card glass mb-4">
      <div class="card-body">
        <form method="GET" style="display:flex; gap:16px; align-items:flex-end;">
          <div class="form-group" style="flex:1">
            <label class="form-label">Search Worker / ACC</label>
            <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Enter name or ACC number...">
          </div>
          <div class="form-group" style="width:200px">
            <label class="form-label">Status Filter</label>
            <select name="status" class="form-control">
              <option value="all">All Statuses</option>
              <option value="temporary_issued" <?= $status_filter == 'temporary_issued' ? 'selected' : '' ?>>Temporary Issued</option>
              <option value="acc_generated" <?= $status_filter == 'acc_generated' ? 'selected' : '' ?>>ACC Generated</option>
              <option value="permanent_active" <?= $status_filter == 'permanent_active' ? 'selected' : '' ?>>Permanent Active</option>
              <option value="expired" <?= $status_filter == 'expired' ? 'selected' : '' ?>>Expired</option>
              <option value="rejected" <?= $status_filter == 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
        </form>
      </div>
    </div>

    <div class="card glass">
      <div class="card-body" style="padding:0">
        <table class="data-table">
          <thead>
            <tr>
              <th>Workman</th>
              <th>Contractor</th>
              <th>Pass Type</th>
              <th>ACC Number</th>
              <th>Validity</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($passes as $p): ?>
            <tr>
              <td>
                <div style="font-weight:600"><?= htmlspecialchars($p['name']) ?></div>
                <div style="font-size:11px; opacity:0.6"><?= ucfirst($p['worker_type']) ?></div>
              </td>
              <td><?= htmlspecialchars($p['contractor_name']) ?></td>
              <td>
                <?php if(in_array($p['status'], ['permanent_active', 'acc_generated'])): ?>
                  <span class="badge badge-success">Permanent</span>
                <?php elseif($p['status'] == 'temporary_issued'): ?>
                  <span class="badge badge-info">Temporary</span>
                <?php else: ?>
                  <span class="badge badge-secondary">N/A</span>
                <?php endif; ?>
              </td>
              <td><code><?= $p['acc_number'] ?: '—' ?></code></td>
              <td>
                <?php if($p['valid_to']): ?>
                  <div style="font-size:13px;"><?= date('d M Y', strtotime($p['valid_to'])) ?></div>
                  <?php if(strtotime($p['valid_to']) < time() && $p['status'] != 'permanent_active'): ?>
                    <span class="text-danger" style="font-size:11px;"><i class="fas fa-exclamation-circle"></i> Expired</span>
                  <?php endif; ?>
                <?php else: ?>
                  <span style="opacity:0.4">—</span>
                <?php endif; ?>
              </td>
              <td>
                <?php
                $status_badges = [
                  'pending' => 'badge-pending',
                  'verified' => 'badge-info',
                  'temporary_issued' => 'badge-info',
                  'acc_generated' => 'badge-warning',
                  'permanent_active' => 'badge-success',
                  'expired' => 'badge-danger',
                  'rejected' => 'badge-danger',
                  'reupload_pending' => 'badge-warning'
                ];
                $badge = $status_badges[$p['status']] ?? 'badge-secondary';
                ?>
                <span class="badge <?= $badge ?>"><?= strtoupper(str_replace('_', ' ', $p['status'])) ?></span>
              </td>
              <td>
                <div class="btn-group">
                  <a href="verify_documents.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-icon btn-outline-primary" title="View Details"><i class="fas fa-eye"></i></a>
                  <?php if(in_array($p['status'], ['temporary_issued', 'permanent_active', 'acc_generated'])): 
                      $ptype = $p['status'] == 'temporary_issued' ? 'temp' : 'perm';
                  ?>
                    <a href="../../api/welfare/download_pass.php?id=<?= $p['id'] ?>&type=<?= $ptype ?>&action=download" target="_blank" class="btn btn-sm btn-icon btn-outline-info" title="Download PDF"><i class="fas fa-file-download"></i></a>
                    <a href="../../api/welfare/download_pass.php?id=<?= $p['id'] ?>&type=<?= $ptype ?>&action=print" target="_blank" class="btn btn-sm btn-icon btn-outline-secondary" title="Print Pass"><i class="fas fa-print"></i></a>
                  <?php endif; ?>
                  <?php if($p['status'] == 'temporary_issued'): ?>
                    <a href="pass_validity.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-icon btn-outline-warning" title="Extend Validity"><i class="fas fa-calendar-plus"></i></a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($passes)): ?>
            <tr><td colspan="7" class="text-center" style="padding:40px;">No pass records match the filters.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
}

// Simple quote helper since db_quote might not be in config.php
function db_quote($val) {
    global $conn;
    return "'" . clms_db_real_escape_string($conn, $val) . "'";
}

renderLayout("Pass Status Tracking", 'renderContent', $role, $name);

