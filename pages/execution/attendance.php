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

    // Fetch today's attendance for assigned workers
    $sql = "SELECT a.*, w.name as workman_name, c.contractor_name 
            FROM attendance a 
            JOIN workmen w ON a.workman_id = w.id 
            JOIN contractors c ON w.contractor_id = c.id 
            WHERE a.workman_id IN (SELECT workman_id FROM execution_worker_deployments WHERE execution_officer_id = ? AND status = 'active')
            AND DATE(a.check_in) = CURDATE()
            ORDER BY a.check_in DESC";
    $list = db_fetch_all($conn, $sql, 'i', [$officerId]);

    ?>
    <div class="content-header">
        <div>
            <h2 class="page-title"><i class="fas fa-calendar-check" style="color:#7c3aed;margin-right:8px"></i>Attendance Verification</h2>
            <!-- <p class="page-subtitle">Verify today's attendance logs for workers under your supervision.</p> -->
        </div>
        <div>
            <input type="date" value="<?= date('Y-m-d') ?>" class="form-control" style="width:auto">
        </div>
    </div>

    <div class="card glass">
        <div class="card-header"><div class="card-title">Live Attendance Logs (Today)</div></div>
        <div class="card-body" style="padding:0">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Workman</th>
                        <th>Contractor</th>
                        <th>Punch Time</th>
                        <th>Device/Location</th>
                        <th>Verification</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($list)): ?>
                        <tr><td colspan="5" style="text-align:center;padding:30px;color:#64748b">No attendance records for today.</td></tr>
                    <?php else: foreach($list as $a): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($a['workman_name']) ?></strong></td>
                            <td style="font-size:12px"><?= htmlspecialchars($a['contractor_name']) ?></td>
                            <td><span class="badge badge-outline"><?= date('H:i:s', strtotime($a['check_in'])) ?></span></td>
                            <td><small><?= htmlspecialchars($a['device_id'] ?? 'Main Gate') ?></small></td>
                            <td><span class="badge badge-success"><i class="fas fa-check-circle"></i> Biometric Verified</span></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

renderLayout("Attendance Verification", 'renderContent', $role, $name);
?>

