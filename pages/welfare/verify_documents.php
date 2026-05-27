<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_user', 'welfare_admin', 'super_admin', 'pass_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function welfareDocsTableExists($conn, $table) {
    $table = mysqli_real_escape_string($conn, $table);
    $result = mysqli_query($conn, "SHOW TABLES LIKE '{$table}'");
    return $result && mysqli_num_rows($result) > 0;
}

function welfareEnsureContractorDocumentsSchema($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS contractor_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contractor_id INT NULL,
        annexure3a_id INT NULL,
        doc_type VARCHAR(100) NULL,
        file_path VARCHAR(255) NULL,
        original_name VARCHAR(255) NULL,
        status VARCHAR(30) DEFAULT 'pending',
        remarks TEXT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL,
        KEY idx_contractor (contractor_id),
        KEY idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $columns = [
        'contractor_id' => 'INT NULL',
        'annexure3a_id' => 'INT NULL',
        'doc_type' => 'VARCHAR(100) NULL',
        'file_path' => 'VARCHAR(255) NULL',
        'original_name' => 'VARCHAR(255) NULL',
        'status' => "VARCHAR(30) DEFAULT 'pending'",
        'remarks' => 'TEXT NULL',
        'uploaded_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'TIMESTAMP NULL DEFAULT NULL'
    ];

    foreach ($columns as $column => $definition) {
        $safeColumn = mysqli_real_escape_string($conn, $column);
        $exists = $conn->query("SHOW COLUMNS FROM contractor_documents LIKE '$safeColumn'");
        if ($exists && $exists->num_rows == 0) {
            $conn->query("ALTER TABLE contractor_documents ADD COLUMN `$column` $definition");
        }
    }

    $conn->query("ALTER TABLE contractor_documents MODIFY status VARCHAR(30) DEFAULT 'pending'");
}

function welfareDocUrl($path) {
    $path = trim((string)$path);
    if ($path === '') return '#';
    if (preg_match('/^https?:\/\//i', $path)) return $path;
    if (strpos($path, '../') === 0) return $path;
    return '../../' . ltrim(str_replace('\\', '/', $path), '/');
}

function renderContent() {
    global $conn;
    welfareEnsureContractorDocumentsSchema($conn);

    $workmanFilter = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $workmanFilterSql = $workmanFilter > 0 ? " AND w.id = $workmanFilter" : "";

    // Only gate pass request workers should appear here. Worker master documents are not the queue source.
    $sql = "
        SELECT
            gpr.id AS request_id,
            gpr.request_no,
            gpr.application_id,
            gpr.pass_type,
            gpr.status AS request_status,
            gpr.created_at AS request_created_at,
            gprw.status AS worker_request_status,
            w.id AS workman_id,
            w.contractor_id,
            w.name AS worker_name,
            w.worker_type,
            c.contractor_name
        FROM gate_pass_request_workers gprw
        JOIN gate_pass_requests gpr ON gpr.id = gprw.request_id
        JOIN workmen w ON w.id = gprw.workman_id
        LEFT JOIN contractors c ON w.contractor_id = c.id
        WHERE COALESCE(gpr.status, 'pending') IN ('pending', 'reupload_required')
          AND COALESCE(gprw.status, 'pending') IN ('pending', 'reupload_required')
          $workmanFilterSql
        ORDER BY gpr.created_at DESC, gprw.id DESC
    ";

    $pendingApps = [];
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $pendingApps[] = $row;
        }
    }

    $approvedSql = "
        SELECT
            gpr.id AS request_id,
            gpr.request_no,
            gpr.application_id,
            gpr.pass_type,
            gpr.status AS request_status,
            gpr.created_at AS request_created_at,
            gpr.updated_at AS request_updated_at,
            gprw.status AS worker_request_status,
            w.id AS workman_id,
            w.contractor_id,
            w.name AS worker_name,
            w.worker_type,
            c.contractor_name
        FROM gate_pass_request_workers gprw
        JOIN gate_pass_requests gpr ON gpr.id = gprw.request_id
        JOIN workmen w ON w.id = gprw.workman_id
        LEFT JOIN contractors c ON w.contractor_id = c.id
        WHERE COALESCE(gpr.status, '') IN ('approved', 'active', 'issued')
          AND COALESCE(gprw.status, '') IN ('approved', 'issued')
          $workmanFilterSql
        ORDER BY COALESCE(gpr.updated_at, gpr.created_at) DESC, gprw.id DESC
        LIMIT 50
    ";
    $approvedApps = [];
    $approvedRes = $conn->query($approvedSql);
    if ($approvedRes && $approvedRes->num_rows > 0) {
        while ($row = $approvedRes->fetch_assoc()) {
            $approvedApps[] = $row;
        }
    }

    $contractorDocs = [];
    $contractorDocsSql = "
        SELECT
            cd.id,
            cd.contractor_id,
            cd.doc_type,
            cd.file_path,
            cd.original_name,
            COALESCE(cd.status, 'pending') AS status,
            COALESCE(cd.remarks, '') AS remarks,
            cd.uploaded_at,
            c.contractor_name,
            c.vendor_code
        FROM contractor_documents cd
        LEFT JOIN contractors c ON cd.contractor_id = c.id
        WHERE COALESCE(cd.status, 'pending') IN ('pending', 'reupload_required')
        ORDER BY cd.uploaded_at DESC, cd.id DESC
    ";
    $contractorDocsRes = $conn->query($contractorDocsSql);
    if ($contractorDocsRes) {
        while ($row = $contractorDocsRes->fetch_assoc()) {
            $contractorDocs[] = $row;
        }
    }

    ?>
    <div class="content-header">
        <div>
            <h2 class="page-title">Document-wise Verification Desk</h2>
            <!-- <p class="page-subtitle">Verify each document separately (PCC, Medical, Aadhaar, Insurance, etc.)</p> -->
        </div>
    </div>

    <?php if (empty($pendingApps)): ?>
        <div class="alert alert-info"><i class="fas fa-info-circle"></i> No pending documents in the queue.</div>
    <?php else: ?>
        <div class="card glass mb-4">
            <div class="card-header bg-light">
                <div class="card-title">
                    <i class="fas fa-users text-primary"></i> Pending Document Verifications
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="custom-data-table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th>Contractor</th>
                                <th>Role</th>
                                <th>Worker Name</th>
                                <th>Application ID</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingApps as $app): 
                                $appId = $app['application_id'] ?: $app['request_no'];
                                $safeId = 'req-' . (int)$app['request_id'] . '-wm-' . (int)$app['workman_id'];
                            ?>
                            <!-- Master Row -->
                            <tr>
                                <td><span class="fw-semibold text-dark"><?= htmlspecialchars($app['contractor_name'] ?? 'N/A') ?></span></td>
                                <td><span class="badge badge-outline"><?= strtoupper($app['pass_type'] ?: ($app['worker_type'] ?? 'Workman')) ?></span></td>
                                <td><?= htmlspecialchars($app['worker_name'] ?? 'Unknown Worker') ?></td>
                                <td>
                                    <code><?= htmlspecialchars($app['request_no'] ?? $appId ?? '') ?></code>
                                    <?php if (!empty($app['application_id'])): ?>
                                        <div style="font-size:10px;color:#64748b;margin-top:2px;"><?= htmlspecialchars($app['application_id']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-warning">Action Required</span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="toggleAppDocs('<?= $safeId ?>')">
                                        <i class="fas fa-eye"></i> View Docs
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Detail Row (Accordion / Horizontal Cards) -->
                            <tr id="docs-row-<?= $safeId ?>" class="docs-detail-row" style="display:none;">
                                <td colspan="6">
                                    <div class="docs-detail-panel">
                                        <h6 class="docs-detail-title">
                                            <i class="fas fa-file-contract"></i> Documents for <?= htmlspecialchars($app['worker_name'] ?? 'Unknown Worker') ?>
                                        </h6>
                                        
                                        <div class="document-card-grid">
                                            <?php
                                            $workmanId = (int)$app['workman_id'];
                                            $requestCreatedAt = $conn->real_escape_string($app['request_created_at'] ?? date('Y-m-d H:i:s'));
                                            $sql1 = "
                                                SELECT
                                                    d.id,
                                                    d.document_type,
                                                    COALESCE(d.status, 'pending') AS status,
                                                    COALESCE(d.remarks, '') AS remarks,
                                                    d.file_path,
                                                    'documents' AS source_table,
                                                    d.uploaded_at
                                                FROM documents d
                                                WHERE d.workman_id = $workmanId
                                                  AND d.document_type IN (
                                                    'Medical Fitness Certificate',
                                                    'Police Clearance Certificate',
                                                    'Proof for Age',
                                                    'Proof for Address',
                                                    'Bank Account Proof',
                                                    'Insurance (ESI/WC)',
                                                    'Training Certificate'
                                                  )
                                                  AND d.uploaded_at >= DATE_SUB('$requestCreatedAt', INTERVAL 10 MINUTE)
                                            ";
                                            $docs = $conn->query("$sql1 ORDER BY uploaded_at DESC, id DESC");
                                            
                                            if (!$docs || $docs->num_rows === 0): ?>
                                                <div class="text-muted" style="font-size: 13px; font-style: italic;">No pending documents found.</div>
                                            <?php endif;
                                            
                                            while ($docs && $doc = $docs->fetch_assoc()):
                                                $statusBadge = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'reupload_required' => 'info'][$doc['status']] ?? 'gray';
                                            ?>
                                            
                                            <!-- Horizontal Document Card -->
                                            <div class="doc-card" id="doc-card-<?= $doc['id'] ?>">
                                                <div class="doc-card-head">
                                                    <strong class="doc-card-title">
                                                        <?= strtoupper(str_replace('_', ' ', $doc['document_type'])) ?>
                                                    </strong>
                                                    <span class="badge badge-<?= $statusBadge ?> doc-card-badge">
                                                        <?= strtoupper($doc['status']) ?>
                                                    </span>
                                                </div>
                                                
                                                <div class="doc-card-view">
                                                    <?php if (!empty($doc['file_path'])):
                                                        $docPath = strpos($doc['file_path'], '/') === false && strpos($doc['file_path'], '\\') === false
                                                            ? '../../uploads/documents/' . $doc['file_path']
                                                            : '../../' . ltrim($doc['file_path'], '/\\');
                                                    ?>
                                                        <a class="btn btn-sm btn-light doc-view-btn" href="<?= htmlspecialchars($docPath) ?>" target="_blank">
                                                            <i class="fas fa-file-pdf" style="color: #ef4444; margin-right: 4px;"></i> View Document
                                                        </a>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-light doc-view-btn" onclick="previewDoc('<?= htmlspecialchars($appId, ENT_QUOTES) ?>', '<?= htmlspecialchars($doc['document_type'], ENT_QUOTES) ?>')">
                                                            <i class="fas fa-file-pdf" style="color: #ef4444; margin-right: 4px;"></i> View Document
                                                        </button>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="doc-card-spacer"></div>

                                                <input type="text" class="form-control form-control-sm doc-remarks" id="remarks-<?= $doc['id'] ?>" value="<?= htmlspecialchars($doc['remarks'] ?? '') ?>" placeholder="Remarks (Mandatory if Rejecting)...">
                                                
                                                <div class="doc-action-row">
                                                    <button class="btn btn-sm btn-success" onclick="updateDoc(<?= $doc['id'] ?>, 'approved', '<?= $doc['source_table'] ?>', <?= (int)$app['request_id'] ?>)">
                                                        <i class="fas fa-check-circle"></i> Approve
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="updateDoc(<?= $doc['id'] ?>, 'reupload_required', '<?= $doc['source_table'] ?>', <?= (int)$app['request_id'] ?>)">
                                                        <i class="fas fa-times-circle"></i> Reject
                                                    </button>
                                                </div>
                                            </div>
                                            <?php endwhile; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card glass mb-4">
        <div class="card-header bg-light">
            <div class="card-title">
                <i class="fas fa-check-double text-success"></i> Approved Gate Pass Document Verifications
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (empty($approvedApps)): ?>
                <div class="alert alert-info" style="margin:16px;"><i class="fas fa-info-circle"></i> No approved gate pass document verifications found.</div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="custom-data-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th>Contractor</th>
                            <th>Role</th>
                            <th>Worker Name</th>
                            <th>Request No</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($approvedApps as $app):
                            $safeId = 'approved-req-' . (int)$app['request_id'] . '-wm-' . (int)$app['workman_id'];
                        ?>
                        <tr>
                            <td><span class="fw-semibold text-dark"><?= htmlspecialchars($app['contractor_name'] ?? 'N/A') ?></span></td>
                            <td><span class="badge badge-outline"><?= strtoupper($app['pass_type'] ?: ($app['worker_type'] ?? 'Workman')) ?></span></td>
                            <td><?= htmlspecialchars($app['worker_name'] ?? 'Unknown Worker') ?></td>
                            <td>
                                <code><?= htmlspecialchars($app['request_no'] ?? '') ?></code>
                                <?php if (!empty($app['application_id'])): ?>
                                    <div style="font-size:10px;color:#64748b;margin-top:2px;"><?= htmlspecialchars($app['application_id']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-success">APPROVED</span></td>
                            <td>
                                <button class="btn btn-sm btn-outline" onclick="toggleAppDocs('<?= $safeId ?>')">
                                    <i class="fas fa-eye"></i> View Docs
                                </button>
                            </td>
                        </tr>
                        <tr id="docs-row-<?= $safeId ?>" class="docs-detail-row" style="display:none;">
                            <td colspan="6">
                                <div class="docs-detail-panel">
                                    <h6 class="docs-detail-title">
                                        <i class="fas fa-file-contract"></i> Approved documents for <?= htmlspecialchars($app['worker_name'] ?? 'Unknown Worker') ?>
                                    </h6>
                                    <div class="document-card-grid">
                                        <?php
                                        $workmanId = (int)$app['workman_id'];
                                        $requestCreatedAt = $conn->real_escape_string($app['request_created_at'] ?? date('Y-m-d H:i:s'));
                                        $docs = $conn->query("
                                            SELECT id, document_type, COALESCE(status, 'pending') AS status, COALESCE(remarks, '') AS remarks, file_path, uploaded_at
                                            FROM documents
                                            WHERE workman_id = $workmanId
                                              AND document_type IN (
                                                'Medical Fitness Certificate',
                                                'Police Clearance Certificate',
                                                'Proof for Age',
                                                'Proof for Address',
                                                'Bank Account Proof',
                                                'Insurance (ESI/WC)',
                                                'Training Certificate'
                                              )
                                              AND uploaded_at >= DATE_SUB('$requestCreatedAt', INTERVAL 10 MINUTE)
                                            ORDER BY uploaded_at DESC, id DESC
                                        ");
                                        if (!$docs || $docs->num_rows === 0): ?>
                                            <div class="text-muted" style="font-size: 13px; font-style: italic;">No gate pass documents found for this approved request.</div>
                                        <?php endif; ?>
                                        <?php while ($docs && $doc = $docs->fetch_assoc()):
                                            $statusBadge = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'reupload_required' => 'info'][$doc['status']] ?? 'gray';
                                            $docPath = '';
                                            if (!empty($doc['file_path'])) {
                                                $docPath = strpos($doc['file_path'], '/') === false && strpos($doc['file_path'], '\\') === false
                                                    ? '../../uploads/documents/' . $doc['file_path']
                                                    : '../../' . ltrim($doc['file_path'], '/\\');
                                            }
                                        ?>
                                        <div class="doc-card">
                                            <div class="doc-card-head">
                                                <strong class="doc-card-title"><?= strtoupper(str_replace('_', ' ', $doc['document_type'])) ?></strong>
                                                <span class="badge badge-<?= $statusBadge ?> doc-card-badge"><?= strtoupper($doc['status']) ?></span>
                                            </div>
                                            <div class="doc-card-view">
                                                <?php if ($docPath): ?>
                                                    <a class="btn btn-sm btn-light doc-view-btn" href="<?= htmlspecialchars($docPath) ?>" target="_blank">
                                                        <i class="fas fa-file-pdf" style="color: #ef4444; margin-right: 4px;"></i> View Document
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted" style="font-size:12px;">No file uploaded</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($doc['remarks'])): ?>
                                                <div style="font-size:11px;color:#64748b;line-height:1.4;">Remarks: <?= htmlspecialchars($doc['remarks']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endwhile; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card glass mb-4" id="contractor-documents">
        <div class="card-header bg-light">
            <div class="card-title">
                <i class="fas fa-building-shield text-primary"></i> Contractor Uploaded Documents
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (empty($contractorDocs)): ?>
                <div class="alert alert-info" style="margin:16px;"><i class="fas fa-info-circle"></i> No pending contractor uploaded documents.</div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="custom-data-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th>Contractor</th>
                            <th>Vendor Code</th>
                            <th>Document Type</th>
                            <th>Uploaded File</th>
                            <th>Uploaded</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contractorDocs as $doc):
                            $statusBadge = ['pending' => 'warning', 'verified' => 'success', 'rejected' => 'danger', 'reupload_required' => 'info'][$doc['status']] ?? 'gray';
                            $docUrl = welfareDocUrl($doc['file_path'] ?? '');
                        ?>
                        <tr id="contractor-doc-row-<?= (int)$doc['id'] ?>">
                            <td><strong><?= htmlspecialchars($doc['contractor_name'] ?? 'N/A') ?></strong></td>
                            <td><code><?= htmlspecialchars($doc['vendor_code'] ?? '-') ?></code></td>
                            <td><?= strtoupper(str_replace('_', ' ', htmlspecialchars($doc['doc_type'] ?? 'Document'))) ?></td>
                            <td>
                                <?php if ($docUrl !== '#'): ?>
                                    <a href="<?= htmlspecialchars($docUrl) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-light" style="font-size:12px;background:#f8fafc;border-color:#cbd5e1;">
                                        <i class="fas fa-file-alt" style="color:#ef4444;margin-right:4px;"></i>
                                        <?= htmlspecialchars($doc['original_name'] ?: basename($doc['file_path'])) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">No file</span>
                                <?php endif; ?>
                            </td>
                            <td><?= !empty($doc['uploaded_at']) ? date('d M Y, H:i', strtotime($doc['uploaded_at'])) : '-' ?></td>
                            <td><span class="badge badge-<?= $statusBadge ?>"><?= strtoupper(str_replace('_', ' ', $doc['status'])) ?></span></td>
                            <td>
                                <input type="text" class="form-control form-control-sm" id="contractor-remarks-<?= (int)$doc['id'] ?>" value="<?= htmlspecialchars($doc['remarks'] ?? '') ?>" placeholder="Remarks if rejecting..." style="font-size:11px;padding:6px 10px;min-width:180px;">
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <button class="btn btn-sm btn-success" onclick="updateContractorDoc(<?= (int)$doc['id'] ?>, 'approved')">
                                        <i class="fas fa-check-circle"></i> Approve
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="updateContractorDoc(<?= (int)$doc['id'] ?>, 'reupload_required')">
                                        <i class="fas fa-times-circle"></i> Reject
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function toggleAppDocs(safeId) {
        const row = document.getElementById('docs-row-' + safeId);
        if (row.style.display === 'none' || row.style.display === '') {
            // Optional: Close all other open rows first for a cleaner look
            document.querySelectorAll('tr[id^="docs-row-"]').forEach(el => el.style.display = 'none');
            
            row.style.display = 'table-row';
            // Scroll slightly to bring it into view smoothly
            row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } else {
            row.style.display = 'none';
        }
    }

    async function updateDoc(id, status, sourceTable, requestId = 0) {
        const remarks = document.getElementById('remarks-' + id).value;
        if (status === 'reupload_required' && !remarks) {
            alert('Mandatory Remark: Please provide a reason for rejection (PDF Page 24).');
            return;
        }

        try {
            const res = await fetch('../../api/welfare/approve_document.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    doc_id: id, 
                    status: status, 
                    remarks: remarks,
                    source_table: sourceTable,
                    request_id: requestId
                })
            });
            const data = await res.json();
            if (data.success) {
                // Update individual doc card UI
                const card = document.getElementById('doc-card-' + id);
                if (card) {
                    const badge = card.querySelector('.badge');
                    if (status === 'approved') {
                        badge.className = 'badge badge-success';
                        badge.textContent = 'APPROVED';
                        card.style.opacity = '0.7';
                    } else {
                        badge.className = 'badge badge-info';
                        badge.textContent = 'REUPLOAD REQUIRED';
                    }
                }

                // If ALL documents approved → update master row status badge
                if (data.all_approved) {
                    const detailRow = card ? card.closest('tr[id^="docs-row-"]') : null;
                    if (detailRow) {
                        const masterRow = detailRow.previousElementSibling;
                        if (masterRow) {
                            const masterBadge = masterRow.querySelector('.badge');
                            if (masterBadge) {
                                masterBadge.className = 'badge badge-success';
                                masterBadge.innerHTML = '<i class="fas fa-check-double"></i> DOCUMENTS VERIFIED';
                            }
                        }
                    }
                    showToast('✅ All documents verified! Worker moved to Pending Pass Requests.', 'success');
                }
            } else {
                alert(data.message || 'Action failed.');
            }
        } catch (error) {
            console.error(error);
            alert('Network error while updating document.');
        }
    }

    async function updateContractorDoc(id, status) {
        const remarksInput = document.getElementById('contractor-remarks-' + id);
        const remarks = remarksInput ? remarksInput.value.trim() : '';
        if (status === 'reupload_required' && !remarks) {
            alert('Please provide a reason for rejection/re-upload.');
            return;
        }

        try {
            const res = await fetch('../../api/welfare/approve_document.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    doc_id: id,
                    status: status,
                    remarks: remarks,
                    source_table: 'contractor_documents'
                })
            });
            const data = await res.json();
            if (data.success) {
                const row = document.getElementById('contractor-doc-row-' + id);
                if (row) row.remove();
                showToast(status === 'approved' ? 'Contractor document verified.' : 'Re-upload requested from contractor.', 'success');
            } else {
                alert(data.message || 'Action failed.');
            }
        } catch (error) {
            console.error(error);
            alert('Network error while updating contractor document.');
        }
    }

    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;padding:16px 24px;border-radius:10px;color:#fff;font-size:14px;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,0.15);animation:slideIn 0.4s ease;max-width:420px;';
        toast.style.background = type === 'success' ? 'linear-gradient(135deg, #10b981, #059669)' : '#ef4444';
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(function() { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.5s'; }, 4000);
        setTimeout(function() { toast.remove(); }, 4500);
    }

    function previewDoc(appId, docType) {
        window.open('../common/doc_viewer.php?appId=' + appId + '&type=' + docType, '_blank', 'width=800,height=600');
    }
    </script>

    <style>
        .custom-data-table { width: 100%; border-collapse: collapse; }
        .custom-data-table th { background: #f8fafc; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e2e8f0; padding: 12px; text-align: left; }
        .custom-data-table td { vertical-align: middle; font-size: 13px; border-bottom: 1px solid #f1f5f9; padding: 12px; }
        .custom-data-table code { white-space: nowrap; }
        .badge-info { background: #3b82f6; color: #fff; }
        .badge-warning { background: #f59e0b; color: #fff; }
        .badge-success { background: #10b981; color: #fff; }
        .badge-danger { background: #ef4444; color: #fff; }
        .docs-detail-row { background-color:#f8fafc; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02); }
        .docs-detail-row > td { padding: 16px !important; vertical-align: top !important; }
        .docs-detail-panel { border-left: 3px solid #3b82f6; padding-left: 14px; width: 100%; }
        .docs-detail-title { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 12px; font-weight: 700; }
        .document-card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 12px;
            width: 100%;
            align-items: stretch;
        }
        .doc-card {
            min-width: 0;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
            transition: all 0.2s ease-in-out;
        }
        .doc-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08) !important; border-color: #cbd5e1 !important; }
        .doc-card-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 12px; }
        .doc-card-title { min-width: 0; font-size: 13px; color: #1e293b; line-height: 1.3; overflow-wrap: anywhere; }
        .doc-card-badge { flex: 0 0 auto; font-size: 9px; padding: 4px 6px; }
        .doc-card-view { margin-bottom: 12px; }
        .doc-view-btn { width: 100%; text-align: left; font-size: 12px; background: #f8fafc; border-color: #cbd5e1; white-space: normal; }
        .doc-card-spacer { flex-grow: 1; }
        .doc-remarks { font-size: 11px; padding: 6px 10px; margin-bottom: 8px; }
        .doc-action-row { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
        .doc-action-row .btn { font-size: 11px; padding: 6px 0; min-width: 0; }
        @media (max-width: 700px) {
            .document-card-grid { grid-template-columns: 1fr; }
            .docs-detail-row > td { padding: 10px !important; }
            .docs-detail-panel { padding-left: 10px; }
        }
    </style>
    <?php
}

renderLayout("Document Verification", 'renderContent', $_SESSION['role'], $_SESSION['name']);
?>
