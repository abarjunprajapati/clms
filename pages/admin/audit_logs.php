<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin', 'welfare_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    
    // Summary
    $todayLogs = db_count($conn, "SELECT COUNT(*) c FROM audit_logs WHERE DATE(created_at) = CURDATE()");
    $adminActions = db_count($conn, "SELECT COUNT(*) c FROM audit_logs l JOIN users u ON l.user_id = u.id WHERE u.role='super_admin'");
    
    $logs = db_fetch_all($conn, "SELECT l.*, u.name as user_name, u.role 
                                 FROM audit_logs l 
                                 LEFT JOIN users u ON l.user_id = u.id 
                                 ORDER BY l.created_at DESC LIMIT 500");
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-clipboard-list" style="color:#6366f1;margin-right:10px;"></i> System Audit Trail</h2>
        <!-- <p class="page-subtitle">Historical record of every administrative and operational action for full accountability.</p> -->
      </div>
      <div class="action-buttons">
        <a href="data_export.php?dataset=audit_logs" class="btn btn-primary"><i class="fas fa-download"></i> Export Master Audit</a>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;">
      <div class="card glass" style="text-align:center;padding:16px;">
        <div style="font-size:24px;font-weight:800;color:#6366f1;"><?= $todayLogs ?></div>
        <div style="font-size:11px;opacity:0.6;">Actions Today</div>
      </div>
      <div class="card glass" style="text-align:center;padding:16px;">
        <div style="font-size:24px;font-weight:800;color:#dc2626;"><?= $adminActions ?></div>
        <div style="font-size:11px;opacity:0.6;">Super Admin Actions</div>
      </div>
      <div class="card glass" style="text-align:center;padding:16px;">
        <div style="font-size:24px;font-weight:800;color:#10b981;"><?= count($logs) ?></div>
        <div style="font-size:11px;opacity:0.6;">Recently Indexed</div>
      </div>
    </div>

    <div class="card glass">
      <div class="card-header">
        <div class="card-title">Security & Operation Logs</div>
      </div>
      <div class="card-body" style="padding:0;">
        <table class="data-table">
          <thead>
            <tr>
              <th>Time</th>
              <th>User</th>
              <th>Action</th>
              <th>Module</th>
              <th>IP / Metadata</th>
              <th style="width:100px;">Data</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($logs as $log): 
                $role = $log['role'] ?? 'system';
                $roleColor = ($role == 'super_admin') ? '#dc2626' : (($role == 'welfare_admin') ? '#7c3aed' : '#64748b');
            ?>
            <tr>
              <td><small><?= date('d M, H:i:s', strtotime($log['created_at'])) ?></small></td>
              <td>
                <div style="font-weight:700; font-size:13px;"><?= htmlspecialchars($log['user_name'] ?? 'SYSTEM') ?></div>
                <small style="color:<?= $roleColor ?>; font-weight:600; font-size:10px;"><?= strtoupper($role) ?></small>
              </td>
              <td><span style="font-weight:600;"><?= htmlspecialchars($log['action'] ?? '-') ?></span></td>
              <td><span class="badge badge-outline" style="font-size:10px;"><?= strtoupper($log['module'] ?? 'CORE') ?></span></td>
              <td>
                <code><?= htmlspecialchars($log['ip_address'] ?? '0.0.0.0') ?></code>
              </td>
              <td>
                <button class="btn btn-sm btn-outline" onclick='viewAuditData(<?= json_encode([
                    "old" => $log["old_value"],
                    "new" => $log["new_value"],
                    "remarks" => $log["remarks"],
                    "details" => $log["details"]
                ]) ?>)'><i class="fas fa-eye"></i> Details</button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Data Modal -->
    <div id="dataModal" class="modal-overlay" style="display:none;">
      <div class="modal-card glass" style="max-width:800px;">
        <div class="modal-header">
          <h3>Action Details & Payload</h3>
          <button onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
           <div id="modalContent"></div>
        </div>
      </div>
    </div>

    <style>
    .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; display:flex; align-items:center; justify-content:center; }
    .modal-card { background:#fff; width:95%; border-radius:12px; overflow:hidden; }
    .modal-header { padding:16px 20px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center; }
    .modal-body { padding:20px; max-height:80vh; overflow-y:auto; }
    .diff-box { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px; margin-bottom:15px; font-family:monospace; font-size:12px; overflow-x:auto; }
    </style>

    <script>
    function viewAuditData(data) {
        document.getElementById('dataModal').style.display = 'flex';
        let html = '';
        
        if (data.remarks) html += `<h4>Remarks</h4><p style="font-size:13px;opacity:0.8;margin-bottom:20px;">${data.remarks}</p>`;
        
        if (data.old || data.new) {
            html += '<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">';
            html += `<div><strong style="font-size:11px;color:#ef4444;">OLD VALUE</strong><div class="diff-box">${data.old || 'N/A'}</div></div>`;
            html += `<div><strong style="font-size:11px;color:#10b981;">NEW VALUE</strong><div class="diff-box">${data.new || 'N/A'}</div></div>`;
            html += '</div>';
        }
        
        if (data.details) {
            html += `<h4>Technical Details</h4><div class="diff-box">${data.details}</div>`;
        }
        
        if (!html) html = '<p style="text-align:center;opacity:0.5;">No extra data payload recorded for this action.</p>';
        
        document.getElementById('modalContent').innerHTML = html;
    }
    function closeModal() { document.getElementById('dataModal').style.display = 'none'; }
    </script>
    <?php
}

renderLayout("Audit Logs", 'renderContent', $_SESSION['role'], $_SESSION['name']);
