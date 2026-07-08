<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin', 'welfare_user', 'pass_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare User';

function entityDirectoryStatus($type, $row) {
    if ($type === 'customer') {
        $activeInd = strtoupper(trim((string)($row['ACTIVE_IND'] ?? 'A')));
        if ($activeInd !== 'A') return 'rejected';
        return !empty($row['user_id']) && strtolower((string)($row['user_status'] ?? '')) === 'active' ? 'approved' : 'pending';
    }

    $status = strtolower(trim((string)($row['status'] ?? 'pending')));
    if (in_array($status, ['approved', 'active'], true)) return 'approved';
    if (in_array($status, ['rejected', 'blocked'], true)) return 'rejected';
    return 'pending';
}

function entityDirectoryRows($conn) {
    $contractors = db_fetch_all($conn, "
        SELECT id, vendor_code, vendor_name, contractor_name, contact_person, contact_person_name,
               mobile, email, email_address, vendor_mob2, pan, pan_no, gst, gst_no, address,
               state, district, pin, work_awarding_department, work_order_no, po_number,
               pwo_number, sales_order_number, nature_of_work, work_location, status,
               approval_reason, compliance_status, created_at, last_action_at
        FROM contractors
        WHERE LOWER(status) IN ('approved', 'active')
        ORDER BY created_at DESC
    ");

    $customers = db_fetch_all($conn, "
        SELECT id, customer_code, customer_name, Customer_MOB1, EMAIL_ADDRESS, Address, ACTIVE_IND, created_at, user_id, user_status
        FROM sap_customer_master
        WHERE UPPER(ACTIVE_IND) = 'A' OR ACTIVE_IND IS NULL OR ACTIVE_IND = ''
        ORDER BY created_at DESC
    ");

    $rows = [];
    foreach ($contractors as $c) {
        $rows[] = [
            'type' => 'contractor',
            'id' => (int)$c['id'],
            'code' => $c['vendor_code'] ?? '',
            'name' => $c['contractor_name'] ?: ($c['vendor_name'] ?? ''),
            'mobile' => $c['mobile'] ?: ($c['vendor_mob2'] ?? ''),
            'email' => $c['email'] ?: ($c['email_address'] ?? ''),
            'address' => $c['address'] ?? '',
            'status' => entityDirectoryStatus('contractor', $c),
            'raw_status' => $c['status'] ?? '',
            'created_at' => $c['created_at'] ?? '',
            'payload' => $c
        ];
    }

    foreach ($customers as $c) {
        $rows[] = [
            'type' => 'customer',
            'id' => (int)$c['id'],
            'code' => $c['customer_code'] ?? '',
            'name' => $c['customer_name'] ?? '',
            'mobile' => $c['Customer_MOB1'] ?? '',
            'email' => $c['EMAIL_ADDRESS'] ?? '',
            'address' => $c['Address'] ?? '',
            'status' => entityDirectoryStatus('customer', $c),
            'raw_status' => $c['ACTIVE_IND'] ?? '',
            'created_at' => $c['created_at'] ?? '',
            'payload' => $c
        ];
    }

    usort($rows, function($a, $b) {
        return strtotime($b['created_at'] ?: '1970-01-01') <=> strtotime($a['created_at'] ?: '1970-01-01');
    });

    return $rows;
}

$entityRows = entityDirectoryRows($conn);

function renderContent() {
    global $entityRows;
?>
<div class="content-header">
    <div>
        <h2 class="page-title"><i class="fas fa-address-book" style="color:#6366f1;margin-right:10px;"></i>Approved Contractors</h2>
    </div>
    <a href="../../api/welfare/entity_directory_export.php" id="exportLink" class="btn btn-primary">
        <i class="fas fa-file-excel"></i> Download Excel
    </a>
</div>

<div class="card glass" style="margin-bottom:16px;">
    <div class="card-body" style="display:grid;grid-template-columns:repeat(4,minmax(160px,1fr));gap:12px;align-items:end;">
        <div class="form-group" style="margin:0;">
            <label class="form-label">Type</label>
            <select class="form-control" id="filterType">
                <option value="">All</option>
                <option value="contractor">Contractor</option>
                <option value="customer">Customer</option>
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Status</label>
            <select class="form-control" id="filterStatus">
                <option value="">All</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Search</label>
            <input type="text" class="form-control" id="filterSearch" placeholder="Code, name, mobile, email">
        </div>
        <div style="display:flex;gap:8px;">
            <button class="btn btn-outline" type="button" onclick="applyEntityFilters()"><i class="fas fa-filter"></i> Filter</button>
            <button class="btn btn-outline" type="button" onclick="resetEntityFilters()"><i class="fas fa-rotate-left"></i> Reset</button>
        </div>
    </div>
</div>

<div class="card glass">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-table"></i> Main Data</div>
        <span class="badge badge-info">Latest records show first</span>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table class="table data-table" id="entityTable">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Added On</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entityRows as $row):
                        $payload = htmlspecialchars(json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                        $badgeClass = $row['status'] === 'approved' ? 'badge-success' : ($row['status'] === 'rejected' ? 'badge-danger' : 'badge-warning');
                    ?>
                    <tr data-type="<?= htmlspecialchars($row['type']) ?>" data-status="<?= htmlspecialchars($row['status']) ?>">
                        <td><span class="badge <?= $row['type'] === 'contractor' ? 'badge-info' : 'badge-secondary' ?>"><?= htmlspecialchars(ucfirst($row['type'])) ?></span></td>
                        <td><code><?= htmlspecialchars($row['code'] ?: '-') ?></code></td>
                        <td>
                            <strong><?= htmlspecialchars($row['name'] ?: '-') ?></strong>
                            <div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($row['address'] ?: '') ?></div>
                        </td>
                        <td>
                            <div style="font-size:12px;"><?= htmlspecialchars($row['mobile'] ?: '-') ?></div>
                            <div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($row['email'] ?: '-') ?></div>
                        </td>
                        <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(strtoupper($row['status'])) ?></span></td>
                        <td><?= $row['created_at'] ? date('d M Y', strtotime($row['created_at'])) : '-' ?></td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <button class="btn btn-sm btn-outline" onclick='viewEntityDetails(<?= $payload ?>)'><i class="fas fa-eye"></i> View</button>
                                <a class="btn btn-sm btn-primary" target="_blank" href="../../api/welfare/entity_directory_pdf.php?type=<?= urlencode($row['type']) ?>&id=<?= (int)$row['id'] ?>">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="entityModal" class="modal-backdrop hidden">
    <div class="modal-content glass" style="max-width:1100px;padding:0;">
        <div class="modal-header" style="padding:20px;border-bottom:1px solid rgba(255,255,255,0.1);">
            <h3 id="entityModalTitle"><i class="fas fa-info-circle"></i> Details</h3>
            <button class="btn-close" onclick="closeEntityModal()">&times;</button>
        </div>
        <div class="modal-body" id="entityDetailsBody" style="padding:0;max-height:75vh;overflow-y:auto;background:#f8fafc;"></div>
    </div>
</div>

<style>
.modal-backdrop { position:fixed; inset:0; background:rgba(0,0,0,.72); backdrop-filter:blur(6px); display:flex; align-items:center; justify-content:center; z-index:1100; }
.modal-content { width:94%; border-radius:14px; border:1px solid rgba(255,255,255,.14); box-shadow:0 25px 50px -12px rgba(0,0,0,.45); max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; }
.modal-header { padding:20px; border-bottom:1px solid rgba(255,255,255,0.1); display:flex; align-items:center; justify-content:space-between; }
.modal-body { overflow-y:auto; padding:0; }
.btn-close { border:0; background:transparent; color:var(--text-muted); cursor:pointer; font-size:28px; }
.hidden { display:none; }

.profile-container { font-family: 'Outfit', 'Inter', sans-serif; color: #1e293b; background: #f8fafc; }
.profile-header-card {
    display: flex; gap: 24px;
    background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
    color: white; padding: 24px;
    align-items: center;
}
.profile-photo-placeholder {
    width: 80px; height: 80px; border-radius: 12px;
    border: 3px solid rgba(255,255,255,0.4);
    background: rgba(255,255,255,0.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 32px; color: rgba(255,255,255,0.7);
    flex-shrink: 0;
}
.profile-header-info { display: flex; flex-direction: column; gap: 6px; flex-grow: 1; }
.profile-name { font-size: 22px; font-weight: 800; margin: 0; color: #fff; line-height: 1.2; }
.profile-sub { font-size: 13px; color: rgba(255,255,255,0.85); margin: 0; }
.badge-container { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 4px; }
.profile-badge { font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 99px; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; }
.badge-role   { background: rgba(255,255,255,0.2); color: white; }
.badge-active { background: #10b981; color: #fff; }
.badge-pending{ background: #f59e0b; color: #fff; }
.badge-rejected{ background: #ef4444; color: #fff; }

.profile-body-grid { display: grid; grid-template-columns: 1fr; gap: 20px; align-items: start; padding: 24px; }
.profile-section-card {
    background: white; border: 1px solid #e2e8f0;
    border-radius: 14px; margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden;
}
.profile-section-title {
    font-size: 11px; font-weight: 800; color: #fff;
    background: #1e3a8a; padding: 9px 16px;
    text-transform: uppercase; letter-spacing: 0.6px;
    display: flex; align-items: center; gap: 8px;
}
.section-body { padding: 16px 18px; }

.details-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px 18px; }
.detail-row { display: flex; flex-direction: column; gap: 3px; }
.detail-label { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; }
.detail-val   { font-size: 13px; font-weight: 600; color: #1e293b; word-break: break-word; }
@media(max-width:900px){ .details-grid{grid-template-columns:1fr 1fr;} }
</style>

<script>
function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
}

function labelize(key) {
    return String(key).replace(/_/g, ' ').replace(/\b\w/g, ch => ch.toUpperCase());
}

function renderDetailRow(label, value) {
    const display = value === null || value === undefined || value === '' ? '-' : value;
    return `<div class="detail-row"><span class="detail-label">${escapeHtml(label)}</span><span class="detail-val">${escapeHtml(display)}</span></div>`;
}

function viewEntityDetails(row) {
    const p = row.payload || {};
    const title = `${row.type === 'contractor' ? 'Contractor' : 'Customer'} Details - ${row.code || ''}`;
    document.getElementById('entityModalTitle').innerHTML = `<i class="fas fa-info-circle"></i> ${escapeHtml(title)}`;

    const stClass = (row.status === 'approved') ? 'badge-active' : ((row.status === 'rejected') ? 'badge-rejected' : 'badge-pending');
    
    let html = `<div class="profile-container">
        <div class="profile-header-card">
            <div class="profile-photo-placeholder"><i class="fas fa-building"></i></div>
            <div class="profile-header-info">
                <h3 class="profile-name">${escapeHtml(row.name || 'Unknown')}</h3>
                <p class="profile-sub">Code: ${escapeHtml(row.code || 'N/A')} &nbsp;|&nbsp; Added On: ${escapeHtml(row.created_at || 'N/A')}</p>
                <div class="badge-container">
                    <span class="profile-badge badge-role"><i class="fas fa-id-badge"></i> ${escapeHtml(row.type.toUpperCase())}</span>
                    <span class="profile-badge ${stClass}"><i class="fas fa-circle"></i> ${escapeHtml(row.status.toUpperCase())}</span>
                </div>
            </div>
        </div>
        <div class="profile-body-grid">
            <div class="profile-section-card">
                <div class="profile-section-title"><i class="fas fa-id-card"></i> Main Information</div>
                <div class="section-body">
                    <div class="details-grid">
                        ${renderDetailRow('Type', row.type)}
                        ${renderDetailRow('Code', row.code)}
                        ${renderDetailRow('Name', row.name)}
                        ${renderDetailRow('Status', row.status.toUpperCase())}
                        ${renderDetailRow('Raw Status', row.raw_status)}
                    </div>
                </div>
            </div>
            
            <div class="profile-section-card">
                <div class="profile-section-title"><i class="fas fa-map-marker-alt"></i> Contact & Address</div>
                <div class="section-body">
                    <div class="details-grid">
                        ${renderDetailRow('Mobile', row.mobile)}
                        ${renderDetailRow('Email', row.email)}
                    </div>
                    <div class="details-grid" style="grid-template-columns:1fr; margin-top:14px;">
                        ${renderDetailRow('Address', row.address)}
                    </div>
                </div>
            </div>
            
            <div class="profile-section-card">
                <div class="profile-section-title"><i class="fas fa-database"></i> All Available Data</div>
                <div class="section-body">
                    <div class="details-grid">`;
    Object.keys(p).forEach(key => {
        if (['password', 'login_password', 'reset_token'].includes(key)) return;
        html += renderDetailRow(labelize(key), p[key]);
    });
    html += `       </div>
                </div>
            </div>
        </div>
    </div>`;

    document.getElementById('entityDetailsBody').innerHTML = html;
    document.getElementById('entityModal').classList.remove('hidden');
}

function closeEntityModal() {
    document.getElementById('entityModal').classList.add('hidden');
}

function applyEntityFilters() {
    const type = document.getElementById('filterType').value;
    const status = document.getElementById('filterStatus').value;
    const search = document.getElementById('filterSearch').value.trim();

    if (window.jQuery && $.fn.DataTable && $.fn.DataTable.isDataTable('#entityTable')) {
        const table = $('#entityTable').DataTable();
        table.column(0).search(type ? '^' + type.charAt(0).toUpperCase() + type.slice(1) + '$' : '', true, false);
        table.column(4).search(status ? status.toUpperCase() : '', false, false);
        table.search(search).draw();
    }

    const params = new URLSearchParams();
    if (type) params.set('type', type);
    if (status) params.set('status', status);
    if (search) params.set('search', search);
    document.getElementById('exportLink').href = '../../api/welfare/entity_directory_export.php' + (params.toString() ? '?' + params.toString() : '');
}

function resetEntityFilters() {
    document.getElementById('filterType').value = '';
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterSearch').value = '';
    document.getElementById('exportLink').href = '../../api/welfare/entity_directory_export.php';
    if (window.jQuery && $.fn.DataTable && $.fn.DataTable.isDataTable('#entityTable')) {
        $('#entityTable').DataTable().search('').columns().search('').draw();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        if (window.jQuery && $.fn.DataTable && $.fn.DataTable.isDataTable('#entityTable')) {
            $('#entityTable').DataTable().page.len(10).order([]).draw();
        }
    }, 150);
});
</script>
<?php
}

renderLayout('Contractor & Customer Directory', 'renderContent', $role, $name);
?>
