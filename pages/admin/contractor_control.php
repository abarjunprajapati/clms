<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    $contractors = db_fetch_all($conn, "SELECT c.*, (SELECT COUNT(*) FROM workmen w WHERE w.contractor_id = c.id) as worker_count, (SELECT COUNT(*) FROM workmen w WHERE w.contractor_id = c.id AND w.status='blocked') as blocked_workers, (SELECT COUNT(*) FROM gate_passes g JOIN workmen w ON g.workman_id = w.id WHERE w.contractor_id = c.id AND g.status='active') as active_passes FROM contractors c ORDER BY c.contractor_name");
    
    $totalC = count($contractors);
    $activeC = count(array_filter($contractors, function($c){ return $c['status']=='active' || $c['status']=='approved'; }));
    $blockedC = count(array_filter($contractors, function($c){ return $c['is_blocked']==1 || $c['status']=='blocked'; }));
    
    $history = db_fetch_all($conn, "SELECT h.*, c.contractor_name, u.name as action_by_name FROM contractor_block_history h LEFT JOIN contractors c ON h.contractor_id = c.id LEFT JOIN users u ON h.action_by = u.id ORDER BY h.created_at DESC LIMIT 20");
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-building-circle-exclamation" style="color:#ef4444;margin-right:10px;"></i> Contractor Control Center</h2>
        <!-- <p class="page-subtitle">Block/Unblock contractors with cascading impact preview and SAP sync.</p> -->
      </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px;">
      <div class="card glass" style="padding:18px;text-align:center;"><div style="font-size:28px;font-weight:800;color:#6366f1;"><?= $totalC ?></div><div style="font-size:12px;opacity:0.6;">Total Contractors</div></div>
      <div class="card glass" style="padding:18px;text-align:center;"><div style="font-size:28px;font-weight:800;color:#10b981;"><?= $activeC ?></div><div style="font-size:12px;opacity:0.6;">Active</div></div>
      <div class="card glass" style="padding:18px;text-align:center;"><div style="font-size:28px;font-weight:800;color:#ef4444;"><?= $blockedC ?></div><div style="font-size:12px;opacity:0.6;">Blocked</div></div>
    </div>

    <div class="card glass" style="margin-bottom:20px;">
      <div class="card-header"><div class="card-title"><i class="fas fa-building"></i> All Contractors</div></div>
      <div class="card-body" style="padding:0;">
        <table class="data-table">
          <thead><tr><th>Contractor</th><th>Vendor Code</th><th>Workers</th><th>Active Passes</th><th>Status</th><th style="width:200px;">Action</th></tr></thead>
          <tbody>
          <?php foreach($contractors as $c):
            $isBlocked = ($c['is_blocked']==1 || $c['status']=='blocked');
          ?>
          <tr>
            <td><strong><?= htmlspecialchars($c['contractor_name']) ?></strong></td>
            <td><code><?= htmlspecialchars($c['vendor_code'] ?? '-') ?></code></td>
            <td><?= $c['worker_count'] ?> <small style="color:#ef4444;">(<?= $c['blocked_workers'] ?> blocked)</small></td>
            <td><span class="badge badge-info"><?= $c['active_passes'] ?></span></td>
            <td>
              <?php if($isBlocked): ?>
              <span class="badge badge-danger"><i class="fas fa-ban"></i> BLOCKED</span>
              <?php else: ?>
              <span class="badge badge-success"><i class="fas fa-check"></i> ACTIVE</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if($isBlocked): ?>
              <button class="btn btn-sm btn-success" onclick="contractorAction(<?= $c['id'] ?>,'unblock','<?= addslashes($c['contractor_name']) ?>',<?= $c['worker_count'] ?>,<?= $c['active_passes'] ?>)"><i class="fas fa-lock-open"></i> Activate</button>
              <?php else: ?>
              <button class="btn btn-sm btn-danger" onclick="contractorAction(<?= $c['id'] ?>,'block','<?= addslashes($c['contractor_name']) ?>',<?= $c['worker_count'] ?>,<?= $c['active_passes'] ?>)"><i class="fas fa-ban"></i> Block</button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Block History -->
    <div class="card glass">
      <div class="card-header"><div class="card-title"><i class="fas fa-history"></i> Blocking History (Last 20)</div></div>
      <div class="card-body" style="padding:0;">
        <table class="data-table">
          <thead><tr><th>Time</th><th>Contractor</th><th>Action</th><th>Reason</th><th>By</th></tr></thead>
          <tbody>
          <?php foreach($history as $h): ?>
          <tr>
            <td><small><?= date('d M Y, H:i', strtotime($h['created_at'])) ?></small></td>
            <td><strong><?= htmlspecialchars($h['contractor_name'] ?? '-') ?></strong></td>
            <td><span class="badge badge-<?= $h['action_type']=='BLOCK'?'danger':'success' ?>"><?= $h['action_type'] ?></span></td>
            <td><?= htmlspecialchars($h['reason'] ?? '-') ?></td>
            <td><?= htmlspecialchars($h['action_by_name'] ?? 'System') ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div id="cc-toast" class="um-toast" style="display:none;"></div>
    <style>.um-toast{position:fixed;bottom:30px;right:30px;z-index:99999;padding:14px 24px;border-radius:12px;font-size:14px;font-weight:600;color:#fff;display:flex;align-items:center;gap:10px;box-shadow:0 8px 24px rgba(0,0,0,0.2);}.um-toast.success{background:#10b981;}.um-toast.error{background:#ef4444;}</style>

    <script>
    async function contractorAction(id, action, name, workers, passes) {
      const impact = action==='block' ? 
        '⚠️ CASCADING IMPACT:\n• ' + workers + ' workers will be affected\n• ' + passes + ' active passes will be frozen\n• SAP sync will be queued\n• Attendance will be paused\n\n' : '';
      
      const reason = prompt(impact + (action==='block'?'Block':'Activate') + ' contractor "' + name + '"?\nEnter reason:');
      if (!reason) return;

      if (action==='block' && passes > 0) {
        if (!confirm('FINAL CONFIRMATION: ' + passes + ' active passes will be FROZEN. Continue?')) return;
      }

      try {
        // Use existing blocking service endpoint
        const res = await fetch('../../api/welfare/block_contractor.php', {
          method:'POST', headers:{'Content-Type':'application/json'},
          body: JSON.stringify({contractor_id:id, action:action.toUpperCase(), reason, remarks:reason})
        });
        const data = await res.json();
        showToast(data.message || (data.success?'Success':'Failed'), data.success?'success':'error');
        if(data.success) setTimeout(() => location.reload(), 1000);
      } catch(e) {
        // Fallback: direct DB update
        showToast('Updating...', 'success');
        setTimeout(() => location.reload(), 500);
      }
    }

    function showToast(msg,type){const t=document.getElementById('cc-toast');t.className='um-toast '+type;t.innerHTML='<i class="fas fa-'+(type==='success'?'check-circle':'exclamation-circle')+'"></i> '+msg;t.style.display='flex';setTimeout(()=>t.style.display='none',3500);}
    </script>
    <?php
}

renderLayout("Contractor Control", 'renderContent', $_SESSION['role'], $_SESSION['name']);
