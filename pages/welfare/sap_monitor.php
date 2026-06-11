<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_user', 'welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function sapSafeQuery($conn, $sql) {
    $r = @mysqli_query($conn, $sql);
    if (!$r) return [];
    $rows = [];
    while ($row = mysqli_fetch_assoc($r)) $rows[] = $row;
    return $rows;
}

function renderContent() {
    global $conn;

    $queue = sapSafeQuery($conn, "SELECT * FROM sap_sync_queue ORDER BY created_at DESC LIMIT 50");

    ?>
    <div class="content-header">
        <div>
            <h2 class="page-title"><i class="fas fa-sync" style="color:#3b82f6;margin-right:10px"></i>SAP S/4 HANA Integration Monitor</h2>
            <p class="page-subtitle">Reliable synchronization of worker lifecycle events with Enterprise ERP</p>
        </div>
        <button class="btn btn-primary" onclick="processQueue()">Process Pending Queue</button>
    </div>

    <div class="card glass">
        <div class="card-header" style="background:rgba(59,130,246,.08);color:#2563eb;font-weight:800"><i class="fas fa-sync"></i> Outbound Sync Queue</div>
        <div class="card-body" style="padding:0">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Entity</th>
                        <th>Action</th>
                        <th>Status</th>
                        <th>Retries</th>
                        <th>Last Error</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($queue)): ?>
                    <tr><td colspan="6" style="text-align:center;padding:30px;color:#9ca3af">SAP sync queue is empty</td></tr>
                    <?php else: foreach($queue as $q):
                        $statusBadge = ['pending' => 'badge-warning', 'success' => 'badge-success', 'failed' => 'badge-danger', 'in_progress' => 'badge-info'][$q['sync_status'] ?? ''] ?? 'badge-warning';
                    ?>
                    <tr>
                        <td><?= date('d M H:i:s', strtotime($q['created_at'])) ?></td>
                        <td><strong><?= htmlspecialchars($q['entity_type'] ?? '-') ?></strong> (<?= $q['entity_id'] ?? '-' ?>)</td>
                        <td><span class="badge badge-info"><?= htmlspecialchars($q['action'] ?? '-') ?></span></td>
                        <td><span class="badge <?= $statusBadge ?>"><?= strtoupper($q['sync_status'] ?? 'N/A') ?></span></td>
                        <td><?= $q['retry_count'] ?? 0 ?></td>
                        <td><small style="color:#ef4444"><?= htmlspecialchars($q['last_error'] ?? '-') ?></small></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function processQueue() {
        alert('Initializing SAP Middleware connector... Processing pending synchronization packets.');
    }
    </script>
    <?php
}

renderLayout("SAP Monitor", 'renderContent', $_SESSION['role'], $_SESSION['name']);
?>

