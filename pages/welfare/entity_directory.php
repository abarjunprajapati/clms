<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin', 'welfare_user', 'pass_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare User';

function entityDirectoryStatus($type, $row) {
    if ($type === 'customer') {
        $status = strtoupper(trim((string)($row['status'] ?? '')));
        $passwordCreated = (int)($row['is_password_created'] ?? 0);
        if ($status === 'INACTIVE') return 'rejected';
        return $passwordCreated ? 'approved' : 'pending';
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
        ORDER BY created_at DESC
    ");

    $customers = db_fetch_all($conn, "
        SELECT id, customer_code, customer_name, Customer_MOB1, customer_MOB2, EMAIL_ADDRESS,
               email, mobile, Address, PIN, ACTIVE_IND, status, is_password_created,
               created_at, last_login, password_updated_at
        FROM sap_customer_master
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
            'mobile' => $c['Customer_MOB1'] ?: ($c['mobile'] ?? ''),
            'email' => $c['EMAIL_ADDRESS'] ?: ($c['email'] ?? ''),
            'address' => $c['Address'] ?? '',
            'status' => entityDirectoryStatus('customer', $c),
            'raw_status' => $c['status'] ?? $c['ACTIVE_IND'] ?? '',
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
        <h2 class="page-title"><i class="fas fa-address-book" style="color:#6366f1;margin-right:10px;"></i>Contractor & Customer Directory</h2>
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
        <div class="modal-body" id="entityDetailsBody" style="padding:24px;max-height:75vh;overflow-y:auto;"></div>
    </div>
</div>

<style>
.modal-backdrop { position:fixed; inset:0; background:rgba(0,0,0,.72); backdrop-filter:blur(6px); display:flex; align-items:center; justify-content:center; z-index:1100; }
.modal-content { width:94%; border-radius:14px; border:1px solid rgba(255,255,255,.14); box-shadow:0 25px 50px -12px rgba(0,0,0,.45); }
.hidden { display:none; }
.modal-header { display:flex; align-items:center; justify-content:space-between; }
.btn-close { border:0; background:transparent; color:var(--text-muted); cursor:pointer; font-size:28px; }
.detail-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; }
.detail-section { grid-column:1 / -1; color:#6366f1; font-size:14px; font-weight:800; border-bottom:1px solid rgba(99,102,241,.2); padding:10px 0 6px; }
.detail-item { border:1px solid rgba(148,163,184,.18); border-radius:8px; padding:12px; background:rgba(255,255,255,.04); min-height:62px; }
.detail-label { font-size:10px; color:var(--text-muted); text-transform:uppercase; font-weight:800; margin-bottom:6px; }
.detail-value { font-size:13px; color:var(--text-primary); word-break:break-word; }
@media(max-width:900px){ .card-body[style*="grid-template-columns"]{grid-template-columns:1fr!important;} .detail-grid{grid-template-columns:1fr;} }
</style>

<script>
function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
}

function labelize(key) {
    return String(key).replace(/_/g, ' ').replace(/\b\w/g, ch => ch.toUpperCase());
}

function renderDetailItem(label, value) {
    const display = value === null || value === undefined || value === '' ? '-' : value;
    return `<div class="detail-item"><div class="detail-label">${escapeHtml(label)}</div><div class="detail-value">${escapeHtml(display)}</div></div>`;
}

function viewEntityDetails(row) {
    const p = row.payload || {};
    const title = `${row.type === 'contractor' ? 'Contractor' : 'Customer'} Details - ${row.code || ''}`;
    document.getElementById('entityModalTitle').innerHTML = `<i class="fas fa-info-circle"></i> ${escapeHtml(title)}`;

    let html = `<div class="detail-grid">`;
    html += `<div class="detail-section">Main Information</div>`;
    html += renderDetailItem('Type', row.type);
    html += renderDetailItem('Code', row.code);
    html += renderDetailItem('Name', row.name);
    html += renderDetailItem('Status', row.status.toUpperCase());
    html += renderDetailItem('Raw Status', row.raw_status);
    html += renderDetailItem('Added On', row.created_at);

    html += `<div class="detail-section">Contact & Address</div>`;
    html += renderDetailItem('Mobile', row.mobile);
    html += renderDetailItem('Email', row.email);
    html += renderDetailItem('Address', row.address);

    html += `<div class="detail-section">All Available Data</div>`;
    Object.keys(p).forEach(key => {
        if (['login_password', 'reset_token'].includes(key)) return;
        html += renderDetailItem(labelize(key), p[key]);
    });
    html += `</div>`;

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
