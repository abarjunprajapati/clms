<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    
    // Summary
    $totalDocs = db_count($conn, "SELECT COUNT(*) c FROM documents");
    $verified = db_count($conn, "SELECT COUNT(*) c FROM documents WHERE status='approved'");
    $pending = db_count($conn, "SELECT COUNT(*) c FROM documents WHERE status='pending' OR status IS NULL");
    $rejected = db_count($conn, "SELECT COUNT(*) c FROM documents WHERE status='rejected'");
    
    $docs = db_fetch_all($conn, "SELECT d.*, c.contractor_name, w.name as workman_name, 
                                 COALESCE(dv.status, d.status) as ver_status, 
                                 COALESCE(dv.remarks, d.remarks) as ver_remarks, 
                                 dv.verified_at
                                 FROM documents d 
                                 JOIN workmen w ON d.workman_id = w.id
                                 JOIN contractors c ON w.contractor_id = c.id 
                                 LEFT JOIN document_verifications dv ON w.application_no = dv.application_id AND d.document_type = dv.document_type
                                 ORDER BY d.uploaded_at DESC LIMIT 100");
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-file-invoice" style="color:#059669;margin-right:10px;"></i> Document Flow Monitoring</h2>
        <!-- <p class="page-subtitle">Granular tracking of document verification status across all contractors.</p> -->
      </div>
    </div>

    <!-- Stats Row -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
      <div class="card glass" style="text-align:center;padding:16px;">
        <div style="font-size:24px;font-weight:800;color:#6366f1;"><?= $totalDocs ?></div>
        <div style="font-size:11px;opacity:0.6;">Total Documents</div>
      </div>
      <div class="card glass" style="text-align:center;padding:16px;">
        <div style="font-size:24px;font-weight:800;color:#f59e0b;"><?= $pending ?></div>
        <div style="font-size:11px;opacity:0.6;">Pending Review</div>
      </div>
      <div class="card glass" style="text-align:center;padding:16px;">
        <div style="font-size:24px;font-weight:800;color:#10b981;"><?= $verified ?></div>
        <div style="font-size:11px;opacity:0.6;">Verified</div>
      </div>
      <div class="card glass" style="text-align:center;padding:16px;">
        <div style="font-size:24px;font-weight:800;color:#ef4444;"><?= $rejected ?></div>
        <div style="font-size:11px;opacity:0.6;">Rejected</div>
      </div>
    </div>

    <div class="card glass">
      <div class="card-header">
        <div class="card-title">Document Verification Queue</div>
      </div>
      <div class="card-body" style="padding:0;">
        <table class="data-table">
          <thead>
            <tr>
              <th>Workman</th>
              <th>Contractor</th>
              <th>Document Type</th>
              <th>Verification</th>
              <th>Remarks</th>
              <th>Uploaded At</th>
              <th>File</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($docs as $doc): 
                $st = $doc['ver_status'] ?? 'pending';
                $badge = ($st == 'approved') ? 'success' : (($st == 'rejected') ? 'danger' : 'warning');
            ?>
            <tr>
              <td><strong><?= htmlspecialchars($doc['workman_name']) ?></strong></td>
              <td><small><?= htmlspecialchars($doc['contractor_name']) ?></small></td>
              <td><span class="badge badge-outline"><?= strtoupper(str_replace('_',' ',$doc['document_type'])) ?></span></td>
              <td><span class="badge badge-<?= $badge ?>"><?= strtoupper($st) ?></span></td>
              <td><small style="opacity:0.6;"><?= htmlspecialchars($doc['ver_remarks'] ?? '-') ?></small></td>
              <td><small><?= date('d M, H:i', strtotime($doc['uploaded_at'])) ?></small></td>
              <td>
                <?php 
                $fpath = $doc['file_path'];
                if ($fpath && strpos($fpath, 'http') !== 0 && strpos($fpath, '../') !== 0) {
                    $fpath = "../../uploads/workers/" . $fpath;
                }
                ?>
                <a href="<?= $fpath ?>" target="_blank" class="btn btn-sm btn-outline">
                  <i class="fas fa-eye"></i> View
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
}

renderLayout("Document Monitor", 'renderContent', $_SESSION['role'], $_SESSION['name']);
