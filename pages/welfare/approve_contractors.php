<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin', 'welfare_user', 'pass_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';
require_once __DIR__ . '/../../include/labour_license_threshold.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Admin';

function renderContent() {
    global $conn;
    $pending_contractors = db_fetch_all($conn, "
        SELECT a.*, c.vendor_code, c.license_file, c.epf_registered, c.esi_registered,
               c.epf_code, c.esi_code, c.epf_esi_exemption_reason, c.wage_category,
               c.ecp_number, c.ecp_valid_from, c.ecp_valid_to, c.workers_ecp,
               c.workers_proposed_to_be_engaged, c.worker_category, c.contact_person as c_contact,
               c.pan_no, c.gst_no, c.address, c.mobile as c_mobile, c.email as c_email,
               a.contractor_id as cid,
               COALESCE(a.contractor_name, c.contractor_name, c.vendor_name) as display_name
        FROM annexure2a a 
        JOIN contractors c ON a.contractor_id = c.id 
        WHERE a.workflow_status IN ('submitted', 'resubmitted', 'under_review', 'pending') 
        ORDER BY a.submitted_at DESC
    ");
    $threshold = clms_get_labour_license_threshold($conn);
    $can_edit_threshold = in_array($_SESSION['role'], ['welfare_admin', 'super_admin']);
    ?>
    <div class="content-header">
      <h2 class="page-title">Contractor Approval (Registration)</h2>
    </div>

    <!-- Threshold Settings Bar -->
    <div class="card glass" style="margin-bottom:18px;">
      <div class="card-body" style="padding:14px 24px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
        <div style="display:flex; align-items:center; gap:12px;">
          <i class="fas fa-hard-hat" style="color:#f59e0b; font-size:18px;"></i>
          <div>
            <div style="font-size:12px; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Labour Licence Threshold</div>
            <div style="font-size:15px; font-weight:800; color:var(--text-primary);">Mandatory when workers &gt;= <span id="thresholdDisplay"><?= $threshold ?></span></div>
          </div>
        </div>
        <?php if ($can_edit_threshold): ?>
        <button class="btn btn-sm btn-outline" onclick="window.location.href='labour_license_threshold.php'" style="font-size:12px;">
          <i class="fas fa-cog me-1"></i> Manage Threshold
        </button>
        <?php endif; ?>
      </div>
    </div>

    <div class="card glass">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-user-check"></i> Pending Approvals</div>
      </div>
      <div class="card-body" style="padding:0">
        <table class="data-table">
          <thead>
            <tr>
              <th>Vendor Code</th>
              <th>Contractor Name</th>
              <th>Contact Info</th>
              <th>License Info</th>
              <th>Submitted</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($pending_contractors)): ?>
            <tr><td colspan="7" class="text-center" style="padding:30px">No pending registrations found.</td></tr>
            <?php endif; ?>
            <?php foreach($pending_contractors as $c): ?>
            <tr>
              <td><code><?= htmlspecialchars($c['vendor_code'] ?? 'N/A') ?></code></td>
              <td>
                <strong><?= htmlspecialchars($c['display_name']) ?></strong><br>
                <span style="font-size:11px; color:var(--text-muted)"><?= htmlspecialchars($c['purchasing_group'] ?? '') ?></span>
              </td>
              <td>
                <div style="font-size:11px">Email: <?= htmlspecialchars($c['email'] ?? 'N/A') ?></div>
                <div style="font-size:11px">Mob: <?= htmlspecialchars($c['mobile'] ?? 'N/A') ?></div>
              </td>
              <td>
                <div style="font-size:11px">WO: <?= htmlspecialchars($c['contract_no'] ?? 'N/A') ?></div>
                <div style="font-size:11px">Project: <?= htmlspecialchars($c['project_name'] ?? 'N/A') ?></div>
              </td>
              <td><?= date('d M Y', strtotime($c['submitted_at'])) ?></td>
              <td>
                <?php
                  $wf = strtolower($c['workflow_status'] ?? 'pending');
                  $wfClass = $wf === 'resubmitted' ? 'badge-warning' : 'badge-info';
                ?>
                <span class="badge <?= $wfClass ?>"><?= htmlspecialchars(strtoupper(str_replace('_', ' ', $wf))) ?></span>
              </td>
              <td>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                  <button class="btn btn-sm btn-outline" onclick='viewDetails(<?= json_encode($c) ?>)'><i class="fas fa-eye"></i> View</button>
                  <button class="btn btn-sm btn-primary" onclick="openActionModal(<?= $c['cid'] ?>, 'approved')"><i class="fas fa-check"></i> Approve</button>
                  <button class="btn btn-sm btn-warning" onclick="openActionModal(<?= $c['cid'] ?>, 'correction_required')"><i class="fas fa-edit"></i> Correction</button>
                  <button class="btn btn-sm btn-secondary" onclick="openActionModal(<?= $c['cid'] ?>, 'hold')"><i class="fas fa-pause"></i> Hold</button>
                  <button class="btn btn-sm btn-danger" onclick="openActionModal(<?= $c['cid'] ?>, 'rejected')"><i class="fas fa-times"></i> Reject</button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Details Modal -->
    <div id="detailsModal" class="modal-backdrop hidden">
      <div class="modal-content glass" style="max-width: 1200px; padding: 0;">
        <div class="modal-header" style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1);">
          <h3><i class="fas fa-info-circle"></i> Contractor Full Profile</h3>
          <button class="btn-close" onclick="closeModal('detailsModal')">&times;</button>
        </div>
        <div class="modal-body" id="detailsBody" style="padding: 24px; max-height: 80vh; overflow-y: auto;">
          <!-- Content populated via JS -->
        </div>
      </div>
    </div>

    <!-- Threshold Modal -->
    <div id="thresholdModal" class="modal-backdrop hidden">
      <div class="modal-content glass" style="max-width:420px; padding:0;">
        <div class="modal-header" style="padding:20px; border-bottom:1px solid rgba(255,255,255,0.1);">
          <h3><i class="fas fa-cog"></i> Set Labour Licence Threshold</h3>
          <button class="btn-close" onclick="closeModal('thresholdModal')">&times;</button>
        </div>
        <div class="modal-body" style="padding:24px;">
          <p style="font-size:13px; color:var(--text-muted); margin-bottom:16px;">Set the minimum number of workers above which Section 7 (Labour Licence Certificate) becomes <strong>mandatory</strong> in Contractor Registration.</p>
          <div class="form-group">
            <label class="form-label">Minimum Workers</label>
            <input type="number" class="form-control" id="thresholdInput" min="1" max="999" value="20">
          </div>
          <div style="margin-top:24px; display:flex; gap:12px; justify-content:flex-end;">
            <button class="btn btn-outline" onclick="closeModal('thresholdModal')">Cancel</button>
            <button class="btn btn-primary" onclick="saveThreshold()"><i class="fas fa-save me-1"></i> Save</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Action Modal (Approve/Reject) -->
    <div id="actionModal" class="modal-backdrop hidden">
      <div class="modal-content glass" style="max-width: 500px; padding: 0;">
        <div class="modal-header" style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1);">
          <h3 id="actionModalTitle">Action</h3>
          <button class="btn-close" onclick="closeModal('actionModal')">&times;</button>
        </div>
        <div class="modal-body" style="padding: 24px;">
          <form id="actionForm">
            <input type="hidden" name="id" id="actionContractorId">
            <input type="hidden" name="status" id="actionStatus">
            
            <div class="form-group">
              <label class="form-label" id="reasonLabel">Reason / Remarks</label>
              <textarea class="form-control" name="reason" id="actionReason" rows="4" required placeholder="Enter reason here..."></textarea>
            </div>
            
            <div class="form-group" style="margin-top:20px;">
              <label class="form-label">Approval Attachment (PDF / Photo)</label>
              <input type="file" class="form-control" name="approval_pdf" accept=".pdf,.jpg,.jpeg,.png">
              <p style="font-size:11px; color:var(--text-muted); margin-top:4px;">Upload approval letter, rejection note, or supporting photo (Optional)</p>
            </div>

            <div style="margin-top: 30px; display: flex; gap: 12px; justify-content: flex-end;">
              <button type="button" class="btn btn-outline" onclick="closeModal('actionModal')">Cancel</button>
              <button type="submit" class="btn btn-primary" id="submitActionBtn">Confirm Action</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <style>
      /* Modal backdrop + content: improved contrast and stacking so details are readable */
      .modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.65);
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 3000;
        padding: 20px;
        overflow-y: auto;
      }

      .modal-content {
        width: 100%;
        max-width: 1200px;
        border-radius: 16px;
        border: 1px solid rgba(255,255,255,0.06);
        box-shadow: 0 30px 80px rgba(0,0,0,0.6);
        background: rgba(6, 12, 24, 0.98);
        color: var(--text-primary);
        max-height: 90vh;
        display: flex;
        flex-direction: column;
      }

      .modal-body { color: var(--text-primary); padding: 20px 28px; overflow-y: auto; }
      /* Force all text inside the Details modal to white for maximum readability */
      #detailsModal, #detailsModal * { color: #ffffff !important; }

      .hidden { display: none !important; visibility: hidden !important; }
      .modal-header { display: flex; justify-content: space-between; align-items: center; background: rgba(255, 255, 255, 0.01); padding: 18px 24px; }
      .btn-close { background: none; border: none; font-size: 26px; color: var(--text-muted); cursor: pointer; transition: color 0.15s ease; }
      .btn-close:hover { color: #ff7a7a; }

      /* Standard Form Styling (lighten value boxes so text is readable) */
      .form-container { display:flex; flex-direction:column; gap:20px; padding-bottom:16px; }
      .form-section-card { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.04); border-radius:12px; padding:18px; }
      .form-section-header { font-size:15px; font-weight:700; color:#ffffff; margin-bottom:14px; padding-bottom:8px; border-bottom:1px solid rgba(255,255,255,0.03); display:flex; align-items:center; gap:8px; }
      .card-title { color: #ffffff; font-weight:700; }
      .form-section-header i {
        font-size: 16px;
      }
      .form-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
      }
      @media (max-width: 900px) {
        .form-grid {
          grid-template-columns: repeat(2, 1fr);
        }
      }
      @media (max-width: 600px) {
        .form-grid {
          grid-template-columns: 1fr;
        }
      }
      .form-field {
        display: flex;
        flex-direction: column;
        gap: 8px;
      }
      .form-field.span-2 {
        grid-column: span 2;
      }
      .form-field.span-3 {
        grid-column: span 3;
      }
      @media (max-width: 900px) {
        .form-field.span-2, .form-field.span-3 {
          grid-column: span 1;
        }
      }
      .form-field label {
        font-size: 11px;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }
      .form-field .value-box {
        background: rgba(255,255,255,0.02);
        border: 1px solid rgba(255,255,255,0.04);
        border-radius: 8px;
        padding: 12px 14px;
        font-size: 13px;
        color: #ffffff; /* force white for visibility */
        font-weight: 500;
        min-height: 44px;
        word-break: break-word;
        display: flex;
        align-items: center;
      }

      .review-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
      }
      .review-table th,
      .review-table td {
        border: 1px solid rgba(255,255,255,0.08);
        padding: 10px;
        text-align: left;
        vertical-align: top;
      }
      .review-table th {
        background: rgba(255,255,255,0.04);
        font-weight: 800;
        text-transform: uppercase;
        font-size: 11px;
      }
      
      /* Documents Styling */
      .document-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
      }
      @media (max-width: 768px) {
        .document-grid {
          grid-template-columns: 1fr;
        }
      }
      .doc-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: rgba(255,255,255,0.02);
        border: 1px solid rgba(255,255,255,0.04);
        border-radius: 10px;
        padding: 12px 16px;
        transition: all 0.15s ease;
      }
      .doc-card:hover {
        background: rgba(99, 102, 241, 0.08);
        border-color: rgba(99, 102, 241, 0.25);
        transform: translateY(-1px);
      }
      .doc-info {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0; /* allows text truncation */
      }
      .doc-icon { font-size:22px; color:#60a5fa; flex-shrink:0; }
      .doc-meta {
        display: flex;
        flex-direction: column;
        min-width: 0;
      }
      .doc-type {
        font-size: 10px;
        font-weight: 700;
        color: #ffffff; /* make document type text white */
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 2px;
      }
      .doc-name {
        font-size: 13px;
        font-weight: 600;
        color: #ffffff; /* make document name white */
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
      .doc-btn {
        font-size:12px; font-weight:700; color:#ffffff; text-decoration:none; display:inline-flex; align-items:center; gap:8px;
        background: rgba(79,70,229,0.12); padding:8px 10px; border-radius:8px; transition:all 0.15s ease; flex-shrink:0; border:1px solid rgba(255,255,255,0.03);
      }
      .doc-btn:hover { background:#4f46e5; color:#fff; transform:translateY(-2px); box-shadow:0 8px 20px rgba(79,70,229,0.18); }
      .no-docs { grid-column: span 2; text-align:center; padding:20px; color:var(--text-muted); font-style:italic; background:rgba(255,255,255,0.01); border:1px dashed rgba(255,255,255,0.04); border-radius:10px; }
    </style>

    <script>
    function showActionFeedback(message, type = 'info', title = '') {
        if (typeof window.notifyUser === 'function') {
            return window.notifyUser(message, type, title);
        }
        const fallbackTitle = title || (type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Notice');
        alert(fallbackTitle + ': ' + message);
        return Promise.resolve();
    }

    async function viewDetails(c) {
        document.getElementById('detailsBody').innerHTML = `<div style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin fa-3x" style="color:#6366f1;margin-bottom:15px;"></i><br><span style="color:var(--text-muted);font-weight:600;">Loading full profile...</span></div>`;
        document.getElementById('detailsModal').classList.remove('hidden');

        let sel = { pos:[], pwos:[], sales:[], docs:[], contractor:{}, threshold:20 };
        try {
            const resp = await fetch(`../../api/welfare/get_contractor_selections.php?id=${encodeURIComponent(c.cid)}`);
            const text = await resp.text();
            let d;
            try {
                d = JSON.parse(text);
            } catch (err) {
                throw new Error('Invalid server response: ' + text.slice(0, 400));
            }
            if (!resp.ok) {
                throw new Error(d.message || 'Server error ' + resp.status);
            }
            if (!d.success) {
                throw new Error(d.message || 'Failed to load contractor details');
            }
            sel = { ...sel, ...d };
        } catch(e) {
            console.error(e);
            document.getElementById('detailsBody').innerHTML = `<div style="padding:40px;color:#f87171;font-weight:600;">Unable to load details: ${e.message}</div>`;
            return;
        }

        // Merge API contractor data with row data (API wins for fields it has)
        const r = { ...c, ...(sel.contractor || {}) };
        const v = (val) => (val && val !== 'null' && val !== 'N/A' && String(val).trim() !== '') ? String(val) : '<span style="font-style:italic;color:var(--text-muted);font-weight:normal;"> - </span>';
        const fmtDate = (d) => { try { return d ? new Date(d).toLocaleDateString('en-GB',{day:'numeric',month:'short',year:'numeric'}) : ' - '; } catch{ return d||' - '; } };
        const badge = (val, yes='YES') => val === yes
            ? `<span style="color:#065f46;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">${val}</span>`
            : `<span style="color:#991b1b;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">${val||' - '}</span>`;
        const parseRows = (value) => {
            if (!value) return [];
            if (Array.isArray(value)) return value;
            try {
                const rows = JSON.parse(value);
                return Array.isArray(rows) ? rows : [];
            } catch (e) {
                return [];
            }
        };
        const reasonPart = (raw, label) => {
            const value = String(raw || '');
            const re = new RegExp(label.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ':\\s*([\\s\\S]*?)(?=\\n[A-Z][A-Za-z ]+ Reason:|$)');
            const match = value.match(re);
            return match ? match[1].trim() : '';
        };
        const renderTable = (headers, rows, mapper) => {
            if (!rows.length) return `<div class="value-box" style="display:block;">No rows added.</div>`;
            return `<table class="review-table"><thead><tr>${headers.map(h => `<th>${h}</th>`).join('')}</tr></thead><tbody>${rows.map((row, index) => `<tr>${mapper(row, index).map(cell => `<td>${v(cell)}</td>`).join('')}</tr>`).join('')}</tbody></table>`;
        };
        const ecpRows = parseRows(r.ecp_details_json);
        const licenseRows = parseRows(r.license_details_json);

        let html = `<div class="form-container">`;

        // ── SECTION 1: General ──────────────────────────────────────────────────
        html += `<div class="form-section-card">
          <div class="form-section-header"><i class="fas fa-id-card"></i> Annexure 2A Application</div>
          <div class="form-grid">
            <div class="form-field"><label>Vendor Code</label><div class="value-box"><code>${v(r.vendor_code)}</code></div></div>
            <div class="form-field"><label>Application No</label><div class="value-box"><code>${v(r.application_id || r.application_no)}</code></div></div>
            <div class="form-field"><label>Status</label><div class="value-box">${v(r.workflow_status || r.status)}</div></div>
            <div class="form-field span-2"><label>Contractor / Vendor Name</label><div class="value-box" style="font-weight:700;color:#a5b4fc;">${v(r.contractor_name||r.vendor_name||r.display_name)}</div></div>
            <div class="form-field"><label>Email</label><div class="value-box">${v(r.c_email||r.email||r.email_address)}</div></div>
            <div class="form-field"><label>Mobile</label><div class="value-box">${v(r.mobile || r.c_mobile)}</div></div>
            <div class="form-field span-3"><label>Office Address</label><div class="value-box">${v(r.office_address||r.address)}</div></div>
          </div>
        </div>`;

        // ── SECTION 2: EPF / ESI ──────────────────────────────────────────────
        html += `<div class="form-section-card">
          <div class="form-section-header"><i class="fas fa-building"></i> 1. Work Awarding Dept</div>
          <div class="form-grid">
            <div class="form-field span-3"><label>Work Awarding Department</label><div class="value-box">${v(r.work_awarding_department||r.project_name)}</div></div>
          </div>
        </div>`;

        html += `<div class="form-section-card">
          <div class="form-section-header"><i class="fas fa-shield-alt"></i> 2 - 4. EPF / ESI Registration</div>
          <div class="form-grid">
            <div class="form-field"><label>Registered under EPF</label><div class="value-box">${badge(r.epf_registered||r.pf)}</div></div>
            <div class="form-field"><label>EPF Establishment Code</label><div class="value-box"><code>${v(r.epf_code)}</code></div></div>
            <div class="form-field"><label>EPF Account No</label><div class="value-box"><code>${v(r.epf_account_no)}</code></div></div>
            <div class="form-field"><label>Registered under ESI</label><div class="value-box">${badge(r.esi_registered||r.esic)}</div></div>
            <div class="form-field"><label>ESI Establishment Code</label><div class="value-box"><code>${v(r.esi_code||r.esic_code)}</code></div></div>
            <div class="form-field span-3"><label>EPF Non-Registration Reason</label><div class="value-box" style="color:#fbbf24;">${v(reasonPart(r.epf_esi_exemption_reason, 'EPF Reason') || r.epf_esi_exemption_reason)}</div></div>
            <div class="form-field span-3"><label>ESI Non-Registration Reason</label><div class="value-box" style="color:#fbbf24;">${v(reasonPart(r.epf_esi_exemption_reason, 'ESI Reason'))}</div></div>
          </div>
        </div>`;

        // SECTION 3: ECP
        html += `<div class="form-section-card">
          <div class="form-section-header"><i class="fas fa-file-alt"></i> 5. Wage Declaration & 6. Employee Compensation Policy</div>
          <div class="form-grid">
            <div class="form-field span-3"><label>Wage Declaration by Contractor</label><div class="value-box">${v(r.wage_declaration || 'I declare to pay minimum wage as per government norms')}</div></div>
            <div class="form-field"><label>Employee Compensation Policy</label><div class="value-box">${badge(r.ecp_covered || (ecpRows.length || r.ecp_number ? 'YES' : 'NO'))}</div></div>
            <div class="form-field span-2"><label>7. EC Policy Non-Coverage Reason</label><div class="value-box">${v(reasonPart(r.epf_esi_exemption_reason, 'EC Policy Reason'))}</div></div>
          </div>
          <div style="margin-top:14px;">${renderTable(['S.No', 'EC Policy Number', 'Valid From', 'Valid To', 'Workers Under EC Policy'], ecpRows.length ? ecpRows : (r.ecp_number ? [{ecp_number:r.ecp_number, ecp_valid_from:r.ecp_valid_from, ecp_valid_to:r.ecp_valid_to, workers_under_policy:r.workers_ecp}] : []), (row, index) => [index + 1, row.ecp_number, fmtDate(row.ecp_valid_from), fmtDate(row.ecp_valid_to), row.workers_under_policy || row.workers_ecp])}</div>
        </div>`;

        html += `<div class="form-section-card">
          <div class="form-section-header"><i class="fas fa-users"></i> 8. Approximate Workforce Details</div>
          <div class="form-grid">
            <div class="form-field"><label>No. of Workers Proposed to be Engaged</label><div class="value-box" style="font-weight:700;color:#a5b4fc;">${v(r.workers_proposed_to_be_engaged||r.workers_proposed)}</div></div>
            <div class="form-field span-2"><label>Category of Working</label><div class="value-box">${v(r.worker_category)}</div></div>
          </div>
        </div>`;

        // ── SECTION 4: Work Order & SAP ───────────────────────────────────────
        const posStr  = sel.pos.length  ? sel.pos.map(p=>`<code>${p}</code>`).join(' ')  : ' - ';
        const pwosStr = sel.pwos.length ? sel.pwos.map(p=>`<code>${p}</code>`).join(' ') : ' - ';
        const soStr   = sel.sales.length? sel.sales.map(s=>`<code>${s}</code>`).join(' ')  : ' - ';
        if (false) html += `<div class="form-section-card">
          <div class="form-section-header"><i class="fas fa-file-signature"></i> 4. Work Order & SAP Allocations</div>
          <div class="form-grid">
            <div class="form-field"><label>Work Order / Contract No</label><div class="value-box"><code>${v(r.contract_no||r.work_order_no||r.po_number||r.pwo_number)}</code></div></div>
            <div class="form-field span-2"><label>Project / Work Name</label><div class="value-box">${v(r.project_name)}</div></div>
            <div class="form-field"><label>Category of Work</label><div class="value-box">${v(r.category_work||r.nature_of_work)}</div></div>
            <div class="form-field"><label>Contract Value (INR)</label><div class="value-box" style="font-weight:700;color:#34d399;">₹ ${r.contract_value ? parseFloat(r.contract_value).toLocaleString('en-IN',{minimumFractionDigits:2}) : ' - '}</div></div>
            <div class="form-field"><label>Contract Period</label><div class="value-box">${fmtDate(r.contract_start)}  ->  ${fmtDate(r.contract_end)}</div></div>
            <div class="form-field"><label>Selected POs</label><div class="value-box" style="display:block;">${posStr}</div></div>
            <div class="form-field"><label>Selected PWOs</label><div class="value-box" style="display:block;">${pwosStr}</div></div>
            <div class="form-field"><label>Selected Sales Orders</label><div class="value-box" style="display:block;">${soStr}</div></div>
          </div>
        </div>`;

        // ── SECTION 5: Labour Licence ─────────────────────────────────────────
        html += `<div class="form-section-card">
          <div class="form-section-header"><i class="fas fa-certificate"></i> 9. Labour License Details</div>
          ${renderTable(['S.No', 'Labour No', 'Issued By', 'Issued Date', 'Expiry Date', 'Uploaded File'], licenseRows.length ? licenseRows : (r.license_no || r.license_file ? [{license_no:r.license_no, license_issued:r.license_issued, issued_date:r.issued_date, expiry_date:r.expiry_date, file_path:r.license_file}] : []), (row, index) => [index + 1, row.license_no, row.license_issued || row.validity, fmtDate(row.issued_date), fmtDate(row.expiry_date), row.file_path ? String(row.file_path).split('/').pop() : ' - '])}
        </div>`;

        html += `<div class="form-section-card">
          <div class="form-section-header"><i class="fas fa-id-card"></i> 10 - 14. Additional Details</div>
          <div class="form-grid">
            <div class="form-field"><label>10. Kerala Labour Welfare Fund Registration No</label><div class="value-box"><code>${v(r.labour_license_appl_no || r.klwf_registration_no)}</code></div></div>
            <div class="form-field"><label>11. Labour Identification Number</label><div class="value-box"><code>${v(r.labour_identification_no)}</code></div></div>
            <div class="form-field"><label>12. Name of Contact Person</label><div class="value-box">${v(r.contact_person || r.c_contact)}</div></div>
            <div class="form-field"><label>13. Mobile Number</label><div class="value-box">${v(r.mobile || r.c_mobile)}</div></div>
            <div class="form-field"><label>Alternate Mobile Number</label><div class="value-box">${v(r.vendor_mob2)}</div></div>
            <div class="form-field span-3"><label>14. Remarks</label><div class="value-box">${v(r.remarks)}</div></div>
          </div>
        </div>`;

        // ── SECTION 8: Documents ──────────────────────────────────────────────
        html += `<div class="form-section-card">
          <div class="form-section-header"><i class="fas fa-file-pdf"></i> Uploaded Documents</div>
          <div class="document-grid">`;

        let hasDocs = false;
        if (sel.docs && sel.docs.length > 0) {
            hasDocs = true;
            sel.docs.forEach(doc => {
                let link = doc.file_path || '';
                if (!link.startsWith('http') && !link.startsWith('../../')) link = '../../uploads/' + link;
                const ext = link.split('.').pop().toLowerCase();
                const icon = ext === 'pdf' ? 'fa-file-pdf' : (ext.match(/jpe?g|png|gif|webp/) ? 'fa-file-image' : 'fa-file');
                const iconColor = ext === 'pdf' ? '#f87171' : '#60a5fa';
                html += `<div class="doc-card">
                  <div class="doc-info">
                    <i class="fas ${icon} doc-icon" style="color:${iconColor};"></i>
                    <div class="doc-meta">
                      <span class="doc-type">${doc.doc_type || 'Document'}</span>
                      <span class="doc-name" title="${doc.original_name||''}">${doc.original_name || 'Attachment'}</span>
                    </div>
                  </div>
                  <a href="${link}" target="_blank" class="doc-btn"><i class="fas fa-external-link-alt"></i> View</a>
                </div>`;
            });
        } else {
            html += `<div class="no-docs"><i class="fas fa-folder-open" style="font-size:24px;margin-bottom:8px;display:block;"></i>No documents uploaded for this application.</div>`;
        }

        html += `</div></div></div>`;
        document.getElementById('detailsBody').innerHTML = html;
    }

    async function submitContractorAction(id, status, reason, approvalFile) {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('status', status);
        formData.append('reason', reason || '');
        if (approvalFile) {
            formData.append('approval_pdf', approvalFile);
        }

        const data = await window.apiFetch('../../api/welfare/update_contractor_status_v2.php', {
            method: 'POST',
            body: formData,
            silent: true
        });

        if (!data.success) {
            throw new Error(data.error || 'Action failed');
        }

        return data;
    }

    async function openActionModal(id, status) {
        let title = 'Action';
        let label = 'Remarks';
        let placeholder = 'Enter details...';

        switch(status) {
            case 'approved': title = 'Approve Contractor'; label = 'Approval Remarks'; placeholder = 'Enter approval remarks...'; break;
            case 'rejected': title = 'Reject Contractor'; label = 'Rejection Reason'; placeholder = 'Enter reason for rejection...'; break;
            case 'correction_required': title = 'Request Correction'; label = 'Correction Notes'; placeholder = 'What needs to be fixed?'; break;
            case 'hold': title = 'Hold Application'; label = 'Reason for Hold'; placeholder = 'Why is this on hold?'; break;
            case 'block': title = 'Block Contractor'; label = 'Blocking Reason'; placeholder = 'Reason for blocking...'; break;
        }

        if (typeof Swal !== 'undefined' && Swal.fire) {
            const result = await Swal.fire({
                title,
                html: `
                    <div style="text-align:left;">
                        <label style="display:block;font-size:12px;font-weight:700;margin-bottom:8px;color:#475569;">${label}</label>
                        <textarea id="swal-action-reason" class="swal2-textarea" placeholder="${placeholder}" style="display:flex;width:100%;min-height:120px;margin:0 0 16px 0;"></textarea>
                        <label style="display:block;font-size:12px;font-weight:700;margin-bottom:8px;color:#475569;">Approval Attachment (PDF / Photo)</label>
                        <input id="swal-action-file" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/*" class="swal2-file" style="display:block;width:100%;margin:0;">
                        <div style="font-size:11px;color:#64748b;margin-top:6px;">Optional</div>
                    </div>
                `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Submit Action',
                cancelButtonText: 'Cancel',
                preConfirm: async () => {
                    const reason = document.getElementById('swal-action-reason')?.value?.trim() || '';
                    const fileInput = document.getElementById('swal-action-file');
                    const file = fileInput?.files?.[0] || null;

                    if (!reason) {
                        Swal.showValidationMessage(`${label} is required.`);
                        return false;
                    }

                    if (file && !/\.(pdf|jpe?g|png)$/i.test(file.name)) {
                        Swal.showValidationMessage('Only PDF, JPG or PNG files are allowed.');
                        return false;
                    }

                    try {
                        await submitContractorAction(id, status, reason, file);
                        return { reason };
                    } catch (error) {
                        Swal.showValidationMessage(error.message || 'Action failed');
                        return false;
                    }
                },
                allowOutsideClick: () => !Swal.isLoading(),
                didOpen: () => {
                    const textarea = document.getElementById('swal-action-reason');
                    if (textarea) textarea.focus();
                }
            });

            if (result.isConfirmed) {
                await showActionFeedback('Contractor ' + status + ' successfully', 'success', 'Status updated');
                location.reload();
            }
            return;
        }

        document.getElementById('actionContractorId').value = id;
        document.getElementById('actionStatus').value = status;
        document.getElementById('actionModalTitle').innerText = title;
        document.getElementById('reasonLabel').innerText = label;
        document.getElementById('actionReason').placeholder = placeholder;
        document.getElementById('actionModal').classList.remove('hidden');
    }

    function openThresholdModal(current) {
        document.getElementById('thresholdInput').value = current;
        document.getElementById('thresholdModal').classList.remove('hidden');
    }

    async function saveThreshold() {
        const val = parseInt(document.getElementById('thresholdInput').value);
        if (!val || val < 1) { showActionFeedback('Please enter a valid number.', 'warning', 'Invalid threshold'); return; }
        const fd = new FormData();
        fd.append('threshold', val);
        try {
            const resp = await fetch('../../api/welfare/update_threshold.php', { method:'POST', body:fd });
            const d = await resp.json();
            if (d.success) {
                document.getElementById('thresholdDisplay').textContent = val;
                closeModal('thresholdModal');
                await showActionFeedback(d.message, 'success', 'Threshold updated');
            } else {
                await showActionFeedback(d.message || 'Failed to save.', 'error', 'Threshold update failed');
            }
        } catch(e) { await showActionFeedback('Network error.', 'error', 'Threshold update failed'); }
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    document.getElementById('actionForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('submitActionBtn');
        btn.disabled = true;
        btn.innerText = 'Processing...';

        const formData = new FormData(e.target);
        
        try {
            const file = formData.get('approval_pdf');
            await submitContractorAction(
                formData.get('id'),
                formData.get('status'),
                formData.get('reason'),
                file && file.name ? file : null
            );
            closeModal('actionModal');
            await showActionFeedback('Contractor ' + formData.get('status') + ' successfully', 'success', 'Status updated');
            location.reload();
        } catch (e) {
            await showActionFeedback(e.message || 'Error updating status', 'error', 'Status update failed');
        } finally {
            btn.disabled = false;
            btn.innerText = 'Confirm Action';
        }
    });
    </script>
    <?php
}
renderLayout("Contractor Approval", 'renderContent', $role, $name);
?>

