<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    $apps = db_fetch_all($conn, "SELECT a.*, c.contractor_name FROM applications a LEFT JOIN contractors c ON a.contractor_id = c.id WHERE a.current_status NOT IN ('approved','rejected','completed') ORDER BY a.updated_at ASC");
    $allApps = db_fetch_all($conn, "SELECT a.*, c.contractor_name FROM applications a LEFT JOIN contractors c ON a.contractor_id = c.id ORDER BY a.updated_at DESC LIMIT 50");
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-gamepad" style="color:#f59e0b;margin-right:10px;"></i> Workflow Control Panel</h2>
        <!-- <p class="page-subtitle"><i class="fas fa-exclamation-triangle" style="color:var(--warning)"></i> Super Admin Override: Force workflow states with approval safeguards.</p> -->
      </div>
    </div>

    <div class="card glass">
      <div class="card-header" style="background:rgba(245,158,11,0.06);">
        <div class="card-title"><i class="fas fa-clock"></i> Pending/In-Progress Applications (<?= count($apps) ?>)</div>
      </div>
      <div class="card-body" style="padding:0;">
        <table class="data-table">
          <thead><tr><th>App ID</th><th>Contractor</th><th>Current Status</th><th>Pending At</th><th>Days Pending</th><th style="width:250px;">Override Action</th></tr></thead>
          <tbody>
          <?php if(empty($apps)): ?>
          <tr><td colspan="6" style="text-align:center;padding:30px;opacity:0.5;">No pending applications.</td></tr>
          <?php else: foreach($apps as $app):
            $pending = "Unknown";
            $st = $app['current_status'] ?? '';
            if(strpos($st,'welfare')!==false) $pending="Welfare Dept";
            elseif(strpos($st,'training')!==false) $pending="Safety Dept";
            elseif(strpos($st,'verify')!==false||strpos($st,'document')!==false) $pending="Pass Section";
            elseif(strpos($st,'draft')!==false||strpos($st,'submitted')!==false) $pending="Initial Review";
            else $pending = ucfirst(str_replace('_',' ',$st));
            
            $days = round((time()-strtotime($app['updated_at']))/86400);
            $dayClass = $days > 5 ? 'badge-danger' : ($days > 3 ? 'badge-warning' : 'badge-info');
          ?>
          <tr>
            <td><strong><?= htmlspecialchars($app['application_no'] ?? $app['id']) ?></strong></td>
            <td><?= htmlspecialchars($app['contractor_name'] ?? 'N/A') ?></td>
            <td><span class="badge badge-warning"><?= strtoupper($st) ?></span></td>
            <td><?= $pending ?></td>
            <td><span class="badge <?= $dayClass ?>"><?= $days ?> Days</span></td>
            <td style="display:flex;gap:8px;align-items:center;">
              <select class="form-control" style="width:140px;font-size:12px;" id="status_<?= $app['id'] ?>">
                <option value="">Force Status...</option>
                <option value="approved">Approved</option>
                <option value="under_review">Under Review</option>
                <option value="rejected">Rejected</option>
                <option value="draft">Reset to Draft</option>
                <option value="welfare_pending">Welfare Pending</option>
                <option value="training_pending">Training Pending</option>
              </select>
              <button class="btn btn-sm btn-warning" onclick="forceUpdate(<?= $app['id'] ?>)">Override</button>
            </td>
          </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div id="wf-toast" class="um-toast" style="display:none;"></div>

    <style>
    .um-toast { position:fixed; bottom:30px; right:30px; z-index:99999; padding:14px 24px; border-radius:12px; font-size:14px; font-weight:600; color:#fff; display:flex; align-items:center; gap:10px; box-shadow:0 8px 24px rgba(0,0,0,0.2); }
    .um-toast.success { background:#10b981; } .um-toast.error { background:#ef4444; } .um-toast.warning { background:#f59e0b; }
    </style>

    <script>
    async function forceUpdate(id) {
      const status = document.getElementById('status_' + id).value;
      if (!status) return alert('Select a status first');
      
      const reason = prompt('Enter override reason (required for audit trail):');
      if (!reason) return;

      try {
        const res = await fetch('../../api/admin/force_workflow_status.php', {
          method: 'POST', headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({app_id: id, new_status: status, reason: reason, confirm_override: false})
        });
        const data = await res.json();
        
        if (data.requires_override) {
          if (!confirm('⚠️ APPROVAL SAFEGUARD\n\n' + data.message + '\n\nThis is a PROTECTED status. Override anyway?')) return;
          
          const res2 = await fetch('../../api/admin/force_workflow_status.php', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({app_id: id, new_status: status, reason: reason, confirm_override: true})
          });
          const data2 = await res2.json();
          showToast(data2.message, data2.success ? 'success' : 'error');
          if(data2.success) setTimeout(() => location.reload(), 1000);
        } else if (data.success) {
          showToast(data.message, 'success');
          setTimeout(() => location.reload(), 1000);
        } else {
          showToast(data.message, 'error');
        }
      } catch(err) { showToast('Network error', 'error'); }
    }

    function showToast(msg, type) {
      const t = document.getElementById('wf-toast');
      t.className = 'um-toast ' + type;
      t.innerHTML = '<i class="fas fa-'+(type==='success'?'check-circle':'exclamation-circle')+'"></i> ' + msg;
      t.style.display = 'flex';
      setTimeout(() => t.style.display = 'none', 4000);
    }
    </script>
    <?php
}

renderLayout("Workflow Control", 'renderContent', $_SESSION['role'], $_SESSION['name']);
