<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['pass_user', 'super_admin', 'welfare_user', 'welfare_admin']);

include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = get_normalized_role();
$name = $_SESSION['name'] ?? 'Pass Issuing Officer';

function renderContent() {
    global $conn;
    
    $query = "SELECT w.*, c.contractor_name 
              FROM workmen w 
              JOIN contractors c ON w.contractor_id = c.id 
              WHERE w.status IN ('rejected', 'reupload_pending')
              ORDER BY w.updated_at DESC";
    $rejected = db_fetch_all($conn, $query);
    ?>
    <div class="content-header">
      <h2 class="page-title">Rejected / Re-upload Cases</h2>
      <!-- <p class="page-subtitle">Track workmen whose documents were rejected and are awaiting correction by the contractor.</p> -->
    </div>

    <div class="card glass">
      <div class="card-body" style="padding:0">
        <table class="data-table">
          <thead>
            <tr>
              <th>Workman</th>
              <th>Contractor</th>
              <th>Rejection Date</th>
              <th>Remarks / Reason</th>
              <th>New Uploads</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($rejected as $r): 
                // Check if any documents were updated after the last workman update
                $new_docs = db_count($conn, "SELECT COUNT(*) FROM workman_documents WHERE workman_id = ? AND updated_at > ?", "is", [$r['id'], $r['updated_at']]);
            ?>
            <tr>
              <td>
                <div style="font-weight:600"><?= htmlspecialchars($r['name']) ?></div>
                <div style="font-size:11px; opacity:0.6">ID: <?= $r['id'] ?></div>
              </td>
              <td><?= htmlspecialchars($r['contractor_name']) ?></td>
              <td><?= date('d M Y', strtotime($r['updated_at'])) ?></td>
              <td><div style="max-width:300px; font-size:13px; color:var(--danger)"><?= htmlspecialchars($r['document_remarks'] ?? 'Documents rejected by Pass Issuer') ?></div></td>
              <td>
                <?php if($new_docs > 0): ?>
                  <span class="badge badge-success"><i class="fas fa-file-circle-check"></i> <?= $new_docs ?> New Upload(s)</span>
                <?php else: ?>
                  <span class="badge badge-pending">Waiting for Upload</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="verify_documents.php?id=<?= $r['id'] ?>" class="btn btn-sm <?= $new_docs > 0 ? 'btn-primary' : 'btn-outline-secondary' ?>">
                  <i class="fas fa-sync"></i> Re-verify
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($rejected)): ?>
            <tr><td colspan="6" class="text-center" style="padding:40px;">No rejected cases currently active.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
}

renderLayout("Re-upload Cases", 'renderContent', $role, $name);

