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
        <h1><i class="fas fa-shield-alt" style="color:#6366f1"></i> Safety Compliance</h1>
        <!-- <p>Monitor safety training completion and qualification status of all onsite workers.</p> -->
    </div>
</div>

<div class="card glass">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table data-table" id="safety-table">
                <thead>
                    <tr>
                        <th>Worker Name</th>
                        <th>Contractor</th>
                        <th>Safety Training Status</th>
                        <th>Qualification</th>
                    </tr>
                </thead>
                <tbody id="safety-list">
                    <tr><td colspan="4" class="text-center">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function loadSafety() {
    try {
        const res = await fetch('../../api/customer/safety.php');
        const data = await res.json();
        const list = document.getElementById('safety-list');
        list.innerHTML = '';
        
        if (data.success && data.data.length > 0) {
            data.data.forEach(s => {
                const status = s.safety_status || 'pending';
                const statusClass = status === 'pass' ? 'success' : (status === 'fail' ? 'danger' : 'warning');
                list.innerHTML += `
                    <tr>
                        <td><strong>${s.worker_name}</strong></td>
                        <td>${s.contractor_name}</td>
                        <td><span class="badge badge-${statusClass}">${status.toUpperCase()}</span></td>
                        <td>${status === 'pass' ? 'Safety Qualified' : 'Not Qualified'}</td>
                    </tr>
                `;
            });
        } else {
            list.innerHTML = '<tr><td colspan="4" class="text-center">No safety records found.</td></tr>';
        }
    } catch (e) { console.error(e); }
}

document.addEventListener('DOMContentLoaded', loadSafety);
</script>
<?php
}

renderLayout("Safety Compliance", 'renderContent', $_SESSION['role'], $name);
?>
