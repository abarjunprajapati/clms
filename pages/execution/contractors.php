<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['execution_officer', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Execution Officer';
$userId = $_SESSION['user_id'];

// Get Officer ID
$officerRes = db_single($conn, "SELECT id FROM execution_officers WHERE employee_code = (SELECT contractor_id FROM users WHERE id = ?)", 'i', [$userId]);
$officerId = $officerRes['id'] ?? 0;

function renderContent() {
    global $conn, $officerId;

    // Fetch assigned contractors with details
    $sql = "SELECT c.* 
            FROM contractors c 
            JOIN execution_officer_contractors eoc ON c.id = eoc.contractor_id 
            WHERE eoc.execution_officer_id = ?
            ORDER BY c.contractor_name ASC";
    $list = db_fetch_all($conn, $sql, 'i', [$officerId]);

    ?>
    <div class="content-header">
        <div>
            <h2 class="page-title"><i class="fas fa-building" style="color:#3b82f6;margin-right:8px"></i>Contractor Monitoring</h2>
            <!-- <p class="page-subtitle">View and monitor details of contractors assigned to your jurisdiction.</p> -->
        </div>
    </div>

    <div class="card glass">
        <div class="card-header"><div class="card-title">Assigned Contractors</div></div>
        <div class="card-body" style="padding:0">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Contractor Name</th>
                        <th>Vendor Code</th>
                        <th>Nature of Work</th>
                        <th>Status</th>
                        <th>Workforce</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($list)): ?>
                        <tr><td colspan="6" style="text-align:center;padding:30px;color:#64748b">No contractors assigned yet.</td></tr>
                    <?php else: foreach($list as $c): 
                        $workerCount = db_count($conn, "SELECT COUNT(*) FROM workmen WHERE contractor_id = ?", 'i', [$c['id']]);
                        $activeCount = db_count($conn, "SELECT COUNT(*) FROM execution_worker_deployments WHERE contractor_id = ? AND status = 'active'", 'i', [$c['id']]);
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($c['contractor_name'] ?? '') ?></strong><br>
                                <small style="opacity:0.6"><?= htmlspecialchars($c['email'] ?? '') ?></small>
                            </td>
                            <td><code><?= htmlspecialchars($c['vendor_code'] ?: 'N/A') ?></code></td>
                            <td style="max-width:200px; font-size:12px"><?= htmlspecialchars($c['nature_of_work'] ?? '') ?></td>
                            <td><span class="badge badge-<?= $c['status'] === 'approved' ? 'success' : 'warning' ?>"><?= strtoupper($c['status'] ?? '') ?></span></td>
                            <td>
                                <div style="font-size:12px">Total: <?= $workerCount ?></div>
                                <div style="font-size:11px; color:#10b981">Deployed: <?= $activeCount ?></div>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline" onclick="viewDetails(<?= $c['id'] ?>)">View Info</button>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for Contractor Details -->
    <div id="detailsModal" class="modal">
        <div class="modal-content glass" style="max-width: 600px;">
            <div class="modal-header">
                <h3 id="modalTitle">Contractor Details</h3>
                <span class="close-modal" onclick="closeDetailsModal()">&times;</span>
            </div>
            <div class="modal-body" id="modalBody">
                <div style="text-align:center; padding:20px;">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p>Loading details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeDetailsModal()">Close</button>
            </div>
        </div>
    </div>

    <style>
    .modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); backdrop-filter:blur(4px); }
    .modal-content { background:#fff; margin:10% auto; padding:0; border-radius:16px; position:relative; animation:slideUp 0.3s ease-out; }
    .modal-header { padding:20px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center; }
    .modal-body { padding:20px; }
    .modal-footer { padding:15px 20px; border-top:1px solid #f1f5f9; text-align:right; }
    .close-modal { cursor:pointer; font-size:24px; color:#64748b; }
    .detail-grid { display:grid; grid-template-columns: 1fr 1fr; gap:15px; }
    .detail-item label { display:block; font-size:11px; color:#94a3b8; font-weight:600; text-transform:uppercase; margin-bottom:4px; }
    .detail-item div { font-size:14px; color:#1e293b; font-weight:500; }
    @keyframes slideUp { from { transform:translateY(20px); opacity:0; } to { transform:translateY(0); opacity:1; } }
    </style>

    <script>
    async function viewDetails(id) {
        document.getElementById('detailsModal').style.display = 'block';
        document.getElementById('modalBody').innerHTML = '<div style="text-align:center; padding:20px;"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Loading details...</p></div>';
        
        try {
            const response = await fetch(`../../api/execution/get_contractor_details.php?id=${id}`);
            const res = await response.json();
            
            if (res.status) {
                const c = res.data;
                document.getElementById('modalTitle').innerText = c.contractor_name;
                document.getElementById('modalBody').innerHTML = `
                    <div class="detail-grid">
                        <div class="detail-item"><label>Vendor Code</label><div><code>${c.vendor_code || 'N/A'}</code></div></div>
                        <div class="detail-item"><label>Registration Status</label><div><span class="badge badge-success">${c.status.toUpperCase()}</span></div></div>
                        <div class="detail-item"><label>Email Address</label><div>${c.email || 'N/A'}</div></div>
                        <div class="detail-item"><label>Contact Person</label><div>${c.proprietor_name || 'N/A'}</div></div>
                        <div class="detail-item"><label>Workforce Summary</label><div>Total: ${c.total_workmen} | Active: ${c.active_deployments}</div></div>
                        <div class="detail-item"><label>Nature of Work</label><div>${c.nature_of_work || 'N/A'}</div></div>
                    </div>
                    <div style="margin-top:20px; padding-top:20px; border-top:1px solid #f1f5f9">
                        <label style="font-size:11px; color:#94a3b8; font-weight:600">OFFICE ADDRESS</label>
                        <div style="font-size:13px; margin-top:5px; line-height:1.5">${c.address || 'Address not available.'}</div>
                    </div>
                `;
            } else {
                document.getElementById('modalBody').innerHTML = `<div class="alert alert-danger">${res.message}</div>`;
            }
        } catch (error) {
            document.getElementById('modalBody').innerHTML = `<div class="alert alert-danger">Failed to fetch details.</div>`;
        }
    }

    function closeDetailsModal() {
        document.getElementById('detailsModal').style.display = 'none';
    }

    // Close on click outside
    window.onclick = function(event) {
        const modal = document.getElementById('detailsModal');
        if (event.target == modal) closeDetailsModal();
    }
    </script>
    <?php
}

renderLayout("Contractor Monitoring", 'renderContent', $role, $name);
?>
