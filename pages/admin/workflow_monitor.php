<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    
    // Summary Stats
    $totalApps = db_count($conn, "SELECT COUNT(*) c FROM applications");
    $approved = db_count($conn, "SELECT COUNT(*) c FROM applications WHERE current_status='approved'");
    $pending = db_count($conn, "SELECT COUNT(*) c FROM applications WHERE current_status NOT IN ('approved','rejected')");
    $rejected = db_count($conn, "SELECT COUNT(*) c FROM applications WHERE current_status='rejected'");
    
    $apps = db_fetch_all($conn, "SELECT a.*, c.contractor_name, aw.current_stage, aw.overall_status
                                 FROM applications a 
                                 LEFT JOIN contractors c ON a.contractor_id = c.id 
                                 LEFT JOIN application_workflow aw ON a.application_no = aw.application_id
                                 ORDER BY a.updated_at DESC LIMIT 100");
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-project-diagram" style="color:#6366f1;margin-right:10px;"></i> Workflow Monitoring</h2>
        <!-- <p class="page-subtitle">Real-time tracking of application lifecycle across all departments.</p> -->
      </div>
      <div class="action-buttons">
        <a href="workflow_control.php" class="btn btn-warning" style="color:#fff;"><i class="fas fa-gamepad"></i> Emergency Override</a>
      </div>
    </div>

    <!-- Stats Row -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
      <div class="card glass" style="text-align:center;padding:16px;">
        <div style="font-size:24px;font-weight:800;color:#6366f1;"><?= $totalApps ?></div>
        <div style="font-size:11px;opacity:0.6;">Total Applications</div>
      </div>
      <div class="card glass" style="text-align:center;padding:16px;">
        <div style="font-size:24px;font-weight:800;color:#f59e0b;"><?= $pending ?></div>
        <div style="font-size:11px;opacity:0.6;">In Progress</div>
      </div>
      <div class="card glass" style="text-align:center;padding:16px;">
        <div style="font-size:24px;font-weight:800;color:#10b981;"><?= $approved ?></div>
        <div style="font-size:11px;opacity:0.6;">Approved</div>
      </div>
      <div class="card glass" style="text-align:center;padding:16px;">
        <div style="font-size:24px;font-weight:800;color:#ef4444;"><?= $rejected ?></div>
        <div style="font-size:11px;opacity:0.6;">Rejected</div>
      </div>
    </div>

    <div class="card glass">
      <div class="card-header">
        <div class="card-title">Live Application Pipeline</div>
      </div>
      <div class="card-body" style="padding:0;">
        <table class="data-table">
          <thead>
            <tr>
              <th>App ID</th>
              <th>Contractor</th>
              <th>Current Stage</th>
              <th>Status</th>
              <th>Efficiency</th>
              <th>Last Activity</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($apps as $app): 
                $st = $app['current_status'] ?? 'pending';
                $stage = $app['current_stage'] ?? 'Initial';
                $days = round((time() - strtotime($app['created_at'])) / 86400);
                $efficiency = ($days <= 3) ? 'success' : (($days <= 7) ? 'warning' : 'danger');
            ?>
            <tr>
              <td><code style="font-weight:700;"><?= htmlspecialchars($app['application_id'] ?? $app['id']) ?></code></td>
              <td><strong><?= htmlspecialchars($app['contractor_name'] ?? 'N/A') ?></strong></td>
              <td><span class="badge badge-outline"><?= strtoupper(str_replace('_',' ',$stage)) ?></span></td>
              <td>
                <span class="badge badge-<?= ($st == 'approved') ? 'success' : (($st == 'rejected') ? 'danger' : 'warning') ?>">
                    <?= strtoupper(str_replace('_',' ',$st)) ?>
                </span>
              </td>
              <td>
                <div style="width:60px; height:6px; background:#e2e8f0; border-radius:3px; margin-bottom:4px;">
                  <div style="width:<?= max(20, 100 - ($days*10)) ?>%; height:100%; background:var(--<?= $efficiency ?>); border-radius:3px;"></div>
                </div>
                <small style="font-size:10px;"><?= $days ?>d pending</small>
              </td>
              <td><small><?= date('d M, H:i', strtotime($app['updated_at'])) ?></small></td>
              <td>
                <button class="btn btn-sm btn-outline" onclick="viewAppTimeline('<?= $app['application_id'] ?>')"><i class="fas fa-history"></i> Flow</button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Timeline Modal Placeholder -->
    <div id="timelineModal" class="modal-overlay" style="display:none;">
      <div class="modal-card glass" style="max-width:600px;">
        <div class="modal-header">
          <h3 id="timelineTitle">Application Flow</h3>
          <button onclick="closeModal()">&times;</button>
        </div>
        <div id="timelineContent" class="modal-body">
          <!-- AJAX content -->
        </div>
      </div>
    </div>

    <style>
    .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; display:flex; align-items:center; justify-content:center; }
    .modal-card { background:#fff; width:90%; border-radius:12px; overflow:hidden; }
    .modal-header { padding:16px 20px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center; }
    .modal-body { padding:20px; max-height:70vh; overflow-y:auto; }
    </style>

    <script>
    function viewAppTimeline(appId) {
        document.getElementById('timelineModal').style.display = 'flex';
        document.getElementById('timelineTitle').innerText = 'Flow History: ' + appId;
        document.getElementById('timelineContent').innerHTML = '<div style="text-align:center;padding:20px;"><i class="fas fa-spinner fa-spin"></i> Loading timeline...</div>';
        
        fetch('../../api/welfare/get_application_history.php?application_id=' + appId)
            .then(r => r.json())
            .then(data => {
                if(data.success && data.history.length > 0) {
                    let html = '<div class="timeline">';
                    data.history.forEach(h => {
                        html += `<div style="padding:10px; border-left:2px solid #6366f1; margin-left:10px; position:relative; margin-bottom:15px;">
                            <div style="position:absolute; left:-7px; top:12px; width:12px; height:12px; background:#6366f1; border-radius:50%;"></div>
                            <div style="font-weight:700; font-size:13px;">${h.action_type || 'Status Update'}</div>
                            <div style="font-size:12px; opacity:0.8;">${h.remark || 'No remarks'}</div>
                            <div style="font-size:11px; opacity:0.6; margin-top:4px;">${h.created_at} by ${h.created_by || 'System'}</div>
                        </div>`;
                    });
                    html += '</div>';
                    document.getElementById('timelineContent').innerHTML = html;
                } else {
                    document.getElementById('timelineContent').innerHTML = '<div style="text-align:center;opacity:0.6;">No history records found for this application.</div>';
                }
            })
            .catch(e => {
                document.getElementById('timelineContent').innerHTML = '<div style="color:red;text-align:center;">Error loading history.</div>';
            });
    }
    function closeModal() { document.getElementById('timelineModal').style.display = 'none'; }
    </script>
    <?php
}

renderLayout("Workflow Monitoring", 'renderContent', $_SESSION['role'], $_SESSION['name']);
