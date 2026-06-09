<?php
require_once '../../include/config.php';
require_once '../../include/auth_middleware.php';
require_once '../../include/layout.php';

require_role(['customer']);

$name = $_SESSION['customer_name'] ?? $_SESSION['name'] ?? 'Customer';
$customer_code = $_SESSION['customer_code'] ?? '';

function renderContent() {
    global $customer_code;
?>
<div class="page-header">
    <div class="header-content">
        <h1>Assigned Contractors</h1>
        <p>List of contractors mapped to your Customer Code: <strong><?= htmlspecialchars($customer_code) ?></strong></p>
    </div>
</div>

<div class="card glass">
    <div class="card-body">
        <style>
            .contractor-name-cell { display:flex; flex-direction:column; gap:6px; }
            .contractor-name-cell .name-link { font-weight:800; color:#2b6cb0; text-decoration:none; line-height:1.25; }
            .contractor-meta-row { display:flex; flex-wrap:wrap; gap:6px; }
            .contractor-meta-chip {
                display:inline-flex;
                align-items:center;
                gap:4px;
                padding:3px 8px;
                border-radius:999px;
                background:#edf2f7;
                color:#2d3748;
                border:1px solid #cbd5e1;
                font-size:11px;
                font-weight:800;
                white-space:nowrap;
            }
            .contractor-meta-chip.code { background:#ebf8ff; color:#1a365d; border-color:#90cdf4; }
            .vendor-code-strong { font-weight:800; color:#1a365d; background:#ebf8ff; border:1px solid #90cdf4; border-radius:8px; padding:4px 8px; display:inline-block; }
        </style>
        <div class="table-responsive">
            <table class="table data-table" id="contractor-full-table">
                <thead>
                    <tr>
                        <th>Vendor Code</th>
                        <th>Contractor Name</th>
                        <th>Work Order No</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="full-contractor-list">
                    <!-- Loaded via JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function loadContractors() {
    try {
        const res = await fetch('../../api/customer/contractors.php');
        const data = await res.json();
        const list = document.getElementById('full-contractor-list');
        list.innerHTML = '';
        
        if (data.success && data.data.length > 0) {
            data.data.forEach(c => {
                const status = ((c.annexure3a_status && c.annexure3a_status !== 'not_submitted') ? c.annexure3a_status : (c.registration_status || 'pending')).toLowerCase();
                let badgeClass = 'badge-warning';
                if (status === 'approved') badgeClass = 'badge-success';
                else if (status === 'rejected') badgeClass = 'badge-danger';
                else if (status === 'resubmitted') badgeClass = 'badge-warning';
                else if (status === 'blocked') badgeClass = 'badge-gray';
                else if (status === 'inactive') badgeClass = 'badge-gray';
                else if (status === 'expired') badgeClass = 'badge-info';
                
                list.innerHTML += `
                    <tr>
                        <td><span class="vendor-code-strong">${c.vendor_code}</span></td>
                        <td>
                            <div class="contractor-name-cell">
                                <a href="contractor_details.php?vendor_code=${c.vendor_code}" class="name-link">
                                    ${c.vendor_name || c.contractor_name || 'Contractor'} <i class="fas fa-external-link-alt small text-muted ms-1"></i>
                                </a>
                                <div class="contractor-meta-row">
                                    <span class="contractor-meta-chip"><i class="fas fa-user-tag"></i> Role: Contractor</span>
                                    <span class="contractor-meta-chip code"><i class="fas fa-barcode"></i> Vendor Code: ${c.vendor_code}</span>
                                </div>
                            </div>
                        </td>
                        <td>${c.work_order_no || 'N/A'}</td>
                        <td>${c.email || 'N/A'}</td>
                        <td>${c.mobile || 'N/A'}</td>
                        <td><span class="badge ${badgeClass}">${status.toUpperCase()}</span></td>
                    </tr>
                `;
            });
        }
    } catch (e) { console.error(e); }
}

document.addEventListener('DOMContentLoaded', loadContractors);
</script>
<?php
}

renderLayout("My Contractors", 'renderContent', $_SESSION['role'], $name);
?>
