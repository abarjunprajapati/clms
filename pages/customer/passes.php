<?php
require_once '../../include/config.php';
require_once '../../include/auth_middleware.php';
require_once '../../include/layout.php';

require_role(['customer']);

$name = $_SESSION['name'] ?? 'Customer';
$customer_code = $_SESSION['customer_code'] ?? '';

function renderContent() {
?>
<div class="page-header">
    <div class="header-content">
        <h1><i class="fas fa-id-card" style="color:#6366f1"></i> Gate Pass Status</h1>
        <!-- <p>Monitor the validity and status of gate passes issued to your contractors' workforce.</p> -->
    </div>
</div>

<div class="card glass">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table data-table" id="passes-table">
                <thead>
                    <tr>
                        <th>Pass No</th>
                        <th>Worker Name</th>
                        <th>Contractor</th>
                        <th>Type</th>
                        <th>Valid Till</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="passes-list">
                    <tr><td colspan="6" class="text-center">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function loadPasses() {
    try {
        const res = await fetch('../../api/customer/passes.php');
        const data = await res.json();
        const list = document.getElementById('passes-list');
        list.innerHTML = '';
        
        if (data.success && data.data.length > 0) {
            data.data.forEach(p => {
                const statusClass = p.status === 'active' ? 'success' : (p.status === 'expired' ? 'danger' : 'warning');
                list.innerHTML += `
                    <tr>
                        <td><code>${p.pass_no || 'PENDING'}</code></td>
                        <td><strong>${p.worker_name}</strong></td>
                        <td>${p.contractor_name}</td>
                        <td>${p.pass_type || 'Monthly'}</td>
                        <td>${p.valid_till || 'N/A'}</td>
                        <td><span class="badge badge-${statusClass}">${p.status.toUpperCase()}</span></td>
                    </tr>
                `;
            });
        } else {
            list.innerHTML = '<tr><td colspan="6" class="text-center">No pass records found.</td></tr>';
        }
    } catch (e) { console.error(e); }
}

document.addEventListener('DOMContentLoaded', loadPasses);
</script>
<?php
}

renderLayout("Gate Pass Status", 'renderContent', $_SESSION['role'], $name);
?>
