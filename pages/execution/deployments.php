<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['execution_officer', 'execution', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/execution_context.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Execution Officer';
$userId = $_SESSION['user_id'];

// Get or create execution officer context for this login
$officerId = clms_execution_get_officer_id($conn, $userId);

function renderContent() {
    global $conn, $officerId;

    // Fetch active deployments
    $sql = "SELECT d.*, w.name as workman_name, w.aadhaar, c.contractor_name, dept.dept_name, wo.work_order_no 
            FROM execution_worker_deployments d 
            LEFT JOIN workmen w ON d.workman_id = w.id 
            LEFT JOIN contractors c ON d.contractor_id = c.id 
            LEFT JOIN master_departments dept ON d.department_id = dept.id 
            LEFT JOIN work_orders wo ON d.work_order_id = wo.id
            WHERE d.execution_officer_id = ? AND d.status = 'active'
            ORDER BY d.deployed_date DESC";
    $list = db_fetch_all($conn, $sql, 'i', [$officerId]);

    ?>
    <div class="content-header">
        <div>
            <h2 class="page-title"><i class="fas fa-users-viewfinder" style="color:#10b981;margin-right:8px"></i>Workforce Supervision</h2>
            <!-- <p class="page-subtitle">Monitor real-time worker deployments across assigned departments and work orders.</p> -->
        </div>
    </div>

    <div class="card glass">
        <div class="card-header"><div class="card-title">Active Deployments</div></div>
        <div class="card-body" style="padding:0">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Worker</th>
                        <th>Contractor</th>
                        <th>Department</th>
                        <th>Work Order</th>
                        <th>Deployed Date</th>
                        <th>Shift</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($list)): ?>
                        <tr><td colspan="7" style="text-align:center;padding:30px;color:#64748b">No active deployments found.</td></tr>
                    <?php else: foreach($list as $d): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($d['workman_name']) ?></strong><br>
                                <small style="opacity:0.6"><?= htmlspecialchars($d['aadhaar'] ? 'xxxx-xxxx-' . substr($d['aadhaar'], -4) : '-') ?></small>
                            </td>
                            <td style="font-size:12px"><?= htmlspecialchars($d['contractor_name']) ?></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars($d['dept_name']) ?></span></td>
                            <td><?= htmlspecialchars($d['workman_name'] ?? 'N/A') ?></td>
                            <td><?= $d['deployed_date'] ? date('d M Y', strtotime($d['deployed_date'])) : 'N/A' ?></td>
                            <td><span class="badge badge-outline"><?= strtoupper($d['shift']) ?></span></td>
                            <td>
                                <div style="display:flex; gap:5px">
                                    <button class="btn btn-sm btn-outline-warning" title="Flag Wrong Deployment" onclick="recommendAction(<?= $d['workman_id'] ?>, 'Wrong Location')"><i class="fas fa-location-dot"></i></button>
                                    <button class="btn btn-sm btn-outline-danger" title="Escalate Violation" onclick="escalateDeployment(<?= $d['workman_id'] ?>, <?= $d['contractor_id'] ?>)"><i class="fas fa-bullhorn"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function recommendAction(workmanId, type) {
        const reason = prompt(`Enter reason for ${type}:`);
        if (!reason) return;
        
        fetch('../../api/execution/recommend_reassignment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ workman_id: workmanId, reason: reason, current_type: type })
        }).then(res => res.json()).then(res => {
            if (res.status) alert('Recommendation logged successfully.');
            else alert('Error: ' + res.message);
        });
    }

    function escalateDeployment(workmanId, contractorId) {
        location.href = `escalations.php?workman_id=${workmanId}&contractor_id=${contractorId}`;
    }
    </script>
    <?php
}

renderLayout("Workforce Supervision", 'renderContent', $role, $name);
?>

