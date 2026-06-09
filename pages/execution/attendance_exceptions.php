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

    ?>
    <div class="content-header">
        <div>
            <h2 class="page-title"><i class="fas fa-triangle-exclamation" style="color:#f59e0b;margin-right:8px"></i>Attendance Exceptions</h2>
            <!-- <p class="page-subtitle">Real-time detection of unauthorized attendance, deployment mismatches, and status conflicts.</p> -->
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
        <!-- Unauthorized Attendance -->
        <div class="card glass">
            <div class="card-header"><div class="card-title">Unauthorized Attendance (No Deployment)</div></div>
            <div class="card-body" style="padding:0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Worker</th>
                            <th>Contractor</th>
                            <th>Punch Time</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Workers present today but NOT in execution_worker_deployments
                        $sql = "SELECT a.workman_id, a.check_in, w.name as workman_name, c.contractor_name 
                                FROM attendance a 
                                JOIN workmen w ON a.workman_id = w.id 
                                JOIN contractors c ON w.contractor_id = c.id 
                                WHERE DATE(a.check_in) = CURDATE() 
                                AND a.workman_id NOT IN (SELECT workman_id FROM execution_worker_deployments WHERE status = 'active')
                                ORDER BY a.check_in DESC";
                        $list = db_fetch_all($conn, $sql);
                        
                        if(empty($list)): ?>
                            <tr><td colspan="4" style="text-align:center;padding:20px;color:#64748b">No exceptions found.</td></tr>
                        <?php else: foreach($list as $e): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($e['workman_name']) ?></strong></td>
                                <td><?= htmlspecialchars($e['contractor_name']) ?></td>
                                <td><?= date('H:i', strtotime($e['check_in'])) ?></td>
                                <td><button class="btn btn-sm btn-danger" onclick="reportException('<?= $e['workman_id'] ?>', 'Unauthorized Attendance')">Report</button></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Blocked Worker Attendance -->
        <div class="card glass">
            <div class="card-header"><div class="card-title">Blocked Worker Attendance</div></div>
            <div class="card-body" style="padding:0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Worker</th>
                            <th>Status</th>
                            <th>Punch Time</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Workers present today but status is blocked/inactive
                        $sql = "SELECT a.workman_id, a.check_in, w.name as workman_name, w.status 
                                FROM attendance a 
                                JOIN workmen w ON a.workman_id = w.id 
                                WHERE DATE(a.check_in) = CURDATE() 
                                AND w.status IN ('blocked', 'inactive', 'perm_blocked')
                                ORDER BY a.check_in DESC";
                        $list = db_fetch_all($conn, $sql);
                        
                        if(empty($list)): ?>
                            <tr><td colspan="4" style="text-align:center;padding:20px;color:#64748b">No critical status conflicts.</td></tr>
                        <?php else: foreach($list as $e): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($e['workman_name']) ?></strong></td>
                                <td><span class="badge badge-danger"><?= strtoupper($e['status']) ?></span></td>
                                <td><?= date('H:i', strtotime($e['check_in'])) ?></td>
                                <td><button class="btn btn-sm btn-danger" onclick="reportException('<?= $e['workman_id'] ?>', 'Blocked Access Attempt')">Escalate</button></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    function reportException(workmanId, type) {
        if(confirm(`Do you want to escalate this ${type}?`)) {
            // Redirect to escalations page with context
            location.href = `escalations.php?workman_id=${workmanId}&type=${encodeURIComponent(type)}&severity=high`;
        }
    }
    </script>
    <?php
}

renderLayout("Attendance Exceptions", 'renderContent', $role, $name);
?>

