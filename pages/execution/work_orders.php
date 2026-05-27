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

    // Fetch assigned work orders via contractor mapping
    $sql = "SELECT DISTINCT wo.* 
            FROM work_orders wo 
            JOIN execution_officer_contractors eoc ON wo.id = eoc.work_order_id 
            WHERE eoc.execution_officer_id = ?
            ORDER BY wo.work_order_no ASC";
    $list = db_fetch_all($conn, $sql, 'i', [$officerId]);

    ?>
    <div class="content-header">
        <div>
            <h2 class="page-title"><i class="fas fa-handshake" style="color:#f59e0b;margin-right:8px"></i>Work Order Tracking</h2>
            <!-- <p class="page-subtitle">Monitor project progress and work order status within your jurisdiction.</p> -->
        </div>
    </div>

    <div class="card glass">
        <div class="card-header"><div class="card-title">Assigned Work Orders</div></div>
        <div class="card-body" style="padding:0">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>WO Number</th>
                        <th>Project/Nature</th>
                        <th>Department</th>
                        <th>Vendor Code</th>
                        <th>Validity</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($list)): ?>
                        <tr><td colspan="6" style="text-align:center;padding:30px;color:#64748b">No work orders mapped yet.</td></tr>
                    <?php else: foreach($list as $wo): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($wo['work_order_no']) ?></code></td>
                            <td>
                                <strong><?= htmlspecialchars($wo['project_name'] ?: 'N/A') ?></strong><br>
                                <small style="opacity:0.6"><?= htmlspecialchars($wo['nature_of_work'] ?? '') ?></small>
                            </td>
                            <td><?= htmlspecialchars($wo['department']) ?></td>
                            <td><code><?= htmlspecialchars($wo['vendor_code']) ?></code></td>
                            <td>
                                <div style="font-size:11px">From: <?= date('d M Y', strtotime($wo['start_date'])) ?></div>
                                <div style="font-size:11px">To: <?= date('d M Y', strtotime($wo['end_date'])) ?></div>
                            </td>
                            <td><span class="badge badge-<?= $wo['wo_status'] === 'ACTIVE' ? 'success' : 'danger' ?>"><?= $wo['wo_status'] ?></span></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

renderLayout("Work Order Tracking", 'renderContent', $role, $name);
?>
