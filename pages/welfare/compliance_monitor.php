<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_user', 'welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function ensureComplianceSchema($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS compliance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contractor_id INT NOT NULL,
        type VARCHAR(50),
        month_year VARCHAR(20),
        challan_number VARCHAR(100),
        amount DECIMAL(10,2),
        file_path VARCHAR(255),
        challan_worker_count INT DEFAULT 0,
        attendance_count INT DEFAULT 0,
        status ENUM('pending', 'verified', 'rejected', 'reupload_required') DEFAULT 'pending',
        verification_remarks TEXT,
        verified_by INT,
        verified_at TIMESTAMP NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_contractor (contractor_id),
        KEY idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $addColumn = function($column, $definition) use ($conn) {
        $column = $conn->real_escape_string($column);
        $exists = $conn->query("SHOW COLUMNS FROM compliance LIKE '$column'");
        if ($exists && $exists->num_rows == 0) {
            $conn->query("ALTER TABLE compliance ADD COLUMN `$column` $definition");
        }
    };

    $addColumn('challan_number', 'VARCHAR(100) AFTER month_year');
    $addColumn('amount', 'DECIMAL(10,2) DEFAULT 0.00 AFTER challan_number');
    $addColumn('file_path', 'VARCHAR(255) AFTER amount');
    $addColumn('challan_worker_count', 'INT DEFAULT 0 AFTER file_path');
    $addColumn('attendance_count', 'INT DEFAULT 0 AFTER challan_worker_count');
    $addColumn('verification_remarks', 'TEXT AFTER status');
    $addColumn('verified_by', 'INT DEFAULT NULL AFTER verification_remarks');
    $addColumn('verified_at', 'TIMESTAMP NULL AFTER verified_by');
    $conn->query("ALTER TABLE compliance MODIFY status ENUM('pending','verified','rejected','reupload_required') DEFAULT 'pending'");
}

function complianceDocumentPreviewMeta($filePath) {
    $meta = [
        'preview_allowed' => false,
        'preview_url' => '',
        'download_url' => '',
        'preview_type' => '',
        'preview_ext' => ''
    ];

    $rawPath = trim((string)$filePath);
    if ($rawPath === '' || preg_match('/^https?:\/\//i', $rawPath) || strpos($rawPath, "\0") !== false) {
        return $meta;
    }

    $normalized = str_replace('\\', '/', $rawPath);
    $filename = basename($normalized);
    if ($filename === '' || $filename === '.' || $filename === '..') {
        return $meta;
    }

    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowed = [
        'pdf' => 'pdf',
        'jpg' => 'image',
        'jpeg' => 'image',
        'png' => 'image',
        'webp' => 'image'
    ];

    if (!isset($allowed[$ext])) {
        return $meta;
    }

    $absolutePath = __DIR__ . '/../../uploads/compliance/' . $filename;
    if (!is_file($absolutePath)) {
        return $meta;
    }

    $baseUrl = defined('BASE_URL') ? BASE_URL : '../../';
    $safeUrl = $baseUrl . 'uploads/compliance/' . rawurlencode($filename);

    return [
        'preview_allowed' => true,
        'preview_url' => $safeUrl,
        'download_url' => $safeUrl,
        'preview_type' => $allowed[$ext],
        'preview_ext' => $ext
    ];
}

function renderContent() {
    global $conn;
    ensureComplianceSchema($conn);

    $sql = "
        SELECT 
            cm.id, cm.type, cm.month_year, cm.challan_number, cm.amount, cm.file_path,
            cm.challan_worker_count, cm.attendance_count, cm.status, cm.verification_remarks,
            cm.uploaded_at, cm.verified_at,
            c.contractor_name,
            u.name as verified_by_name
        FROM compliance cm
        LEFT JOIN contractors c ON cm.contractor_id = c.id
        LEFT JOIN users u ON cm.verified_by = u.id
        ORDER BY CASE WHEN cm.status = 'pending' THEN 1 ELSE 2 END, cm.uploaded_at DESC
        LIMIT 50
    ";
    
    $rows = [];
    $result = @$conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row = array_merge($row, complianceDocumentPreviewMeta($row['file_path'] ?? ''));
            $rows[] = $row;
        }
    }

    $statusColors = [
        'pending' => 'badge-warning',
        'verified' => 'badge-success',
        'rejected' => 'badge-danger',
        'reupload_required' => 'badge-info'
    ];

    ?>
    <div class="content-header">
        <div>
            <h2 class="page-title"><i class="fas fa-shield-halved" style="color:#6366f1;margin-right:8px"></i> Compliance Verification Desk</h2>
            <!-- <p class="page-subtitle">Verify ESI, PF, and KLWF challans submitted by contractors</p> -->
        </div>
    </div>

    <div class="card glass">
        <div class="card-header"><div class="card-title">Challan Submissions</div></div>
        <div class="card-body" style="padding:0">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:50px;">S.No</th>
                        <th>Contractor</th>
                        <th>Challan Type</th>
                        <th>Month</th>
                        <th>Uploaded Date</th>
                        <th>Attendance Match</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rows)): ?>
                        <?php $sno = 1; foreach($rows as $row): 
                            $attCount = (int)($row['attendance_count'] ?? 0);
                            $chalCount = (int)($row['challan_worker_count'] ?? 0);
                            $diff = abs($attCount - $chalCount);
                            $matchStatus = ($attCount === $chalCount)
                                ? '<span class="text-success"><i class="fas fa-check-circle"></i> Match</span>'
                                : '<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> Mismatch ('.$diff.')</span>';
                        ?>
                        <tr class="table-row">
                            <td><?= $sno++ ?></td>
                            <td><strong><?= htmlspecialchars($row['contractor_name']) ?></strong></td>
                            <td><span class="badge badge-gray"><?= strtoupper($row['type'] ?? 'N/A') ?></span></td>
                            <td><?= htmlspecialchars($row['month_year']) ?></td>
                            <td><?= date('d M Y', strtotime($row['uploaded_at'])) ?></td>
                            <td><?= $matchStatus ?></td>
                            <td><span class="badge <?= $statusColors[$row['status']] ?? 'badge-gray' ?>"><?= ucfirst(str_replace('_', ' ', $row['status'])) ?></span></td>
                            <td>
                                <?php if ($row['status'] === 'pending'): ?>
                                    <button class="btn btn-sm btn-primary" onclick="openVerifyModal(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)">
                                        <i class="fas fa-search"></i> Review
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="openVerifyModal(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    </div>

    <!-- Verification Modal -->
    <div id="verifyModal" class="custom-modal">
        <div class="custom-modal-content">
            <div class="custom-modal-header">
                <h3><i class="fas fa-file-signature"></i> Verify Compliance Document</h3>
                <span class="close-btn" onclick="closeModal()">&times;</span>
            </div>
            
            <div class="custom-modal-body">
                <!-- Left Column: Details -->
                <div class="modal-left-col">
                    <div class="info-card">
                        <h5>Contractor Details</h5>
                        <div class="info-grid">
                            <div><strong>Name:</strong> <span id="m_contractor" class="text-primary fw-bold"></span></div>
                            <div><strong>Type:</strong> <span id="m_type" class="badge badge-primary"></span></div>
                            <div><strong>Month:</strong> <span id="m_month" class="fw-bold"></span></div>
                            <div><strong>Date:</strong> <span id="m_date"></span></div>
                        </div>
                    </div>

                    <div class="info-card">
                        <h5>Challan Details</h5>
                        <div class="info-grid">
                            <div><strong>Challan No:</strong> <span id="m_challan_no" class="fw-bold"></span></div>
                            <div><strong>Amount:</strong> <span class="text-success fw-bold">₹<span id="m_amount"></span></span></div>
                        </div>
                    </div>

                    <div class="info-card">
                        <h5>Worker Validation</h5>
                        <div class="info-grid">
                            <div><strong>System Attendance:</strong> <span id="m_att_count" class="fw-bold"></span> workers</div>
                            <div><strong>Challan Paid For:</strong> <span id="m_chal_count" class="fw-bold"></span> workers</div>
                            <div style="grid-column: span 2; margin-top: 5px; padding-top: 5px; border-top: 1px dashed #cbd5e1;">
                                <strong>Status:</strong> <span id="m_match_status"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div id="m_actions_container">
                        <div class="form-group" style="margin-bottom:15px;">
                            <label>Verification Remarks (Required for Reject/Reupload)</label>
                            <textarea id="m_remarks" class="form-control" rows="3" placeholder="Required only for reject or reupload..."></textarea>
                        </div>
                        <div class="modal-actions">
                            <input type="hidden" id="m_id">
                            <button type="button" id="verifyBtn" class="btn btn-success" onclick="submitVerification('verified')"><i class="fas fa-check"></i> Verify</button>
                            <button type="button" id="reuploadBtn" class="btn btn-warning" onclick="submitVerification('reupload_required')"><i class="fas fa-undo"></i> Ask Reupload</button>
                            <button type="button" id="rejectBtn" class="btn btn-danger" onclick="submitVerification('rejected')"><i class="fas fa-times"></i> Reject</button>
                        </div>
                        <div id="verifyDisabledNote" class="validation-note" style="display:none;"></div>
                    </div>

                    <div id="m_history" class="history-box" style="display:none;">
                        <p class="text-muted small text-center">This record has been verified and is now read-only.</p>
                        <strong>Last Status:</strong> <span id="m_last_status" class="badge"></span><br>
                        <strong>Verified By:</strong> <span id="m_verified_by" class="fw-bold"></span> on <span id="m_verified_at"></span><br>
                        <strong>Remarks:</strong> <span id="m_prev_remarks" class="text-muted"></span>
                    </div>
                </div>

                <!-- Right Column: Document Preview -->
                <div class="modal-right-col">
                    <div class="preview-header">
                        <div>
                            <strong><i class="fas fa-file-alt text-danger"></i> Document Preview</strong>
                            <div id="m_file_meta" class="preview-meta">PDF, JPG, PNG, WEBP only</div>
                        </div>
                        <a id="m_download_link" href="#" target="_blank" rel="noopener" class="btn btn-xs btn-outline-primary"><i class="fas fa-download"></i> Download</a>
                    </div>
                    <div class="preview-body">
                        <div id="m_preview_loading" class="preview-state preview-loading" style="display:none;">
                            <span class="preview-spinner"></span>
                            <span>Loading document...</span>
                        </div>
                        <iframe id="m_preview_frame" src="about:blank" title="Compliance document preview" loading="lazy" sandbox="allow-same-origin allow-downloads"></iframe>
                        <img id="m_preview_image" src="" alt="Compliance document preview">
                        <div id="m_no_file" class="preview-state" style="display:none;">
                            <i class="fas fa-file-circle-xmark"></i>
                            <strong>No document available</strong>
                            <span>Upload a PDF or image file to preview it here.</span>
                        </div>
                        <div id="m_preview_error" class="preview-state preview-error" style="display:none;">
                            <i class="fas fa-triangle-exclamation"></i>
                            <strong>Unable to load document.</strong>
                            <span>Please download the file or ask the contractor to reupload.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    let complianceCountsMatch = false;
    const allowedPreviewExtensions = new Set(['pdf', 'jpg', 'jpeg', 'png', 'webp']);

    function getPreviewExtension(url) {
        try {
            const cleanPath = new URL(url, window.location.origin).pathname.toLowerCase();
            return cleanPath.split('.').pop();
        } catch (e) {
            return '';
        }
    }

    function isSafePreviewUrl(url) {
        if (!url || typeof url !== 'string') return false;
        try {
            const parsed = new URL(url, window.location.origin);
            const ext = getPreviewExtension(parsed.href);
            return parsed.origin === window.location.origin
                && parsed.pathname.includes('/uploads/compliance/')
                && allowedPreviewExtensions.has(ext)
                && !parsed.pathname.toLowerCase().endsWith('.php')
                && !parsed.pathname.includes('..');
        } catch (e) {
            return false;
        }
    }

    function setPreviewState(state) {
        const iframe = document.getElementById('m_preview_frame');
        const image = document.getElementById('m_preview_image');
        const loading = document.getElementById('m_preview_loading');
        const noFile = document.getElementById('m_no_file');
        const error = document.getElementById('m_preview_error');

        iframe.style.display = state === 'pdf' ? 'block' : 'none';
        image.style.display = state === 'image' ? 'block' : 'none';
        loading.style.display = state === 'loading' ? 'flex' : 'none';
        noFile.style.display = state === 'empty' ? 'flex' : 'none';
        error.style.display = state === 'error' ? 'flex' : 'none';
    }

    function resetDocumentPreview() {
        const iframe = document.getElementById('m_preview_frame');
        const image = document.getElementById('m_preview_image');
        const dlLink = document.getElementById('m_download_link');
        document.getElementById('m_file_meta').textContent = 'PDF, JPG, PNG, WEBP only';
        iframe.onload = null;
        image.onload = null;
        image.onerror = null;
        iframe.src = 'about:blank';
        image.removeAttribute('src');
        dlLink.removeAttribute('href');
        dlLink.style.display = 'none';
        setPreviewState('empty');
    }

    function renderDocumentPreview(data) {
        resetDocumentPreview();

        if (!data.preview_allowed || !isSafePreviewUrl(data.preview_url)) {
            setPreviewState('empty');
            return;
        }

        const url = new URL(data.preview_url, window.location.origin).href;
        const ext = (data.preview_ext || getPreviewExtension(url)).toLowerCase();
        const type = ext === 'pdf' ? 'pdf' : 'image';
        const iframe = document.getElementById('m_preview_frame');
        const image = document.getElementById('m_preview_image');
        const dlLink = document.getElementById('m_download_link');
        const fileMeta = document.getElementById('m_file_meta');

        fileMeta.textContent = ext.toUpperCase() + ' document';
        dlLink.href = url;
        dlLink.style.display = 'inline-flex';
        setPreviewState('loading');

        if (type === 'pdf') {
            iframe.onload = function() {
                setPreviewState('pdf');
            };
            iframe.src = url + '#toolbar=1&navpanes=0&view=FitH';
            setTimeout(function() {
                if (document.getElementById('m_preview_loading').style.display !== 'none') {
                    setPreviewState('pdf');
                }
            }, 1200);
            return;
        }

        image.onload = function() {
            setPreviewState('image');
        };
        image.onerror = function() {
            setPreviewState('error');
        };
        image.src = url;
    }

    function openVerifyModal(data) {
        document.getElementById('m_id').value = data.id;
        document.getElementById('m_contractor').textContent = data.contractor_name;
        document.getElementById('m_type').textContent = (data.type || '').toUpperCase();
        document.getElementById('m_month').textContent = data.month_year;
        document.getElementById('m_date').textContent = new Date(data.uploaded_at).toLocaleDateString('en-GB');
        
        document.getElementById('m_challan_no').textContent = data.challan_number || '-';
        document.getElementById('m_amount').textContent = data.amount || '0.00';
        
        const attCount = parseInt(data.attendance_count) || 0;
        const chalCount = parseInt(data.challan_worker_count) || 0;
        document.getElementById('m_att_count').textContent = attCount;
        document.getElementById('m_chal_count').textContent = chalCount;
        
        const diff = Math.abs(attCount - chalCount);
        const matchStatus = document.getElementById('m_match_status');
        complianceCountsMatch = attCount === chalCount;
        if (complianceCountsMatch) {
            matchStatus.innerHTML = '<span class="text-success fw-bold"><i class="fas fa-check-circle"></i> Match</span>';
        } else if (attCount < chalCount) {
            matchStatus.innerHTML = '<span class="text-danger fw-bold"><i class="fas fa-exclamation-triangle"></i> Mismatch (Attendance short by ' + diff + ' workers)</span>';
        } else {
            matchStatus.innerHTML = '<span class="text-danger fw-bold"><i class="fas fa-exclamation-triangle"></i> Mismatch (Challan short by ' + diff + ' workers)</span>';
        }

        const verifyBtn = document.getElementById('verifyBtn');
        const disabledNote = document.getElementById('verifyDisabledNote');
        verifyBtn.disabled = !complianceCountsMatch;
        verifyBtn.title = complianceCountsMatch ? '' : 'Attendance mismatch. Verify disabled.';
        disabledNote.style.display = complianceCountsMatch ? 'none' : 'block';
        disabledNote.textContent = complianceCountsMatch ? '' : 'Attendance mismatch. Verify disabled.';

        document.getElementById('m_remarks').value = '';

        if (data.status !== 'pending') {
            document.getElementById('m_history').style.display = 'block';
            document.getElementById('m_actions_container').style.display = 'none';
            document.getElementById('m_last_status').textContent = data.status.toUpperCase();
            document.getElementById('m_verified_by').textContent = data.verified_by_name || 'Unknown';
            document.getElementById('m_verified_at').textContent = data.verified_at ? new Date(data.verified_at).toLocaleString('en-GB') : '-';
            document.getElementById('m_prev_remarks').textContent = data.verification_remarks || 'None';
        } else {
            document.getElementById('m_history').style.display = 'none';
            document.getElementById('m_actions_container').style.display = 'block';
        }

        renderDocumentPreview(data);

        document.getElementById('verifyModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('verifyModal').style.display = 'none';
        resetDocumentPreview();
    }

    async function submitVerification(status) {
        const id = document.getElementById('m_id').value;
        const remarks = document.getElementById('m_remarks').value.trim();

        if ((status === 'rejected' || status === 'reupload_required') && !remarks) {
            alert('Please provide remarks explaining the reason.');
            return;
        }

        if (!confirm(`Are you sure you want to mark this document as ${status.toUpperCase()}?`)) return;

        try {
            const formData = new FormData();
            formData.append('action', 'verify_compliance');
            formData.append('id', id);
            formData.append('status', status);
            formData.append('remarks', remarks);

            const res = await fetch('../../api/welfare/verify_compliance.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await res.json();
            if (data.success) {
                alert('Compliance status updated successfully.');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        } catch(e) {
            alert('Network error occurred.');
        }
    }
    </script>

    <style>
    .custom-modal { display: none; position: fixed; z-index: 1050; inset: 0; align-items: center; justify-content: center; padding: 24px; background-color: rgba(15, 23, 42, 0.62); backdrop-filter: blur(5px); }
    .custom-modal-content { background: #ffffff; width: min(1180px, 100%); border-radius: 12px; box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28); display: flex; flex-direction: column; max-height: min(92vh, 860px); overflow: hidden; border: 1px solid #e2e8f0; animation: modalFadeIn 0.22s ease-out; }
    .custom-modal-header { padding: 16px 22px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #ffffff; }
    .custom-modal-header h3 { margin: 0; font-size: 1.08rem; font-weight: 800; color: #1e293b; display: flex; align-items: center; gap: 10px; letter-spacing: 0; }
    .custom-modal-header h3 i { color: #6366f1; }
    .close-btn { font-size: 28px; font-weight: bold; color: #94a3b8; cursor: pointer; transition: color 0.2s; line-height: 1; }
    .close-btn:hover { color: #ef4444; }
    .custom-modal-body { display: grid; grid-template-columns: minmax(360px, 0.9fr) minmax(420px, 1.1fr); padding: 18px; gap: 18px; flex: 1; min-height: 0; overflow: hidden; background: #f8fafc; }
    .modal-left-col { min-width: 0; overflow-y: auto; padding-right: 2px; display: flex; flex-direction: column; gap: 14px; }
    .modal-right-col { min-width: 0; border: 1px solid #dbe3ee; border-radius: 10px; background: #ffffff; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04); }
    .info-card { background: #ffffff; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03); }
    .info-card h5 { margin: 0 0 12px 0; font-size: 0.78rem; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0; }
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 0.875rem; color: #334155; line-height: 1.45; }
    .info-grid strong { color: #64748b; font-weight: 600; margin-right: 4px; }
    .history-box { background: #f0fdf4; padding: 12px 16px; border-radius: 8px; border: 1px solid #bbf7d0; font-size: 0.875rem; }
    .modal-actions { display: flex; gap: 12px; margin-top: auto; padding-top: 10px; }
    .modal-actions .btn { flex: 1 1 0; min-width: 0; justify-content: center; padding: 10px 12px; font-weight: 700; border-radius: 8px; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05); white-space: nowrap; }
    .modal-actions .btn:disabled { opacity: 0.55; cursor: not-allowed; box-shadow: none; }
    .validation-note { margin-top: 8px; color: #dc2626; font-size: 0.82rem; font-weight: 700; }
    .preview-header { padding: 12px 14px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; gap: 12px; background: #ffffff; }
    .preview-header strong { display: flex; align-items: center; gap: 8px; color: #1f2937; }
    .preview-meta { color: #64748b; font-size: 0.76rem; margin-top: 3px; }
    .preview-body { flex: 1; position: relative; min-height: 520px; overflow: hidden; background: #eef2f7; }
    .preview-body iframe,
    .preview-body img { display: none; width: 100%; height: 100%; min-height: 520px; border: 0; background: #ffffff; }
    .preview-body img { object-fit: contain; padding: 14px; box-sizing: border-box; }
    .preview-state { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; text-align: center; color: #64748b; padding: 28px; }
    .preview-state i { font-size: 34px; color: #94a3b8; }
    .preview-state strong { color: #334155; font-size: 0.95rem; }
    .preview-loading { color: #475569; font-weight: 700; }
    .preview-error i, .preview-error strong { color: #dc2626; }
    .preview-spinner { width: 26px; height: 26px; border: 3px solid #cbd5e1; border-top-color: #6366f1; border-radius: 999px; animation: previewSpin 0.8s linear infinite; }
    .form-group label { font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 8px; display: block; }
    .form-control { width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; font-size: 0.875rem; transition: border-color 0.2s, box-shadow 0.2s; resize: vertical; }
    .form-control:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
    .badge-warning { background: #fef3c7; color: #92400e; }
    .badge-success { background: #dcfce7; color: #166534; }
    .badge-danger { background: #fee2e2; color: #991b1b; }
    .text-success { color: #16a34a !important; }
    .text-danger { color: #dc2626 !important; }
    @media (max-width: 980px) {
        .custom-modal { padding: 12px; align-items: flex-start; }
        .custom-modal-content { max-height: calc(100vh - 24px); }
        .custom-modal-body { grid-template-columns: 1fr; overflow-y: auto; }
        .modal-left-col { overflow: visible; }
        .preview-body, .preview-body iframe, .preview-body img { min-height: 420px; }
    }
    @keyframes previewSpin { to { transform: rotate(360deg); } }
    @keyframes modalFadeIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
    <?php
}

renderLayout("Compliance Monitor", 'renderContent', $_SESSION['role'], $_SESSION['name']);
?>
