<?php
require_once '../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin', 'welfare_user']);
include '../../include/config.php';
include '../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Admin';

function renderContent() {
    global $conn;
    $pending = db_fetch_all($conn, "
        SELECT a3.*
        FROM contractor_annexure3a a3
        WHERE a3.status IN ('pending', 'resubmitted')
        ORDER BY COALESCE(a3.updated_at, a3.created_at) DESC, a3.id DESC
    ");
?>
<div class="content-header">
    <div>
        <h2 class="page-title"><i class="fas fa-check-double" style="color:#6366f1;margin-right:10px;"></i>Contractor Info Approvals</h2>
        <!-- <p class="page-subtitle">Verify specific work order details and manpower strength for job execution.</p> -->
    </div>
</div>

<div class="card glass">
    <div class="card-header"><div class="card-title">Pending  Approvals</div></div>
    <div class="card-body" style="padding:0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Customer Code</th>
                    <th>License No</th>
                    <th>Insurance Policy</th>
                    <th>Manpower</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($pending)): ?>
                    <tr><td colspan="7" style="text-align:center;padding:40px;">No pending 3A submissions.</td></tr>
                <?php else: ?>
                <?php foreach($pending as $p): 
                    $p['documents'] = db_fetch_all($conn, "
                        SELECT doc_type, file_path, original_name, status, uploaded_at
                        FROM contractor_documents
                        WHERE annexure3a_id = ?
                        ORDER BY uploaded_at DESC, id DESC
                    ", 'i', [$p['id']]);

                    if (!empty($p['license_details_json'])) {
                        $licenseDocs = json_decode($p['license_details_json'], true);
                        if (is_array($licenseDocs)) {
                            foreach ($licenseDocs as $idx => $licenseDoc) {
                                $licensePath = trim($licenseDoc['file_path'] ?? '');
                                if ($licensePath !== '') {
                                    $p['documents'][] = [
                                        'doc_type' => 'labour_license',
                                        'file_path' => $licensePath,
                                        'original_name' => $licenseDoc['license_no'] ? ('Labour License - ' . $licenseDoc['license_no']) : ('Labour License ' . ($idx + 1)),
                                        'status' => $p['status'] ?? 'pending',
                                        'uploaded_at' => $p['updated_at'] ?? $p['created_at'] ?? ''
                                    ];
                                }
                            }
                        }
                    }
                    $payload = htmlspecialchars(json_encode($p, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                ?>
                <tr>
                    <td><code><?= htmlspecialchars($p['customer_code'] ?? '') ?></code></td>
                    <td>
                        <div style="font-size:11px;">No: <?= htmlspecialchars($p['labour_license_no'] ?? '—') ?></div>
                        <div style="font-size:11px;">Exp: <?= ($p['labour_license_expiry_date']) ? date('d/m/y', strtotime($p['labour_license_expiry_date'])) : '—' ?></div>
                    </td>
                    <td>
                        <div style="font-size:11px;">Policy: <?= htmlspecialchars($p['insurance_policy_no'] ?? '—') ?></div>
                        <div style="font-size:11px;">Exp: <?= ($p['insurance_validity']) ? date('d/m/y', strtotime($p['insurance_validity'])) : '—' ?></div>
                    </td>
                    <td>
                        <span class="badge badge-success">S: <?= (int)($p['skilled_workers'] ?? 0) ?></span>
                        <span class="badge badge-info">SS: <?= (int)($p['semi_skilled_workers'] ?? 0) ?></span>
                        <span class="badge badge-warning">U: <?= (int)($p['unskilled_workers'] ?? 0) ?></span>
                        <div style="font-size:11px;margin-top:4px;">Total: <?= (int)($p['total_workers'] ?? 0) ?></div>
                    </td>
                    <td><?= ($p['created_at']) ? date('d M Y', strtotime($p['created_at'])) : '—' ?></td>
                    <td>
                        <span class="badge <?= strtolower($p['status'] ?? '') === 'resubmitted' ? 'badge-warning' : 'badge-info' ?>">
                            <?= htmlspecialchars(strtoupper(str_replace('_', ' ', $p['status'] ?? 'pending'))) ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:5px;">
                            <button class="btn btn-sm btn-outline" onclick='viewDetails(<?= $payload ?>)'><i class="fas fa-eye"></i> View</button>
                            <button class="btn btn-sm btn-primary" onclick="process3A(<?= $p['id'] ?>, 'approved')">Approve</button>
                            <button class="btn btn-sm btn-danger" onclick="process3A(<?= $p['id'] ?>, 'rejected')">Reject</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Details Modal -->
<div id="detailsModal" class="modal-backdrop hidden">
    <div class="modal-content glass" style="max-width: 1100px; padding: 0;">
        <div class="modal-header" style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1);">
            <h3><i class="fas fa-info-circle"></i> Customer Registration Details</h3>
            <button class="btn-close" onclick="closeModal('detailsModal')">&times;</button>
        </div>
        <div class="modal-body" id="detailsBody" style="padding: 24px; max-height: 70vh; overflow-y: auto;">
            <!-- Content populated via JS -->
        </div>
    </div>
</div>

<style>
.modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(10px); display: flex; align-items: center; justify-content: center; z-index: 3000; padding: 20px; overflow-y: auto; }
.modal-content { width: 100%; border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); box-shadow: 0 30px 80px rgba(0,0,0,0.6); background: rgba(6, 12, 24, 0.98); color: var(--text-primary); max-height: 90vh; display: flex; flex-direction: column; }
.modal-body { color: var(--text-primary); padding: 20px 28px; overflow-y: auto; }
#detailsModal, #detailsModal * { color: #ffffff !important; }
.hidden { display: none !important; visibility: hidden !important; }
.modal-header { display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.01); padding: 18px 24px; }
.btn-close { background: none; border: none; font-size: 26px; color: var(--text-muted); cursor: pointer; transition: color 0.15s ease; }
.btn-close:hover { color: #ff7a7a; }
.form-container { display:flex; flex-direction:column; gap:20px; padding-bottom:16px; }
.form-section-card { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.04); border-radius:12px; padding:18px; }
.form-section-header { font-size:15px; font-weight:700; color:#ffffff; margin-bottom:14px; padding-bottom:8px; border-bottom:1px solid rgba(255,255,255,0.03); display:flex; align-items:center; gap:8px; }
.form-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:20px; }
.form-field { display:flex; flex-direction:column; gap:8px; }
.form-field.span-2 { grid-column:span 2; }
.form-field.span-3 { grid-column:span 3; }
.form-field label { font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; }
.form-field .value-box { background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.04); border-radius:8px; padding:12px 14px; font-size:13px; color:#ffffff; font-weight:500; min-height:44px; word-break:break-word; display:flex; align-items:center; }
.review-table { width:100%; border-collapse:collapse; font-size:12px; }
.review-table th, .review-table td { border:1px solid rgba(255,255,255,0.08); padding:10px; text-align:left; }
.review-table th { background:rgba(255,255,255,0.04); font-weight:800; text-transform:uppercase; font-size:11px; }
.document-grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:16px; }
.doc-card { display:flex; align-items:center; justify-content:space-between; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.04); border-radius:10px; padding:12px 16px; gap:14px; }
.doc-info { display:flex; align-items:center; gap:14px; min-width:0; }
.doc-icon { font-size:22px; color:#60a5fa; flex-shrink:0; }
.doc-meta { display:flex; flex-direction:column; min-width:0; }
.doc-type { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:2px; }
.doc-name { font-size:13px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.doc-btn { font-size:12px; font-weight:700; color:#fff; text-decoration:none; display:inline-flex; align-items:center; gap:8px; background:rgba(79,70,229,0.12); padding:8px 10px; border-radius:8px; flex-shrink:0; border:1px solid rgba(255,255,255,0.03); }
.doc-btn:hover { background:#4f46e5; color:#fff; }
.no-docs { grid-column:span 2; text-align:center; padding:20px; color:var(--text-muted); font-style:italic; background:rgba(255,255,255,0.01); border:1px dashed rgba(255,255,255,0.04); border-radius:10px; }
@media (max-width: 900px) { .form-grid { grid-template-columns:repeat(2, 1fr); } .form-field.span-2, .form-field.span-3 { grid-column:span 1; } }
@media (max-width: 700px) { .form-grid, .document-grid { grid-template-columns:1fr; } .no-docs { grid-column:span 1; } }
</style>

<script>
const docLabels = {
    labour_license: 'Labour License',
    insurance_policy: 'Insurance Policy',
    epf_challan: 'EPF Challan',
    esi_challan: 'ESI Challan',
    bank_details: 'Bank Details / Cancelled Cheque',
    pan: 'PAN Card Copy',
    gst: 'GST Registration Copy',
    agreement_copy: 'Work Order / Agreement Copy'
};

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (ch) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[ch]));
}

function text(value, fallback = '—') {
    const clean = value === null || value === undefined || value === '' ? fallback : value;
    return escapeHtml(clean);
}

function formatDate(value) {
    if (!value) return '—';
    const date = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return text(value);
    return date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
}

function docUrl(path) {
    if (!path) return '#';
    if (/^https?:\/\//i.test(path)) return path;
    return '../../' + String(path).replace(/^\/+/, '');
}

function renderDocuments(docs) {
    if (!Array.isArray(docs) || docs.length === 0) {
        return `<div class="no-docs"><i class="fas fa-folder-open" style="font-size:24px;margin-bottom:8px;display:block;"></i>No documents uploaded for this application.</div>`;
    }

    return `<div class="document-grid">${docs.map((doc) => {
        const label = docLabels[doc.doc_type] || String(doc.doc_type || '').replace(/_/g, ' ').toUpperCase();
        const fileName = doc.original_name || (doc.file_path ? String(doc.file_path).split('/').pop() : 'Document');
        return `<div class="doc-card">
            <div class="doc-info">
                <i class="fas fa-file-alt doc-icon"></i>
                <div class="doc-meta">
                    <span class="doc-type">${text(label)}</span>
                    <span class="doc-name" title="${text(fileName)}">${text(fileName)}</span>
                </div>
            </div>
            <a href="${escapeHtml(docUrl(doc.file_path))}" target="_blank" rel="noopener" class="doc-btn"><i class="fas fa-external-link-alt"></i> View</a>
        </div>`;
    }).join('')}</div>`;
}

function valueBox(label, value, span = '') {
    return `<div class="form-field ${span}"><label>${escapeHtml(label)}</label><div class="value-box">${text(value)}</div></div>`;
}

function parseJsonRows(value) {
    if (!value) return [];
    if (Array.isArray(value)) return value;
    try {
        const rows = JSON.parse(value);
        return Array.isArray(rows) ? rows : [];
    } catch (e) {
        return [];
    }
}

function reasonPart(raw, label) {
    const value = String(raw || '');
    const re = new RegExp(label.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ':\\s*([\\s\\S]*?)(?=\\n[A-Z][A-Za-z ]+ Reason:|$)');
    const match = value.match(re);
    return match ? match[1].trim() : '';
}

function renderRowsTable(headers, rows, mapper) {
    if (!rows.length) return `<div class="value-box" style="display:block;">No rows added.</div>`;
    return `<table class="review-table"><thead><tr>${headers.map(h => `<th>${escapeHtml(h)}</th>`).join('')}</tr></thead><tbody>${rows.map((row, index) => `<tr>${mapper(row, index).map(cell => `<td>${text(cell)}</td>`).join('')}</tr>`).join('')}</tbody></table>`;
}

function oldViewDetails(p) {
    let html = `<div class="detail-grid">
        <div class="section-title">Contractor Identification</div>
        <div class="detail-item"><div class="detail-label">Name</div><div class="detail-value">${p.contractor_name}</div></div>
        <div class="detail-item"><div class="detail-label">Customer Code</div><div class="detail-value">${p.customer_code}</div></div>
        
        <div class="section-title">Procurement & Work Order</div>
        <div class="detail-item"><div class="detail-label">WO No</div><div class="detail-value">${p.wo_no || '—'}</div></div>
        <div class="detail-item"><div class="detail-label">PWO No</div><div class="detail-value">${p.pwo_no || '—'}</div></div>
        <div class="detail-item"><div class="detail-label">SO No</div><div class="detail-value">${p.so_no || '—'}</div></div>
        
        <div class="section-title">Statutory Details</div>
        <div class="detail-item"><div class="detail-label">EPF Reg</div><div class="detail-value">${p.is_epf_registered == '1' ? 'YES ('+p.epf_code+')' : 'NO'}</div></div>
        <div class="detail-item"><div class="detail-label">ESI Reg</div><div class="detail-value">${p.is_esi_registered == '1' ? 'YES ('+p.esi_code+')' : 'NO'}</div></div>
        
        <div class="section-title">Insurance & License</div>
        <div class="detail-item"><div class="detail-label">Labour License No</div><div class="detail-value">${p.labour_license_no || '—'}</div></div>
        <div class="detail-item"><div class="detail-label">License Expiry</div><div class="detail-value">${p.labour_license_expiry_date || '—'}</div></div>
        <div class="detail-item"><div class="detail-label">Insurance Policy No</div><div class="detail-value">${p.insurance_policy_no || '—'}</div></div>
        <div class="detail-item"><div class="detail-label">Insurance Validity</div><div class="detail-value">${p.insurance_validity || '—'}</div></div>
        
        <div class="section-title">Proposed Workforce</div>
        <div class="detail-item"><div class="detail-label">Skilled</div><div class="detail-value">${p.skilled_workers}</div></div>
        <div class="detail-item"><div class="detail-label">Unskilled</div><div class="detail-value">${p.unskilled_workers}</div></div>
        <div class="detail-item"><div class="detail-label">Total</div><div class="detail-value"><b>${parseInt(p.skilled_workers)+parseInt(p.unskilled_workers)}</b></div></div>
        
        <div class="section-title">Wage Declaration</div>
        <div class="detail-item" style="grid-column: span 2;"><div class="detail-label">Statement</div><div class="detail-value" style="background: rgba(0,0,0,0.05); padding: 10px; border-radius: 8px;">${p.wage_declaration || 'No declaration provided'}</div></div>
    </div>`;
    
    document.getElementById('detailsBody').innerHTML = html;
    document.getElementById('detailsModal').classList.remove('hidden');
}

function viewDetails(p) {
    const skilled = parseInt(p.skilled_workers || 0, 10);
    const semiSkilled = parseInt(p.semi_skilled_workers || 0, 10);
    const unskilled = parseInt(p.unskilled_workers || 0, 10);
    const total = parseInt(p.total_workers || 0, 10) || (skilled + semiSkilled + unskilled);
    const epfYes = String(p.is_epf_registered) === '1' || p.is_epf_registered === 'YES';
    const esiYes = String(p.is_esi_registered) === '1' || p.is_esi_registered === 'YES';
    const ecpRows = parseJsonRows(p.ecp_details_json);
    const licenseRows = parseJsonRows(p.license_details_json);
    const reasons = p.epf_esi_exemption_reason || '';

    const html = `<div class="form-container">
        <div class="form-section-card">
            <div class="form-section-header"><i class="fas fa-building"></i> Annexure 3A Submission</div>
            <div class="form-grid">
                ${valueBox('Work Order No', p.work_order_no, 'span-2')}
                ${valueBox('Submitted On', formatDate(p.created_at))}
                ${valueBox('Customer Code', p.customer_code)}
                ${valueBox('Vendor Code', p.vendor_code)}
                ${valueBox('Status', String(p.status || 'pending').replace(/_/g, ' ').toUpperCase())}
                ${valueBox('PIN Code', p.pin_code)}
            </div>
        </div>

        <div class="form-section-card">
            <div class="form-section-header"><i class="fas fa-file-signature"></i> Customer Registration Fields</div>
            <div class="form-grid">
                ${valueBox('1. Work Awarding Department', p.work_awarding_department, 'span-3')}
                ${valueBox('2. Whether Registered under EPF', epfYes ? 'YES' : 'NO')}
                ${valueBox('3. EPF Establishment Code', p.epf_code)}
                ${valueBox('EPF Non-Registration Reason', reasonPart(reasons, 'EPF Reason'), 'span-3')}
                ${valueBox('4. Whether Registered under ESI', esiYes ? 'YES' : 'NO')}
                ${valueBox('ESI Establishment Code', p.esi_code)}
                ${valueBox('ESI Non-Registration Reason', reasonPart(reasons, 'ESI Reason'), 'span-3')}
                ${valueBox('5. Wage Declaration by Contractor', p.wage_declaration || 'No declaration provided', 'span-3')}
                ${valueBox('Salary / Wage Category', p.salary_category || p.wage_category)}
                ${valueBox('6. Employee Compensation Policy', p.ecp_covered)}
                ${valueBox('7. EC Policy Non-Coverage Reason', reasonPart(reasons, 'EC Policy Reason'), 'span-3')}
                ${valueBox('8. No. of Workers Proposed to be Engaged', p.workers_proposed_to_be_engaged || total)}
                ${valueBox('Category of Working', p.worker_category)}
                ${valueBox('Total Workers', total)}
            </div>
        </div>

        <div class="form-section-card">
            <div class="form-section-header"><i class="fas fa-file-alt"></i> EC Policy Rows</div>
            ${renderRowsTable(['S.No', 'EC Policy Number', 'Valid From', 'Valid To', 'Workers Under EC Policy'], ecpRows, (row, index) => [index + 1, row.ecp_number, formatDate(row.ecp_valid_from), formatDate(row.ecp_valid_to), row.workers_under_policy || row.ecp_workers])}
        </div>

        <div class="form-section-card">
            <div class="form-section-header"><i class="fas fa-certificate"></i> Labour License Details</div>
            ${renderRowsTable(['S.No', 'Labour No', 'Issued By', 'Issued Date', 'Expiry Date', 'Uploaded File'], licenseRows, (row, index) => [index + 1, row.license_no, row.license_issued || row.validity, formatDate(row.issued_date), formatDate(row.expiry_date), row.file_path ? String(row.file_path).split('/').pop() : ' - '])}
        </div>

        <div class="form-section-card">
            <div class="form-section-header"><i class="fas fa-id-card"></i> Additional Details</div>
            <div class="form-grid">
                ${valueBox('10. Kerala Labour Welfare Fund Registration No', p.labour_license_appl_no)}
                ${valueBox('11. Labour Identification Number', p.labour_identification_no)}
                ${valueBox('12. Name of Contact Person', p.contact_person)}
                ${valueBox('13. Mobile Number', p.mobile)}
                ${valueBox('Alternate Mobile Number', p.vendor_mob2)}
                ${valueBox('14. Remarks', p.remarks, 'span-3')}
            </div>
        </div>

        <div class="form-section-card">
            <div class="form-section-header"><i class="fas fa-shield-alt"></i> WC Insurance Policy</div>
            <div class="form-grid">
                ${valueBox('Policy Name', p.insurance_policy_name)}
                ${valueBox('Policy Number', p.insurance_policy_no)}
                ${valueBox('Validity Date', formatDate(p.insurance_validity))}
                ${valueBox('Workers Covered', p.insurance_workers_count)}
            </div>
        </div>

        <div class="form-section-card">
            <div class="form-section-header"><i class="fas fa-users"></i> Worker Category Counts</div>
            <div class="form-grid">
                ${valueBox('Skilled Workers', skilled)}
                ${valueBox('Semi Skilled Workers', semiSkilled)}
                ${valueBox('Unskilled Workers', unskilled)}
            </div>
        </div>

        <div class="form-section-card">
            <div class="form-section-header"><i class="fas fa-file-pdf"></i> Uploaded Documents</div>
            ${renderDocuments(p.documents)}
        </div>
    </div>`;

    document.getElementById('detailsBody').innerHTML = html;
    document.getElementById('detailsModal').classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

async function process3A(id, status) {
    const isApproving = status === 'approved';
    const title = isApproving ? 'Approve Customer Registration?' : 'Reject Customer Registration?';
    const message = isApproving 
        ? 'Add approval remarks and optional attachment.'
        : 'Please provide a reason for rejection and optional attachment.';
    const confirmText = isApproving ? 'Yes, Approve' : 'Yes, Reject';
    const confirmColor = isApproving ? '#10b981' : '#ef4444';
    
    // If rejecting, show input for reason
    const result = await Swal.fire({
        title: title,
        html: `
            <div style="text-align:left;">
                <div style="font-size:13px;color:#64748b;margin-bottom:10px;">${message}</div>
                <label style="display:block;font-size:12px;font-weight:700;margin-bottom:8px;color:#475569;">Reason / Remarks</label>
                <textarea id="a3-action-reason" class="swal2-textarea" placeholder="${isApproving ? 'Enter approval remarks...' : 'Enter rejection reason...'}" style="display:flex;width:100%;min-height:110px;margin:0 0 16px 0;"></textarea>
                <label style="display:block;font-size:12px;font-weight:700;margin-bottom:8px;color:#475569;">Attachment (PDF / Photo)</label>
                <input id="a3-action-file" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/*" class="swal2-file" style="display:block;width:100%;margin:0;">
                <div style="font-size:11px;color:#64748b;margin-top:6px;">Optional</div>
            </div>
        `,
        icon: status === 'approved' ? 'question' : 'warning',
        showCancelButton: true,
        confirmButtonColor: confirmColor,
        confirmButtonText: confirmText,
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const reason = document.getElementById('a3-action-reason')?.value?.trim() || '';
            const file = document.getElementById('a3-action-file')?.files?.[0] || null;
            if (!reason) {
                Swal.showValidationMessage('Reason / remarks is required');
                return false;
            }
            if (file && !/\.(pdf|jpe?g|png)$/i.test(file.name)) {
                Swal.showValidationMessage('Only PDF, JPG or PNG files are allowed.');
                return false;
            }
            return { reason, file };
        },
        allowOutsideClick: false,
        didOpen: () => {
            setTimeout(() => document.getElementById('a3-action-reason')?.focus(), 100);
        }
    });

    if (!result.isConfirmed) return;

    const reason = result.value?.reason || '';
    const file = result.value?.file || null;

    Swal.fire({
        title: 'Processing...',
        html: 'Please wait while we update the status.',
        icon: 'info',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const fd = new FormData();
        fd.append('id', id);
        fd.append('status', status);
        fd.append('reason', reason);
        if (file) fd.append('approval_file', file);
        const resp = await fetch('../../api/welfare/update_3a_status.php', { method: 'POST', body: fd });
        const res = await resp.json();
        if(res.success) {
            Swal.fire({
                title: 'Success!',
                text: isApproving ? 'Customer Registration has been approved.' : 'Customer Registration has been rejected.',
                icon: 'success',
                confirmButtonColor: confirmColor
            }).then(() => location.reload());
        } else {
            Swal.fire({
                title: 'Error',
                text: res.message || 'Update failed. Please try again.',
                icon: 'error',
                confirmButtonColor: '#ef4444'
            });
        }
    } catch(e) { 
        Swal.fire({
            title: 'Network Error',
            text: 'Failed to connect to server. Please try again.',
            icon: 'error',
            confirmButtonColor: '#ef4444'
        });
    }
}
</script>
<?php
}
renderLayout('Customer Registration Approval', 'renderContent', $role, $name);
?>
