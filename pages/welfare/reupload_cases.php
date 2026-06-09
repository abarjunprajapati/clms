<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['pass_user', 'super_admin', 'welfare_user', 'welfare_admin']);

include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = get_normalized_role();
$name = $_SESSION['name'] ?? 'Pass Issuing Officer';

function reuploadCasesEnsureColumn($conn, $table, $column, $definition) {
    try {
        $safeTable = str_replace('`', '``', $table);
        $safeColumn = clms_db_real_escape_string($conn, $column);
        $exists = clms_db_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$safeColumn'");
        if ($exists && clms_db_num_rows($exists) === 0) {
            @clms_db_query($conn, "ALTER TABLE `$safeTable` ADD COLUMN `$column` $definition");
        }
    } catch (Throwable $e) {
        error_log("reupload cases schema check failed: " . $e->getMessage());
    }
}

function renderContent() {
    global $conn;
    reuploadCasesEnsureColumn($conn, 'documents', 'remarks', 'TEXT NULL');
    reuploadCasesEnsureColumn($conn, 'documents', 'uploaded_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
    
    $query = "
        SELECT
            d.id AS document_id,
            d.document_type,
            d.file_path,
            COALESCE(d.status, 'pending') AS doc_status,
            COALESCE(d.remarks, '') AS remarks,
            d.uploaded_at,
            w.id AS workman_id,
            w.name AS worker_name,
            w.temp_id,
            c.contractor_name,
            (
                SELECT gpr2.id
                FROM gate_pass_request_workers gprw2
                JOIN gate_pass_requests gpr2 ON gpr2.id = gprw2.request_id
                WHERE gprw2.workman_id = w.id
                ORDER BY gpr2.created_at DESC, gpr2.id DESC
                LIMIT 1
            ) AS request_id,
            (
                SELECT gpr2.request_no
                FROM gate_pass_request_workers gprw2
                JOIN gate_pass_requests gpr2 ON gpr2.id = gprw2.request_id
                WHERE gprw2.workman_id = w.id
                ORDER BY gpr2.created_at DESC, gpr2.id DESC
                LIMIT 1
            ) AS request_no,
            (
                SELECT COALESCE(gpr2.status, 'pending')
                FROM gate_pass_request_workers gprw2
                JOIN gate_pass_requests gpr2 ON gpr2.id = gprw2.request_id
                WHERE gprw2.workman_id = w.id
                ORDER BY gpr2.created_at DESC, gpr2.id DESC
                LIMIT 1
            ) AS request_status
        FROM documents d
        JOIN workmen w ON w.id = d.workman_id
        LEFT JOIN contractors c ON w.contractor_id = c.id
        WHERE COALESCE(d.status, 'pending') IN ('rejected', 'reupload_required')
        ORDER BY d.uploaded_at DESC, d.id DESC
    ";
    $rejected = db_fetch_all($conn, $query);
    ?>
    <div class="content-header">
      <h2 class="page-title">Rejected / Re-upload Document Records</h2>
      <!-- <p class="page-subtitle">Track workmen whose documents were rejected and are awaiting correction by the contractor.</p> -->
    </div>

    <div class="card glass">
      <div class="card-body" style="padding:0">
        <table class="data-table">
          <thead>
            <tr>
              <th>Workman</th>
              <th>Contractor</th>
              <th>Request No</th>
              <th>Document</th>
              <th>Rejected On</th>
              <th>Remarks / Reason</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($rejected as $r): ?>
            <tr>
              <td>
                <div style="font-weight:600"><?= htmlspecialchars($r['worker_name']) ?></div>
                <div style="font-size:11px; opacity:0.6">
                  ID: <?= (int)$r['workman_id'] ?>
                  <?= !empty($r['temp_id']) ? ' | ' . htmlspecialchars($r['temp_id']) : '' ?>
                </div>
              </td>
              <td><?= htmlspecialchars($r['contractor_name']) ?></td>
              <td><code><?= htmlspecialchars($r['request_no'] ?? '-') ?></code></td>
              <td>
                <div style="max-width:320px;white-space:normal;font-weight:600;"><?= htmlspecialchars($r['document_type']) ?></div>
                <?php
                  $docPath = !empty($r['file_path'])
                    ? (strpos($r['file_path'], '/') === false && strpos($r['file_path'], '\\') === false
                        ? '../../uploads/documents/' . $r['file_path']
                        : '../../' . ltrim($r['file_path'], '/\\'))
                    : '';
                ?>
                <?php if ($docPath): ?>
                  <a href="<?= htmlspecialchars($docPath) ?>" target="_blank" class="btn btn-sm btn-outline" style="margin-top:6px;"><i class="fas fa-eye"></i> View File</a>
                <?php endif; ?>
              </td>
              <td><?= !empty($r['uploaded_at']) ? date('d M Y, H:i', strtotime($r['uploaded_at'])) : '-' ?></td>
              <td><div style="max-width:300px; font-size:13px; color:var(--danger)"><?= htmlspecialchars($r['remarks'] ?: 'Document rejected by Pass User') ?></div></td>
              <td>
                <span class="badge badge-danger"><?= strtoupper(str_replace('_', ' ', $r['doc_status'])) ?></span>
              </td>
              <td>
                <a href="verify_documents.php?id=<?= (int)$r['workman_id'] ?>" class="btn btn-sm btn-primary">
                  <i class="fas fa-sync"></i> Re-verify
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($rejected)): ?>
            <tr><td colspan="8" class="text-center" style="padding:40px;">No rejected document records currently active.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
}

renderLayout("Re-upload Cases", 'renderContent', $role, $name);
