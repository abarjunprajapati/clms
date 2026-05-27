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
        <h1>Worker Visibility</h1>
        <!-- <p>Comprehensive list of workers across all your mapped contractors.</p> -->
    </div>
</div>

<div class="card glass">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table data-table" id="worker-full-table">
                <thead>
                    <tr>
                        <th>Worker Name</th>
                        <th>Contractor</th>
                        <th>Skill/Trade</th>
                        <th>Safety Status</th>
                        <th>Pass Status</th>
                        <th>ACC Status</th>
                    </tr>
                </thead>
                <tbody id="full-worker-list">
                    <!-- Loaded via JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function loadWorkers() {
    try {
        const res = await fetch('../../api/customer/workers.php');
        const data = await res.json();
        const list = document.getElementById('full-worker-list');
        list.innerHTML = '';
        
        if (data.success && data.data.length > 0) {
            data.data.forEach(w => {
                list.innerHTML += `
                    <tr>
                        <td style="font-weight:600">${w.name}</td>
                        <td style="font-size:12px">${w.contractor_name}</td>
                        <td>${w.skill || 'Unskilled'} / ${w.trade || 'General'}</td>
                        <td><span class="worker-status status-active">${w.safety_status || 'Completed'}</span></td>
                        <td><span class="worker-status status-active">${w.pass_status || 'Active'}</span></td>
                        <td><span class="worker-status status-active">${w.acc_status || 'Issued'}</span></td>
                    </tr>
                `;
            });
        }
    } catch (e) { console.error(e); }
}

document.addEventListener('DOMContentLoaded', loadWorkers);
</script>
<?php
}

renderLayout("Worker Visibility", 'renderContent', $_SESSION['role'], $name);
?>
