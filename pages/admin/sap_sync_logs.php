<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    $logs = db_fetch_all($conn, "SELECT * FROM sap_integration_log ORDER BY id DESC LIMIT 50");
    $queue = db_fetch_all($conn, "SELECT sq.*, c.contractor_name FROM sap_sync_queue sq LEFT JOIN contractors c ON sq.entity_id = c.id AND sq.entity_type = 'CONTRACTOR' ORDER BY sq.created_at DESC LIMIT 30");
    
    $totalLogs = count($logs);
    $successLogs = count(array_filter($logs, function($l){ return ($l['status'] ?? '') == 'success'; }));
    $failedLogs = count(array_filter($logs, function($l){ return ($l['status'] ?? '') == 'failed'; }));
    $pendingQueue = count(array_filter($queue, function($q){ return ($q['status'] ?? $q['sync_status'] ?? '') == 'pending'; }));
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-sync" style="color:#0284c7;margin-right:10px;"></i> SAP Integration Monitor</h2>
        <!-- <p class="page-subtitle">SAP S/4 HANA synchronization logs, queue status, and retry management.</p> -->
      </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px;">
      <div class="card glass" style="padding:18px;text-align:center;"><div style="font-size:28px;font-weight:800;color:#6366f1;"><?= $totalLogs ?></div><div style="font-size:12px;opacity:0.6;">Total Syncs</div></div>
      <div class="card glass" style="padding:18px;text-align:center;"><div style="font-size:28px;font-weight:800;color:#10b981;"><?= $successLogs ?></div><div style="font-size:12px;opacity:0.6;">Successful</div></div>
      <div class="card glass" style="padding:18px;text-align:center;"><div style="font-size:28px;font-weight:800;color:#ef4444;"><?= $failedLogs ?></div><div style="font-size:12px;opacity:0.6;">Failed</div></div>
      <div class="card glass" style="padding:18px;text-align:center;"><div style="font-size:28px;font-weight:800;color:#f59e0b;"><?= $pendingQueue ?></div><div style="font-size:12px;opacity:0.6;">Queued</div></div>
    </div>

    <!-- Sync Queue -->
    <div class="card glass" style="margin-bottom:20px;">
      <div class="card-header"><div class="card-title"><i class="fas fa-clock"></i> Sync Queue</div></div>
      <div class="card-body" style="padding:0;">
        <table class="data-table">
          <thead><tr><th>Entity</th><th>Action</th><th>Status</th><th>Retries</th><th>Error</th><th>Created</th></tr></thead>
          <tbody>
          <?php if(empty($queue)): ?>
          <tr><td colspan="6" style="text-align:center;padding:30px;opacity:0.5;">Queue is empty.</td></tr>
          <?php else: foreach($queue as $q): ?>
          <tr>
            <td><strong><?= htmlspecialchars($q['contractor_name'] ?? $q['entity_type'].'-'.$q['entity_id']) ?></strong></td>
            <td><span class="badge badge-info"><?= $q['action'] ?></span></td>
            <td><span class="badge badge-<?= (($q['status'] ?? $q['sync_status'] ?? '') == 'pending') ? 'warning' : ((($q['status'] ?? $q['sync_status'] ?? '') == 'synced' || ($q['status'] ?? $q['sync_status'] ?? '') == 'success') ? 'success' : 'danger') ?>"><?= strtoupper($q['status'] ?? $q['sync_status'] ?? 'UNKNOWN') ?></span></td>
            <td><?= $q['retry_count'] ?? 0 ?></td>
            <td><small style="color:#ef4444;"><?= htmlspecialchars(substr($q['last_error'] ?? '-', 0, 80)) ?></small></td>
            <td><small><?= date('d M, H:i', strtotime($q['created_at'])) ?></small></td>
          </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Integration Logs -->
    <div class="card glass">
      <div class="card-header"><div class="card-title"><i class="fas fa-list"></i> Integration Logs</div></div>
      <div class="card-body" style="padding:0;">
        <table class="data-table">
          <thead><tr><th>ID</th><th>Operation</th><th>System</th><th>Status</th><th>Timestamp</th></tr></thead>
          <tbody>
          <?php foreach($logs as $l): ?>
          <tr>
            <td><code><?= $l['id'] ?></code></td>
            <td><?= htmlspecialchars($l['operation'] ?? '-') ?></td>
            <td><?= htmlspecialchars($l['system_name'] ?? 'SAP') ?></td>
            <td><span class="badge badge-<?= ($l['status'] ?? '') == 'success' ? 'success' : 'danger' ?>"><?= strtoupper($l['status'] ?? 'UNKNOWN') ?></span></td>
            <td><small><?= date('d M Y, H:i', strtotime($l['created_at'])) ?></small></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
}

renderLayout("SAP Sync Logs", 'renderContent', $_SESSION['role'], $_SESSION['name']);
